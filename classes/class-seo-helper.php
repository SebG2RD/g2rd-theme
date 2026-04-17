<?php
/**
 * SEO Helper — alertes et checklist SEO légères dans l'admin.
 *
 * Analyse la page/l'article en cours d'édition et affiche un score
 * + une checklist dans la sidebar de l'éditeur Gutenberg.
 * Ne remplace pas un plugin SEO complet — objectif : aider les clients
 * non techniques à ne pas oublier les bases.
 *
 * @package G2RD Theme
 * @since   1.4.0
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO_Helper {

	/**
	 * Enregistre les hooks WordPress du SEO Helper.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_assets' ] );
		\add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Charge le script et le style du panneau SEO dans l'éditeur Gutenberg.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! (bool) \get_option( 'g2rd_seo_helper', true ) ) {
			return;
		}

		$screen = \get_current_screen();
		if ( ! $screen || ! \in_array( $screen->post_type, [ 'post', 'page' ], true ) ) {
			return;
		}

		\wp_enqueue_script(
			'g2rd-seo-helper',
			\get_template_directory_uri() . '/assets/js/seo-helper.js',
			[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ],
			\wp_get_theme()->get( 'Version' ),
			true
		);

		\wp_set_script_translations( 'g2rd-seo-helper', 'g2rd' );

		\wp_localize_script( 'g2rd-seo-helper', 'g2rdSEO', [
			'nonce'   => \wp_create_nonce( 'wp_rest' ),
			'restUrl' => \rest_url( 'g2rd/v1/seo-analyze' ),
		] );

		\wp_enqueue_style(
			'g2rd-seo-helper',
			\get_template_directory_uri() . '/assets/css/seo-helper.css',
			[],
			\wp_get_theme()->get( 'Version' )
		);
	}

	/**
	 * Endpoint REST pour l'analyse SEO côté serveur.
	 */
	public function register_rest_routes(): void {
		\register_rest_route( 'g2rd/v1', '/seo-analyze', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'analyze_content' ],
			'permission_callback' => function () {
				return \current_user_can( 'edit_posts' );
			},
			'args' => [
				'post_id' => [
					'required'          => true,
					'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
					'sanitize_callback' => 'absint',
				],
			],
		] );
	}

	/**
	 * Analyse un post et retourne le score + les points de checklist.
	 *
	 * @param \WP_REST_Request $request Requête REST.
	 * @return \WP_REST_Response
	 */
	public function analyze_content( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_id = $request->get_param( 'post_id' );
		$post    = \get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', \__( 'Post introuvable.', 'g2rd' ), [ 'status' => 404 ] );
		}

		if ( ! \current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'forbidden', \__( 'Accès refusé.', 'g2rd' ), [ 'status' => 403 ] );
		}

		$checks = $this->run_checks( $post );
		$score  = $this->compute_score( $checks );

		return new \WP_REST_Response( [
			'score'  => $score,
			'checks' => $checks,
		] );
	}

	/**
	 * Exécute tous les contrôles SEO sur un post.
	 *
	 * @param \WP_Post $post Le post à analyser.
	 * @return array<string, array{label: string, status: string, message: string}>
	 */
	private function run_checks( \WP_Post $post ): array {
		$content       = $post->post_content;
		$title         = $post->post_title;
		$excerpt       = $post->post_excerpt;
		$content_plain = \wp_strip_all_tags( \strip_shortcodes( $content ) );
		preg_match_all( '/\p{L}+/u', $content_plain, $words_match );
		$word_count    = count( $words_match[0] );

		$checks = [];

		// 1. Titre
		$title_len = mb_strlen( $title );
		if ( $title_len >= 30 && $title_len <= 60 ) {
			$checks['title'] = [
				'label'   => \esc_html__( 'Titre de la page', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre de caractères du titre.
			'message' => sprintf( \__( 'Titre de %d caractères (idéal : 30–60)', 'g2rd' ), $title_len ),
			];
		} elseif ( $title_len < 30 ) {
			$checks['title'] = [
				'label'   => \esc_html__( 'Titre de la page', 'g2rd' ),
				'status'  => 'warning',
				// translators: %d = nombre de caractères du titre.
			'message' => sprintf( \__( 'Titre trop court (%d car.) — visez 30–60 caractères', 'g2rd' ), $title_len ),
			];
		} else {
			$checks['title'] = [
				'label'   => \esc_html__( 'Titre de la page', 'g2rd' ),
				'status'  => 'warning',
				// translators: %d = nombre de caractères du titre.
			'message' => sprintf( \__( 'Titre trop long (%d car.) — visez 30–60 caractères', 'g2rd' ), $title_len ),
			];
		}

		// 2. Extrait / meta description
		$excerpt_len = mb_strlen( $excerpt );
		if ( empty( $excerpt ) ) {
			$checks['excerpt'] = [
				'label'   => \esc_html__( 'Extrait (meta description)', 'g2rd' ),
				'status'  => 'error',
				'message' => \esc_html__( 'Extrait manquant — renseignez-le (120–160 car.)', 'g2rd' ),
			];
		} elseif ( $excerpt_len >= 120 && $excerpt_len <= 160 ) {
			$checks['excerpt'] = [
				'label'   => \esc_html__( 'Extrait (meta description)', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre de caractères de l'extrait.
				'message' => sprintf( \__( 'Extrait de %d caractères (idéal : 120–160)', 'g2rd' ), $excerpt_len ),
			];
		} else {
			$checks['excerpt'] = [
				'label'   => \esc_html__( 'Extrait (meta description)', 'g2rd' ),
				'status'  => 'warning',
				// translators: %d = nombre de caractères de l'extrait.
				'message' => sprintf( \__( 'Extrait de %d car. — idéal : 120–160 caractères', 'g2rd' ), $excerpt_len ),
			];
		}

		// 3. H1 dans le contenu (le titre du post fait office de H1 en FSE)
		$checks['h1'] = [
			'label'   => \esc_html__( 'Balise H1', 'g2rd' ),
			'status'  => 'ok',
			'message' => \esc_html__( 'H1 fourni par le titre de la page — OK', 'g2rd' ),
		];

		// 4. H2 dans le contenu
		$h2_count = (int) preg_match_all( '/<h2[\s>]/i', $content );
		if ( $h2_count >= 2 ) {
			$checks['headings'] = [
				'label'   => \esc_html__( 'Sous-titres H2', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre de sous-titres H2 détectés.
				'message' => sprintf( \__( '%d sous-titres H2 trouvés', 'g2rd' ), $h2_count ),
			];
		} elseif ( 1 === $h2_count ) {
			$checks['headings'] = [
				'label'   => \esc_html__( 'Sous-titres H2', 'g2rd' ),
				'status'  => 'warning',
				'message' => \esc_html__( '1 seul H2 — ajoutez des sous-titres pour structurer le contenu', 'g2rd' ),
			];
		} else {
			$checks['headings'] = [
				'label'   => \esc_html__( 'Sous-titres H2', 'g2rd' ),
				'status'  => 'error',
				'message' => \esc_html__( 'Aucun H2 trouvé — structurez le contenu avec des titres', 'g2rd' ),
			];
		}

		// 5. Images sans alt
		$images_total   = (int) preg_match_all( '/<img[^>]+>/i', $content, $all_imgs );
		$images_no_alt  = 0;
		if ( $images_total > 0 ) {
			foreach ( $all_imgs[0] as $img ) {
				if ( ! preg_match( '/alt=["\'][^"\']+["\']/', $img ) ) {
					++$images_no_alt;
				}
			}
		}

		if ( 0 === $images_total ) {
			$checks['images'] = [
				'label'   => \esc_html__( 'Images & attributs alt', 'g2rd' ),
				'status'  => 'info',
				'message' => \esc_html__( 'Aucune image dans le contenu', 'g2rd' ),
			];
		} elseif ( 0 === $images_no_alt ) {
			$checks['images'] = [
				'label'   => \esc_html__( 'Images & attributs alt', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre d'images avec attribut alt renseigné.
				'message' => sprintf( \__( '%d image(s) avec alt renseigné', 'g2rd' ), $images_total ),
			];
		} else {
			$checks['images'] = [
				'label'   => \esc_html__( 'Images & attributs alt', 'g2rd' ),
				'status'  => 'error',
				// translators: %d = nombre d'images sans attribut alt.
				'message' => sprintf( \__( '%d image(s) sans attribut alt — à corriger', 'g2rd' ), $images_no_alt ),
			];
		}

		// 6. Longueur du contenu
		if ( $word_count >= 300 ) {
			$checks['content_length'] = [
				'label'   => \esc_html__( 'Longueur du contenu', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre de mots dans le contenu.
				'message' => sprintf( \__( '%d mots (minimum recommandé : 300)', 'g2rd' ), $word_count ),
			];
		} elseif ( $word_count >= 150 ) {
			$checks['content_length'] = [
				'label'   => \esc_html__( 'Longueur du contenu', 'g2rd' ),
				'status'  => 'warning',
				// translators: %d = nombre de mots dans le contenu.
				'message' => sprintf( \__( '%d mots — contenu court, visez 300 mots minimum', 'g2rd' ), $word_count ),
			];
		} else {
			$checks['content_length'] = [
				'label'   => \esc_html__( 'Longueur du contenu', 'g2rd' ),
				'status'  => 'error',
				// translators: %d = nombre de mots dans le contenu.
				'message' => sprintf( \__( 'Seulement %d mots — contenu insuffisant pour le SEO', 'g2rd' ), $word_count ),
			];
		}

		// 7. Image à la une
		$thumbnail_id = (int) \get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id > 0 ) {
			$checks['featured_image'] = [
				'label'   => \esc_html__( 'Image à la une', 'g2rd' ),
				'status'  => 'ok',
				'message' => \esc_html__( 'Image à la une définie', 'g2rd' ),
			];
		} else {
			$checks['featured_image'] = [
				'label'   => \esc_html__( 'Image à la une', 'g2rd' ),
				'status'  => 'warning',
				'message' => \esc_html__( 'Aucune image à la une — recommandé pour les réseaux sociaux', 'g2rd' ),
			];
		}

		// 8. Liens internes
		$internal_links = (int) preg_match_all(
			'/<a[^>]+href=["\']' . preg_quote( \home_url(), '/' ) . '[^"\']*["\'][^>]*>/i',
			$content
		);
		if ( $internal_links >= 2 ) {
			$checks['internal_links'] = [
				'label'   => \esc_html__( 'Maillage interne', 'g2rd' ),
				'status'  => 'ok',
				// translators: %d = nombre de liens internes détectés.
				'message' => sprintf( \__( '%d lien(s) interne(s)', 'g2rd' ), $internal_links ),
			];
		} elseif ( 1 === $internal_links ) {
			$checks['internal_links'] = [
				'label'   => \esc_html__( 'Maillage interne', 'g2rd' ),
				'status'  => 'warning',
				'message' => \esc_html__( '1 lien interne — ajoutez-en 2+ pour améliorer le maillage', 'g2rd' ),
			];
		} else {
			$checks['internal_links'] = [
				'label'   => \esc_html__( 'Maillage interne', 'g2rd' ),
				'status'  => 'warning',
				'message' => \esc_html__( 'Aucun lien interne — ajoutez des liens vers vos autres pages', 'g2rd' ),
			];
		}

		return $checks;
	}

	/**
	 * Calcule un score global sur 100.
	 *
	 * @param array $checks Résultats des contrôles.
	 * @return int Score entre 0 et 100.
	 */
	private function compute_score( array $checks ): int {
		$weights = [
			'title'          => 20,
			'excerpt'        => 20,
			'h1'             => 5,
			'headings'       => 10,
			'images'         => 15,
			'content_length' => 15,
			'featured_image' => 5,
			'internal_links' => 10,
		];

		$score = 0;
		foreach ( $checks as $key => $check ) {
			$weight = $weights[ $key ] ?? 0;
			if ( 'ok' === $check['status'] || 'info' === $check['status'] ) {
				$score += $weight;
			} elseif ( 'warning' === $check['status'] ) {
				$score += (int) round( $weight * 0.5 );
			}
		}

		return min( 100, $score );
	}
}
