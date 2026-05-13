<?php
/**
 * MCP Security Gate
 *
 * Orchestrates the seven security layers for every inbound MCP request:
 *
 *   Layer 1 — IP lockout check          (McpRateLimiter::is_locked_out)
 *   Layer 2 — Token format + validation  (McpTokenManager::validate_token)
 *   Layer 3 — Token scope check          (McpTokenManager::scope_satisfies)
 *   Layer 4 — WordPress capability check (current_user_can)
 *   Layer 5 — IP allowlist enforcement   (token.allowed_ips)
 *   Layer 6 — Rate limiting              (McpRateLimiter::check_requests)
 *   Layer 7 — Audit log                  (McpAuditLog::log)
 *
 * Fail-closed: any failed layer returns a denial result and logs the event.
 * Layers 6–7 stub points are intentionally left for SP-2 (REST endpoint).
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Seven-layer security orchestrator for inbound MCP requests.
 */
class McpSecurityGate {

	/** @var McpTokenManager Token lifecycle manager. */
	private McpTokenManager $tokens;

	/** @var McpRateLimiter Rate limiting per client IP. */
	private McpRateLimiter $limiter;

	/** @var McpAuditLog INSERT-only audit trail. */
	private McpAuditLog $audit;

	/**
	 * @param McpTokenManager $tokens  Token manager instance.
	 * @param McpRateLimiter  $limiter Rate limiter instance.
	 * @param McpAuditLog     $audit   Audit log instance.
	 */
	public function __construct(
		McpTokenManager $tokens,
		McpRateLimiter $limiter,
		McpAuditLog $audit
	) {
		$this->tokens  = $tokens;
		$this->limiter = $limiter;
		$this->audit   = $audit;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Runs all seven security layers for an inbound MCP request.
	 *
	 * @param string               $raw_token      Bearer token from Authorization header.
	 * @param string               $required_scope  Minimum scope needed for this ability.
	 * @param string               $wp_capability   WordPress capability required (e.g. 'edit_posts').
	 * @param string               $ability_name    MCP ability identifier (e.g. 'g2rd/list-posts').
	 * @param mixed                $input           Raw request payload (hashed before audit storage).
	 * @param string               $client_ip       Client IP address.
	 * @param array<string, mixed> $req_ctx         Optional request context: start_ms, user_agent, screen_context.
	 * @return array{allowed: bool, user_id: int, token_id: int, scope: string, denial_reason: string} Gate result.
	 */
	public function authorize(
		string $raw_token,
		string $required_scope,
		string $wp_capability,
		string $ability_name,
		mixed $input,
		string $client_ip,
		array $req_ctx = []
	): array {
		// Layer 1: IP lockout — fastest check, no DB hit.
		if ( $this->limiter->is_locked_out( $client_ip ) ) {
			return $this->deny( 0, 0, $ability_name, $input, $client_ip, 'ip_locked_out', 'IP locked out due to repeated authentication failures', $req_ctx );
		}

		// Layer 2: Token format + DB validation.
		$token_data = $this->tokens->validate_token( $raw_token );
		if ( false === $token_data ) {
			$this->limiter->record_auth_failure( $client_ip );
			return $this->deny( 0, 0, $ability_name, $input, $client_ip, 'invalid_token', 'Token is invalid, expired or revoked', $req_ctx );
		}

		$user_id  = $token_data['user_id'];
		$token_id = $token_data['id'];

		// Auth succeeded — reset failure counter.
		$this->limiter->reset_auth_failures( $client_ip );

		// Layer 3: Scope hierarchy check.
		if ( ! $this->tokens->scope_satisfies( $token_data['scope'], $required_scope ) ) {
			return $this->deny( $user_id, $token_id, $ability_name, $input, $client_ip, 'insufficient_scope', "Token scope '{$token_data['scope']}' does not satisfy required '{$required_scope}'", $req_ctx );
		}

		// Layer 4: WordPress capability check.
		if ( ! $this->check_wp_capability( $user_id, $wp_capability ) ) {
			return $this->deny( $user_id, $token_id, $ability_name, $input, $client_ip, 'insufficient_capability', "User lacks required WordPress capability: {$wp_capability}", $req_ctx );
		}

		// Layer 5: IP allowlist (empty list = all IPs allowed).
		if ( ! $this->check_ip_allowlist( $client_ip, $token_data['allowed_ips'] ) ) {
			return $this->deny( $user_id, $token_id, $ability_name, $input, $client_ip, 'ip_not_allowed', 'Client IP is not in the token allowlist', $req_ctx );
		}

		// Layer 6: Request rate limiting.
		if ( ! $this->limiter->check_requests( $client_ip ) ) {
			return $this->deny( $user_id, $token_id, $ability_name, $input, $client_ip, 'rate_limited', 'Too many requests — please slow down', $req_ctx );
		}

		// Layer 7: Audit log — allowed decision.
		$execution_ms = isset( $req_ctx['start_ms'] )
			? \max( 0, (int) \round( \microtime( true ) * 1000 ) - $req_ctx['start_ms'] )
			: null;

		$this->audit->log( [
			'user_id'        => $user_id,
			'token_id'       => $token_id,
			'ip_address'     => $client_ip,
			'ability_name'   => $ability_name,
			'input'          => $input,
			'decision'       => 'allowed',
			'user_agent'     => $req_ctx['user_agent'] ?? '',
			'execution_ms'   => $execution_ms,
			'screen_context' => $req_ctx['screen_context'] ?? '',
		] );

		return [
			'allowed'       => true,
			'user_id'       => $user_id,
			'token_id'      => $token_id,
			'scope'         => $token_data['scope'],
			'denial_reason' => '',
		];
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Builds a denial result and writes it to the audit log.
	 *
	 * @param int                  $user_id      WordPress user ID (0 if unknown).
	 * @param int                  $token_id     Token row ID (0 if unknown).
	 * @param string               $ability_name Ability being attempted.
	 * @param mixed                $input        Raw request payload.
	 * @param string               $client_ip    Client IP address.
	 * @param string               $code         Machine-readable denial code.
	 * @param string               $reason       Human-readable denial reason.
	 * @param array<string, mixed> $req_ctx      Optional request context: start_ms, user_agent, screen_context.
	 * @return array{allowed: bool, user_id: int, token_id: int, scope: string, denial_reason: string}
	 */
	private function deny(
		int $user_id,
		int $token_id,
		string $ability_name,
		mixed $input,
		string $client_ip,
		string $code,
		string $reason,
		array $req_ctx = []
	): array {
		$execution_ms = isset( $req_ctx['start_ms'] )
			? \max( 0, (int) \round( \microtime( true ) * 1000 ) - $req_ctx['start_ms'] )
			: null;

		$this->audit->log( [
			'user_id'        => $user_id,
			'token_id'       => $token_id,
			'ip_address'     => $client_ip,
			'ability_name'   => $ability_name,
			'input'          => $input,
			'decision'       => 'denied',
			'denial_reason'  => "[{$code}] {$reason}",
			'user_agent'     => $req_ctx['user_agent'] ?? '',
			'execution_ms'   => $execution_ms,
			'screen_context' => $req_ctx['screen_context'] ?? '',
		] );

		return [
			'allowed'       => false,
			'user_id'       => $user_id,
			'token_id'      => $token_id,
			'scope'         => '',
			'denial_reason' => $reason,
		];
	}

	/**
	 * Checks whether the given user has the required WordPress capability.
	 *
	 * Switches to the user's context if the current user is different,
	 * then restores the original context. This ensures capability checks
	 * are always performed against the token's owner, never the HTTP session.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param string $capability WordPress capability slug.
	 * @return bool True if the user has the capability.
	 */
	private function check_wp_capability( int $user_id, string $capability ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		$current  = \get_current_user_id();
		$switched = $current !== $user_id;

		if ( $switched ) {
			\wp_set_current_user( $user_id );
		}

		try {
			$has = \current_user_can( $capability );
		} finally {
			// Always restore original user, even if current_user_can() throws.
			if ( $switched ) {
				\wp_set_current_user( $current );
			}
		}

		return $has;
	}

	/**
	 * Checks whether the client IP is in the token's allowlist.
	 *
	 * @param string      $client_ip   Client IP address.
	 * @param string|null $allowed_ips Comma-separated allowlist, or null for unrestricted.
	 * @return bool True if allowed.
	 */
	private function check_ip_allowlist( string $client_ip, ?string $allowed_ips ): bool {
		if ( null === $allowed_ips || '' === $allowed_ips ) {
			return true;
		}

		$list = \array_map( 'trim', \explode( ',', $allowed_ips ) );

		return \in_array( $client_ip, $list, true );
	}
}
