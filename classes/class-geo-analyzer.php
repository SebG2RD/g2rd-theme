<?php
/**
 * Module GEO Analyzer — Generative Engine Optimization
 *
 * Analyse le contenu d'une page dans Gutenberg et calcule un score GEO
 * (capacité du contenu à être compris, résumé et cité par une IA).
 *
 * Architecture :
 *  - Ce fichier PHP gère l'enqueue des assets éditeur.
 *  - L'analyse est faite côté JS en temps réel (pas de requête serveur).
 *  - Un endpoint REST optionnel est exposé pour des analyses supplémentaires.
 *
 * @package    G2RD
 * @since      1.3.4
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Classe GeoAnalyzer
 */
class GeoAnalyzer {

    /**
     * Namespace REST pour les endpoints GEO.
     *
     * @var string
     */
    private const REST_NAMESPACE = 'g2rd/v1';

    /**
     * Enregistre les hooks WordPress.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
        \add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
    }

    /**
     * Charge les assets du module GEO dans l'éditeur Gutenberg.
     *
     * @return void
     */
    public function enqueue_editor_assets(): void {
        $dir_path = \get_template_directory();
        $dir_uri  = \get_template_directory_uri();

        $js_path  = $dir_path . '/blocks/g2rd-geo-analyzer/build/index.js';
        $css_path = $dir_path . '/blocks/g2rd-geo-analyzer/build/style-index.css';

        if ( ! \file_exists( $js_path ) ) {
            return;
        }

        \wp_enqueue_script(
            'g2rd-geo-analyzer',
            $dir_uri . '/blocks/g2rd-geo-analyzer/build/index.js',
            [
                'wp-plugins',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-compose',
                'wp-edit-post',
                'wp-block-editor',
                'wp-i18n',
            ],
            (string) \filemtime( $js_path ),
            true
        );

        \wp_set_script_translations( 'g2rd-geo-analyzer', 'g2rd' );

        if ( \file_exists( $css_path ) ) {
            \wp_enqueue_style(
                'g2rd-geo-analyzer',
                $dir_uri . '/blocks/g2rd-geo-analyzer/build/style-index.css',
                [ 'wp-components' ],
                (string) \filemtime( $css_path )
            );
        }
    }

    /**
     * Enregistre les endpoints REST GEO.
     *
     * Endpoint POST /wp-json/g2rd/v1/geo-analyze :
     *   Analyse serveur (détection schema.org, métadonnées…).
     *
     * @return void
     */
    public function register_rest_routes(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            '/geo-analyze',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'rest_analyze' ],
                'permission_callback' => static function (): bool {
                    return \current_user_can( 'edit_posts' );
                },
                'args'                => [
                    'post_id' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'content' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ],
                ],
            ]
        );
    }

    /**
     * Callback REST — analyse complémentaire côté serveur.
     *
     * Retourne des informations que le JS ne peut pas détecter facilement :
     *  - Présence de données structurées JSON-LD dans la page
     *  - Meta description renseignée
     *  - Nombre de mots (texte seul, sans balises)
     *
     * @param \WP_REST_Request $request Requête REST.
     * @return \WP_REST_Response
     */
    public function rest_analyze( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = $request->get_param( 'post_id' );
        $content = $request->get_param( 'content' ) ?? '';

        $result = [
            'wordCount'       => \str_word_count( \wp_strip_all_tags( $content ) ),
            'hasMetaDesc'     => false,
            'hasJsonLd'       => \str_contains( $content, 'application/ld+json' ),
            'hasGeoSummary'   => \str_contains( $content, 'wp-block-g2rd-geo-summary' ),
            'hasGeoFaq'       => \str_contains( $content, 'wp-block-g2rd-geo-faq' ),
        ];

        if ( $post_id ) {
            $meta_desc            = \get_post_meta( $post_id, '_yoast_wpseo_metadesc', true )
                ?? \get_post_meta( $post_id, 'rank_math_description', true )
                ?? '';
            $result['hasMetaDesc'] = ! empty( $meta_desc );
        }

        return new \WP_REST_Response( $result, 200 );
    }
}
