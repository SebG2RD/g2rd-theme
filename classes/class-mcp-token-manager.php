<?php
/**
 * MCP Token Manager
 *
 * Manages the full lifecycle of MCP authentication tokens:
 * creation, validation, revocation, rotation and listing.
 *
 * Token format: g2rd_ + 40 unbiased base62 characters = 45 chars total.
 * Entropy: ~238 bits — brute-force resistant.
 *
 * Storage: only the HMAC-SHA256 hash is stored in the database.
 * The raw token is returned once at creation and never persisted.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Creates, validates, revokes and rotates MCP authentication tokens.
 */
class McpTokenManager {

	/** @var string[] Valid scope values in ascending privilege order. */
	private const VALID_SCOPES = [ 'read_only', 'editor', 'admin', 'full' ];

	/** @var array<string, int> Scope hierarchy — higher int = more privileges. */
	private const SCOPE_HIERARCHY = [
		'read_only' => 0,
		'editor'    => 1,
		'admin'     => 2,
		'full'      => 3,
	];

	/** @var string Base62 alphabet for unbiased token generation. */
	private const TOKEN_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	/** @var int Number of random base62 characters after the g2rd_ prefix. */
	private const TOKEN_RANDOM_LENGTH = 40;

	/** @var int Default token validity in days before mandatory rotation. */
	private const DEFAULT_EXPIRES_DAYS = 90;

	/** @var McpEncryption Cryptographic operations. */
	private McpEncryption $crypto;

	/** @var McpAuditLog Audit log for token lifecycle events. */
	private McpAuditLog $audit;

	/**
	 * @param McpEncryption $crypto Encryption instance.
	 * @param McpAuditLog   $audit  Audit log instance.
	 */
	public function __construct( McpEncryption $crypto, McpAuditLog $audit ) {
		$this->crypto = $crypto;
		$this->audit  = $audit;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Creates a new MCP token for a user.
	 *
	 * Returns the raw token ONCE — it is never stored and cannot be retrieved.
	 * The caller must display it to the user immediately.
	 *
	 * @param int    $user_id User ID the token belongs to.
	 * @param string $name    Human-readable token name (max 100 chars).
	 * @param string $scope   One of: read_only, editor, admin, full.
	 * @param array<string, mixed> $options {
	 *     Optional overrides.
	 *     @type int      $expires_in_days Days until expiry (default 90).
	 *     @type string[] $allowed_ips     IP whitelist. Empty = all IPs allowed.
	 * }
	 * @return array{token: string, id: int, expires_at: string, scope: string}|false Token data or false on failure.
	 */
	public function create_token( int $user_id, string $name, string $scope, array $options = [] ): array|false {
		if ( ! $this->validate_scope( $scope ) ) {
			return false;
		}

		$raw_token    = $this->generate_raw_token();
		$token_hash   = $this->crypto->hash_token( $raw_token );
		$token_prefix = $this->extract_prefix( $raw_token );
		$expires_days = \absint( $options['expires_in_days'] ?? self::DEFAULT_EXPIRES_DAYS );
		$expires_at   = \gmdate( 'Y-m-d H:i:s', \time() + $expires_days * \DAY_IN_SECONDS );

		$allowed_ips = null;
		if ( ! empty( $options['allowed_ips'] ) && \is_array( $options['allowed_ips'] ) ) {
			$allowed_ips = \implode( ',', \array_map( '\sanitize_text_field', $options['allowed_ips'] ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'g2rd_mcp_tokens';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Token creation: direct insert required.
		$result = $wpdb->insert(
			$table,
			[
				'user_id'      => $user_id,
				'token_name'   => \sanitize_text_field( \substr( $name, 0, 100 ) ),
				'token_hash'   => $token_hash,
				'token_prefix' => $token_prefix,
				'scope'        => $scope,
				'allowed_ips'  => $allowed_ips,
				'expires_at'   => $expires_at,
				'created_at'   => \current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $result ) {
			return false;
		}

		return [
			'token'      => $raw_token,
			'id'         => (int) $wpdb->insert_id,
			'expires_at' => $expires_at,
			'scope'      => $scope,
		];
	}

	/**
	 * Validates a raw token and returns its associated data.
	 *
	 * Performs format check, prefix lookup, constant-time hash comparison,
	 * revocation check and expiry check. Updates last_used_at on success.
	 *
	 * @param string $raw_token Raw token submitted by the MCP client.
	 * @return array{id: int, user_id: int, scope: string, allowed_ips: string|null, expires_at: string}|false Token data or false.
	 */
	public function validate_token( string $raw_token ): array|false {
		if ( ! $this->has_valid_format( $raw_token ) ) {
			return false;
		}

		$prefix = $this->extract_prefix( $raw_token );

		global $wpdb;
		$table = $wpdb->prefix . 'g2rd_mcp_tokens';

		// Prefix lookup narrows the result before the expensive hash comparison.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Token validation: live read required; table name from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE token_prefix = %s AND revoked_at IS NULL AND expires_at > UTC_TIMESTAMP() LIMIT 1",
				$prefix
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $row ) {
			return false;
		}

		// Full hash comparison in constant time — prevents timing attacks.
		if ( ! $this->crypto->verify_token_hash( $raw_token, $row['token_hash'] ) ) {
			return false;
		}

		$this->update_last_used( (int) $row['id'] );

		return [
			'id'          => (int) $row['id'],
			'user_id'     => (int) $row['user_id'],
			'scope'       => (string) $row['scope'],
			'allowed_ips' => $row['allowed_ips'] ?? null,
			'expires_at'  => (string) $row['expires_at'],
		];
	}

	/**
	 * Revokes a token immediately.
	 *
	 * Only the owner of the token or a user with manage_options can revoke.
	 *
	 * @param int $token_id          Token row ID to revoke.
	 * @param int $requesting_user_id User performing the revocation.
	 * @return bool True if revoked, false if not found or unauthorised.
	 */
	public function revoke_token( int $token_id, int $requesting_user_id ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'g2rd_mcp_tokens';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Live token lookup; table name from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT user_id, revoked_at FROM `{$table}` WHERE id = %d", $token_id ),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $row || ! \is_null( $row['revoked_at'] ) ) {
			return false;
		}

		$is_owner = (int) $row['user_id'] === $requesting_user_id;
		$is_admin = \current_user_can( 'manage_options' );

		if ( ! $is_owner && ! $is_admin ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Revocation update; no caching needed for a write operation.
		$result = $wpdb->update(
			$table,
			[ 'revoked_at' => \current_time( 'mysql', true ) ],
			[ 'id' => $token_id ],
			[ '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Rotates a token: revokes the current one and creates a replacement with the same scope.
	 *
	 * Returns the new token data (including the raw token, shown once).
	 *
	 * @param int $token_id          Token row ID to rotate.
	 * @param int $requesting_user_id User performing the rotation.
	 * @return array{token: string, id: int, expires_at: string, scope: string}|false New token data or false.
	 */
	public function rotate_token( int $token_id, int $requesting_user_id ): array|false {
		global $wpdb;
		$table = $wpdb->prefix . 'g2rd_mcp_tokens';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Live token lookup for rotation; table name from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d AND revoked_at IS NULL", $token_id ),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $row ) {
			return false;
		}

		if ( ! $this->revoke_token( $token_id, $requesting_user_id ) ) {
			return false;
		}

		return $this->create_token(
			(int) $row['user_id'],
			(string) $row['token_name'] . ' (rotated)',
			(string) $row['scope']
		);
	}

	/**
	 * Lists all active tokens for a user.
	 *
	 * Never returns token_hash or the raw token.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int, array{id: int, token_name: string, scope: string, token_prefix: string, last_used_at: string|null, last_used_ip: string|null, expires_at: string}> Token list.
	 */
	public function list_tokens( int $user_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'g2rd_mcp_tokens';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Live token list; table name from $wpdb->prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, token_name, scope, token_prefix, last_used_at, last_used_ip, expires_at, created_at FROM `{$table}` WHERE user_id = %d AND revoked_at IS NULL ORDER BY created_at DESC",
				$user_id
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $rows ?: [];
	}

	/**
	 * Returns usage statistics for a specific token.
	 *
	 * @param int $token_id Token row ID.
	 * @return array{last_used_at: string|null, last_used_ip: string|null, total_requests: int} Stats.
	 */
	public function get_token_stats( int $token_id ): array {
		global $wpdb;
		$token_table = $wpdb->prefix . 'g2rd_mcp_tokens';
		$audit_table = $wpdb->prefix . 'g2rd_mcp_audit_log';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Live stats queries; table names from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT last_used_at, last_used_ip FROM `{$token_table}` WHERE id = %d", $token_id ),
			\ARRAY_A
		);

		$count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM `{$audit_table}` WHERE token_id = %d AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)", $token_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return [
			'last_used_at'   => $row['last_used_at'] ?? null,
			'last_used_ip'   => $row['last_used_ip'] ?? null,
			'total_requests' => $count,
		];
	}

	/**
	 * Checks if a token scope satisfies a minimum required scope.
	 *
	 * @param string $token_scope    The scope the token has.
	 * @param string $required_scope The minimum scope required.
	 * @return bool True if token scope >= required scope.
	 */
	public function scope_satisfies( string $token_scope, string $required_scope ): bool {
		$token_level    = self::SCOPE_HIERARCHY[ $token_scope ]    ?? -1;
		$required_level = self::SCOPE_HIERARCHY[ $required_scope ] ?? PHP_INT_MAX;

		return $token_level >= $required_level;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Generates a cryptographically secure raw token using unbiased base62 sampling.
	 *
	 * Format: g2rd_ + 40 base62 characters.
	 * Rejection sampling discards bytes 248-255 to eliminate modulo bias
	 * (248 = 4 x 62, the largest multiple of 62 below 256).
	 *
	 * @return string Raw token (45 chars minimum).
	 */
	private function generate_raw_token(): string {
		$chars  = self::TOKEN_CHARS;
		$result = 'g2rd_';
		$count  = 0;

		while ( $count < self::TOKEN_RANDOM_LENGTH ) {
			$byte = \ord( \random_bytes( 1 ) );
			if ( $byte < 248 ) {
				$result .= $chars[ $byte % 62 ];
				++$count;
			}
		}

		return $result;
	}

	/**
	 * Validates that a scope value is one of the allowed scopes.
	 *
	 * @param string $scope Scope to validate.
	 * @return bool True if valid.
	 */
	private function validate_scope( string $scope ): bool {
		return \in_array( $scope, self::VALID_SCOPES, true );
	}

	/**
	 * Checks whether a token DB row is currently valid (not revoked, not expired).
	 *
	 * @param array<string, mixed> $row Database row from g2rd_mcp_tokens.
	 * @return bool True if the row represents a valid, usable token.
	 */
	private function is_token_row_valid( array $row ): bool {
		// Treat any non-null value as revoked — including empty string (fail closed).
		if ( null !== $row['revoked_at'] ) {
			return false;
		}

		if ( empty( $row['expires_at'] ) ) {
			return false;
		}

		return \strtotime( (string) $row['expires_at'] ) > \time();
	}

	/**
	 * Returns the first 8 characters of a raw token (used as DB lookup prefix).
	 *
	 * @param string $raw_token Raw token value.
	 * @return string 8-character prefix string.
	 */
	private function extract_prefix( string $raw_token ): string {
		return \substr( $raw_token, 0, 8 );
	}

	/**
	 * Checks whether a string matches the expected raw token format.
	 *
	 * @param string $raw_token String to check.
	 * @return bool True if format is valid.
	 */
	private function has_valid_format( string $raw_token ): bool {
		return \str_starts_with( $raw_token, 'g2rd_' ) && \strlen( $raw_token ) >= 45;
	}

	/**
	 * Updates last_used_at and last_used_ip for a token after successful validation.
	 *
	 * @param int $token_id Token row ID.
	 * @return void
	 */
	private function update_last_used( int $token_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- REMOTE_ADDR is set by the server, not user input.
		$ip = \sanitize_text_field( \wp_unslash( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Last-used timestamp update on successful auth; no caching needed for a write.
		$wpdb->update(
			$wpdb->prefix . 'g2rd_mcp_tokens',
			[
				'last_used_at' => \current_time( 'mysql', true ),
				'last_used_ip' => \substr( $ip, 0, 45 ),
			],
			[ 'id' => $token_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}
}
