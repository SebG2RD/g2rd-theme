<?php
/**
 * Client IA G2RD — Wrappeur WP AI Client + fallback
 *
 * Encapsule tous les appels vers l'IA. Utilise wp_ai_client() (WordPress 7.0+)
 * en priorité. Retourne un WP_Error explicite si le connecteur est absent.
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
	 * Nombre maximum de tokens dans la réponse IA.
	 *
	 * @var int
	 */
	private const MAX_TOKENS = 2048;

	/**
	 * Envoie un prompt au connecteur IA et retourne la réponse.
	 *
	 * Priorité 1 : WordPress 7.0 Connectors (wp_ai_client).
	 * Fallback    : WP_Error clair si aucun connecteur n'est disponible.
	 *
	 * @param string               $prompt Prompt à envoyer.
	 * @param array<string, mixed> $args   Arguments optionnels (max_tokens, temperature…).
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

		// WP 7.0+ Connectors — chemin principal.
		if ( \function_exists( 'wp_ai_client' ) ) {
			return $this->call_wp_ai_client( $prompt, $args );
		}

		// Fallback : aucun connecteur disponible.
		return new \WP_Error(
			'ai_connector_unavailable',
			\esc_html__(
				'Aucun connecteur IA WordPress disponible. Configurez un connecteur dans Réglages > IA.',
				'g2rd'
			)
		);
	}

	/**
	 * Appelle wp_ai_client() (WordPress 7.0 Connectors).
	 *
	 * @param string               $prompt Prompt sanitisé.
	 * @param array<string, mixed> $args   Arguments supplémentaires.
	 * @return string|\WP_Error
	 */
	private function call_wp_ai_client( string $prompt, array $args ): string|\WP_Error {
		$defaults = [
			'max_tokens'  => self::MAX_TOKENS,
			'temperature' => 0.7,
		];

		$params = \wp_parse_args( $args, $defaults );

		try {
			/** @var object $client */
			$client = \wp_ai_client(); // phpcs:ignore WordPress.Security -- WP 7.0 function.

			$response = $client->complete( $prompt, $params );

			if ( \is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! \is_string( $response ) || empty( \trim( $response ) ) ) {
				return new \WP_Error(
					'ai_empty_response',
					\esc_html__( 'Le connecteur IA a retourné une réponse vide.', 'g2rd' )
				);
			}

			return \sanitize_textarea_field( $response );

		} catch ( \Throwable $e ) {
			return new \WP_Error(
				'ai_exception',
				\esc_html__( 'Erreur lors de l\'appel au connecteur IA.', 'g2rd' ),
				[ 'exception' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Indique si un connecteur IA est disponible sur cette installation.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \function_exists( 'wp_ai_client' );
	}
}
