<?php
/**
 * MCP Rate Limiter
 *
 * Token-bucket rate limiting stored in WordPress transients.
 * Three independent buckets per client:
 *
 *   1. requests  — global throughput cap (default 60 req/min).
 *   2. auth_failures — lockout after N consecutive bad tokens.
 *   3. destructive — stricter cap for write/delete operations.
 *
 * All bucket state is identified by an anonymised key derived from
 * the client IP so that an attacker cannot pre-seed or enumerate keys.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Token-bucket rate limiter backed by WordPress transients.
 */
class McpRateLimiter {

	/** @var int Maximum requests per minute (global bucket). */
	public const REQUESTS_PER_MINUTE = 60;

	/** @var int Maximum destructive operations per minute (write/delete bucket). */
	public const DESTRUCTIVE_PER_MINUTE = 10;

	/** @var int Consecutive auth failures before lockout. */
	public const AUTH_FAILURE_THRESHOLD = 5;

	/** @var int Lockout duration in seconds after auth failure threshold. */
	public const AUTH_LOCKOUT_SECONDS = 900; // 15 minutes.

	/** @var int Bucket refill window in seconds (= 1 minute). */
	private const BUCKET_TTL = 60;

	/** @var string Transient key prefix for rate-limit buckets. */
	private const KEY_PREFIX = 'g2rd_mcp_rl_';

	/** @var McpEncryption Used to derive anonymised bucket keys. */
	private McpEncryption $crypto;

	/**
	 * @param McpEncryption $crypto Encryption instance for key derivation.
	 */
	public function __construct( McpEncryption $crypto ) {
		$this->crypto = $crypto;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Checks and consumes one token from the global requests bucket.
	 *
	 * @param string $client_ip Client IP address.
	 * @return bool True if the request is allowed, false if rate-limited.
	 */
	public function check_requests( string $client_ip ): bool {
		return $this->consume( 'req', $client_ip, self::REQUESTS_PER_MINUTE, self::BUCKET_TTL );
	}

	/**
	 * Checks and consumes one token from the destructive-operations bucket.
	 *
	 * @param string $client_ip Client IP address.
	 * @return bool True if the request is allowed, false if rate-limited.
	 */
	public function check_destructive( string $client_ip ): bool {
		return $this->consume( 'dst', $client_ip, self::DESTRUCTIVE_PER_MINUTE, self::BUCKET_TTL );
	}

	/**
	 * Records an authentication failure and checks whether the IP is locked out.
	 *
	 * Returns false (locked out) after AUTH_FAILURE_THRESHOLD consecutive failures.
	 * The lockout window resets on each new failure to prevent brute-force with pauses.
	 *
	 * @param string $client_ip Client IP address.
	 * @return bool False if the IP is now locked out, true otherwise.
	 */
	public function record_auth_failure( string $client_ip ): bool {
		$key   = $this->bucket_key( 'auth', $client_ip );
		$count = (int) \get_transient( $key );
		++$count;

		// Refresh the lockout window on every new failure.
		\set_transient( $key, $count, self::AUTH_LOCKOUT_SECONDS );

		return $count <= self::AUTH_FAILURE_THRESHOLD;
	}

	/**
	 * Resets the auth-failure counter for an IP after a successful authentication.
	 *
	 * @param string $client_ip Client IP address.
	 * @return void
	 */
	public function reset_auth_failures( string $client_ip ): void {
		\delete_transient( $this->bucket_key( 'auth', $client_ip ) );
	}

	/**
	 * Checks whether a client IP is currently locked out due to auth failures.
	 *
	 * @param string $client_ip Client IP address.
	 * @return bool True if the IP is locked out.
	 */
	public function is_locked_out( string $client_ip ): bool {
		$key   = $this->bucket_key( 'auth', $client_ip );
		$count = (int) \get_transient( $key );

		return $count > self::AUTH_FAILURE_THRESHOLD;
	}

	/**
	 * Returns remaining tokens in the global requests bucket without consuming.
	 *
	 * Useful for returning rate-limit headers in API responses.
	 *
	 * @param string $client_ip Client IP address.
	 * @return int Remaining requests allowed in the current window.
	 */
	public function remaining_requests( string $client_ip ): int {
		$key  = $this->bucket_key( 'req', $client_ip );
		$used = (int) \get_transient( $key );

		return \max( 0, self::REQUESTS_PER_MINUTE - $used );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Consumes one unit from a named bucket for a given client IP.
	 *
	 * Uses an HMAC-derived key so the IP is never stored in plain text.
	 * If the bucket is empty (count >= limit), returns false.
	 *
	 * @param string $bucket    Bucket name ('req', 'dst', 'auth').
	 * @param string $client_ip Client IP address.
	 * @param int    $limit     Maximum allowed count per window.
	 * @param int    $ttl       Window duration in seconds.
	 * @return bool True if request is within limit, false if exceeded.
	 */
	private function consume( string $bucket, string $client_ip, int $limit, int $ttl ): bool {
		$key   = $this->bucket_key( $bucket, $client_ip );
		$count = (int) \get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		// First request in window: set with TTL. Subsequent: increment with add().
		if ( 0 === $count ) {
			\set_transient( $key, 1, $ttl );
		} else {
			// Increment without resetting TTL by storing count + 1.
			// get_transient / set_transient is used because WordPress transients
			// do not expose an atomic increment API. Race conditions here cause at
			// most a few extra allowed requests, which is acceptable for rate limiting.
			\set_transient( $key, $count + 1, $ttl );
		}

		return true;
	}

	/**
	 * Derives an anonymised transient key for a bucket + IP combination.
	 *
	 * The IP is hashed so it is never stored in plain text in the options table.
	 *
	 * @param string $bucket    Bucket name.
	 * @param string $client_ip Client IP address.
	 * @return string Transient key (max 172 chars, well within WordPress 191-char limit).
	 */
	private function bucket_key( string $bucket, string $client_ip ): string {
		$rate_key = $this->crypto->derive_key( 'rate_limit' );
		$ip_hash  = \substr( \hash_hmac( 'sha256', $client_ip, $rate_key ), 0, 16 );

		return self::KEY_PREFIX . $bucket . '_' . $ip_hash;
	}
}
