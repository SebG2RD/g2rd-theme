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
        if ( ! (bool) \get_option( 'g2rd_geo_helper', true ) ) {
            return;
        }

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
            '/geo-score',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'rest_save_score' ],
                'permission_callback' => static function (): bool {
                    return \current_user_can( 'edit_posts' );
                },
                'args'                => [
                    'post_id'   => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'score'     => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'page_type' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                        'default'           => '',
                    ],
                ],
            ]
        );

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
     * Callback REST — sauvegarde du score GEO en post meta.
     *
     * @param \WP_REST_Request $request Requête REST.
     * @return \WP_REST_Response
     */
    public function rest_save_score( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id   = (int) $request->get_param( 'post_id' );
        $score     = min( 100, max( 0, (int) $request->get_param( 'score' ) ) );
        $page_type = (string) $request->get_param( 'page_type' );

        if ( ! \get_post( $post_id ) || ! \current_user_can( 'edit_post', $post_id ) ) {
            return new \WP_REST_Response( [ 'error' => 'Forbidden' ], 403 );
        }

        \update_post_meta( $post_id, '_g2rd_geo_score',     $score );
        \update_post_meta( $post_id, '_g2rd_geo_page_type', \sanitize_key( $page_type ) );
        \update_post_meta( $post_id, '_g2rd_geo_score_date', \current_time( 'mysql' ) );

        return new \WP_REST_Response( [ 'success' => true, 'score' => $score ], 200 );
    }

    /**
     * Callback REST — analyse complémentaire côté serveur.
     *
     * Retourne des informations que le JS ne peut pas détecter :
     *  - Types de schemas JSON-LD présents et leur complétude (@graph supporté)
     *  - Meta description renseignée (Yoast / RankMath / SEOPress)
     *  - Lisibilité : longueur moyenne des phrases
     *  - Nombre de mots (texte seul, sans balises)
     *
     * @param \WP_REST_Request $request Requête REST.
     * @return \WP_REST_Response
     */
    public function rest_analyze( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = $request->get_param( 'post_id' );
        $content = $request->get_param( 'content' ) ?? '';
        $plain   = \wp_strip_all_tags( $content );

        $result = [
            'wordCount'     => \str_word_count( $plain ),
            'hasMetaDesc'   => false,
            'hasGeoSummary' => \str_contains( $content, 'wp-block-g2rd-geo-summary' ),
            'hasGeoFaq'     => \str_contains( $content, 'wp-block-g2rd-geo-faq' ) || \str_contains( $content, 'g2rd-faq--geo' ),
            'jsonLd'        => [
                'detected'   => false,
                'types'      => [],
                'isComplete' => false,
            ],
            'readability'   => [
                'avgWordsPerSentence' => 0,
                'sentenceCount'       => 0,
            ],
        ];

        // ── Détection JSON-LD dans le contenu du post ──────────────────────
        $source = $post_id ? (string) \get_post_field( 'post_content', \absint( $post_id ) ) : $content;

        if ( \preg_match_all(
            '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si',
            $source,
            $matches
        ) ) {
            $json_ld_types    = [];
            $json_ld_complete = false;

            foreach ( $matches[1] as $json_raw ) {
                $data = \json_decode( \wp_strip_all_tags( $json_raw ), true );
                if ( ! \is_array( $data ) ) {
                    continue;
                }

                $items = isset( $data['@graph'] ) ? $data['@graph'] : [ $data ];
                foreach ( $items as $item ) {
                    if ( ! \is_array( $item ) ) {
                        continue;
                    }
                    $type = $item['@type'] ?? null;
                    if ( $type ) {
                        $json_ld_types[] = \sanitize_text_field( (string) $type );
                        if ( ! empty( $item['name'] ) && ( ! empty( $item['description'] ) || ! empty( $item['url'] ) ) ) {
                            $json_ld_complete = true;
                        }
                    }
                }
            }

            $result['jsonLd'] = [
                'detected'   => ! empty( $json_ld_types ),
                'types'      => \array_values( \array_unique( $json_ld_types ) ),
                'isComplete' => $json_ld_complete,
            ];
        }

        // ── Meta description (Yoast / RankMath / SEOPress) ─────────────────
        if ( $post_id ) {
            $pid       = \absint( $post_id );
            $meta_desc = \get_post_meta( $pid, '_yoast_wpseo_metadesc', true )
                ?: \get_post_meta( $pid, 'rank_math_description', true )
                ?: \get_post_meta( $pid, '_seopress_titles_desc', true )
                ?: '';
            $result['hasMetaDesc'] = ! empty( $meta_desc );
        }

        // ── Lisibilité serveur : longueur moyenne des phrases ───────────────
        if ( $plain ) {
            $raw_sentences = \preg_split( '/[.!?]+/u', $plain, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
            $sentences     = \array_values(
                \array_filter(
                    $raw_sentences,
                    static function ( string $s ): bool {
                        return \str_word_count( \trim( $s ) ) > 3;
                    }
                )
            );

            $sentence_count = \count( $sentences );
            if ( $sentence_count > 0 ) {
                $total_words = (int) \array_sum(
                    \array_map(
                        static function ( string $s ): int {
                            return (int) \str_word_count( \trim( $s ) );
                        },
                        $sentences
                    )
                );
                $result['readability'] = [
                    'avgWordsPerSentence' => \round( $total_words / $sentence_count, 1 ),
                    'sentenceCount'       => $sentence_count,
                ];
            }
        }

        return new \WP_REST_Response( $result, 200 );
    }
}
