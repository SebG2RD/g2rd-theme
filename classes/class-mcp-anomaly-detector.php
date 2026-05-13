<?php
/**
 * MCP Anomaly Detector — SP-5 behavioral pattern analysis
 *
 * Scans the audit log for suspicious request patterns and surfaces them to the
 * administrator via the REST API and the admin options page.
 *
 * Three detection categories:
 *   brute_force    — IP with ≥5 denials within a 15-minute sliding window
 *   high_denial    — Token with >50% denial rate over the last 24 h (min 5 requests)
 *   volume_spike   — Total requests in the last hour > 3× the 7-day hourly average
 *
 * Detection is read-only; it never modifies any table.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Detects anomalous patterns in the MCP audit log.
 */
class McpAnomalyDetector {

	/** @var string Audit log table suffix (without $wpdb->prefix). */
	private const AUDIT_TABLE = 'g2rd_mcp_audit_log';

	/** @var int Minimum denial count to trigger brute-force alert. */
	private const BRUTE_FORCE_THRESHOLD = 5;

	/** @var int Minutes window for brute-force detection. */
	private const BRUTE_FORCE_WINDOW_MIN = 15;

	/** @var float Denial rate (0–1) above which a token is flagged. */
	private const HIGH_DENIAL_RATE = 0.5;

	/** @var int Minimum requests in 24 h before denial-rate check applies. */
	private const HIGH_DENIAL_MIN_REQUESTS = 5;

	/** @var float Request-volume multiplier that triggers a spike alert. */
	private const VOLUME_SPIKE_FACTOR = 3.0;

	/** @var string Transient key for the cached detection result. */
	private const CACHE_KEY = 'g2rd_mcp_anomalies';

	/** @var int Cache TTL in seconds (5 minutes — balances freshness vs. DB load). */
	private const CACHE_TTL = 300;

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Runs all detectors and returns a flat array of anomaly records.
	 *
	 * Results are cached for 5 minutes via a WordPress transient.
	 * Each record has the shape:
	 *   type       string  'brute_force' | 'high_denial' | 'volume_spike'
	 *   severity   string  'critical' | 'high' | 'medium'
	 *   detail     array   Type-specific details (ip_address, token_id, count, …)
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function detect(): array {
		$cached = \get_transient( self::CACHE_KEY );
		if ( \is_array( $cached ) ) {
			return $cached;
		}

		$anomalies = [];

		foreach ( $this->detect_brute_force() as $a ) {
			$anomalies[] = $a;
		}
		foreach ( $this->detect_high_denial() as $a ) {
			$anomalies[] = $a;
		}

		$spike = $this->detect_volume_spike();
		if ( null !== $spike ) {
			$anomalies[] = $spike;
		}

		\set_transient( self::CACHE_KEY, $anomalies, self::CACHE_TTL );

		return $anomalies;
	}

	/**
	 * Runs detection once and returns both the anomaly list and a severity summary.
	 *
	 * Used by the REST endpoint to avoid running the queries twice.
	 *
	 * @return array{anomalies: array<int, array<string, mixed>>, summary: array{total: int, critical: int, high: int, medium: int}}
	 */
	public function detect_with_summary(): array {
		$anomalies = $this->detect();

		$summary = [ 'total' => \count( $anomalies ), 'critical' => 0, 'high' => 0, 'medium' => 0 ];
		foreach ( $anomalies as $a ) {
			if ( isset( $summary[ $a['severity'] ] ) ) {
				++$summary[ $a['severity'] ];
			}
		}

		return [
			'anomalies' => $anomalies,
			'summary'   => $summary,
		];
	}

	/**
	 * Returns a severity summary of the current anomaly set.
	 *
	 * @return array{total: int, critical: int, high: int, medium: int}
	 */
	public function get_summary(): array {
		return $this->detect_with_summary()['summary'];
	}

	// ── Detectors ─────────────────────────────────────────────────────────────

	/**
	 * Finds IP addresses with ≥5 denied requests in the last 15 minutes.
	 *
	 * Severity: critical (≥10 denials), high (5–9 denials).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function detect_brute_force(): array {
		global $wpdb;

		$table    = $wpdb->prefix . self::AUDIT_TABLE;
		$since    = \gmdate( 'Y-m-d H:i:s', \time() - ( self::BRUTE_FORCE_WINDOW_MIN * 60 ) );
		$min_hits = self::BRUTE_FORCE_THRESHOLD;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT ip_address, COUNT(*) AS hit_count FROM `{$table}` WHERE decision = 'denied' AND created_at >= %s GROUP BY ip_address HAVING hit_count >= %d ORDER BY hit_count DESC LIMIT 20",
				$since,
				$min_hits
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! \is_array( $rows ) ) {
			return [];
		}

		$anomalies = [];
		foreach ( $rows as $row ) {
			$count     = (int) $row['hit_count'];
			$anomalies[] = [
				'type'     => 'brute_force',
				'severity' => $count >= 10 ? 'critical' : 'high',
				'detail'   => [
					'ip_address'  => (string) $row['ip_address'],
					'denial_count' => $count,
					'window_min'  => self::BRUTE_FORCE_WINDOW_MIN,
				],
			];
		}

		return $anomalies;
	}

	/**
	 * Finds tokens whose denial rate exceeds 50% in the last 24 hours.
	 *
	 * Only tokens with at least 5 requests are considered to avoid noise.
	 * Severity: high (>80% denial rate), medium (50–80%).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function detect_high_denial(): array {
		global $wpdb;

		$table       = $wpdb->prefix . self::AUDIT_TABLE;
		$since       = \gmdate( 'Y-m-d H:i:s', \time() - 86400 );
		$min_reqs    = self::HIGH_DENIAL_MIN_REQUESTS;
		$denial_rate = self::HIGH_DENIAL_RATE * 100;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT token_id, COUNT(*) AS total_count, SUM(decision = 'denied') AS denied_count FROM `{$table}` WHERE created_at >= %s GROUP BY token_id HAVING total_count >= %d AND (denied_count * 100 / total_count) > %f ORDER BY (denied_count * 100 / total_count) DESC LIMIT 20",
				$since,
				$min_reqs,
				$denial_rate
			),
			\ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! \is_array( $rows ) ) {
			return [];
		}

		$anomalies = [];
		foreach ( $rows as $row ) {
			$rate        = (int) $row['total_count'] > 0
				? \round( ( (int) $row['denied_count'] / (int) $row['total_count'] ) * 100, 1 )
				: 0;
			$anomalies[] = [
				'type'     => 'high_denial',
				'severity' => $rate > 80 ? 'high' : 'medium',
				'detail'   => [
					'token_id'     => (int) $row['token_id'],
					'total_count'  => (int) $row['total_count'],
					'denied_count' => (int) $row['denied_count'],
					'denial_rate'  => $rate,
					'window_hours' => 24,
				],
			];
		}

		return $anomalies;
	}

	/**
	 * Detects a request-volume spike: last-hour count > 3× the 7-day hourly average.
	 *
	 * Returns null when there is not enough history (< 24 h of data) or no spike.
	 * Severity: high (>5× average), medium (3–5×).
	 *
	 * @return array<string, mixed>|null
	 */
	private function detect_volume_spike(): ?array {
		global $wpdb;

		$table      = $wpdb->prefix . self::AUDIT_TABLE;
		$hour_ago   = \gmdate( 'Y-m-d H:i:s', \time() - 3600 );
		$week_ago   = \gmdate( 'Y-m-d H:i:s', \time() - ( 7 * 86400 ) );
		$day_ago    = \gmdate( 'Y-m-d H:i:s', \time() - 86400 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_hour = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s",
				$hour_ago
			)
		);

		$week_total = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT COUNT(*) FROM `{$table}` WHERE created_at >= %s AND created_at < %s",
				$week_ago,
				$hour_ago
			)
		);

		$data_start = (string) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
			"SELECT MIN(created_at) FROM `{$table}`"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Require at least 24 h of data before flagging a volume spike.
		if ( ! $data_start || \strtotime( $data_start ) > \strtotime( $day_ago ) ) {
			return null;
		}

		// Compute 7-day hourly average (use 167 hours to exclude the current window).
		$hourly_avg = $week_total > 0 ? $week_total / 167.0 : 0;

		if ( $hourly_avg < 1 || $last_hour < self::BRUTE_FORCE_THRESHOLD ) {
			return null;
		}

		$factor = $last_hour / $hourly_avg;

		if ( $factor < self::VOLUME_SPIKE_FACTOR ) {
			return null;
		}

		return [
			'type'     => 'volume_spike',
			'severity' => $factor >= 5 ? 'high' : 'medium',
			'detail'   => [
				'last_hour_count' => $last_hour,
				'hourly_avg'      => \round( $hourly_avg, 1 ),
				'spike_factor'    => \round( $factor, 1 ),
			],
		];
	}
}
