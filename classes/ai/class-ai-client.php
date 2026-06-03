<?php
/**
 * Client IA G2RD — Appel direct API Anthropic
 *
 * Envoie les prompts à l'API Anthropic via wp_remote_post().
 * Aucune dépendance à wp_ai_client() ni à un plugin tiers.
 *
 * @package    G2RD\AI
 * @since      1.14.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD\AI;

/**
 * Classe AiClient
 */
class AiClient {

	/**
	 * URL de l'API Anthropic Messages.
	 *
	 * @var string
	 */
	private const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Modèle Anthropic utilisé par défaut.
	 *
	 * @var string
	 */
	private const MODEL = 'claude-sonnet-4-6';

	/**
	 * Nombre maximum de tokens dans la réponse.
	 *
	 * @var int
	 */
	private const MAX_TOKENS = 2048;

	/**
	 * Version de l'API Anthropic.
	 *
	 * @var string
	 */
	private const ANTHROPIC_VERSION = '2023-06-01';

	/**
	 * Envoie un prompt à l'API Anthropic et retourne la réponse textuelle.
	 *
	 * @param string               $prompt Prompt à envoyer.
	 * @param array<string, mixed> $args   Arguments optionnels (max_tokens…).
	 * @return string|\WP_Error Réponse textuelle ou WP_Error.
	 */
	public function complete( string $prompt, array $args = [] ): string|\WP_Error {
		$prompt = \sanitize_textarea_field( $prompt );

		if ( empty( $prompt ) ) {
			return new \WP_Error(
				'ai_empty_prompt',
				\esc_html__( 'Le prompt ne peut pas être vide.', 'g2rd' )
			);
		}

		// 1. Connecteur WordPress AI Client (plugin « AI » + providers) si disponible.
		$via_connector = $this->complete_via_connector( $prompt );
		if ( null !== $via_connector ) {
			return $via_connector;
		}

		// 2. Fallback : appel direct à l'API Anthropic.
		return $this->complete_direct( $prompt, $args );
	}

	/**
	 * Tente la génération via le connecteur WordPress AI Client.
	 *
	 * Retourne null si le connecteur est absent/non configuré ou échoue, afin de
	 * basculer proprement sur le client direct (try/catch défensif : l'API exacte
	 * du plugin peut évoluer).
	 *
	 * @param string $prompt Prompt déjà sanitisé.
	 * @return string|\WP_Error|null
	 */
	private function complete_via_connector( string $prompt ): string|\WP_Error|null {
		$class = '\WordPress\AiClient\AiClient';

		if ( ! \class_exists( $class ) ) {
			return null;
		}

		try {
			if ( \method_exists( $class, 'isConfigured' ) && ! $class::isConfigured() ) {
				return null;
			}

			$text = $class::prompt( $prompt )->generateText();
			$text = \is_string( $text ) ? \trim( $text ) : '';

			return ( '' !== $text ) ? \sanitize_textarea_field( $text ) : null;
		} catch ( \Throwable $e ) {
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- diagnostic conditionné à WP_DEBUG
				\error_log( 'G2RD AiClient : connecteur indisponible, fallback direct — ' . $e->getMessage() );
			}
			return null;
		}
	}

	/**
	 * Appel direct à l'API Anthropic (fallback quand le connecteur est absent).
	 *
	 * @param string               $prompt Prompt à envoyer.
	 * @param array<string, mixed> $args   Arguments optionnels (max_tokens…).
	 * @return string|\WP_Error Réponse textuelle ou WP_Error.
	 */
	private function complete_direct( string $prompt, array $args = [] ): string|\WP_Error {
		$settings = \get_option( AiModule::OPTION_KEY, [] );
		$api_key  = \is_array( $settings ) ? ( $settings['api_key'] ?? '' ) : '';

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'ai_not_configured',
				\esc_html__( 'Clé API Anthropic non configurée. Rendez-vous dans Réglages → IA.', 'g2rd' ),
				[ 'status' => 503 ]
			);
		}

		$max_tokens = isset( $args['max_tokens'] ) ? \absint( $args['max_tokens'] ) : self::MAX_TOKENS;

		$response = \wp_remote_post(
			self::ANTHROPIC_API_URL,
			[
				'timeout' => 30,
				'headers' => [
					'x-api-key'         => $api_key,
					'anthropic-version' => self::ANTHROPIC_VERSION,
					'content-type'      => 'application/json',
				],
				'body'    => \wp_json_encode(
					[
						'model'      => self::MODEL,
						'max_tokens' => $max_tokens,
						'messages'   => [
							[ 'role' => 'user', 'content' => $prompt ],
						],
					]
				),
			]
		);

		if ( \is_wp_error( $response ) ) {
			return new \WP_Error(
				'ai_http_error',
				$response->get_error_message(),
				[ 'status' => 502 ]
			);
		}

		$code = (int) \wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$body = \json_decode( \wp_remote_retrieve_body( $response ), true );
			$msg  = ( \is_array( $body ) && isset( $body['error']['message'] ) )
				? \sanitize_text_field( (string) $body['error']['message'] )
				: \esc_html__( 'Erreur API Anthropic.', 'g2rd' );
			return new \WP_Error( 'ai_api_error', $msg, [ 'status' => $code ] );
		}

		$body = \json_decode( \wp_remote_retrieve_body( $response ), true );
		$text = (string) ( $body['content'][0]['text'] ?? '' );

		if ( empty( \trim( $text ) ) ) {
			return new \WP_Error(
				'ai_empty_response',
				\esc_html__( 'L\'API Anthropic a retourné une réponse vide.', 'g2rd' )
			);
		}

		return \sanitize_textarea_field( $text );
	}

	/**
	 * Indique si une clé API Anthropic est configurée.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		// Connecteur WordPress AI Client configuré ?
		$class = '\WordPress\AiClient\AiClient';
		if ( \class_exists( $class ) ) {
			try {
				if ( ! \method_exists( $class, 'isConfigured' ) || $class::isConfigured() ) {
					return true;
				}
			} catch ( \Throwable $e ) {
				if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- diagnostic conditionné à WP_DEBUG
					\error_log( 'G2RD AiClient::is_available : ' . $e->getMessage() );
				}
			}
		}

		// Sinon, clé API directe configurée ?
		$settings = \get_option( AiModule::OPTION_KEY, [] );
		return \is_array( $settings ) && ! empty( $settings['api_key'] );
	}
}
