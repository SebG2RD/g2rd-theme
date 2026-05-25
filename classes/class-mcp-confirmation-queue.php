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

	/** @var string[] Post statuses allowed for create-post. */
	private const ALLOWED_STATUSES = [ 'draft', 'pending', 'publish' ];

	/** @var string[] Post statuses allowed for update-post (broader — includes future and private). */
	private const UPDATE_ALLOWED_STATUSES = [ 'draft', 'pending', 'publish', 'future', 'private' ];

	/** @var string[] WordPress option keys that may be updated via g2rd/update-option. */
	private const OPTION_WHITELIST = [
		'blogname',
		'blogdescription',
		'timezone_string',
		'date_format',
		'time_format',
		'posts_per_page',
		'default_comment_status',
		'default_ping_status',
		'permalink_structure',
	];

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
				case 'g2rd/delete-post':
					return $this->exec_delete_post( $arguments );
				case 'g2rd/update-post-meta':
					return $this->exec_update_post_meta( $arguments );
				case 'g2rd/update-seo-data':
					return $this->exec_update_seo_data( $arguments );
				case 'g2rd/create-redirection':
					return $this->exec_create_redirection( $arguments );
				case 'g2rd/create-category':
					return $this->exec_create_category( $arguments );
				case 'g2rd/create-tag':
					return $this->exec_create_tag( $arguments );
				case 'g2rd/update-media':
					return $this->exec_update_media( $arguments );
				case 'g2rd/activate-plugin':
					return $this->exec_activate_plugin( $arguments );
				case 'g2rd/deactivate-plugin':
					return $this->exec_deactivate_plugin( $arguments );
				case 'g2rd/update-plugin':
					return $this->exec_update_plugin( $arguments );
				case 'g2rd/update-option':
					return $this->exec_update_option( $arguments );
				case 'g2rd/flush-cache':
					return $this->exec_flush_cache();
				case 'g2rd/update-menu-item':
					return $this->exec_update_menu_item( $arguments );
				case 'g2rd/upload-media':
					return $this->exec_upload_media( $arguments );
				case 'g2rd/upload-media-base64':
					return $this->exec_upload_media_base64( $arguments );
				case 'g2rd/delete-media':
					return $this->exec_delete_media( $arguments );
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
	 * Executes g2rd/update-post: updates an existing post with extended fields.
	 *
	 * Supports title, content, excerpt, status, categories, tags, featured image,
	 * slug, publish date and page template. Capability check runs inside the switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (post_id required; all other fields optional).
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
		if ( isset( $args['status'] ) ) {
			$status = \sanitize_key( (string) $args['status'] );
			if ( \in_array( $status, self::UPDATE_ALLOWED_STATUSES, true ) ) {
				$postarr['post_status'] = $status;
			}
		}
		if ( isset( $args['slug'] ) ) {
			$postarr['post_name'] = \sanitize_title( (string) $args['slug'] );
		}
		if ( isset( $args['date'] ) ) {
			$date_gmt = \get_gmt_from_date( \sanitize_text_field( (string) $args['date'] ) );
			if ( $date_gmt ) {
				$postarr['post_date_gmt'] = $date_gmt;
				$postarr['post_date']     = \sanitize_text_field( (string) $args['date'] );
			}
		}

		// At least one field must differ from the ID.
		if ( \count( $postarr ) <= 1 &&
			! isset( $args['categories'] ) &&
			! isset( $args['tags'] ) &&
			! isset( $args['featured_image_id'] ) &&
			! isset( $args['template'] )
		) {
			return false;
		}

		$result = \wp_update_post( $postarr, true );

		if ( \is_wp_error( $result ) || ! $result ) {
			return false;
		}

		if ( isset( $args['categories'] ) && \is_array( $args['categories'] ) ) {
			$cat_ids = \array_filter( \array_map( 'absint', $args['categories'] ) );
			\wp_set_post_categories( $post_id, $cat_ids );
		}

		if ( isset( $args['tags'] ) && \is_array( $args['tags'] ) ) {
			$tags = \array_filter( \array_map( 'sanitize_text_field', $args['tags'] ) );
			\wp_set_post_tags( $post_id, $tags, false );
		}

		if ( isset( $args['featured_image_id'] ) ) {
			$img_id = \absint( $args['featured_image_id'] );
			if ( 0 === $img_id ) {
				\delete_post_thumbnail( $post_id );
			} else {
				\set_post_thumbnail( $post_id, $img_id );
			}
		}

		if ( isset( $args['template'] ) ) {
			\update_post_meta( $post_id, '_wp_page_template', \sanitize_text_field( (string) $args['template'] ) );
		}

		return true;
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

	// ── Additional write tool executors ───────────────────────────────────────

	/**
	 * Executes g2rd/delete-post: moves a post to the trash.
	 *
	 * Never performs permanent deletion. Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (post_id required).
	 * @return bool True if the post was trashed successfully.
	 */
	private function exec_delete_post( array $args ): bool {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return false;
		}

		if ( ! ( \get_post( $post_id ) instanceof \WP_Post ) ) {
			return false;
		}

		if ( ! \current_user_can( 'delete_post', $post_id ) ) {
			return false;
		}

		return (bool) \wp_trash_post( $post_id );
	}

	/**
	 * Executes g2rd/update-post-meta: updates a meta field on a post.
	 *
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (post_id, meta_key, meta_value required).
	 * @return bool True if the meta was updated.
	 */
	private function exec_update_post_meta( array $args ): bool {
		$post_id    = \absint( $args['post_id'] ?? 0 );
		$meta_key   = \sanitize_key( (string) ( $args['meta_key'] ?? '' ) );
		$meta_value = (string) ( $args['meta_value'] ?? '' );

		if ( $post_id <= 0 || '' === $meta_key ) {
			return false;
		}

		if ( ! ( \get_post( $post_id ) instanceof \WP_Post ) ) {
			return false;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$result = \update_post_meta( $post_id, $meta_key, \sanitize_text_field( $meta_value ) );

		return false !== $result;
	}

	/**
	 * Executes g2rd/update-seo-data: writes SEO meta via the active SEO plugin.
	 *
	 * Auto-detects the active SEO plugin. Silently succeeds if no plugin is found.
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (post_id required; SEO fields optional).
	 * @return bool True on success.
	 */
	private function exec_update_seo_data( array $args ): bool {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 || ! ( \get_post( $post_id ) instanceof \WP_Post ) ) {
			return false;
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$plugin = $this->detect_active_seo_plugin_queue();

		$map = [];

		switch ( $plugin ) {
			case 'yoast':
				$map = [
					'meta_title'       => '_yoast_wpseo_title',
					'meta_description' => '_yoast_wpseo_metadesc',
					'canonical'        => '_yoast_wpseo_canonical',
					'og_title'         => '_yoast_wpseo_opengraph-title',
					'og_description'   => '_yoast_wpseo_opengraph-description',
					'focus_keyword'    => '_yoast_wpseo_focuskw',
				];
				break;
			case 'rank_math':
				$map = [
					'meta_title'       => 'rank_math_title',
					'meta_description' => 'rank_math_description',
					'canonical'        => 'rank_math_canonical_url',
					'og_title'         => 'rank_math_facebook_title',
					'og_description'   => 'rank_math_facebook_description',
					'focus_keyword'    => 'rank_math_focus_keyword',
				];
				break;
			case 'seopress':
				$map = [
					'meta_title'       => '_seopress_titles_title',
					'meta_description' => '_seopress_titles_desc',
					'canonical'        => '_seopress_robots_canonical',
					'og_title'         => '_seopress_social_fb_title',
					'og_description'   => '_seopress_social_fb_desc',
					'focus_keyword'    => '_seopress_analysis_target_kw',
				];
				break;
			case 'aioseo':
				$map = [
					'meta_title'       => '_aioseo_title',
					'meta_description' => '_aioseo_description',
					'canonical'        => '_aioseo_canonical_url',
					'og_title'         => '_aioseo_og_title',
					'og_description'   => '_aioseo_og_description',
					'focus_keyword'    => '', // handled below
				];
				break;
		}

		foreach ( $map as $arg_key => $meta_key ) {
			if ( isset( $args[ $arg_key ] ) && '' !== $meta_key ) {
				\update_post_meta( $post_id, $meta_key, \sanitize_text_field( (string) $args[ $arg_key ] ) );
			}
		}

		// noindex — plugin-specific handling.
		if ( isset( $args['noindex'] ) ) {
			$noindex = (bool) $args['noindex'];
			if ( 'yoast' === $plugin ) {
				\update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', $noindex ? '1' : '0' );
			} elseif ( 'rank_math' === $plugin ) {
				$current = (string) \get_post_meta( $post_id, 'rank_math_robots', true );
				if ( $noindex && false === \strpos( $current, 'noindex' ) ) {
					\update_post_meta( $post_id, 'rank_math_robots', 'noindex,nofollow' );
				} elseif ( ! $noindex ) {
					\update_post_meta( $post_id, 'rank_math_robots', 'index,follow' );
				}
			} elseif ( 'seopress' === $plugin ) {
				\update_post_meta( $post_id, '_seopress_robots_index', $noindex ? '1' : '' );
			} elseif ( 'aioseo' === $plugin ) {
				\update_post_meta( $post_id, '_aioseo_robots_default', $noindex ? '0' : '1' );
			}
		}

		return true;
	}

	/**
	 * Executes g2rd/create-redirection: creates a URL redirection via the Redirection plugin.
	 *
	 * Returns false if the Redirection plugin is not active.
	 *
	 * @param array<string, mixed> $args Tool arguments (source, target, type required).
	 * @return bool True on success.
	 */
	private function exec_create_redirection( array $args ): bool {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return false;
		}

		$source = \sanitize_text_field( (string) ( $args['source'] ?? '' ) );
		$target = \sanitize_text_field( (string) ( $args['target'] ?? '' ) );
		$type   = \absint( $args['type'] ?? 301 );

		if ( '' === $source || '' === $target ) {
			return false;
		}

		if ( ! \in_array( $type, [ 301, 302 ], true ) ) {
			$type = 301;
		}

		if ( ! \class_exists( 'Red_Item' ) ) {
			return false;
		}

		$item = \Red_Item::create( [
			'url'         => $source,
			'action_data' => [ 'url' => $target ],
			'action_type' => 'url',
			'action_code' => $type,
			'match_type'  => 'url',
			'group_id'    => 1,
		] );

		return ! \is_wp_error( $item );
	}

	/**
	 * Executes g2rd/create-category: inserts a new post category.
	 *
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (name required; slug, description, parent_id optional).
	 * @return bool True if the category was created.
	 */
	private function exec_create_category( array $args ): bool {
		if ( ! \current_user_can( 'manage_categories' ) ) {
			return false;
		}

		$name = \sanitize_text_field( (string) ( $args['name'] ?? '' ) );

		if ( '' === $name ) {
			return false;
		}

		$term_args = [
			'description' => \sanitize_textarea_field( (string) ( $args['description'] ?? '' ) ),
			'parent'      => \absint( $args['parent_id'] ?? 0 ),
		];

		if ( ! empty( $args['slug'] ) ) {
			$term_args['slug'] = \sanitize_title( (string) $args['slug'] );
		}

		$result = \wp_insert_term( $name, 'category', $term_args );

		return ! \is_wp_error( $result );
	}

	/**
	 * Executes g2rd/create-tag: inserts a new post tag.
	 *
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (name required; slug, description optional).
	 * @return bool True if the tag was created.
	 */
	private function exec_create_tag( array $args ): bool {
		if ( ! \current_user_can( 'manage_categories' ) ) {
			return false;
		}

		$name = \sanitize_text_field( (string) ( $args['name'] ?? '' ) );

		if ( '' === $name ) {
			return false;
		}

		$term_args = [
			'description' => \sanitize_textarea_field( (string) ( $args['description'] ?? '' ) ),
		];

		if ( ! empty( $args['slug'] ) ) {
			$term_args['slug'] = \sanitize_title( (string) $args['slug'] );
		}

		$result = \wp_insert_term( $name, 'post_tag', $term_args );

		return ! \is_wp_error( $result );
	}

	/**
	 * Executes g2rd/update-media: updates a media attachment's metadata.
	 *
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (media_id required; alt, title, description, caption optional).
	 * @return bool True on success.
	 */
	private function exec_update_media( array $args ): bool {
		$media_id = \absint( $args['media_id'] ?? 0 );

		if ( $media_id <= 0 ) {
			return false;
		}

		$post = \get_post( $media_id );

		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return false;
		}

		if ( ! \current_user_can( 'edit_post', $media_id ) ) {
			return false;
		}

		if ( isset( $args['alt'] ) ) {
			\update_post_meta( $media_id, '_wp_attachment_image_alt', \sanitize_text_field( (string) $args['alt'] ) );
		}

		$postarr = [ 'ID' => $media_id ];

		if ( isset( $args['title'] ) ) {
			$postarr['post_title'] = \sanitize_text_field( (string) $args['title'] );
		}
		if ( isset( $args['description'] ) ) {
			$postarr['post_content'] = \sanitize_textarea_field( (string) $args['description'] );
		}
		if ( isset( $args['caption'] ) ) {
			$postarr['post_excerpt'] = \sanitize_text_field( (string) $args['caption'] );
		}

		if ( \count( $postarr ) > 1 ) {
			$result = \wp_update_post( $postarr, true );
			if ( \is_wp_error( $result ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Executes g2rd/activate-plugin: activates an installed plugin.
	 *
	 * Refuses to operate if no activate_plugins capability. Capability check
	 * runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (plugin_file required).
	 * @return bool True on success.
	 */
	private function exec_activate_plugin( array $args ): bool {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		$plugin_file = \sanitize_text_field( (string) ( $args['plugin_file'] ?? '' ) );

		if ( '' === $plugin_file ) {
			return false;
		}

		if ( ! \function_exists( 'activate_plugin' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = \activate_plugin( $plugin_file );

		return ! \is_wp_error( $result );
	}

	/**
	 * Executes g2rd/deactivate-plugin: deactivates an active plugin.
	 *
	 * Refuses to deactivate the G2RD theme-core plugin. Capability check
	 * runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (plugin_file required).
	 * @return bool True on success.
	 */
	private function exec_deactivate_plugin( array $args ): bool {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		$plugin_file = \sanitize_text_field( (string) ( $args['plugin_file'] ?? '' ) );

		if ( '' === $plugin_file ) {
			return false;
		}

		// Self-protection: never deactivate G2RD core or MCP-related plugins.
		if ( false !== \strpos( $plugin_file, 'g2rd' ) ) {
			return false;
		}

		if ( ! \function_exists( 'deactivate_plugins' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		\deactivate_plugins( $plugin_file );

		return true;
	}

	/**
	 * Executes g2rd/update-plugin: updates a plugin to its latest available version.
	 *
	 * Logs the previous version before updating. Capability check runs inside
	 * switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (plugin_file required).
	 * @return bool True if the update succeeded or the plugin was already current.
	 */
	private function exec_update_plugin( array $args ): bool {
		if ( ! \current_user_can( 'update_plugins' ) ) {
			return false;
		}

		$plugin_file = \sanitize_text_field( (string) ( $args['plugin_file'] ?? '' ) );

		if ( '' === $plugin_file ) {
			return false;
		}

		if ( ! \function_exists( 'get_plugins' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! \class_exists( 'Plugin_Upgrader' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! \class_exists( 'Automatic_Upgrader_Skin' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		}

		$all_plugins = \get_plugins();
		$prev_version = isset( $all_plugins[ $plugin_file ] ) ? (string) $all_plugins[ $plugin_file ]['Version'] : 'unknown';

		// Log the version being replaced.
		\update_option( 'g2rd_mcp_last_plugin_update', [
			'plugin'       => $plugin_file,
			'from_version' => $prev_version,
			'updated_at'   => \gmdate( 'Y-m-d H:i:s' ),
		] );

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $plugin_file );

		// Plugin_Upgrader::upgrade() returns true on success, WP_Error on failure,
		// or false/null when plugin is already up to date (not an error).
		return true === $result || null === $result || false === $result;
	}

	/**
	 * Executes g2rd/update-option: updates a whitelisted WordPress option.
	 *
	 * Refuses all keys outside OPTION_WHITELIST. Capability check runs inside
	 * switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (option_key, option_value required).
	 * @return bool True on success.
	 */
	private function exec_update_option( array $args ): bool {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return false;
		}

		$key   = \sanitize_key( (string) ( $args['option_key'] ?? '' ) );
		$value = \sanitize_text_field( (string) ( $args['option_value'] ?? '' ) );

		if ( '' === $key || ! \in_array( $key, self::OPTION_WHITELIST, true ) ) {
			return false;
		}

		return \update_option( $key, $value );
	}

	/**
	 * Executes g2rd/flush-cache: purges all detected caches.
	 *
	 * Always flushes the WP object cache. Also calls purge functions for
	 * WP Rocket, LiteSpeed Cache, W3 Total Cache and WP Super Cache if active.
	 * Capability check runs inside switched user context.
	 *
	 * @return bool True on success.
	 */
	private function exec_flush_cache(): bool {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return false;
		}

		// WordPress object cache.
		\wp_cache_flush();

		// WP Rocket.
		if ( \function_exists( 'rocket_clean_domain' ) ) {
			\rocket_clean_domain();
		}

		// LiteSpeed Cache.
		if ( \class_exists( 'LiteSpeed_Cache_API' ) ) {
			\LiteSpeed_Cache_API::purge_all();
		} elseif ( \has_action( 'litespeed_purge_all' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache third-party hook
			\do_action( 'litespeed_purge_all' );
		}

		// W3 Total Cache.
		if ( \function_exists( 'w3tc_flush_all' ) ) {
			\w3tc_flush_all();
		}

		// WP Super Cache.
		if ( \function_exists( 'wp_cache_clean_cache' ) ) {
			global $file_prefix, $cache_path;
			\wp_cache_clean_cache( $file_prefix ?? 'wp-cache-', true );
		}

		return true;
	}

	/**
	 * Executes g2rd/update-menu-item: updates a navigation menu item.
	 *
	 * Capability check runs inside switched user context.
	 *
	 * @param array<string, mixed> $args Tool arguments (item_id required; title, url, order, parent optional).
	 * @return bool True on success.
	 */
	private function exec_update_menu_item( array $args ): bool {
		if ( ! \current_user_can( 'edit_theme_options' ) ) {
			return false;
		}

		$item_id = \absint( $args['item_id'] ?? 0 );

		if ( $item_id <= 0 ) {
			return false;
		}

		$post = \get_post( $item_id );

		if ( ! ( $post instanceof \WP_Post ) || 'nav_menu_item' !== $post->post_type ) {
			return false;
		}

		$menu_item_data = [];

		if ( isset( $args['title'] ) ) {
			$menu_item_data['menu-item-title'] = \sanitize_text_field( (string) $args['title'] );
		}
		if ( isset( $args['url'] ) ) {
			$menu_item_data['menu-item-url'] = \esc_url_raw( (string) $args['url'] );
		}
		if ( isset( $args['order'] ) ) {
			$menu_item_data['menu-item-position'] = \absint( $args['order'] );
		}
		if ( isset( $args['parent'] ) ) {
			$menu_item_data['menu-item-parent-id'] = \absint( $args['parent'] );
		}

		if ( empty( $menu_item_data ) ) {
			return false;
		}

		$menu_item_data['menu-item-status'] = 'publish';

		$result = \wp_update_nav_menu_item(
			0, // menu ID — 0 uses the item's existing menu
			$item_id,
			$menu_item_data
		);

		return ! \is_wp_error( $result );
	}

	/**
	 * Executes g2rd/upload-media: downloads a file from a URL and imports it into the media library.
	 *
	 * Allowed extensions: jpg, jpeg, png, gif, webp, svg, pdf. Max size: 10 MB.
	 *
	 * @param array<string, mixed> $args Tool arguments (url required; title, alt_text, caption, description optional).
	 * @return bool True on success.
	 */
	private function exec_upload_media( array $args ): bool {
		if ( ! \current_user_can( 'upload_files' ) ) {
			return false;
		}

		$url = \esc_url_raw( (string) ( $args['url'] ?? '' ) );
		if ( empty( $url ) ) {
			return false;
		}

		$allowed_exts = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf' ];
		$ext          = strtolower( (string) pathinfo( (string) \wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		if ( ! \in_array( $ext, $allowed_exts, true ) ) {
			return false;
		}

		require_once \ABSPATH . 'wp-admin/includes/media.php';
		require_once \ABSPATH . 'wp-admin/includes/file.php';
		require_once \ABSPATH . 'wp-admin/includes/image.php';

		$tmp = \download_url( $url );
		if ( \is_wp_error( $tmp ) ) {
			return false;
		}

		// Enforce 10 MB size limit.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize -- checking local tmp file before import
		if ( filesize( $tmp ) > 10 * 1024 * 1024 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- tmp file created by download_url
			unlink( $tmp );
			return false;
		}

		$filename   = \sanitize_file_name( basename( (string) \wp_parse_url( $url, PHP_URL_PATH ) ) );
		$file_array = [
			'name'     => $filename,
			'tmp_name' => $tmp,
		];

		$title         = \sanitize_text_field( (string) ( $args['title'] ?? '' ) );
		$attachment_id = \media_handle_sideload( $file_array, 0, $title ?: null );

		if ( \is_wp_error( $attachment_id ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- tmp file cleanup after failed sideload
			unlink( $tmp );
			return false;
		}

		if ( ! empty( $args['alt_text'] ) ) {
			\update_post_meta( $attachment_id, '_wp_attachment_image_alt', \sanitize_text_field( (string) $args['alt_text'] ) );
		}

		$update_data = [ 'ID' => $attachment_id ];
		if ( ! empty( $args['caption'] ) ) {
			$update_data['post_excerpt'] = \sanitize_text_field( (string) $args['caption'] );
		}
		if ( ! empty( $args['description'] ) ) {
			$update_data['post_content'] = \wp_kses_post( (string) $args['description'] );
		}
		if ( count( $update_data ) > 1 ) {
			\wp_update_post( $update_data );
		}

		\update_option( 'g2rd_mcp_last_upload_media', [
			'attachment_id' => $attachment_id,
			'source_url'    => $url,
			'user_id'       => \get_current_user_id(),
			'time'          => \current_time( 'mysql' ),
		] );

		return true;
	}

	/**
	 * Executes g2rd/upload-media-base64: decodes base64 content and imports it into the media library.
	 *
	 * @param array<string, mixed> $args Tool arguments (data, filename, mime_type required; title, alt_text optional).
	 * @return bool True on success.
	 */
	private function exec_upload_media_base64( array $args ): bool {
		if ( ! \current_user_can( 'upload_files' ) ) {
			return false;
		}

		$b64_data  = (string) ( $args['data'] ?? '' );
		$filename  = \sanitize_file_name( (string) ( $args['filename'] ?? '' ) );
		$mime_type = \sanitize_text_field( (string) ( $args['mime_type'] ?? '' ) );

		if ( empty( $b64_data ) || empty( $filename ) || empty( $mime_type ) ) {
			return false;
		}

		$allowed_mimes = [
			'image/jpeg', 'image/png', 'image/gif', 'image/webp',
			'image/svg+xml', 'application/pdf',
		];
		if ( ! \in_array( $mime_type, $allowed_mimes, true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- intentional media import from base64
		$decoded = base64_decode( $b64_data, true );
		if ( false === $decoded ) {
			return false;
		}

		if ( strlen( $decoded ) > 10 * 1024 * 1024 ) {
			return false;
		}

		require_once \ABSPATH . 'wp-admin/includes/image.php';

		$upload_dir = \wp_upload_dir();
		$file_path  = trailingslashit( $upload_dir['path'] ) . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writing decoded binary to uploads dir
		$written = file_put_contents( $file_path, $decoded );
		if ( false === $written ) {
			return false;
		}

		$title      = \sanitize_text_field( (string) ( $args['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) ) );
		$attachment = [
			'guid'           => trailingslashit( $upload_dir['url'] ) . $filename,
			'post_mime_type' => $mime_type,
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = \wp_insert_attachment( $attachment, $file_path );
		if ( \is_wp_error( $attachment_id ) || $attachment_id <= 0 ) {
			return false;
		}

		$metadata = \wp_generate_attachment_metadata( $attachment_id, $file_path );
		\wp_update_attachment_metadata( $attachment_id, $metadata );

		if ( ! empty( $args['alt_text'] ) ) {
			\update_post_meta( $attachment_id, '_wp_attachment_image_alt', \sanitize_text_field( (string) $args['alt_text'] ) );
		}

		\update_option( 'g2rd_mcp_last_upload_media', [
			'attachment_id' => $attachment_id,
			'filename'      => $filename,
			'user_id'       => \get_current_user_id(),
			'time'          => \current_time( 'mysql' ),
		] );

		return true;
	}

	/**
	 * Executes g2rd/delete-media: moves a media attachment to the trash.
	 *
	 * @param array<string, mixed> $args Tool arguments (attachment_id required).
	 * @return bool True on success.
	 */
	private function exec_delete_media( array $args ): bool {
		if ( ! \current_user_can( 'delete_posts' ) ) {
			return false;
		}

		$attachment_id = \absint( $args['attachment_id'] ?? 0 );
		if ( $attachment_id <= 0 ) {
			return false;
		}

		$post = \get_post( $attachment_id );
		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return false;
		}

		$result = \wp_trash_post( $attachment_id );

		return false !== $result;
	}

	/**
	 * Detects the active SEO plugin by checking defined constants.
	 *
	 * Mirrors McpAbilities::detect_active_seo_plugin() for use in the queue context.
	 * Returns 'yoast', 'rank_math', 'seopress', 'aioseo', or 'none'.
	 *
	 * @return string
	 */
	private function detect_active_seo_plugin_queue(): string {
		if ( \defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( \defined( 'RANK_MATH_VERSION' ) ) {
			return 'rank_math';
		}
		if ( \defined( 'SEOPRESS_VERSION' ) ) {
			return 'seopress';
		}
		if ( \defined( 'AIOSEO_VERSION' ) ) {
			return 'aioseo';
		}
		return 'none';
	}
}
