<?php
/**
 * Gestionnaire des avis Google — Endpoint REST + cache transient
 *
 * Expose :
 *  GET  /wp-json/g2rd/v1/google-reviews          → récupère les avis (avec cache 12h)
 *  DELETE /wp-json/g2rd/v1/google-reviews/cache  → vide le cache (admin)
 *
 * La clé API Google Maps est stockée dans wp_options via ThemeOptions.
 *
 * @package    G2RD
 * @since      1.6.9
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * GoogleReviews — récupération, mise en cache et endpoint REST des avis Google.
 */
class GoogleReviews {

	private const REST_NAMESPACE = 'g2rd/v1';
	private const CACHE_TTL      = 12 * \HOUR_IN_SECONDS;
	private const PLACES_API_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

	// ── Init ─────────────────────────────────────────────────────────────

	/**
	 * Enregistre les hooks WordPress.
	 *
	 * @return void
	 */
	public static function init(): void {
		\add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		\add_action( 'wp_head',       [ __CLASS__, 'maybe_print_endpoint' ] );
	}

	// ── REST Routes ──────────────────────────────────────────────────────

	/**
	 * Enregistre les routes REST.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		\register_rest_route(
			self::REST_NAMESPACE,
			'/google-reviews',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_reviews' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'place_id'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $val ) {
							return ! empty( $val ) && \strlen( $val ) <= 300;
						},
					],
					'min_rating' => [
						'default'           => 4,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'max'        => [
						'default'           => 5,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		\register_rest_route(
			self::REST_NAMESPACE,
			'/google-reviews/cache',
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ __CLASS__, 'clear_cache' ],
				'permission_callback' => static fn() => \current_user_can( 'manage_options' ),
				'args'                => [
					'place_id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	// ── Callbacks ────────────────────────────────────────────────────────

	/**
	 * Endpoint GET — retourne les avis avec cache transient 12h + cache stale permanent.
	 *
	 * Stratégie stale-while-revalidate :
	 * 1. Transient frais (12h)  → réponse immédiate.
	 * 2. Transient expiré       → appel API Google.
	 * 3. API en erreur          → fallback sur le cache stale (wp_options) si disponible.
	 * 4. Aucun cache            → retourne l'erreur.
	 *
	 * @param \WP_REST_Request $request Requête REST.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_reviews( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$place_id   = $request->get_param( 'place_id' );
		$min_rating = \max( 1, \min( 5, (int) $request->get_param( 'min_rating' ) ) );
		$max        = \max( 1, \min( 5, (int) $request->get_param( 'max' ) ) );

		$cache_key = 'g2rd_gr_' . \md5( $place_id );
		$stale_key = 'g2rd_gr_stale_' . \md5( $place_id );

		// 1. Cache transient frais — priorité absolue.
		$cached = \get_transient( $cache_key );
		if ( false !== $cached ) {
			return self::filtered_response( $cached, $min_rating, $max );
		}

		// 2. Clé API manquante — fallback stale avant d'échouer.
		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			$stale = \get_option( $stale_key );
			if ( ! empty( $stale ) ) {
				return self::filtered_response( $stale, $min_rating, $max );
			}
			return new \WP_Error(
				'no_api_key',
				\__( 'Clé API Google Maps non configurée. Rendez-vous dans Options G2RD → Intégrations.', 'g2rd' ),
				[ 'status' => 400 ]
			);
		}

		$locale  = \get_locale();
		$lang    = 'fr_FR' === $locale ? 'fr' : \substr( $locale, 0, 2 );

		$api_url = \add_query_arg(
			[
				'place_id' => \rawurlencode( $place_id ),
				'fields'   => 'name,reviews,rating,user_ratings_total,url',
				'language' => $lang,
				'key'      => $api_key,
			],
			self::PLACES_API_URL
		);

		$response = \wp_remote_get( $api_url, [ 'timeout' => 10, 'sslverify' => true ] );

		// 3. Erreur réseau ou API → fallback sur le cache stale.
		if ( \is_wp_error( $response ) ) {
			$stale = \get_option( $stale_key );
			if ( ! empty( $stale ) ) {
				return self::filtered_response( $stale, $min_rating, $max );
			}
			return $response;
		}

		$body = \json_decode( \wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['status'] ) || 'OK' !== $body['status'] ) {
			// 3bis. Réponse Google invalide (REQUEST_DENIED, quota, etc.) → stale.
			$stale = \get_option( $stale_key );
			if ( ! empty( $stale ) ) {
				return self::filtered_response( $stale, $min_rating, $max );
			}
			return new \WP_Error(
				'places_api_error',
				\sanitize_text_field( $body['status'] ?? 'UNKNOWN_ERROR' ),
				[ 'status' => 502 ]
			);
		}

		// 4. Succès → met à jour le transient frais ET le cache stale permanent.
		$result   = $body['result'] ?? [];
		$raw_data = [
			'place_name'     => \sanitize_text_field( $result['name'] ?? '' ),
			'overall_rating' => (float) ( $result['rating'] ?? 0 ),
			'total_ratings'  => \absint( $result['user_ratings_total'] ?? 0 ),
			'place_url'      => \esc_url_raw( $result['url'] ?? '' ),
			'reviews'        => self::normalize_reviews( $result['reviews'] ?? [] ),
		];

		\set_transient( $cache_key, $raw_data, self::CACHE_TTL );
		\update_option( $stale_key, $raw_data, false ); // autoload=false, stockage permanent

		return self::filtered_response( $raw_data, $min_rating, $max );
	}

	/**
	 * Endpoint DELETE — vide le cache pour un Place ID.
	 *
	 * @param \WP_REST_Request $request Requête REST.
	 * @return \WP_REST_Response
	 */
	public static function clear_cache( \WP_REST_Request $request ): \WP_REST_Response {
		$place_id  = $request->get_param( 'place_id' );
		$hash      = \md5( $place_id );
		\delete_transient( 'g2rd_gr_' . $hash );
		\delete_option( 'g2rd_gr_stale_' . $hash );

		return new \WP_REST_Response( [ 'cleared' => true ], 200 );
	}

	// ── Frontend ─────────────────────────────────────────────────────────

	/**
	 * Injecte l'URL REST dans le <head> si le bloc Testimonial est présent.
	 *
	 * @return void
	 */
	public static function maybe_print_endpoint(): void {
		if ( ! \has_block( 'g2rd/testimonial' ) ) {
			return;
		}
		\wp_print_inline_script_tag(
			'window.G2RDGoogleReviewsEndpoint=' . \wp_json_encode( \rest_url( 'g2rd/v1/google-reviews' ) ) . ';'
		);
	}

	// ── Helpers ──────────────────────────────────────────────────────────

	/**
	 * Retourne la clé API stockée dans wp_options.
	 *
	 * @return string
	 */
	public static function get_api_key(): string {
		return (string) \get_option( 'g2rd_google_maps_api_key', '' );
	}

	/**
	 * Normalise les avis bruts de l'API Places.
	 *
	 * @param array<int, array<string, mixed>> $raw Avis bruts.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_reviews( array $raw ): array {
		$out = [];
		foreach ( $raw as $r ) {
			$out[] = [
				'author'        => \sanitize_text_field( $r['author_name'] ?? '' ),
				'author_url'    => \esc_url_raw( $r['author_url'] ?? '' ),
				'avatar'        => \esc_url_raw( $r['profile_photo_url'] ?? '' ),
				'rating'        => \absint( $r['rating'] ?? 5 ),
				'text'          => \wp_kses_post( $r['text'] ?? '' ),
				'relative_time' => \sanitize_text_field( $r['relative_time_description'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Applique le filtre note/max et retourne la réponse REST.
	 *
	 * @param array<string, mixed> $data       Données complètes.
	 * @param int                  $min_rating Note minimum.
	 * @param int                  $max        Nombre maximum d'avis.
	 * @return \WP_REST_Response
	 */
	private static function filtered_response( array $data, int $min_rating, int $max ): \WP_REST_Response {
		$filtered        = \array_filter(
			$data['reviews'],
			static fn( $r ) => ( $r['rating'] ?? 0 ) >= $min_rating
		);
		$data['reviews'] = \array_values( \array_slice( $filtered, 0, $max ) );

		return new \WP_REST_Response( $data, 200 );
	}
}
