<?php
/**
 * MCP Server
 *
 * Registers the WordPress REST endpoint and dispatches inbound JSON-RPC 2.0
 * requests to the appropriate MCP abilities.
 *
 * Endpoint:  POST /wp-json/g2rd/mcp/v1
 * Auth:      Bearer token in Authorization header (validated by McpSecurityGate).
 *            permission_callback is always __return_true — WP session is never used.
 *
 * JSON-RPC methods handled:
 *   initialize            — unauthenticated capability handshake
 *   notifications/*       — unauthenticated notifications (202, no response)
 *   tools/list            — requires read_only scope
 *   tools/call            — dispatches to registered MCP abilities;
 *                           write tools (editor scope) enqueue for admin confirmation
 *
 * Admin confirmation handlers (admin-post.php — requires WP login):
 *   admin_post_g2rd_mcp_confirm — executes a confirmed write operation
 *   admin_post_g2rd_mcp_reject  — rejects a pending write operation
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * JSON-RPC 2.0 dispatcher over WordPress REST API.
 */
class McpServer {

	/** @var string REST namespace for the MCP endpoint. */
	private const REST_NAMESPACE = 'g2rd/mcp/v1';

	/** @var string MCP protocol version this server implements. */
	private const MCP_VERSION = '2024-11-05';

	/** @var string Server name exposed in initialize response. */
	private const SERVER_NAME = 'G2RD MCP Server';

	/** @var string Server version exposed in initialize response. */
	private const SERVER_VERSION = '1.12.0';

	/** @var McpSecurityGate Seven-layer security orchestrator. */
	private McpSecurityGate $gate;

	/** @var McpAbilities Tool registry (read-only + write). */
	private McpAbilities $abilities;

	/** @var McpConfirmationQueue|null Write-ability confirmation queue (null in tests). */
	private ?McpConfirmationQueue $queue;

	/**
	 * Accepts optional injected dependencies for testability.
	 * When all are omitted, builds the full production stack.
	 *
	 * @param McpSecurityGate|null      $gate      Security gate.
	 * @param McpAbilities|null         $abilities Tool registry.
	 * @param McpConfirmationQueue|null $queue     Confirmation queue.
	 */
	public function __construct(
		?McpSecurityGate $gate = null,
		?McpAbilities $abilities = null,
		?McpConfirmationQueue $queue = null
	) {
		if ( null === $gate ) {
			$crypto       = new McpEncryption();
			$limiter      = new McpRateLimiter( $crypto );
			$audit        = new McpAuditLog( $crypto );
			$tokens       = new McpTokenManager( $crypto );
			$this->gate   = new McpSecurityGate( $tokens, $limiter, $audit );
			$this->queue  = $queue ?? new McpConfirmationQueue( $crypto, $audit );
		} else {
			$this->gate  = $gate;
			$this->queue = $queue;
		}

		$this->abilities = $abilities ?? new McpAbilities( $this->queue );
	}

	// ── WordPress hooks ───────────────────────────────────────────────────────

	/**
	 * Registers REST and admin-post hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'rest_api_init', [ $this, 'register_route' ] );
		\add_action( 'admin_post_g2rd_mcp_confirm', [ $this, 'handle_admin_confirm' ] );
		\add_action( 'admin_post_g2rd_mcp_reject', [ $this, 'handle_admin_reject' ] );
	}

	/**
	 * Registers the MCP REST route.
	 * Called by rest_api_init.
	 *
	 * @return void
	 */
	public function register_route(): void {
		\register_rest_route(
			self::REST_NAMESPACE,
			'/',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	// ── Request handler ───────────────────────────────────────────────────────

	/**
	 * Main entry point — validates JSON-RPC structure and dispatches.
	 *
	 * Always returns HTTP 200 (JSON-RPC convention), except notifications
	 * which return 202 (no body required per JSON-RPC 2.0 spec).
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response JSON-RPC 2.0 response.
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! $this->is_valid_jsonrpc( $body ) ) {
			return $this->rpc_error( null, -32600, 'Invalid Request' );
		}

		$method = (string) $body['method'];
		$params = $body['params'] ?? [];

		// Notifications have no 'id' — return 202, no response body required.
		if ( ! \array_key_exists( 'id', $body ) ) {
			return new \WP_REST_Response( null, 202 );
		}

		$id = $body['id'];

		// initialize — unauthenticated capability handshake.
		if ( 'initialize' === $method ) {
			return $this->handle_initialize( $id );
		}

		// All other methods require a Bearer token.
		$raw_token = $this->extract_bearer_token( $request );
		$client_ip = $this->extract_client_ip();
		$req_ctx   = $this->build_request_context( $request );

		if ( '' === $raw_token ) {
			return $this->rpc_error( $id, -32001, 'Missing Authorization header' );
		}

		switch ( $method ) {
			case 'tools/list':
				return $this->handle_tools_list( $id, $params, $raw_token, $client_ip, $req_ctx );
			case 'tools/call':
				return $this->handle_tools_call( $id, $params, $raw_token, $client_ip, $req_ctx );
			default:
				return $this->rpc_error( $id, -32601, 'Method not found' );
		}
	}

	// ── Admin confirmation handlers ───────────────────────────────────────────

	/**
	 * Processes an administrator confirm click from the email link.
	 *
	 * Hooked to admin_post_g2rd_mcp_confirm (requires WP login via admin-post.php).
	 * Token entropy (256 bits) is the primary authentication; WP session is the gate.
	 *
	 * @return void
	 */
	public function handle_admin_confirm(): void {
		$this->handle_write_confirmation( 'confirm' );
	}

	/**
	 * Processes an administrator reject click from the email link.
	 *
	 * Hooked to admin_post_g2rd_mcp_reject (requires WP login via admin-post.php).
	 *
	 * @return void
	 */
	public function handle_admin_reject(): void {
		$this->handle_write_confirmation( 'reject' );
	}

	// ── MCP method handlers ───────────────────────────────────────────────────

	/**
	 * Handles the MCP initialize handshake (no auth required).
	 *
	 * @param mixed $id JSON-RPC request ID.
	 * @return \WP_REST_Response
	 */
	private function handle_initialize( mixed $id ): \WP_REST_Response {
		return $this->rpc_success( $id, [
			'protocolVersion' => self::MCP_VERSION,
			'capabilities'    => [
				'tools' => [ 'listChanged' => false ],
			],
			'serverInfo'      => [
				'name'    => self::SERVER_NAME,
				'version' => self::SERVER_VERSION,
			],
		] );
	}

	/**
	 * Handles tools/list — returns all registered MCP tools.
	 *
	 * @param mixed                $id        JSON-RPC request ID.
	 * @param mixed                $params    Request params.
	 * @param string               $raw_token Bearer token.
	 * @param string               $client_ip Client IP address.
	 * @param array<string, mixed> $req_ctx   Request context (user_agent, screen_context, start_ms).
	 * @return \WP_REST_Response
	 */
	private function handle_tools_list( mixed $id, mixed $params, string $raw_token, string $client_ip, array $req_ctx = [] ): \WP_REST_Response {
		$gate_result = $this->gate->authorize(
			$raw_token,
			'read_only',
			'read',
			'mcp/tools-list',
			$params,
			$client_ip,
			$req_ctx
		);

		if ( ! $gate_result['allowed'] ) {
			return $this->auth_error( $id, $gate_result );
		}

		return $this->rpc_success( $id, [
			'tools' => $this->abilities->list_tools(),
		] );
	}

	/**
	 * Handles tools/call — dispatches to a registered ability.
	 *
	 * Injects client_ip into the gate_result so write abilities can pass it
	 * to the confirmation queue's enqueue() method.
	 *
	 * @param mixed                $id        JSON-RPC request ID.
	 * @param mixed                $params    Request params (must include 'name').
	 * @param string               $raw_token Bearer token.
	 * @param string               $client_ip Client IP address.
	 * @param array<string, mixed> $req_ctx   Request context (user_agent, screen_context, start_ms).
	 * @return \WP_REST_Response
	 */
	private function handle_tools_call( mixed $id, mixed $params, string $raw_token, string $client_ip, array $req_ctx = [] ): \WP_REST_Response {
		$tool_name = \is_array( $params ) ? (string) ( $params['name'] ?? '' ) : '';
		$arguments = \is_array( $params ) ? ( $params['arguments'] ?? [] ) : [];

		if ( '' === $tool_name ) {
			return $this->rpc_error( $id, -32602, 'Invalid params: missing tool name' );
		}

		$ability = $this->abilities->get( $tool_name );
		if ( null === $ability ) {
			return $this->rpc_error( $id, -32601, "Unknown tool: {$tool_name}" );
		}

		$gate_result = $this->gate->authorize(
			$raw_token,
			$ability['required_scope'],
			$ability['wp_capability'],
			$tool_name,
			$arguments,
			$client_ip,
			$req_ctx
		);

		if ( ! $gate_result['allowed'] ) {
			return $this->auth_error( $id, $gate_result );
		}

		// Expose client IP to write abilities for queue enqueue().
		$gate_result['client_ip'] = $client_ip;

		$tool_result = $this->abilities->call( $tool_name, $arguments, $gate_result );

		if ( isset( $tool_result['isError'] ) && true === $tool_result['isError'] ) {
			return $this->rpc_error( $id, -32603, (string) ( $tool_result['content'][0]['text'] ?? 'Internal error' ) );
		}

		return $this->rpc_success( $id, $tool_result );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Shared logic for confirm/reject admin-post handlers.
	 *
	 * Validates token format, delegates to queue, and calls wp_die() with
	 * a user-friendly message. The queue enforces single-use and TTL.
	 *
	 * @param string $action 'confirm' or 'reject'.
	 * @return void
	 */
	private function handle_write_confirmation( string $action ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- token (256-bit entropy) is the auth; WP session provides CSRF protection via admin-post.php
		$token = \sanitize_text_field( \wp_unslash( (string) ( $_GET['token'] ?? '' ) ) );

		if ( null === $this->queue || ! \preg_match( '/^[0-9a-f]{64}$/', $token ) ) {
			\wp_die(
				\esc_html__( 'Lien invalide ou malformé.', 'g2rd' ),
				\esc_html__( 'MCP — Erreur', 'g2rd' ),
				[ 'response' => 400, 'back_link' => true ]
			);
		}

		if ( 'confirm' === $action ) {
			$success = $this->queue->confirm( $token );

			if ( $success ) {
				\wp_die(
					\esc_html__( 'Opération MCP confirmée et exécutée avec succès.', 'g2rd' ),
					\esc_html__( 'MCP — Confirmation', 'g2rd' ),
					[ 'response' => 200, 'back_link' => true ]
				);
			}

			\wp_die(
				\esc_html__( 'Lien invalide, expiré ou déjà utilisé. Aucune modification effectuée.', 'g2rd' ),
				\esc_html__( 'MCP — Erreur de confirmation', 'g2rd' ),
				[ 'response' => 403, 'back_link' => true ]
			);
		}

		$success = $this->queue->reject( $token );

		if ( $success ) {
			\wp_die(
				\esc_html__( 'Opération MCP refusée. Aucune modification effectuée.', 'g2rd' ),
				\esc_html__( 'MCP — Refus', 'g2rd' ),
				[ 'response' => 200, 'back_link' => true ]
			);
		}

		\wp_die(
			\esc_html__( 'Lien invalide, expiré ou déjà utilisé.', 'g2rd' ),
			\esc_html__( 'MCP — Erreur de refus', 'g2rd' ),
			[ 'response' => 403, 'back_link' => true ]
		);
	}

	/**
	 * Returns true for a structurally valid JSON-RPC 2.0 request.
	 *
	 * Note: batch requests (array body) are intentionally unsupported.
	 *
	 * @param mixed $body Decoded request body.
	 * @return bool
	 */
	private function is_valid_jsonrpc( mixed $body ): bool {
		return \is_array( $body )
			&& isset( $body['jsonrpc'], $body['method'] )
			&& '2.0' === $body['jsonrpc']
			&& \is_string( $body['method'] )
			&& '' !== $body['method'];
	}

	/**
	 * Extracts the raw Bearer token from the Authorization header.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return string Token value, or empty string if absent/malformed.
	 */
	private function extract_bearer_token( \WP_REST_Request $request ): string {
		$auth = (string) ( $request->get_header( 'authorization' ) ?? '' );

		if ( ! \str_starts_with( $auth, 'Bearer ' ) ) {
			return '';
		}

		return \trim( \substr( $auth, 7 ) );
	}

	/**
	 * Builds the request context array from HTTP headers for audit log enrichment.
	 *
	 * Captures User-Agent, the X-G2RD-Screen header (injected by McpJsBridge on
	 * admin pages), and the request start timestamp so the security gate can
	 * store user_agent, screen_context, and execution_ms in every audit entry.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array{start_ms: int, screen_context: string, user_agent: string}
	 */
	private function build_request_context( \WP_REST_Request $request ): array {
		return [
			'start_ms'       => (int) \round( \microtime( true ) * 1000 ),
			'screen_context' => \sanitize_text_field( \substr( (string) ( $request->get_header( 'x-g2rd-screen' ) ?? '' ), 0, 500 ) ),
			'user_agent'     => \sanitize_text_field( \substr( (string) ( $request->get_header( 'user-agent' ) ?? '' ), 0, 255 ) ),
		];
	}

	/**
	 * Returns the real client IP address.
	 *
	 * Uses REMOTE_ADDR by default. Reads X-Forwarded-For only when the constant
	 * G2RD_MCP_TRUSTED_PROXIES is defined as an array in wp-config.php AND
	 * REMOTE_ADDR is present in that list (i.e., we are behind a known proxy).
	 *
	 * Example wp-config.php entry:
	 *   define( 'G2RD_MCP_TRUSTED_PROXIES', [ '10.0.0.1', '10.0.0.2' ] );
	 *
	 * @return string Client IP address string.
	 */
	private function extract_client_ip(): string {
		$remote_addr = \sanitize_text_field( \wp_unslash( (string) ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- ?? operator provides default

		if (
			! \defined( 'G2RD_MCP_TRUSTED_PROXIES' )
			|| ! \is_array( \G2RD_MCP_TRUSTED_PROXIES )
		) {
			return $remote_addr;
		}

		// Only trust X-Forwarded-For if REMOTE_ADDR is an explicitly declared proxy.
		if ( ! \in_array( $remote_addr, \G2RD_MCP_TRUSTED_PROXIES, true ) ) {
			return $remote_addr;
		}

		$forwarded = \sanitize_text_field( \wp_unslash( (string) ( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- ?? operator provides default

		if ( '' === $forwarded ) {
			return $remote_addr;
		}

		// X-Forwarded-For: client, proxy1, proxy2 — leftmost IP is the real client.
		$parts  = \explode( ',', $forwarded );
		$client = \trim( $parts[0] );

		return '' !== $client ? $client : $remote_addr;
	}

	/**
	 * Returns a JSON-RPC 2.0 success response (HTTP 200).
	 *
	 * @param mixed $id     Request ID.
	 * @param mixed $result Result payload.
	 * @return \WP_REST_Response
	 */
	private function rpc_success( mixed $id, mixed $result ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'jsonrpc' => '2.0',
				'result'  => $result,
				'id'      => $id,
			],
			200
		);
	}

	/**
	 * Returns a JSON-RPC 2.0 error response (HTTP 200 — JSON-RPC convention).
	 *
	 * @param mixed  $id      Request ID (null for parse/invalid-request errors).
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Human-readable message.
	 * @return \WP_REST_Response
	 */
	private function rpc_error( mixed $id, int $code, string $message ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'jsonrpc' => '2.0',
				'error'   => [
					'code'    => $code,
					'message' => $message,
				],
				'id'      => $id,
			],
			200
		);
	}

	/**
	 * Maps a SecurityGate denial result to a JSON-RPC -32001 error.
	 *
	 * @param mixed                $id          Request ID.
	 * @param array<string, mixed> $gate_result Denial result from McpSecurityGate.
	 * @return \WP_REST_Response
	 */
	private function auth_error( mixed $id, array $gate_result ): \WP_REST_Response {
		return $this->rpc_error(
			$id,
			-32001,
			$gate_result['denial_reason'] ?: 'Authorization denied'
		);
	}
}
