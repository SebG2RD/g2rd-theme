<?php
/**
 * Endpoints REST du module IA G2RD
 *
 * Tous les endpoints /g2rd/v1/ai/* sont définis ici.
 * Chaque endpoint applique : nonce REST, droits utilisateur, sanitisation,
 * rate-limit journalier, appel IA, log d'audit.
 *
 * @package    G2RD\AI
 * @since      1.14.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD\AI;

/**
 * Classe AiRest
 */
class AiRest {

	/**
	 * Enregistre toutes les routes REST.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$routes = [
			'/ai/block-action'       => 'handle_block_action',
			'/ai/generate-page'      => 'handle_generate_page',
			'/ai/generate-post'      => 'handle_generate_post',
			'/ai/optimize-content'   => 'handle_optimize_content',
			'/ai/generate-seo'       => 'handle_generate_seo',
			'/ai/generate-social'    => 'handle_generate_social',
			'/ai/suggest-links'      => 'handle_suggest_links',
			'/ai/settings'           => 'handle_settings',
			'/ai/profile'            => 'handle_profile',
		];

		$dual_method_routes = [ '/ai/settings', '/ai/profile' ];

		foreach ( $routes as $route => $method ) {
			$http_method = \in_array( $route, $dual_method_routes, true )
				? [ \WP_REST_Server::READABLE, \WP_REST_Server::CREATABLE ]
				: \WP_REST_Server::CREATABLE;

			\register_rest_route(
				AiModule::REST_NAMESPACE,
				$route,
				[
					'methods'             => $http_method,
					'callback'            => [ $this, $method ],
					'permission_callback' => [ $this, 'check_permission' ],
				]
			);
		}
	}

	// ──────────────────────────────────────────────────────────────────────
	// PERMISSION
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Vérifie les droits d'accès communs à tous les endpoints IA.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission(): bool|\WP_Error {
		if ( ! \current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'ai_forbidden',
				\esc_html__( 'Accès interdit.', 'g2rd' ),
				[ 'status' => 403 ]
			);
		}

		$settings = AiModule::get_settings();
		if ( ! AiModule::current_user_has_ai_access( $settings['ai_allowed_roles'] ) ) {
			return new \WP_Error(
				'ai_role_forbidden',
				\esc_html__( 'Votre rôle ne permet pas d\'accéder au module IA.', 'g2rd' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	// ──────────────────────────────────────────────────────────────────────
	// ENDPOINTS BLOCS
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * POST /g2rd/v1/ai/block-action — Action IA générique pour les blocs Gutenberg.
	 *
	 * @param \WP_REST_Request $request Requête REST.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_block_action( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$action     = \sanitize_key( (string) $request->get_param( 'action' ) );
		$block_type = \sanitize_key( (string) $request->get_param( 'block_type' ) );
		$ctx        = $this->sanitize_context( $request->get_param( 'context' ) );

		if ( empty( $action ) ) {
			return new \WP_Error( 'ai_missing_action', \esc_html__( 'Action manquante.', 'g2rd' ), [ 'status' => 400 ] );
		}

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = $this->get_block_prompt( $action, $block_type, $ctx );
		if ( empty( $prompt ) ) {
			return new \WP_Error( 'ai_unknown_action', \esc_html__( 'Action inconnue.', 'g2rd' ), [ 'status' => 400 ] );
		}

		return $this->call_ai_and_respond( $prompt, $action, $block_type, $request );
	}

	// ──────────────────────────────────────────────────────────────────────
	// ENDPOINTS PAGES & ARTICLES
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * POST /g2rd/v1/ai/generate-page.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_generate_page( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$page_type = \sanitize_key( (string) $request->get_param( 'page_type' ) );
		$ctx       = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = match ( $page_type ) {
			'service' => AiPrompts::page_service( $ctx ),
			'local'   => AiPrompts::page_local( $ctx ),
			default   => AiPrompts::page_service( $ctx ),
		};

		return $this->call_ai_and_respond( $prompt, 'generate-page', $page_type, $request );
	}

	/**
	 * POST /g2rd/v1/ai/generate-post.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_generate_post( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$mode = \sanitize_key( (string) ( $request->get_param( 'mode' ) ?? 'outline' ) );
		$ctx  = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = 'full' === $mode
			? AiPrompts::post_generate( $ctx )
			: AiPrompts::post_outline( $ctx );

		return $this->call_ai_and_respond( $prompt, 'generate-post-' . $mode, 'post', $request );
	}

	/**
	 * POST /g2rd/v1/ai/optimize-content.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_optimize_content( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ctx = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = AiPrompts::seo_generate( $ctx );
		return $this->call_ai_and_respond( $prompt, 'optimize-content', 'content', $request );
	}

	/**
	 * POST /g2rd/v1/ai/generate-seo.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_generate_seo( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ctx = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = AiPrompts::seo_generate( $ctx );
		return $this->call_ai_and_respond( $prompt, 'generate-seo', 'seo', $request );
	}

	/**
	 * POST /g2rd/v1/ai/generate-social.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_generate_social( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ctx = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = AiPrompts::social_generate( $ctx );
		return $this->call_ai_and_respond( $prompt, 'generate-social', 'social', $request );
	}

	/**
	 * POST /g2rd/v1/ai/suggest-links.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_suggest_links( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$ctx = $this->sanitize_context( $request->get_param( 'context' ) );

		$limit_error = $this->check_rate_limit();
		if ( \is_wp_error( $limit_error ) ) {
			return $limit_error;
		}

		$prompt = AiPrompts::suggest_links( $ctx );
		return $this->call_ai_and_respond( $prompt, 'suggest-links', 'links', $request );
	}

	// ──────────────────────────────────────────────────────────────────────
	// ENDPOINT RÉGLAGES
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * GET|POST /g2rd/v1/ai/settings.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_settings( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'ai_settings_forbidden', \esc_html__( 'Accès interdit.', 'g2rd' ), [ 'status' => 403 ] );
		}

		if ( \WP_REST_Server::CREATABLE === $request->get_method() ) {
			$new_settings = $request->get_json_params();
			if ( ! \is_array( $new_settings ) ) {
				return new \WP_Error( 'ai_settings_invalid', \esc_html__( 'Données invalides.', 'g2rd' ), [ 'status' => 400 ] );
			}
			// Fusionne avec l'option existante pour préserver api_key si non renvoyée.
			$existing = \get_option( AiModule::OPTION_KEY, [] );
			$existing = \is_array( $existing ) ? $existing : [];
			\update_option( AiModule::OPTION_KEY, \array_merge( $existing, $this->sanitize_settings( $new_settings ) ) );
		}

		// Ne jamais exposer la clé brute — retourner seulement api_key_set + api_key_preview.
		$settings = AiModule::get_settings();
		$api_key  = $settings['api_key'] ?? '';
		unset( $settings['api_key'] );
		$settings['api_key_set']     = ! empty( $api_key );
		$settings['api_key_preview'] = ! empty( $api_key ) ? '••••' . \substr( $api_key, -4 ) : '';

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * GET|POST /g2rd/v1/ai/profile.
	 *
	 * GET : lecture du profil du site (utilisé pour pré-remplir les générations).
	 * POST : sauvegarde du profil (admin uniquement). Source unique du contexte —
	 * remplace les champs activité/ville/ton re-saisis dans chaque bloc.
	 *
	 * @param \WP_REST_Request $request Requête.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_profile( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		// Lit le profil depuis le body JSON (get_param) avec repli sur get_json_params.
		$incoming = $request->get_param( 'profile' );
		if ( null === $incoming ) {
			$json     = $request->get_json_params();
			$incoming = \is_array( $json ) ? ( $json['profile'] ?? null ) : null;
		}

		// Toute requête qui fournit « profile » est une écriture — indépendamment de
		// la détection de méthode (plus robuste que comparer get_method()).
		if ( null !== $incoming ) {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return new \WP_Error( 'ai_profile_forbidden', \esc_html__( 'Accès interdit.', 'g2rd' ), [ 'status' => 403 ] );
			}
			if ( ! \is_array( $incoming ) ) {
				return new \WP_Error( 'ai_profile_invalid', \esc_html__( 'Données invalides.', 'g2rd' ), [ 'status' => 400 ] );
			}

			$clean = [
				'activity' => \sanitize_text_field( (string) ( $incoming['activity'] ?? '' ) ),
				'city'     => \sanitize_text_field( (string) ( $incoming['city'] ?? '' ) ),
				'target'   => \sanitize_text_field( (string) ( $incoming['target'] ?? '' ) ),
				'tone'     => \sanitize_key( (string) ( $incoming['tone'] ?? 'professionnel' ) ),
			];
			\update_option( AiModule::PROFILE_KEY, $clean, false );

			// Garde-fou : si l'option ne persiste pas (blocage plugin de sécu / DB),
			// le signaler dans les logs pour diagnostic (ne change pas la réponse).
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$persisted = \get_option( AiModule::PROFILE_KEY, [] );
				if ( $persisted !== $clean ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- diagnostic conditionné à WP_DEBUG
					\error_log( 'G2RD AiRest : profil NON persisté après update_option — lu=' . \wp_json_encode( $persisted ) );
				}
			}
		}

		// Anti-cache : empêche un cache REST de servir un profil périmé au rechargement.
		$response = new \WP_REST_Response( [ 'profile' => AiModule::get_profile() ], 200 );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		return $response;
	}

	// ──────────────────────────────────────────────────────────────────────
	// HELPERS PRIVÉS
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Résout le prompt selon le couple action/block_type.
	 *
	 * @param string               $action     Action demandée.
	 * @param string               $block_type Type de bloc.
	 * @param array<string,string> $ctx        Contexte.
	 * @return string Prompt ou chaîne vide si inconnu.
	 */
	private function get_block_prompt( string $action, string $block_type, array $ctx ): string {
		$map = [
			'hero-heading'      => [ AiPrompts::class, 'hero_heading' ],
			'hero-subheading'   => [ AiPrompts::class, 'hero_subheading' ],
			'hero-cta'          => [ AiPrompts::class, 'hero_cta' ],
			'hero-rewrite'      => [ AiPrompts::class, 'hero_rewrite' ],
			'hero-seo-local'    => [ AiPrompts::class, 'hero_seo_local' ],
			'faq-generate'      => [ AiPrompts::class, 'faq_generate' ],
			'cta-texts'         => [ AiPrompts::class, 'cta_band_texts' ],
			'pricing-benefits'  => [ AiPrompts::class, 'pricing_benefits' ],
			'testimonial'       => [ AiPrompts::class, 'testimonial_improve' ],
			'image-alt'         => [ AiPrompts::class, 'image_alt_texts' ],
		];

		if ( ! isset( $map[ $action ] ) ) {
			return '';
		}

		return call_user_func( $map[ $action ], $ctx ); // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FunctionHandlingFunctions.WarnFunctionHandling -- callable est une liste blanche statique d'AiPrompts, aucune entrée utilisateur
	}

	/**
	 * Vérifie la limite journalière de l'utilisateur courant.
	 *
	 * @return true|\WP_Error True si autorisé.
	 */
	private function check_rate_limit(): true|\WP_Error {
		$settings = AiModule::get_settings();
		$user_id  = \get_current_user_id();

		if ( AiModule::is_daily_limit_reached( $user_id, (int) $settings['ai_daily_limit'] ) ) {
			return new \WP_Error(
				'ai_rate_limit',
				\esc_html__( 'Limite journalière de générations IA atteinte.', 'g2rd' ),
				[ 'status' => 429 ]
			);
		}

		return true;
	}

	/**
	 * Appelle l'IA, logge le résultat et retourne la réponse REST.
	 *
	 * @param string           $prompt     Prompt à envoyer.
	 * @param string           $action     Identifiant de l'action (pour les logs).
	 * @param string           $context    Contexte de l'action (bloc ou page).
	 * @param \WP_REST_Request $request    Requête REST.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function call_ai_and_respond(
		string $prompt,
		string $action,
		string $context,
		\WP_REST_Request $request
	): \WP_REST_Response|\WP_Error {
		$settings     = AiModule::get_settings();
		$custom_instr = \trim( (string) ( $settings['ai_custom_instructions'] ?? '' ) );
		if ( ! empty( $custom_instr ) ) {
			$prompt = "Contexte du site web : {$custom_instr}\n\n" . $prompt;
		}

		$client   = new AiClient();
		$start_ms = \intval( \microtime( true ) * 1000 );

		$result = $client->complete( $prompt );

		$exec_ms = \intval( \microtime( true ) * 1000 ) - $start_ms;
		$post_id = \absint( $request->get_param( 'post_id' ) ?? 0 );

		$this->log_action( $action, $context, $post_id, $exec_ms, $result );

		if ( \is_wp_error( $result ) ) {
			return $result;
		}

		// Tentative de décodage JSON si la réponse semble structurée.
		$decoded = \json_decode( $result, true );
		$payload = ( \JSON_ERROR_NONE === \json_last_error() && \is_array( $decoded ) )
			? $decoded
			: $result;

		return new \WP_REST_Response( [ 'result' => $payload ], 200 );
	}

	/**
	 * Enregistre l'action IA dans McpAuditLog.
	 *
	 * @param string         $action    Action.
	 * @param string         $context   Contexte (bloc, type de page…).
	 * @param int            $post_id   ID de la page/article courant.
	 * @param int            $exec_ms   Durée d'exécution.
	 * @param string|\WP_Error $result  Résultat ou erreur.
	 * @return void
	 */
	private function log_action(
		string $action,
		string $context,
		int $post_id,
		int $exec_ms,
		string|\WP_Error $result
	): void {
		$settings = AiModule::get_settings();
		if ( ! $settings['ai_logs_enabled'] ) {
			return;
		}

		if ( ! \class_exists( '\G2RD\McpAuditLog' ) ) {
			return;
		}

		( new \G2RD\McpAuditLog( new \G2RD\McpEncryption() ) )->log( [
			'user_id'       => \get_current_user_id(),
			'token_id'      => 0,
			'ip_address'    => (string) ( \filter_var( \sanitize_text_field( \wp_unslash( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) ), \FILTER_VALIDATE_IP ) ?: 'unknown' ),
			'ability_name'  => 'g2rd/ai/' . $action,
			'input'         => [ 'action' => $action, 'context' => $context, 'post_id' => $post_id ],
			'decision'      => \is_wp_error( $result ) ? 'denied' : 'allowed',
			'execution_ms'  => $exec_ms,
			'denial_reason' => \is_wp_error( $result ) ? $result->get_error_message() : '',
		] );
	}

	/**
	 * Sanitise le contexte reçu depuis le frontend.
	 *
	 * @param mixed $raw Données brutes.
	 * @return array<string, string>
	 */
	private function sanitize_context( mixed $raw ): array {
		$clean = [];

		if ( \is_array( $raw ) ) {
			$allowed_keys = [
				'activity', 'city', 'service', 'target', 'tone', 'language',
				'length', 'existing_content', 'keywords', 'objective', 'zone',
				'pages_list', 'post_type',
			];

			foreach ( $allowed_keys as $key ) {
				if ( isset( $raw[ $key ] ) ) {
					$clean[ $key ] = \sanitize_textarea_field( (string) $raw[ $key ] );
				}
			}
		}

		// Profil du site : réinjecté comme valeurs par défaut quand le frontend ne
		// les fournit pas. Source unique du contexte → fini la re-saisie par bloc.
		// Une valeur explicite venant de la requête a toujours priorité.
		$profile = AiModule::get_profile();
		foreach ( [ 'activity', 'city', 'target', 'tone' ] as $key ) {
			if ( '' !== (string) $profile[ $key ] && empty( $clean[ $key ] ) ) {
				$clean[ $key ] = $profile[ $key ];
			}
		}

		return $clean;
	}

	/**
	 * Sanitise les réglages IA avant sauvegarde.
	 *
	 * @param array<string, mixed> $raw Réglages bruts.
	 * @return array<string, mixed>
	 */
	private function sanitize_settings( array $raw ): array {
		$bools = [ 'ai_blocks_enabled', 'ai_editor_enabled', 'ai_woo_enabled', 'ai_logs_enabled' ];
		$clean = [];

		foreach ( $bools as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$clean[ $key ] = (bool) $raw[ $key ];
			}
		}

		if ( isset( $raw['ai_daily_limit'] ) ) {
			$clean['ai_daily_limit'] = max( 1, min( 500, \absint( $raw['ai_daily_limit'] ) ) );
		}

		if ( isset( $raw['ai_default_tone'] ) ) {
			$clean['ai_default_tone'] = \sanitize_key( (string) $raw['ai_default_tone'] );
		}

		if ( isset( $raw['ai_default_length'] ) ) {
			$clean['ai_default_length'] = \sanitize_key( (string) $raw['ai_default_length'] );
		}

		if ( isset( $raw['ai_allowed_roles'] ) && \is_array( $raw['ai_allowed_roles'] ) ) {
			$clean['ai_allowed_roles'] = \array_map( 'sanitize_key', $raw['ai_allowed_roles'] );
		}

		if ( isset( $raw['ai_custom_instructions'] ) ) {
			$clean['ai_custom_instructions'] = \sanitize_textarea_field( (string) $raw['ai_custom_instructions'] );
		}

		// api_key : sauvegarder uniquement si une nouvelle valeur est fournie.
		if ( ! empty( $raw['api_key'] ) ) {
			$clean['api_key'] = \sanitize_text_field( (string) $raw['api_key'] );
		}

		return $clean;
	}
}
