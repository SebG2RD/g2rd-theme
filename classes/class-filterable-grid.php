<?php
/**
 * Grille filtrable — Endpoint REST unifié
 *
 * Expose deux routes REST :
 *  - GET /g2rd/v1/content-types  → liste tous les CPT disponibles (WP + ecommerce)
 *  - GET /g2rd/v1/posts          → récupère des contenus normalisés depuis n'importe quel CPT
 *
 * Prend en charge nativement :
 *  - Tous les CPT WordPress natifs/custom
 *  - WooCommerce products (product / product_variation)
 *  - SureCart products (sc-product)
 *  - FluentCart products (fluent_cart_product)
 *
 * @package G2RD
 * @since   1.2.0
 */

namespace G2RD;

/**
 * Fournit un endpoint REST normalisé pour la Grille filtrable.
 */
class FilterableGrid {
    /** Namespace REST */
    private const REST_NAMESPACE = 'g2rd/v1';

    // ─── Hooks ───────────────────────────────────────────────────────────────

    /**
     * Enregistre les hooks WordPress pour la grille filtrable.
     */
    public function register_hooks(): void {
        \add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Enregistre les routes REST API pour la grille filtrable.
     */
    public function registerRoutes(): void {
        // Endpoint public : expose uniquement les CPT publics (post_status=publish garanti côté getPosts)
        \register_rest_route(self::REST_NAMESPACE, '/content-types', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getContentTypes'],
            'permission_callback' => '__return_true',
        ]);

        \register_rest_route(self::REST_NAMESPACE, '/posts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getPosts'],
            'permission_callback' => '__return_true',
            'args'                => [
                'post_type' => [
                    'default'           => 'post',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => [$this, 'validatePostType'],
                ],
                'per_page'  => [
                    'default'           => 6,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn( $v ) => \is_numeric($v) && (int) $v >= 1 && (int) $v <= 48,
                ],
                'page'      => [
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn( $v ) => \is_numeric($v) && (int) $v >= 1,
                ],
                'search'    => [
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => static fn( $v ) => \strlen($v) <= 200,
                ],
                'taxonomy'  => [
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => [$this, 'validateTaxonomy'],
                ],
                'term'      => [
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                ],
                'orderby'   => [
                    'default'           => 'date',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static fn( $v ) => \in_array(
                        $v,
                        ['date', 'title', 'menu_order', 'rand', 'comment_count', 'modified', 'meta_value_num', 'price'],
                        true
                    ),
                ],
                'order'     => [
                    'default'           => 'DESC',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => static fn( $v ) => \in_array(\strtoupper($v), ['ASC', 'DESC'], true),
                ],
            ],
        ]);
    }

    /**
     * Valide que le post_type demandé est un CPT public enregistré.
     *
     * @param  string $value
     * @return bool
     */
    public function validatePostType( string $value ): bool {
        $public_types = \get_post_types(['public' => true]);
        return isset($public_types[$value]);
    }

    /**
     * Valide que la taxonomie demandée est associée à un CPT public.
     *
     * @param  string $value
     * @return bool
     */
    public function validateTaxonomy( string $value ): bool {
        if ('' === $value) {
            return true;
        }
        $tax = \get_taxonomy($value);
        return $tax instanceof \WP_Taxonomy && $tax->show_in_rest;
    }

    // ─── Route : content-types ───────────────────────────────────────────────

    /**
     * Retourne tous les types de contenu disponibles, y compris les produits ecommerce.
     *
     * @return \WP_REST_Response
     */
    public function getContentTypes(): \WP_REST_Response
    {
        $types = [];

        // ── CPT WordPress natifs (show_in_rest ou viewable) ──────────────────
        $wp_types = \get_post_types(['public' => true], 'objects');
        foreach ($wp_types as $slug => $obj) {
            // Exclure les types internes Gutenberg
            if (\in_array($slug, ['attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'wp_font_family', 'wp_font_face'], true)) {
                continue;
            }

            $taxonomies = \get_object_taxonomies($slug, 'objects');
            $tax_list   = [];
            foreach ($taxonomies as $tax_slug => $tax_obj) {
                if ($tax_obj->show_in_rest) {
                    $tax_list[] = [
                        'slug'  => $tax_slug,
                        'label' => $tax_obj->label,
                    ];
                }
            }

            $type_data = [
                'slug'          => $slug,
                'label'         => $obj->label,
                'singular'      => $obj->labels->singular_name ?? $obj->label,
                'rest_base'     => $obj->rest_base ?: $slug,
                'taxonomies'    => $tax_list,
                'is_product'    => false,
                'product_meta'  => [],
                'source'        => 'wordpress',
            ];

            // ── WooCommerce ──────────────────────────────────────────────────
            if ($slug === 'product' && \class_exists('WooCommerce')) {
                $type_data['is_product']   = true;
                $type_data['source']       = 'woocommerce';
                $type_data['product_meta'] = ['price', 'sale_price', 'on_sale', 'stock_status', 'rating_count', 'average_rating', 'sku'];
            }

            // ── SureCart ─────────────────────────────────────────────────────
            if ($slug === 'sc-product' || \in_array($slug, ['sc_product', 'sc-product'], true)) {
                $type_data['is_product']   = true;
                $type_data['source']       = 'surecart';
                $type_data['product_meta'] = ['price', 'stock_status'];
            }

            // ── FluentCart ───────────────────────────────────────────────────
            if (\str_starts_with($slug, 'fluent_cart') || \str_starts_with($slug, 'fct_')) {
                $type_data['is_product']   = true;
                $type_data['source']       = 'fluentcart';
                $type_data['product_meta'] = ['price', 'stock_status'];
            }

            $types[$slug] = $type_data;
        }

        // ── SureCart : vérification via classes si le CPT n'est pas public ───
        if (\class_exists('\SureCart\Models\Product') && !isset($types['sc-product'])) {
            $types['sc-product'] = [
                'slug'         => 'sc-product',
                'label'        => 'SureCart Products',
                'singular'     => 'SureCart Product',
                'rest_base'    => 'sc-product',
                'taxonomies'   => [],
                'is_product'   => true,
                'product_meta' => ['price', 'stock_status'],
                'source'       => 'surecart',
            ];
        }

        return new \WP_REST_Response(\array_values($types), 200);
    }

    // ─── Route : posts ───────────────────────────────────────────────────────

    /**
     * Retourne des articles normalisés depuis n'importe quel CPT.
     *
     * @param  \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function getPosts(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $post_type = $request->get_param('post_type');
        $per_page  = \min((int) $request->get_param('per_page'), 48);
        $page      = (int) $request->get_param('page');
        $search    = (string) $request->get_param('search');
        $taxonomy  = (string) $request->get_param('taxonomy');
        $term      = (int)    $request->get_param('term');
        $orderby   = $request->get_param('orderby');
        $order     = \strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC';

        // Valider orderby — 'price' est géré séparément via meta_value_num (WooCommerce)
        $allowed_orderby = ['date', 'title', 'menu_order', 'rand', 'comment_count', 'modified', 'meta_value_num', 'price'];
        if (!\in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }

        // ── WP_Query ─────────────────────────────────────────────────────────
        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        if (!empty($search)) {
            $query_args['s'] = $search;
        }

        if (!empty($taxonomy) && $term > 0) {
            // Vérifier que la taxonomie appartient bien au post_type demandé (cross-validation REST)
            $post_type_taxonomies = \get_object_taxonomies($post_type);
            if (\in_array($taxonomy, $post_type_taxonomies, true)) {
                $query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => [$term],
                    ],
                ];
            }
        }

        // WooCommerce : méta-tri par prix
        if ($orderby === 'price' && \class_exists('WooCommerce')) {
            $query_args['orderby']  = 'meta_value_num';
            $query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        }

        $query = new \WP_Query($query_args);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = $this->normalizePost($post, $post_type);
        }

        \wp_reset_postdata();

        return new \WP_REST_Response([
            'items'       => $items,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => $page,
        ], 200);
    }

    // ─── Normalisation ───────────────────────────────────────────────────────

    /**
     * Normalise un WP_Post en objet unifié.
     *
     * @param  \WP_Post $post
     * @param  string   $post_type
     * @return array<string, mixed>
     */
    private function normalizePost(\WP_Post $post, string $post_type): array {
        $excerpt = '';
        if (!empty($post->post_excerpt)) {
            $excerpt = \wp_strip_all_tags($post->post_excerpt);
        } else {
            $excerpt = \wp_trim_words(\wp_strip_all_tags(\get_the_content(null, false, $post)), 25);
        }

        $thumbnail = '';
        if (\has_post_thumbnail($post->ID)) {
            $img_data  = \wp_get_attachment_image_src(\get_post_thumbnail_id($post->ID), 'medium_large');
            $thumbnail = $img_data ? $img_data[0] : '';
        }

        // Taxonomies : premier terme de chaque taxo publique
        $badge_terms = [];
        $taxonomies  = \get_object_taxonomies($post_type);
        foreach ($taxonomies as $tax) {
            $terms = \get_the_terms($post->ID, $tax);
            if ($terms && !\is_wp_error($terms)) {
                $badge_terms[$tax] = \array_map(fn($t) => ['id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug], $terms);
            }
        }

        $item = [
            'id'        => $post->ID,
            'title'     => \get_the_title($post),
            'excerpt'   => $excerpt,
            'link'      => \get_permalink($post),
            'date'      => \get_the_date('', $post),
            'date_iso'  => \get_the_date('c', $post),
            'thumbnail' => $thumbnail,
            'terms'     => $badge_terms,
            'is_product'=> false,
            'product'   => null,
        ];

        // ── Enrichissement produits ──────────────────────────────────────────
        if ($post_type === 'product' && \class_exists('WooCommerce')) {
            $item = \array_merge($item, $this->normalizeWooProduct($post->ID));
        } elseif (\in_array($post_type, ['sc-product', 'sc_product'], true) && \class_exists('\SureCart\Models\Product')) {
            $item = \array_merge($item, $this->normalizeSureCartProduct($post->ID));
        } elseif (\str_starts_with($post_type, 'fluent_cart') || \str_starts_with($post_type, 'fct_')) {
            $item = \array_merge($item, $this->normalizeFluentCartProduct($post->ID));
        }

        return $item;
    }

    /** Enrichit avec les données WooCommerce. */
    private function normalizeWooProduct(int $post_id): array {
        $product = \wc_get_product($post_id);
        if (!$product) {
            return ['is_product' => true, 'product' => null];
        }

        return [
            'is_product' => true,
            'thumbnail'  => \get_the_post_thumbnail_url($post_id, 'medium_large') ?: '',
            'link'       => $product->get_permalink(),
            'product'    => [
                'source'          => 'woocommerce',
                'price_html'      => $product->get_price_html(),
                'price'           => (float) $product->get_price(),
                'regular_price'   => (float) $product->get_regular_price(),
                'sale_price'      => (float) $product->get_sale_price(),
                'on_sale'         => $product->is_on_sale(),
                'stock_status'    => $product->get_stock_status(), // valeurs : instock, outofstock, onbackorder
                'sku'             => $product->get_sku(),
                'average_rating'  => (float) $product->get_average_rating(),
                'rating_count'    => (int)   $product->get_rating_count(),
                'add_to_cart_url' => $product->add_to_cart_url(),
            ],
        ];
    }

    /** Enrichit avec les données SureCart. */
    private function normalizeSureCartProduct(int $post_id): array {
        $price    = \get_post_meta($post_id, 'sc_price_amount', true);
        $currency = \get_post_meta($post_id, 'sc_currency', true) ?: 'USD';

        return [
            'is_product' => true,
            'product'    => [
                'source'       => 'surecart',
                'price'        => $price ? (float) $price / 100 : null,
                'price_html'   => $price ? \number_format((float) $price / 100, 2) . ' ' . \strtoupper($currency) : '',
                'stock_status' => \get_post_meta($post_id, 'sc_stock_enabled', true) ? 'instock' : 'outofstock',
                'on_sale'      => false,
            ],
        ];
    }

    /** Enrichit avec les données FluentCart. */
    private function normalizeFluentCartProduct(int $post_id): array {
        $price = \get_post_meta($post_id, '_price', true)
            ?: \get_post_meta($post_id, 'price', true)
            ?: \get_post_meta($post_id, '_regular_price', true);

        return [
            'is_product' => true,
            'product'    => [
                'source'     => 'fluentcart',
                'price'      => $price ? (float) $price : null,
                'price_html' => $price ? \number_format((float) $price, 2) : '',
                'on_sale'    => (bool) \get_post_meta($post_id, '_sale_price', true),
                'stock_status' => 'instock',
            ],
        ];
    }
}
