<?php
/**
 * MCP Confirmation Queue — SP-3 write-ability human-in-the-loop gate
 *
 * Queues write-tool executions, emails a one-time confirm/reject link to the
 * administrator, and executes the operation only after explicit approval.
 *
 * Flow:
 *   1. MCP client calls g2rd/create-post or g2rd/update-post
 *   2. enqueue() inserts a pending entry, encrypts arguments, emails the admin
 *   3. Admin clicks "Confirm" → admin-post.php fires confirm()
 *      confirm() decrypts arguments, executes the write, logs 'mcp_write_allowed'
 *   4. Admin clicks "Reject"  → admin-post.php fires reject()
 *      reject() logs 'mcp_write_rolled_back', no write executed
 *   5. Entries past expires_at are silently ignored; prune_expired() cleans them up
 *
 * Security properties:
 *   - confirm/reject tokens are 32 random bytes (64-char hex) — 256-bit entropy
 *   - Single-use: status checked at read time; UPDATE WHERE status='pending' guards races
 *   - 15-minute TTL enforced at read time, independently of prune schedule
 *   - Arguments AES-256-GCM encrypted at rest via McpEncryption (RGPD)
 *   - admin-post.php requires WP login; combined with token entropy this replaces nonce
 *   - Write ops run under the MCP token owner's WP user context (not the confirming admin)
 *   - Only draft/pending/publish statuses allowed for create-post (no future/private)
 *   - Capability check (`edit_posts`, `edit_post`) runs inside the switched user context
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Manages the write-ability confirmation queue.
 */
class McpConfirmationQueue {

	/** @var int Confirmation TTL in minutes. */
	private const TTL_MINUTES = 15;

	/** @var int Token entropy in bytes (produces 64-char hex string). */
	private const TOKEN_BYTES = 32;

	/** @var string Table name suffix (without $wpdb->prefix). */
	private const TABLE_SUFFIX = 'g2rd_mcp_confirmation_queue';

	/** @var string[] Post statuses allowed via write tools (no future/private/trash). */
	private const ALLOWED_STATUSES = [ 'draft', 'pending', 'publish' ];

	/** @var McpEncryption AES-256-GCM encryption provider. */
	private McpEncryption $crypto;

	/** @var McpAuditLog INSERT-only audit log. */
	private McpAuditLog $audit;

	/**
	 * @param McpEncryption $crypto Encryption provider.
	 * @param McpAuditLog   $audit  Audit log.
	 */
	public function __construct( McpEncryption $crypto, McpAuditLog $audit ) {
		$this->crypto = $crypto;
		$this->audit  = $audit;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Enqueues a write operation for human confirmation and emails the administrator.
	 *
	 * @param int                  $user_id      WordPress user ID of the MCP token owner.
	 * @param int                  $token_id     MCP API token row ID.
	 * @param string               $ip_address   Client IP address.
	 * @param string               $ability_name Tool name (e.g. 'g2rd/create-post').
	 * @param array<string, mixed> $arguments    Tool arguments (plain text — encrypted before storage).
	 * @return array{confirm_token: string, reject_token: string, expires_at: string}|false
	 *   Confirmation handles on success, false on DB or encryption failure.
	 */
	public function enqueue(
		int $user_id,
		int $token_id,
		string $ip_address,
		string $ability_name,
		array $arguments
	): array|false {
		global $wpdb;

		$confirm_token = $this->generate_token();
		$reject_token  = $this->generate_token();
		$now           = \gmdate( 'Y-m-d H:i:s' );
		$expires_at    = \gmdate( 'Y-m-d H:i:s', \time() + ( self::TTL_MINUTES * 60 ) );
		$arguments_enc = $this->crypto->encrypt( (string) \wp_json_encode( $arguments ) );

		if ( false === $arguments_enc ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- confirmation queue write
		$inserted = $wpdb->insert(
			$wpdb->prefix . self::TABLE_SUFFIX,
			[
				'confirm_token' => $confirm_token,
				'reject_token'  => $reject_token,
				'user_id'       => $user_id,
				'token_id'      => $token_id,
				'ip_address'    => $ip_address,
				'ability_name'  => $ability_name,
				'arguments_enc' => $arguments_enc,
				'status'        => 'pending',
				'created_at'    => $now,
				'expires_at'    => $expires_at,
			],
			[ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $inserted ) {
			return false;
		}

		$this->send_email( $user_id, $ability_name, $arguments, $confirm_token, $reject_token, $expires_at );

		return [
			'confirm_token' => $confirm_token,
			'reject_token'  => $reject_token,
			'expires_at'    => $expires_at,
		];
	}

	/**
	 * Confirms a pending operation and executes it.
	 *
	 * The admin must be logged in (enforced by admin-post.php routing).
	 *
	 * @param string $confirm_token 64-char hex token from the email link.
	 * @return bool True on successful execution, false on invalid token or execution failure.
	 */
	public function confirm( string $confirm_token ): bool {
		$entry = $this->get_by_column( 'confirm_token', $confirm_token );

		if ( null === $entry || 'pending' !== $entry['status'] || $this->is_expired( $entry ) ) {
			return false;
		}

		return $this->resolve( $entry, 'confirmed', true );
	}

	/**
	 * Rejects a pending operation without executing it.
	 *
	 * @param string $reject_token 64-char hex token from the email link.
	 * @return bool True on successful rejection, false on invalid or already-resolved token.
	 */
	public function reject( string $reject_token ): bool {
		$entry = $this->get_by_column( 'reject_token', $reject_token );

		if ( null === $entry || 'pending' !== $entry['status'] || $this->is_expired( $entry ) ) {
			return false;
		}

		return $this->resolve( $entry, 'rejected', false );
	}

	/**
	 * Returns a page of pending confirmation entries for the admin UI.
	 *
	 * Does not include confirm/reject tokens in the result (admin uses the email links).
	 * arguments_enc is excluded — decrypted only on actual confirm().
	 *
	 * @param int $page     1-based page number.
	 * @param int $per_page Number of entries per page (max 50).
	 * @return array{entries: array<int, array<string, mixed>>, total: int, total_pages: int}
	 */
	public function list_pending( int $page = 1, int $per_page = 20 ): array {
		global $wpdb;

		$per_page = \min( 50, \max( 1, $per_page ) );
		$page     = \max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = $wpdb->prefix . self::TABLE_SUFFIX;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
			"SELECT COUNT(*) FROM `{$table}` WHERE status = 'pending'"
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant; column list is literal
				"SELECT id, user_id, ability_name, ip_address, status, created_at, expires_at FROM `{$table}` WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return [
			'entries'     => \is_array( $rows ) ? $rows : [],
			'total'       => $total,
			'total_pages' => (int) \ceil( $total / $per_page ),
		];
	}

	/**
	 * Confirms a pending entry by its row ID (used by the admin REST API).
	 *
	 * Requires the entry to be pending and not expired.
	 *
	 * @param int $id Row primary key.
	 * @return bool True on successful execution, false otherwise.
	 */
	public function confirm_by_id( int $id ): bool {
		$entry = $this->get_by_id( $id );

		if ( null === $entry || 'pending' !== $entry['status'] || $this->is_expired( $entry ) ) {
			return false;
		}

		return $this->resolve( $entry, 'confirmed', true );
	}

	/**
	 * Rejects a pending entry by its row ID (used by the admin REST API).
	 *
	 * @param int $id Row primary key.
	 * @return bool True on successful rejection, false otherwise.
	 */
	public function reject_by_id( int $id ): bool {
		$entry = $this->get_by_id( $id );

		if ( null === $entry || 'pending' !== $entry['status'] || $this->is_expired( $entry ) ) {
			return false;
		}

		return $this->resolve( $entry, 'rejected', false );
	}

	/**
	 * Marks all expired pending entries as 'expired'.
	 *
	 * @return int Number of rows updated.
	 */
	public function prune_expired(): int {
		global $wpdb;

		$now   = \gmdate( 'Y-m-d H:i:s' );
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from server constant
				"UPDATE `{$table}` SET status = 'expired', resolved_at = %s WHERE status = 'pending' AND expires_at < %s",
				$now,
				$now
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return \is_int( $result ) ? $result : 0;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Generates a cryptographically random 64-char hex token.
	 *
	 * @return string
	 */
	private function generate_token(): string {
		return \bin2hex( \random_bytes( self::TOKEN_BYTES ) );
	}

	/**
	 * Returns true if the entry's expires_at is in the past.
	 *
	 * @param array<string, mixed> $entry Database row.
	 * @return bool
	 */
	private function is_expired( array $entry ): bool {
		return \strtotime( (string) $entry['expires_at'] ) < \time();
	}

	/**
	 * Marks an entry as resolved and optionally executes the queued operation.
	 *
	 * The UPDATE WHERE status='pending' is the single-use race-condition guard:
	 * if two requests arrive simultaneously, only one will match the WHERE clause.
	 *
	 * @param array<string, mixed> $entry   Database row.
	 * @param string               $status  'confirmed' or 'rejected'.
	 * @param bool                 $execute Whether to run the write operation.
	 * @return bool
	 */
	private function resolve( array $entry, string $status, bool $execute ): bool {
		global $wpdb;

		$now = \gmdate( 'Y-m-d H:i:s' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$wpdb->prefix . self::TABLE_SUFFIX,
			[
				'status'      => $status,
				'resolved_at' => $now,
			],
			[
				'id'     => (int) $entry['id'],
				'status' => 'pending',
			],
			[ '%s', '%s' ],
			[ '%d', '%s' ]
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// 0 rows updated means another request already resolved this entry.
		if ( ! $updated ) {
			return false;
		}

		if ( ! $execute ) {
			$this->audit->log( [
				'user_id'      => (int) $entry['user_id'],
				'token_id'     => (int) $entry['token_id'],
				'ip_address'   => (string) $entry['ip_address'],
				'ability_name' => (string) $entry['ability_name'],
				'decision'     => 'rolled_back',
				'input'        => [ 'queue_id' => (int) $entry['id'] ],
			] );
			return true;
		}

		$plain_json = $this->crypto->decrypt( (string) $entry['arguments_enc'] );

		if ( false === $plain_json ) {
			return false;
		}

		$arguments = \json_decode( $plain_json, true );

		if ( ! \is_array( $arguments ) ) {
			return false;
		}

		$success = $this->execute_operation(
			(string) $entry['ability_name'],
			$arguments,
			(int) $entry['user_id']
		);

		$this->audit->log( [
			'user_id'      => (int) $entry['user_id'],
			'token_id'     => (int) $entry['token_id'],
			'ip_address'   => (string) $entry['ip_address'],
			'ability_name' => (string) $entry['ability_name'],
			'decision'     => $success ? 'allowed' : 'denied',
			'input'        => [ 'queue_id' => (int) $entry['id'], 'exec_success' => $success ],
		] );

		return $success;
	}

	/**
	 * Dispatches a write operation under the token owner's user context.
	 *
	 * Uses try/finally to guarantee user context is restored even if the
	 * operation throws an exception.
	 *
	 * @param string               $ability_name Tool name.
	 * @param array<string, mixed> $arguments    Decrypted tool arguments.
	 * @param int                  $user_id      User ID to run the operation as.
	 * @return bool True on success.
	 */
	private function execute_operation( string $ability_name, array $arguments, int $user_id ): bool {
		$original_user = \get_current_user_id();
		\wp_set_current_user( $user_id );

		try {
			switch ( $ability_name ) {
				case 'g2rd/create-post':
					return $this->exec_create_post( $arguments );
				case 'g2rd/update-post':
					return $this->exec_update_post( $arguments );
				default:
					return false;
			}
		} finally {
			\wp_set_current_user( $original_user );
		}
	}

	/**
	 * Executes g2rd/create-post: inserts a new WordPress post.
	 *
	 * Capability check runs inside the switched user context.
	 * Only statuses in ALLOWED_STATUSES are accepted (no future/private/trash).
	 *
	 * @param array<string, mixed> $args Tool arguments (title, content, status, post_type, excerpt).
	 * @return bool True if the post was created successfully.
	 */
	private function exec_create_post( array $args ): bool {
		if ( ! \current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$title = \sanitize_text_field( (string) ( $args['title'] ?? '' ) );

		if ( '' === $title ) {
			return false;
		}

		$status = \sanitize_key( (string) ( $args['status'] ?? 'draft' ) );
		if ( ! \in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			$status = 'draft';
		}

		$postarr = [
			'post_title'   => $title,
			'post_content' => \wp_kses_post( (string) ( $args['content'] ?? '' ) ),
			'post_status'  => $status,
			'post_type'    => \sanitize_key( (string) ( $args['post_type'] ?? 'post' ) ),
			'post_excerpt' => \sanitize_textarea_field( (string) ( $args['excerpt'] ?? '' ) ),
		];

		$result = \wp_insert_post( $postarr, true );

		return ! \is_wp_error( $result ) && $result > 0;
	}

	/**
	 * Executes g2rd/update-post: updates an existing post.
	 *
	 * Capability check runs inside the switched user context.
	 * At least one content field (title, content, excerpt) must be provided.
	 *
	 * @param array<string, mixed> $args Tool arguments (post_id required; title, content, excerpt optional).
	 * @return bool True if the post was updated successfully.
	 */
	private function exec_update_post( array $args ): bool {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return false;
		}

		if ( ! ( \get_post( $post_id ) instanceof \WP_Post ) ) {
			return false;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$postarr = [ 'ID' => $post_id ];

		if ( isset( $args['title'] ) ) {
			$postarr['post_title'] = \sanitize_text_field( (string) $args['title'] );
		}
		if ( isset( $args['content'] ) ) {
			$postarr['post_content'] = \wp_kses_post( (string) $args['content'] );
		}
		if ( isset( $args['excerpt'] ) ) {
			$postarr['post_excerpt'] = \sanitize_textarea_field( (string) $args['excerpt'] );
		}

		if ( \count( $postarr ) <= 1 ) {
			return false;
		}

		$result = \wp_update_post( $postarr, true );

		return ! \is_wp_error( $result ) && $result > 0;
	}

	/**
	 * Sends the confirmation email to the site administrator.
	 *
	 * The email contains plain-text confirm/reject URLs. Token entropy (256 bits)
	 * provides authentication; WP login is required by admin-post.php routing.
	 *
	 * @param int                  $user_id       MCP token owner's user ID.
	 * @param string               $ability_name  Tool name.
	 * @param array<string, mixed> $arguments     Plain-text arguments (displayed for admin review).
	 * @param string               $confirm_token 64-char hex confirm token.
	 * @param string               $reject_token  64-char hex reject token.
	 * @param string               $expires_at    Expiry datetime (UTC, Y-m-d H:i:s).
	 * @return bool Whether wp_mail() accepted the message.
	 */
	private function send_email(
		int $user_id,
		string $ability_name,
		array $arguments,
		string $confirm_token,
		string $reject_token,
		string $expires_at
	): bool {
		$admin_email  = (string) \get_option( 'admin_email' );
		$user         = \get_userdata( $user_id );
		$user_login   = $user ? (string) $user->user_login : "user #{$user_id}";
		$site_name    = (string) \get_bloginfo( 'name' );
		$args_display = (string) \wp_json_encode( $arguments, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE );

		$confirm_url = \add_query_arg(
			[
				'action' => 'g2rd_mcp_confirm',
				'token'  => $confirm_token,
			],
			\admin_url( 'admin-post.php' )
		);

		$reject_url = \add_query_arg(
			[
				'action' => 'g2rd_mcp_reject',
				'token'  => $reject_token,
			],
			\admin_url( 'admin-post.php' )
		);

		/* translators: %s: site name */
		$subject = \sprintf( \__( '[%s] MCP — Action requiert votre confirmation', 'g2rd' ), $site_name );

		$message = \sprintf(
			/* translators: 1: user login 2: tool name 3: arguments JSON 4: expiry datetime UTC 5: confirm URL 6: reject URL */
			\__(
				"Un agent MCP connecté en tant que %1\$s demande à exécuter l'action suivante :\n\nOutil : %2\$s\nArguments :\n%3\$s\n\nCette demande expire à %4\$s (UTC).\n\n✅ CONFIRMER : %5\$s\n\n❌ REFUSER : %6\$s\n\nSi vous n'êtes pas à l'origine de cette demande, cliquez sur REFUSER immédiatement.",
				'g2rd'
			),
			$user_login,
			$ability_name,
			$args_display,
			$expires_at,
			$confirm_url,
			$reject_url
		);

		return (bool) \wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Fetches a single queue entry by its row ID.
	 *
	 * @param int $id Row primary key.
	 * @return array<string, mixed>|null Row as associative array, or null if not found.
	 */
	private function get_by_id( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT * FROM `{$table}` WHERE id = %d LIMIT 1",
				$id
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return \is_array( $row ) ? $row : null;
	}

	/**
	 * Fetches a single queue entry by a validated unique column value.
	 *
	 * Column is validated against an explicit allowlist before interpolation
	 * to prevent SQL injection; the value is parameterised via $wpdb->prepare().
	 *
	 * @param string $column Column name — must be 'confirm_token' or 'reject_token'.
	 * @param string $value  Token value to look up.
	 * @return array<string, mixed>|null Row as associative array, or null if not found.
	 */
	private function get_by_column( string $column, string $value ): ?array {
		global $wpdb;

		$allowed = [ 'confirm_token', 'reject_token' ];

		if ( ! \in_array( $column, $allowed, true ) ) {
			return null;
		}

		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column validated against allowlist; table from server constant
				"SELECT * FROM `{$table}` WHERE `{$column}` = %s LIMIT 1",
				$value
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return \is_array( $row ) ? $row : null;
	}
}
