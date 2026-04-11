<?php
/**
 * Connecteur API G2RD
 *
 * Enregistre un endpoint REST WordPress qui sert de proxy sécurisé pour le
 * bloc « G2RD Block API » en mode connecteur côté serveur.
 * Localise également les données nécessaires au script frontend (nonce, URL REST).
 *
 * @package G2RD
 */

namespace G2RD;

/**
 * Gère le proxy REST et la localisation du script frontend.
 */
class ApiConnector {

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		\add_action( 'rest_api_init',   [ $this, 'registerRestRoute' ] );
		\add_action( 'wp_enqueue_scripts', [ $this, 'localizeViewScript' ], 20 );
	}

	// -------------------------------------------------------------------------
	// Endpoint REST proxy
	// -------------------------------------------------------------------------

	/**
	 * Déclare la route REST /wp-json/g2rd/v1/api-proxy.
	 */
	public function registerRestRoute(): void {
		\register_rest_route(
			'g2rd/v1',
			'/api-proxy',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handleProxyRequest' ],
				'permission_callback' => [ $this, 'checkPermission' ],
				'args'                => [
					'url'     => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => static function ( $value ) {
							return \filter_var( $value, FILTER_VALIDATE_URL ) !== false;
						},
					],
					'method'  => [
						'type'              => 'string',
						'default'           => 'GET',
						'sanitize_callback' => static function ( $value ) {
							return \strtoupper( \sanitize_text_field( $value ) );
						},
						'validate_callback' => static function ( $value ) {
							return \in_array(
								\strtoupper( $value ),
								[ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ],
								true
							);
						},
					],
					'headers' => [
						'type'    => 'array',
						'default' => [],
					],
					'body'    => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'wp_kses_no_null',
					],
				],
			]
		);
	}

	/**
	 * Vérifie que l'utilisateur a la capacité requise pour utiliser le proxy serveur.
	 *
	 * `edit_posts` est la capacité minimale des contributeurs/rédacteurs —
	 * suffisante pour utiliser les blocs de contenu, mais trop élevée pour un
	 * simple abonné/client, ce qui empêche l'utilisation détournée du proxy.
	 *
	 * @return bool|\WP_Error
	 */
	public function checkPermission(): bool|\WP_Error {
		if ( ! \current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				\esc_html__( "Vous n'avez pas les droits suffisants pour utiliser le proxy API serveur.", 'g2rd' ),
				[ 'status' => \rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Vérifie qu'une URL ne cible pas un réseau privé ou une ressource interne
	 * (protection contre les attaques SSRF).
	 *
	 * Bloque : localhost, ::1, 127.x.x.x, plages RFC 1918 (10/8, 172.16/12, 192.168/16),
	 * link-local (169.254/16), loopback IPv6, et les URLs sans hôte résolvable.
	 *
	 * @param string $url URL à valider.
	 * @return bool True si l'URL est autorisée, false sinon.
	 */
	private function isUrlAllowed( string $url ): bool {
		$parsed = \wp_parse_url( $url );
		$host   = $parsed['host'] ?? '';

		if ( '' === $host ) {
			return false;
		}

		// Refuser les noms d'hôte locaux courants.
		$blocked_hostnames = [ 'localhost', 'ip6-localhost', 'ip6-loopback' ];
		if ( \in_array( \strtolower( $host ), $blocked_hostnames, true ) ) {
			return false;
		}

		// Résoudre le nom d'hôte en IP pour détecter les alias de localhost.
		$ip = \filter_var( $host, FILTER_VALIDATE_IP ) ? $host : \gethostbyname( $host );

		if ( ! \filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			// Impossible de résoudre — refuser par précaution.
			return false;
		}

		// Refuser les IPs privées, réservées et de loopback (RFC 1918, RFC 5735, RFC 4193).
		if ( false === \filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return false;
		}

		// Bloquer explicitement le loopback IPv4 (127.0.0.0/8) et link-local (169.254.0.0/16).
		if ( 0 === \strncmp( $ip, '127.', 4 ) || 0 === \strncmp( $ip, '169.254.', 8 ) ) {
			return false;
		}

		// Bloquer le loopback IPv6.
		if ( '::1' === $ip ) {
			return false;
		}

		return true;
	}

	/**
	 * Exécute la requête vers l'API distante et retourne la réponse.
	 *
	 * @param \WP_REST_Request $request Requête REST entrante.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handleProxyRequest( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$url    = $request->get_param( 'url' );
		$method = $request->get_param( 'method' );
		$body   = $request->get_param( 'body' );

		// Vérification anti-SSRF : bloquer les URLs pointant vers des ressources internes.
		if ( ! $this->isUrlAllowed( $url ) ) {
			return new \WP_Error(
				'proxy_url_forbidden',
				\esc_html__( "Cette URL n'est pas autorisée par le proxy.", 'g2rd' ),
				[ 'status' => 403 ]
			);
		}

		// Construire les en-têtes personnalisés.
		$headers     = [];
		$raw_headers = $request->get_param( 'headers' );
		if ( \is_array( $raw_headers ) ) {
			foreach ( $raw_headers as $header ) {
				if ( ! empty( $header['key'] ) ) {
					// RFC 7230 : les noms d'en-têtes HTTP autorisent lettres, chiffres, tiret et underscore.
					// sanitize_key() est trop restrictif (minuscules uniquement, retire les majuscules
					// et les tirets après nettoyage) — on utilise une regex ciblée à la place.
					$key             = \strtolower( \trim( \preg_replace( '/[^a-zA-Z0-9\-_]/', '', $header['key'] ) ) );
					$value           = \trim( \wp_kses_no_null( $header['value'] ?? '' ) );
					$headers[ $key ] = $value;
				}
			}
		}

		// Arguments pour wp_remote_request.
		$args = [
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		];

		if ( $body && ! \in_array( $method, [ 'GET', 'HEAD' ], true ) ) {
			$args['body'] = $body;
			if ( ! isset( $headers['content-type'] ) ) {
				$args['headers']['Content-Type'] = 'application/json';
			}
		}

		$response = \wp_remote_request( $url, $args );

		if ( \is_wp_error( $response ) ) {
			return new \WP_Error(
				'proxy_request_failed',
				$response->get_error_message(),
				[ 'status' => 502 ]
			);
		}

		$status_code  = (int) \wp_remote_retrieve_response_code( $response );
		$body_content = \wp_remote_retrieve_body( $response );
		$decoded      = \json_decode( $body_content, true );

		// Transmettre le code HTTP upstream dans le wrapper ET comme statut REST,
		// afin que le JS côté client puisse détecter les erreurs API (4xx, 5xx).
		// Les codes < 200 ou invalides sont ramenés à 502.
		$rest_status = ( $status_code >= 200 && $status_code < 600 ) ? $status_code : 502;

		return new \WP_REST_Response(
			[
				'status' => $status_code,
				'data'   => $decoded ?? $body_content,
			],
			$rest_status
		);
	}

	// -------------------------------------------------------------------------
	// Localisation du script frontend
	// -------------------------------------------------------------------------

	/**
	 * Passe les données nécessaires au script view.js via wp_localize_script.
	 *
	 * En mode serveur, les credentials (en-têtes, body) des blocs présents sur la
	 * page sont transmis ici — jamais dans le HTML via data-config — afin de rester
	 * invisibles dans le source public.
	 *
	 * Structure : g2rdApiData.credentials[blockId] = { apiHeaders, apiBody, ... }
	 */
	public function localizeViewScript(): void {
		if ( ! \has_block( 'g2rd/block-api' ) ) {
			return;
		}

		// Handle WordPress auto-dérivé depuis block.json : {namespace}-{name}-view-script.
		$handle      = 'g2rd-block-api-view-script';
		$credentials = [];

		// Parcourir tous les blocs de la page pour extraire les credentials serveur.
		// Cas 1 : page/article classique avec contenu dans WP_Post.
		$post = \get_post();
		if ( $post instanceof \WP_Post ) {
			$blocks = \parse_blocks( $post->post_content );
			$this->collectServerCredentials( $blocks, $credentials );
		} elseif ( \class_exists( 'WP_Block_Template' ) && \function_exists( 'get_block_template' ) ) {
			// Cas 2 : contexte FSE (template de thème actif, pas de WP_Post classique).
			// Détermine le slug du template actif (ex. "page", "index", "single").
			$template_slug = \get_page_template_slug() ?: 'index';
			$template      = \get_block_template( \get_stylesheet() . '//' . $template_slug );
			if ( $template instanceof \WP_Block_Template && ! empty( $template->content ) ) {
				$blocks = \parse_blocks( $template->content );
				$this->collectServerCredentials( $blocks, $credentials );
			}
		}

		\wp_localize_script(
			$handle,
			'g2rdApiData',
			[
				'restUrl'     => \esc_url_raw( \rest_url() ),
				'nonce'       => \wp_create_nonce( 'wp_rest' ),
				// Credentials indexés par blockId — jamais exposés dans data-config HTML.
				'credentials' => $credentials,
			]
		);
	}

	/**
	 * Collecte récursivement les credentials des blocs g2rd/block-api en mode serveur.
	 *
	 * @param array $blocks      Tableau de blocs parsés.
	 * @param array $credentials Tableau passé par référence à compléter.
	 */
	private function collectServerCredentials( array $blocks, array &$credentials ): void {
		foreach ( $blocks as $block ) {
			if ( 'g2rd/block-api' === $block['blockName'] ) {
				$attrs = $block['attrs'] ?? [];
				if ( 'server' === ( $attrs['connectorType'] ?? 'client' ) ) {
					$block_id = \sanitize_text_field( $attrs['blockId'] ?? '' );
					if ( $block_id ) {
						$credentials[ $block_id ] = [
							'apiHeaders' => \is_array( $attrs['apiHeaders'] ?? null ) ? $attrs['apiHeaders'] : [],
							'apiBody'    => \sanitize_textarea_field( $attrs['apiBody'] ?? '' ),
						];
					}
				}
			}
			// Descendre dans les innerBlocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->collectServerCredentials( $block['innerBlocks'], $credentials );
			}
		}
	}
}
