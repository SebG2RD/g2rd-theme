<?php
/**
 * MCP Audit Log
 *
 * Immutable INSERT-only audit table with cryptographic chain integrity.
 * Every row's chain_hash covers all its columns plus the previous row's
 * chain_hash, so any retroactive modification is detectable via
 * verify_integrity().
 *
 * RGPD: input payloads are never stored in clear — only their SHA-256 hash.
 * Retention: 30 days by default, 1 year for destructive 'allowed' decisions.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * INSERT-only audit log with chain-hash tamper detection.
 *
 * No update() or delete() methods are intentionally exposed.
 */
class McpAuditLog {

	/** @var int Default retention in days for standard entries. */
	private const RETENTION_DEFAULT_DAYS = 30;

	/** @var int Extended retention for destructive allowed decisions. */
	private const RETENTION_DESTRUCTIVE_DAYS = 365;

	/** @var string WordPress option holding the last chain hash seed. */
	private const LAST_HASH_OPTION = 'g2rd_mcp_audit_chain_seed';

	/** @var McpEncryption Crypto helper for chain key derivation. */
	private McpEncryption $crypto;

	/**
	 * @param McpEncryption $crypto Encryption instance for chain key derivation.
	 */
	public function __construct( McpEncryption $crypto ) {
		$this->crypto = $crypto;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Inserts an immutable audit entry and computes its chain hash.
	 *
	 * Required keys in $entry:
	 *   user_id      (int)    WordPress user ID.
	 *   token_id     (int)    MCP token ID (0 for unauthenticated attempts).
	 *   ip_address   (string) Client IP address.
	 *   ability_name (string) Ability identifier (e.g. 'g2rd/list-posts').
	 *   input        (mixed)  Raw input — hashed before storage, never stored clear.
	 *   decision     (string) 'allowed' | 'denied' | 'pending' | 'rolled_back'.
	 *
	 * Optional keys:
	 *   user_agent    (string) HTTP User-Agent header.
	 *   denial_reason (string) Human-readable reason for denial.
	 *   execution_ms  (int)    Execution time in milliseconds.
	 *   screen_context (string) Admin URL from X-G2RD-Screen header.
	 *
	 * @param array<string, mixed> $entry Audit entry data.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function log( array $entry ): int {
		global $wpdb;

		$prev_hash  = $this->get_last_chain_hash();
		$input_hash = \hash( 'sha256', \wp_json_encode( $entry['input'] ?? '' ) );
		$now        = \current_time( 'mysql', true ); // UTC with microseconds format.

		$row = [
			'created_at'    => $now,
			'user_id'       => \absint( $entry['user_id'] ?? 0 ),
			'token_id'      => \absint( $entry['token_id'] ?? 0 ),
			'ip_address'    => \sanitize_text_field( (string) ( $entry['ip_address'] ?? '' ) ),
			'user_agent'    => \sanitize_text_field( \substr( (string) ( $entry['user_agent'] ?? '' ), 0, 255 ) ),
			'ability_name'  => \sanitize_key( (string) ( $entry['ability_name'] ?? '' ) ),
			'input_hash'    => $input_hash,
			'decision'      => $this->sanitize_decision( (string) ( $entry['decision'] ?? 'denied' ) ),
			'denial_reason' => isset( $entry['denial_reason'] )
				? \sanitize_text_field( \substr( (string) $entry['denial_reason'], 0, 255 ) )
				: null,
			'execution_ms'  => isset( $entry['execution_ms'] ) ? \absint( $entry['execution_ms'] ) : null,
			'screen_context' => isset( $entry['screen_context'] )
				? \sanitize_text_field( \substr( (string) $entry['screen_context'], 0, 500 ) )
				: null,
			'chain_hash'    => '', // Computed below after row data is final.
		];

		$row['chain_hash'] = $this->compute_chain_hash( $row, $prev_hash );

		$table  = $wpdb->prefix . 'g2rd_mcp_audit_log';
		$format = [ '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Audit log: direct insert required for immutability guarantee.
		$result = $wpdb->insert( $table, $row, $format );

		if ( false === $result ) {
			return 0;
		}

		$inserted_id = (int) $wpdb->insert_id;

		// Persist the new chain hash so the next insert can chain from it.
		\update_option( self::LAST_HASH_OPTION, $row['chain_hash'], false );

		return $inserted_id;
	}

	/**
	 * Queries the audit log with optional filters and pagination.
	 *
	 * @param array<string, mixed> $filters  Optional filters: user_id, token_id, decision, ability_name, date_from, date_to.
	 * @param int                  $page     1-based page number.
	 * @param int                  $per_page Rows per page (max 200).
	 * @return array{items: array<int, array<string, mixed>>, total: int} Paginated results.
	 */
	public function query( array $filters = [], int $page = 1, int $per_page = 50 ): array {
		global $wpdb;

		$table      = $wpdb->prefix . 'g2rd_mcp_audit_log';
		$per_page   = \min( \absint( $per_page ), 200 );
		$page       = \max( 1, \absint( $page ) );
		$offset     = ( $page - 1 ) * $per_page;
		$conditions = [ '1=1' ];
		$values     = [];

		if ( ! empty( $filters['user_id'] ) ) {
			$conditions[] = 'user_id = %d';
			$values[]     = \absint( $filters['user_id'] );
		}
		if ( ! empty( $filters['token_id'] ) ) {
			$conditions[] = 'token_id = %d';
			$values[]     = \absint( $filters['token_id'] );
		}
		if ( ! empty( $filters['decision'] ) ) {
			$conditions[] = 'decision = %s';
			$values[]     = $this->sanitize_decision( (string) $filters['decision'] );
		}
		if ( ! empty( $filters['ability_name'] ) ) {
			$conditions[] = 'ability_name = %s';
			$values[]     = \sanitize_key( (string) $filters['ability_name'] );
		}
		if ( ! empty( $filters['date_from'] ) ) {
			$conditions[] = 'created_at >= %s';
			$values[]     = \sanitize_text_field( (string) $filters['date_from'] );
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$conditions[] = 'created_at <= %s';
			$values[]     = \sanitize_text_field( (string) $filters['date_to'] );
		}

		$where = \implode( ' AND ', $conditions );

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Audit log: no caching (live integrity data required); $where built from sanitized scalar values only; spread count matches placeholders dynamically.
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE {$where}", ...$values ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Same: live audit data, dynamic but safe placeholders.
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d", ...\array_merge( $values, [ $per_page, $offset ] ) ), \ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Audit log: no caching; no user input in this branch.
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Same.
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ), \ARRAY_A );
		}

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	/**
	 * Returns the chain hash of the last inserted row (or a seed if empty).
	 *
	 * Used by log() to chain the next entry.
	 *
	 * @return string 64-character hex HMAC string.
	 */
	public function get_last_chain_hash(): string {
		$stored = \get_option( self::LAST_HASH_OPTION, '' );

		if ( ! empty( $stored ) ) {
			return (string) $stored;
		}

		// Genesis hash: deterministic seed derived from the audit chain key.
		return \hash_hmac( 'sha256', 'genesis', $this->crypto->derive_key( 'audit_chain' ) );
	}

	/**
	 * Verifies the chain integrity of all rows in a given ID range.
	 *
	 * Recomputes each row's chain_hash and compares it to the stored value.
	 * A mismatch indicates the row (or a predecessor) was tampered with.
	 *
	 * @param int $from_id Starting row ID (inclusive). Default 1 (from beginning).
	 * @param int $to_id   Ending row ID (inclusive). Default 0 (all rows).
	 * @return array{valid: bool, checked: int, broken_at: int|null} Integrity report.
	 */
	public function verify_integrity( int $from_id = 1, int $to_id = 0 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'g2rd_mcp_audit_log';

		if ( $to_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Integrity check must read live data.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id >= %d AND id <= %d ORDER BY id ASC", $from_id, $to_id ), \ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Same.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id >= %d ORDER BY id ASC", $from_id ), \ARRAY_A );
		}

		if ( empty( $rows ) ) {
			return [ 'valid' => true, 'checked' => 0, 'broken_at' => null ];
		}

		// Determine the previous hash before the first row in range.
		if ( (int) $rows[0]['id'] <= 1 ) {
			$prev_hash = \hash_hmac( 'sha256', 'genesis', $this->crypto->derive_key( 'audit_chain' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Live chain hash fetch for integrity verification.
			$prev_row  = $wpdb->get_row( $wpdb->prepare( "SELECT chain_hash FROM `{$table}` WHERE id = %d", (int) $rows[0]['id'] - 1 ), \ARRAY_A );
			$prev_hash = $prev_row ? (string) $prev_row['chain_hash'] : '';
		}

		$checked = 0;

		foreach ( $rows as $row ) {
			$stored_hash   = $row['chain_hash'];
			$row_for_hash  = $row;
			unset( $row_for_hash['chain_hash'] );
			$expected_hash = $this->compute_chain_hash( $row_for_hash, $prev_hash );

			if ( ! \hash_equals( $expected_hash, $stored_hash ) ) {
				return [ 'valid' => false, 'checked' => $checked, 'broken_at' => (int) $row['id'] ];
			}

			$prev_hash = $stored_hash;
			++$checked;
		}

		return [ 'valid' => true, 'checked' => $checked, 'broken_at' => null ];
	}

	/**
	 * Exports audit log entries as CSV or signed JSON.
	 *
	 * JSON format includes an HMAC signature for external verification.
	 *
	 * @param array<string, mixed> $filters Filters passed to query().
	 * @param string               $format  'csv' or 'json'.
	 * @return string Formatted export string.
	 */
	public function export( array $filters = [], string $format = 'csv' ): string {
		$result = $this->query( $filters, 1, 200 );
		$items  = $result['items'];

		if ( 'json' === $format ) {
			$payload   = \wp_json_encode( $items );
			$signature = \hash_hmac( 'sha256', (string) $payload, $this->crypto->derive_key( 'audit_export' ) );
			return (string) \wp_json_encode( [ 'data' => $items, 'signature' => $signature ] );
		}

		// CSV export.
		if ( empty( $items ) ) {
			return '';
		}

		$output  = \implode( ',', \array_keys( $items[0] ) ) . "\n";
		foreach ( $items as $row ) {
			$output .= \implode( ',', \array_map( static fn( $v ) => '"' . \str_replace( '"', '""', (string) $v ) . '"', $row ) ) . "\n";
		}

		return $output;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Computes the HMAC chain hash for a row.
	 *
	 * Hash input: JSON(row_data) + prev_chain_hash, keyed with audit_chain subkey.
	 *
	 * @param array<string, mixed> $row       Row data (without chain_hash).
	 * @param string               $prev_hash Chain hash of the preceding row.
	 * @return string 64-character hex HMAC.
	 */
	private function compute_chain_hash( array $row, string $prev_hash ): string {
		$chain_key = $this->crypto->derive_key( 'audit_chain' );
		$payload   = \wp_json_encode( $row ) . $prev_hash;

		return \hash_hmac( 'sha256', (string) $payload, $chain_key );
	}

	/**
	 * Sanitizes a decision value to one of the allowed ENUM values.
	 *
	 * @param string $decision Raw decision string.
	 * @return string Sanitized decision or 'denied' as safe fallback.
	 */
	private function sanitize_decision( string $decision ): string {
		$allowed = [ 'allowed', 'denied', 'pending', 'rolled_back' ];

		return \in_array( $decision, $allowed, true ) ? $decision : 'denied';
	}
}
