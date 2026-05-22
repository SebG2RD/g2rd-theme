<?php
/**
 * MCP Admin API — SP-4 REST endpoints for the admin options page
 *
 * Provides read/write access to MCP tokens, the audit log, and the confirmation
 * queue for the React admin UI (g2rd-options-page).
 *
 * All endpoints are under the existing g2rd/v1 namespace and require
 * manage_options capability (WordPress administrators only).
 *
 * Endpoints:
 *   GET    /mcp-tokens             — list current user's non-revoked tokens
 *   POST   /mcp-tokens             — create a token (raw value returned ONCE)
 *   DELETE /mcp-tokens/{id}        — revoke a token (own tokens only)
 *   GET    /mcp-audit              — paginated audit log with optional filters
 *   GET    /mcp-queue              — paginated pending confirmation entries
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * REST endpoints for the MCP admin UI.
 */
class McpAdminApi {

	/** @var string REST namespace shared with ThemeOptions. */
	private const REST_NAMESPACE = 'g2rd/v1';

	/** @var McpTokenManager Token CRUD service. */
	private McpTokenManager $tokens;

	/** @var McpAuditLog Audit log query service. */
	private McpAuditLog $audit;

	/** @var McpConfirmationQueue Confirmation queue service. */
	private McpConfirmationQueue $queue;

	/** @var McpAnomalyDetector Behavioral anomaly detector. */
	private McpAnomalyDetector $anomalies;

	/**
	 * Builds the full MCP stack for admin operations.
	 */
	public function __construct() {
		$crypto          = new McpEncryption();
		$audit           = new McpAuditLog( $crypto );
		$this->audit     = $audit;
		$this->tokens    = new McpTokenManager( $crypto, $audit );
		$this->queue     = new McpConfirmationQueue( $crypto, $audit );
		$this->anomalies = new McpAnomalyDetector();
	}

	// ── WordPress hooks ───────────────────────────────────────────────────────

	/**
	 * Registers REST API hook.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers all MCP admin REST routes.
	 * Called by rest_api_init.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$admin_perm = static fn() => \current_user_can( 'manage_options' );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-tokens', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tokens' ],
				'permission_callback' => $admin_perm,
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_token' ],
				'permission_callback' => $admin_perm,
				'args'                => [
					'name'  => [
						'required' => true,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'minLength' => 1,
						'maxLength' => 100,
					],
					'scope' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ 'read_only', 'editor' ],
					],
					'expires_in_days' => [
						'required' => false,
						'type'     => 'integer',
						'minimum'  => 1,
						'maximum'  => 365,
						'default'  => 30,
					],
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-tokens/(?P<id>\d+)', [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'revoke_token' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-tokens/(?P<id>\d+)/purge', [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'purge_token' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-audit', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_audit' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'page'     => [
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				],
				'per_page' => [
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
					'default' => 25,
				],
				'decision' => [
					'type' => 'string',
					'enum' => [ 'allowed', 'denied', 'pending', 'rolled_back' ],
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-queue', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_queue' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'page'     => [
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				],
				'per_page' => [
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 50,
					'default' => 20,
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-anomalies', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_anomalies' ],
			'permission_callback' => $admin_perm,
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-queue/(?P<id>\d+)/confirm', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'confirm_queue_entry' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
			],
		] );

		\register_rest_route( self::REST_NAMESPACE, '/mcp-queue/(?P<id>\d+)/reject', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'reject_queue_entry' ],
			'permission_callback' => $admin_perm,
			'args'                => [
				'id' => [
					'required' => true,
					'type'     => 'integer',
					'minimum'  => 1,
				],
			],
		] );
	}

	// ── Endpoint callbacks ────────────────────────────────────────────────────

	/**
	 * GET /mcp-tokens — lists tokens for the current user.
	 *
	 * Pass ?include_inactive=1 to include revoked and expired tokens.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_tokens( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id          = \get_current_user_id();
		$include_inactive = ! empty( $request->get_param( 'include_inactive' ) );
		$tokens           = $this->tokens->list_tokens( $user_id, $include_inactive );

		return new \WP_REST_Response( [
			'tokens' => $tokens,
			'total'  => \count( $tokens ),
		], 200 );
	}

	/**
	 * POST /mcp-tokens — creates a new API token.
	 *
	 * The raw token is returned ONCE in the response and never stored.
	 *
	 * @param \WP_REST_Request $request REST request (name, scope, expires_in_days).
	 * @return \WP_REST_Response
	 */
	public function create_token( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = \get_current_user_id();
		$name    = \sanitize_text_field( (string) $request->get_param( 'name' ) );
		$scope   = (string) $request->get_param( 'scope' );
		$days    = \absint( $request->get_param( 'expires_in_days' ) ?: 30 );

		$result = $this->tokens->create_token( $user_id, $name, $scope, [ 'expires_in_days' => $days ] );

		if ( false === $result ) {
			return new \WP_REST_Response( [
				'code'    => 'token_creation_failed',
				'message' => \__( 'Impossible de créer le token. Vérifiez les paramètres.', 'g2rd' ),
			], 400 );
		}

		return new \WP_REST_Response( [
			'token'      => $result['token'],
			'id'         => $result['id'],
			'expires_at' => $result['expires_at'],
			'scope'      => $result['scope'],
			'message'    => \__( 'Token créé. Copiez-le maintenant — il ne sera plus affiché.', 'g2rd' ),
		], 201 );
	}

	/**
	 * DELETE /mcp-tokens/{id} — revokes a token owned by the current user.
	 *
	 * @param \WP_REST_Request $request REST request (id as route param).
	 * @return \WP_REST_Response
	 */
	public function revoke_token( \WP_REST_Request $request ): \WP_REST_Response {
		$token_id = \absint( $request->get_param( 'id' ) );
		$user_id  = \get_current_user_id();

		$success = $this->tokens->revoke_token( $token_id, $user_id );

		if ( ! $success ) {
			return new \WP_REST_Response( [
				'code'    => 'revoke_failed',
				'message' => \__( 'Token introuvable ou déjà révoqué.', 'g2rd' ),
			], 404 );
		}

		// Suppression immédiate après révocation — le token révoqué ne doit pas rester en base.
		$this->tokens->purge_token( $token_id, $user_id );

		return new \WP_REST_Response( [ 'revoked' => true ], 200 );
	}

	/**
	 * DELETE /mcp-tokens/{id}/purge — permanently removes an inactive token from the database.
	 *
	 * Only revoked or expired tokens can be purged. Active tokens are refused (400).
	 *
	 * @param \WP_REST_Request $request REST request (id as route param).
	 * @return \WP_REST_Response
	 */
	public function purge_token( \WP_REST_Request $request ): \WP_REST_Response {
		$token_id = \absint( $request->get_param( 'id' ) );
		$user_id  = \get_current_user_id();

		$success = $this->tokens->purge_token( $token_id, $user_id );

		if ( ! $success ) {
			return new \WP_REST_Response( [
				'code'    => 'purge_failed',
				'message' => \__( 'Token introuvable, toujours actif, ou suppression non autorisée.', 'g2rd' ),
			], 400 );
		}

		return new \WP_REST_Response( [ 'purged' => true ], 200 );
	}

	/**
	 * GET /mcp-audit — returns a paginated page of audit log entries.
	 *
	 * @param \WP_REST_Request $request REST request (page, per_page, decision).
	 * @return \WP_REST_Response
	 */
	public function get_audit( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = \absint( $request->get_param( 'page' ) ?: 1 );
		$per_page = \absint( $request->get_param( 'per_page' ) ?: 25 );
		$decision = $request->get_param( 'decision' );

		$filters = [];
		if ( ! empty( $decision ) ) {
			$filters['decision'] = \sanitize_key( (string) $decision );
		}

		$result = $this->audit->query( $filters, $page, $per_page );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /mcp-queue — returns pending confirmation entries.
	 *
	 * Prunes expired entries before returning so the list is always fresh.
	 *
	 * @param \WP_REST_Request $request REST request (page, per_page).
	 * @return \WP_REST_Response
	 */
	public function get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = \absint( $request->get_param( 'page' ) ?: 1 );
		$per_page = \absint( $request->get_param( 'per_page' ) ?: 20 );

		$this->queue->prune_expired();

		$result = $this->queue->list_pending( $page, $per_page );

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /mcp-anomalies — returns detected behavioral anomalies in the audit log.
	 *
	 * @param \WP_REST_Request $request REST request (unused — required by WP callback signature).
	 * @return \WP_REST_Response
	 */
	public function get_anomalies( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- required by WP callback signature
		$result = $this->anomalies->detect_with_summary();

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /mcp-queue/{id}/confirm — confirms a pending write operation.
	 *
	 * @param \WP_REST_Request $request REST request (id as route param).
	 * @return \WP_REST_Response
	 */
	public function confirm_queue_entry( \WP_REST_Request $request ): \WP_REST_Response {
		$id = \absint( $request->get_param( 'id' ) );

		if ( ! $this->queue->confirm_by_id( $id ) ) {
			return new \WP_REST_Response( [
				'code'    => 'confirm_failed',
				'message' => \__( 'Entrée introuvable, expirée ou déjà résolue.', 'g2rd' ),
			], 404 );
		}

		return new \WP_REST_Response( [ 'confirmed' => true ], 200 );
	}

	/**
	 * POST /mcp-queue/{id}/reject — rejects a pending write operation.
	 *
	 * @param \WP_REST_Request $request REST request (id as route param).
	 * @return \WP_REST_Response
	 */
	public function reject_queue_entry( \WP_REST_Request $request ): \WP_REST_Response {
		$id = \absint( $request->get_param( 'id' ) );

		if ( ! $this->queue->reject_by_id( $id ) ) {
			return new \WP_REST_Response( [
				'code'    => 'reject_failed',
				'message' => \__( 'Entrée introuvable, expirée ou déjà résolue.', 'g2rd' ),
			], 404 );
		}

		return new \WP_REST_Response( [ 'rejected' => true ], 200 );
	}
}
