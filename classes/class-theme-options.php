<?php
/**
 * Page d'options du thème G2RD — Architecture React
 *
 * Ce fichier enregistre la page admin, expose un endpoint REST GET/POST
 * pour la lecture et la sauvegarde des options, et monte l'application
 * React via un div racine. Toute la UI est gérée côté JS.
 *
 * @package    G2RD
 * @since      1.1.0
 * @license    EUPL-1.2
 * @copyright  (c) 2024 Sebastien GERARD
 */

namespace G2RD;

/**
 * Gestion de la page d'options du thème
 */
class ThemeOptions {

    // ── Clés wp_options ───────────────────────────────────────────────────

    private const OPTION_FEATURES         = 'g2rd_theme_features';
    private const OPTION_BLOCKS           = 'g2rd_disabled_blocks';
    private const OPTION_PRICING_BLOCK_SYNC = 'g2rd_pricing_block_sync_v1';
    private const OPTION_COLORS           = 'g2rd_admin_colors';
    private const OPTION_CPTS             = 'g2rd_cpt_settings';
    private const OPTION_COMING_SOON      = 'g2rd_coming_soon';
    private const PAGE_SLUG               = 'g2rd-theme-settings';
    private const REST_NAMESPACE          = 'g2rd/v1';

    // ── Définitions statiques ─────────────────────────────────────────────

    private const CPT_DEFAULTS = [
        'portfolio' => [
            'enabled'       => true,
            'singular'      => 'Projet',
            'plural'        => 'Portfolio',
            'all_items'     => 'Tous les projets',
            'slug'          => 'portfolio',
            'menu_icon'     => 'dashicons-admin-appearance',
            'menu_position' => 5,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'tax_enabled'   => true,
            'tax_singular'  => 'Type de projet',
            'tax_plural'    => 'Types de projets',
            'tax_slug'      => 'type-projets',
        ],
        'prestations' => [
            'enabled'       => true,
            'singular'      => 'Prestation',
            'plural'        => 'Prestations',
            'all_items'     => 'Toutes les prestations',
            'slug'          => 'prestations',
            'menu_icon'     => 'dashicons-clipboard',
            'menu_position' => 6,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'tax_enabled'   => true,
            'tax_singular'  => 'Catégorie de prestation',
            'tax_plural'    => 'Catégories de prestations',
            'tax_slug'      => 'categories-prestations',
        ],
        'qui-sommes-nous' => [
            'enabled'       => true,
            'singular'      => 'Membre',
            'plural'        => 'Qui sommes-nous',
            'all_items'     => "Les membres de l'équipe",
            'slug'          => 'qui-sommes-nous',
            'menu_icon'     => 'dashicons-groups',
            'menu_position' => 7,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'tax_enabled'   => true,
            'tax_singular'  => 'Métier',
            'tax_plural'    => 'Métiers',
            'tax_slug'      => 'metiers',
        ],
    ];

    private const DEFAULT_COLOR_SLUGS = [
        'admin_bg'       => 'primary',
        'admin_text'     => 'white',
        'btn_bg'         => 'secondary',
        'btn_text'       => 'white',
        'btn_bg_hover'   => 'secondary',
        'btn_text_hover' => 'white',
    ];

    private const FEATURES = [
        'gsap_animations' => [
            'label'       => 'Animations GSAP',
            'description' => 'Charge la bibliothèque GSAP et les animations au scroll sur le front-end.',
        ],
        'particles_effect' => [
            'label'       => 'Effet particules',
            'description' => 'Ajoute un effet de particules animées sur les blocs groupe.',
        ],
        'glass_effect' => [
            'label'       => 'Effet glassmorphism',
            'description' => 'Active l\'option glassmorphism dans l\'éditeur de blocs.',
        ],
        'clickable_articles' => [
            'label'       => 'Articles entièrement cliquables',
            'description' => 'Rend les cartes d\'articles entièrement cliquables.',
        ],
        'accessibility' => [
            'label'       => 'Panneau d\'accessibilité',
            'description' => 'Ajoute un bouton flottant donnant accès aux options d\'accessibilité (taille du texte, contraste, animations, etc.).',
        ],
        'dark_mode' => [
            'label'       => 'Mode sombre (Dark Mode)',
            'description' => 'Ajoute un bouton flottant pour basculer en mode sombre. Respecte la préférence système.',
        ],
        'enable_ai' => [
            'label'       => 'Intégration IA / MCP (WordPress Abilities API)',
            'description' => 'Expose les CPTs et la configuration du thème à des outils IA compatibles MCP. Désactivé par défaut.',
        ],
        'patterns_require_license' => [
            'label'       => 'Compositions G2RD nécessitent une licence',
            'description' => 'Masque les compositions G2RD personnalisées lorsque la licence n\'est pas active.',
        ],
    ];

    private const FEATURE_DEFAULTS = [
        'enable_ai'                => false,
        'patterns_require_license' => false,
    ];

    private const BLOCKS = [
        'g2rd/advanced-heading'  => [ 'title' => 'Titre avancé',          'icon' => 'heading' ],
        'g2rd/advanced-list'     => [ 'title' => 'Liste avancée',          'icon' => 'editor-ul' ],
        'g2rd/charts'            => [ 'title' => 'Graphiques',             'icon' => 'chart-bar' ],
        'g2rd/dynamic-content'   => [ 'title' => 'Contenu dynamique',      'icon' => 'database' ],
        'g2rd/faq'               => [ 'title' => 'FAQ (accordéon + GEO)',   'icon' => 'editor-help' ],
        'g2rd/breadcrumb'        => [ 'title' => 'Fil d\'Ariane',          'icon' => 'arrow-right-alt' ],
        'g2rd/card'              => [ 'title' => 'Carte',                  'icon' => 'id-alt' ],
        'g2rd/carousel'          => [ 'title' => 'Carrousel',              'icon' => 'slides' ],
        'g2rd/code'              => [ 'title' => 'Bloc de code',           'icon' => 'editor-code' ],
        'g2rd/countdown'         => [ 'title' => 'Compte à rebours',       'icon' => 'clock' ],
        'g2rd/counter'           => [ 'title' => 'Compteur animé',         'icon' => 'chart-bar' ],
        'g2rd/device-mockup'     => [ 'title' => 'Device Mockup',          'icon' => 'smartphone' ],
        'g2rd/filterable-grid'   => [ 'title' => 'Grille filtrable',       'icon' => 'grid-view' ],
        'g2rd/icon-box'          => [ 'title' => 'Icône Box',              'icon' => 'star-filled' ],
        'g2rd/info'              => [ 'title' => 'Bloc Info',              'icon' => 'info' ],
        'g2rd/map'               => [ 'title' => 'Carte interactive',      'icon' => 'location' ],
        'g2rd/marquee'           => [ 'title' => 'Marquee (défilement)',   'icon' => 'text' ],
        'g2rd/modal'             => [ 'title' => 'Fenêtre modale',         'icon' => 'editor-expand' ],
        'g2rd/pricing-table'     => [ 'title' => 'Tableau de prix',        'icon' => 'money' ],
        'g2rd/progress-bar'      => [ 'title' => 'Barre de progression',   'icon' => 'minus' ],
        'g2rd/share-buttons'     => [ 'title' => 'Boutons de partage',     'icon' => 'share' ],
        'g2rd/slider'            => [ 'title' => 'Slider',                 'icon' => 'images-alt2' ],
        'g2rd/sliding-panel'     => [ 'title' => 'Panneau coulissant',     'icon' => 'arrow-right-alt2' ],
        'g2rd/table-of-contents' => [ 'title' => 'Table des matières',    'icon' => 'list-view' ],
        'g2rd/toggle-content'    => [ 'title' => 'Toggle Content',         'icon' => 'plus-alt2' ],
        'g2rd/toolbars'          => [ 'title' => 'Toolbars',               'icon' => 'admin-tools' ],
        'g2rd/typed'             => [ 'title' => 'Texte animé (Typed)',    'icon' => 'edit' ],
        'g2rd/block-api'         => [ 'title' => 'Connecteur API',         'icon' => 'rest-api' ],
        'g2rd/container'         => [ 'title' => 'Conteneur responsive',   'icon' => 'layout' ],
        'g2rd/bases'             => [ 'title' => 'Blocs de base G2RD',     'icon' => 'layout' ],
    ];

    /** @var string|false|null Hook suffix stocké après add_theme_page(). */
    private $hook_suffix = null;

    // ── Helpers statiques (utilisés par d'autres classes du thème) ────────

    /**
     * Retourne vrai si la fonctionnalité est activée dans les options.
     *
     * @param string $key Clé de la fonctionnalité.
     * @return bool
     */
    public static function isFeatureEnabled( string $key ): bool {
        $features = (array) \get_option( self::OPTION_FEATURES, [] );
        if ( isset( $features[ $key ] ) ) {
            return (bool) $features[ $key ];
        }
        return self::FEATURE_DEFAULTS[ $key ] ?? true;
    }

    /**
     * Retourne vrai si le bloc est dans la liste des blocs désactivés.
     *
     * @param string $block_name Nom complet du bloc (ex. g2rd/carousel).
     * @return bool
     */
    public static function isBlockDisabled( string $block_name ): bool {
        $disabled = (array) \get_option( self::OPTION_BLOCKS, [] );
        return \in_array( $block_name, $disabled, true );
    }

    /**
     * Retourne les paramètres d'un CPT en fusionnant les valeurs sauvegardées avec les valeurs par défaut.
     *
     * @param string $cpt_key Clé du CPT (portfolio, prestations, qui-sommes-nous).
     * @return array<string, mixed>
     */
    public static function getCPTSettings( string $cpt_key ): array {
        $defaults  = self::CPT_DEFAULTS[ $cpt_key ] ?? [];
        $saved_all = (array) \get_option( self::OPTION_CPTS, [] );
        $saved_cpt = \is_array( $saved_all[ $cpt_key ] ?? null ) ? $saved_all[ $cpt_key ] : [];
        return \array_merge( $defaults, $saved_cpt );
    }

    /**
     * Retourne vrai si le CPT est activé dans les options.
     *
     * @param string $cpt_key Clé du CPT.
     * @return bool
     */
    public static function isCPTEnabled( string $cpt_key ): bool {
        return (bool) ( self::getCPTSettings( $cpt_key )['enabled'] ?? true );
    }

    // ── Hooks ─────────────────────────────────────────────────────────────

    /**
     * Enregistre les hooks WordPress.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'admin_init',   [ $this, 'maybeSyncPricingTableBlock' ], 1 );
        \add_action( 'admin_menu',   [ $this, 'registerAdminPage' ] );
        \add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        \add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
    }

    /**
     * Synchronise une seule fois le bloc PricingTable dans la liste des blocs désactivés.
     *
     * @return void
     */
    public function maybeSyncPricingTableBlock(): void {
        if ( \get_option( self::OPTION_PRICING_BLOCK_SYNC, '' ) ) {
            return;
        }
        $pricing = 'g2rd/pricing-table';
        if ( ! isset( self::BLOCKS[ $pricing ] ) ) {
            \update_option( self::OPTION_PRICING_BLOCK_SYNC, '1' );
            return;
        }
        $disabled = (array) \get_option( self::OPTION_BLOCKS, [] );
        if ( \in_array( $pricing, $disabled, true ) ) {
            \update_option( self::OPTION_PRICING_BLOCK_SYNC, '1' );
            return;
        }
        foreach ( \array_keys( self::BLOCKS ) as $name ) {
            if ( $name === $pricing ) {
                continue;
            }
            if ( ! \in_array( $name, $disabled, true ) ) {
                \update_option( self::OPTION_PRICING_BLOCK_SYNC, '1' );
                return;
            }
        }
        $disabled[] = $pricing;
        \update_option( self::OPTION_BLOCKS, $disabled );
        \update_option( self::OPTION_PRICING_BLOCK_SYNC, '1' );
    }

    /**
     * Enregistre la page d'options dans le menu Apparence de WordPress.
     *
     * @return void
     */
    public function registerAdminPage(): void {
        $this->hook_suffix = \add_theme_page(
            \__( 'Options du thème G2RD', 'g2rd' ),
            \__( 'Options G2RD', 'g2rd' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'renderPage' ]
        );
    }

    /**
     * Charge les assets de la page d'options (JS + CSS React) uniquement sur la page admin du thème.
     *
     * @param string $hook Hook suffix de la page admin courante.
     * @return void
     */
    public function enqueueAssets( string $hook ): void {
        if ( ! $this->hook_suffix || $hook !== $this->hook_suffix ) {
            return;
        }

        $dir_path = \get_template_directory();
        $dir_uri  = \get_template_directory_uri();
        $js_path  = $dir_path . '/blocks/g2rd-options-page/build/index.js';
        $css_path = $dir_path . '/blocks/g2rd-options-page/build/style-index.css';

        if ( ! \file_exists( $js_path ) ) {
            return;
        }

        if ( \file_exists( $css_path ) ) {
            \wp_enqueue_style(
                'g2rd-options-page',
                $dir_uri . '/blocks/g2rd-options-page/build/style-index.css',
                [ 'wp-components', 'dashicons' ],
                (string) \filemtime( $css_path )
            );
        }

        \wp_enqueue_media();

        \wp_enqueue_script(
            'g2rd-options-page',
            $dir_uri . '/blocks/g2rd-options-page/build/index.js',
            [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-compose', 'react', 'react-dom' ],
            (string) \filemtime( $js_path ),
            true
        );

        \wp_set_script_translations( 'g2rd-options-page', 'g2rd' );

        // Données initiales transmises à l'app React
        \wp_localize_script( 'g2rd-options-page', 'G2RDOptionsData', $this->get_initial_data() );
    }

    /** Rendu de la page : point de montage React uniquement. */
    public function renderPage(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div id="g2rd-options-root" class="wrap"></div>';
    }

    // ── REST API ──────────────────────────────────────────────────────────

    /**
     * Enregistre les routes REST GET et POST pour la lecture et la sauvegarde des options.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        \register_rest_route(
            self::REST_NAMESPACE,
            '/settings',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'rest_get_settings' ],
                    'permission_callback' => static fn() => \current_user_can( 'manage_options' ),
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'rest_save_settings' ],
                    'permission_callback' => static fn() => \current_user_can( 'manage_options' ),
                    'args'                => [
                        'settings' => [
                            'required' => true,
                            'type'     => 'object',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Callback REST GET — retourne les options actuelles.
     *
     * @return \WP_REST_Response
     */
    public function rest_get_settings(): \WP_REST_Response {
        return new \WP_REST_Response( $this->get_current_settings(), 200 );
    }

    /**
     * Callback REST POST — valide, sanitise et sauvegarde toutes les options du thème.
     *
     * @param \WP_REST_Request $request Requête REST entrante.
     * @return \WP_REST_Response
     */
    public function rest_save_settings( \WP_REST_Request $request ): \WP_REST_Response {
        $data = (array) ( $request->get_param( 'settings' ) ?? [] );

        // --- Fonctionnalités ---
        $features_raw = \is_array( $data['features'] ?? null ) ? $data['features'] : [];
        $features     = [];
        foreach ( \array_keys( self::FEATURES ) as $key ) {
            $features[ $key ] = ! empty( $features_raw[ $key ] ) ? 1 : 0;
        }
        \update_option( self::OPTION_FEATURES, $features );

        // --- Blocs désactivés (nécessite licence) ---
        if ( LicenseManager::is_active() ) {
            $blocks_raw      = \is_array( $data['disabledBlocks'] ?? null ) ? $data['disabledBlocks'] : [];
            $valid_blocks    = \array_keys( self::BLOCKS );
            $disabled_blocks = \array_values(
                \array_filter(
                    \array_map( 'sanitize_text_field', $blocks_raw ),
                    static fn( $b ) => \in_array( $b, $valid_blocks, true )
                )
            );
            \update_option( self::OPTION_BLOCKS, $disabled_blocks );
        }

        // --- Couleurs admin ---
        $palette_raw = \wp_get_global_settings( [ 'color', 'palette', 'theme' ] );
        $valid_slugs = \array_column( (array) $palette_raw, 'slug' );
        $colors_raw  = \is_array( $data['colors'] ?? null ) ? $data['colors'] : [];
        $colors      = [];
        foreach ( \array_keys( self::DEFAULT_COLOR_SLUGS ) as $slot ) {
            $submitted    = \sanitize_text_field( (string) ( $colors_raw[ $slot ] ?? '' ) );
            $colors[ $slot ] = \in_array( $submitted, $valid_slugs, true )
                ? $submitted
                : self::DEFAULT_COLOR_SLUGS[ $slot ];
        }
        \update_option( self::OPTION_COLORS, $colors );

        // --- CPTs ---
        $cpts_raw = \is_array( $data['cpts'] ?? null ) ? $data['cpts'] : [];
        $cpts     = [];
        foreach ( \array_keys( self::CPT_DEFAULTS ) as $cpt_key ) {
            $cpt_raw = \is_array( $cpts_raw[ $cpt_key ] ?? null ) ? $cpts_raw[ $cpt_key ] : [];
            $def     = self::CPT_DEFAULTS[ $cpt_key ];
            $cpts[ $cpt_key ] = [
                'enabled'       => ! empty( $cpt_raw['enabled'] ),
                'singular'      => \sanitize_text_field( (string) ( $cpt_raw['singular']     ?? $def['singular'] ) ),
                'plural'        => \sanitize_text_field( (string) ( $cpt_raw['plural']       ?? $def['plural'] ) ),
                'all_items'     => \sanitize_text_field( (string) ( $cpt_raw['all_items']    ?? $def['all_items'] ) ),
                'slug'          => \sanitize_title( (string) ( $cpt_raw['slug']              ?? $def['slug'] ) ),
                'menu_icon'     => \sanitize_text_field( (string) ( $cpt_raw['menu_icon']    ?? $def['menu_icon'] ) ),
                'menu_position' => \absint( $cpt_raw['menu_position']                         ?? $def['menu_position'] ),
                'has_archive'   => ! empty( $cpt_raw['has_archive'] ),
                'show_in_rest'  => ! empty( $cpt_raw['show_in_rest'] ),
                'tax_enabled'   => ! empty( $cpt_raw['tax_enabled'] ),
                'tax_singular'  => \sanitize_text_field( (string) ( $cpt_raw['tax_singular'] ?? $def['tax_singular'] ) ),
                'tax_plural'    => \sanitize_text_field( (string) ( $cpt_raw['tax_plural']   ?? $def['tax_plural'] ) ),
                'tax_slug'      => \sanitize_title( (string) ( $cpt_raw['tax_slug']          ?? $def['tax_slug'] ) ),
            ];
        }
        \update_option( self::OPTION_CPTS, $cpts );

        // --- Mode Bientôt disponible ---
        $cs_raw = \is_array( $data['comingSoon'] ?? null ) ? $data['comingSoon'] : [];
        \update_option( self::OPTION_COMING_SOON, [
            'enabled' => ! empty( $cs_raw['enabled'] ),
            'page_id' => \absint( $cs_raw['page_id'] ?? 0 ),
        ] );

        // --- Mode Business ---
        $allowed_types   = [ 'vitrine', 'leads', 'ecommerce', '' ];
        $business_type   = \sanitize_text_field( (string) ( $data['businessType'] ?? '' ) );
        \update_option( 'g2rd_business_type', \in_array( $business_type, $allowed_types, true ) ? $business_type : '' );

        // --- Mode Client ---
        \update_option( 'g2rd_client_mode',         ! empty( $data['clientMode'] ) ? 1 : 0 );
        \update_option( 'g2rd_client_mode_message',  \sanitize_textarea_field( (string) ( $data['clientMessage'] ?? '' ) ) );

        // --- SEO Helper ---
        \update_option( 'g2rd_seo_helper', ! empty( $data['seoHelper'] ) ? 1 : 0 );

        // --- GEO Helper ---
        \update_option( 'g2rd_geo_helper', ! empty( $data['geoHelper'] ) ? 1 : 0 );

        // --- Clé API Google Maps ---
        if ( isset( $data['googleMapsApiKey'] ) ) {
            $raw_key = \sanitize_text_field( (string) $data['googleMapsApiKey'] );
            if ( '' !== $raw_key ) {
                \update_option( 'g2rd_google_maps_api_key', $raw_key );
            }
        }

        // --- Page de connexion ---
        if ( \is_array( $data['loginSettings'] ?? null ) ) {
            LoginCustomizer::save_settings( $data['loginSettings'] );
        }

        // Réécriture des règles de routage (slugs CPT)
        \update_option( 'g2rd_needs_rewrite_flush', 1 );

        return new \WP_REST_Response( [
            'success'  => true,
            'settings' => $this->get_current_settings(),
        ], 200 );
    }

    // ── Helpers privés ────────────────────────────────────────────────────

    /** Retourne toutes les options actuelles sous forme normalisée. */
    private function get_current_settings(): array {
        return [
            'features'       => (array) \get_option( self::OPTION_FEATURES, [] ),
            'disabledBlocks' => (array) \get_option( self::OPTION_BLOCKS, [] ),
            'colors'         => (array) \get_option( self::OPTION_COLORS, self::DEFAULT_COLOR_SLUGS ),
            'cpts'           => (array) \get_option( self::OPTION_CPTS, [] ),
            'comingSoon'     => (array) \get_option( self::OPTION_COMING_SOON, [ 'enabled' => false, 'page_id' => 0 ] ),
            'businessType'   => (string) \get_option( 'g2rd_business_type', '' ),
            'clientMode'     => (bool) \get_option( 'g2rd_client_mode', 0 ),
            'clientMessage'  => (string) \get_option( 'g2rd_client_mode_message', '' ),
            'seoHelper'      => (bool) \get_option( 'g2rd_seo_helper', 1 ),
            'geoHelper'      => (bool) \get_option( 'g2rd_geo_helper', 1 ),
            'loginSettings'      => LoginCustomizer::get_settings(),
            'googleMapsApiKeySet' => '' !== (string) \get_option( 'g2rd_google_maps_api_key', '' ),
        ];
    }

    /**
     * Données transmises à l'app React via wp_localize_script.
     *
     * @return array<string, mixed>
     */
    private function get_initial_data(): array {
        // Palette de couleurs du thème (theme.json)
        $palette_raw = \wp_get_global_settings( [ 'color', 'palette', 'theme' ] );
        $palette     = \array_map(
            static fn( $color ) => [
                'slug'  => $color['slug'] ?? '',
                'name'  => $color['name'] ?? '',
                'color' => $color['color'] ?? '',
            ],
            (array) $palette_raw
        );

        // Liste des pages publiées pour le sélecteur "Mode bientôt disponible"
        $pages = \get_posts( [
            'post_type'      => 'page',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ] );
        $pages_list = \array_map(
            static fn( $id ) => [ 'id' => $id, 'title' => \get_the_title( $id ) ],
            (array) $pages
        );

        return [
            'restUrl'         => \rest_url( self::REST_NAMESPACE . '/settings' ),
            'licenseRestUrl'  => \rest_url( self::REST_NAMESPACE . '/license' ),
            'nonce'           => \wp_create_nonce( 'wp_rest' ),
            'version'         => (string) \wp_get_theme()->get( 'Version' ),
            'themeUri'        => \get_template_directory_uri(),
            'onboardingUrl'   => \admin_url( 'admin.php?page=g2rd-onboarding' ),
            'settings'        => $this->get_current_settings(),
            'features'        => self::FEATURES,
            'featureDefaults' => self::FEATURE_DEFAULTS,
            'blocks'          => self::BLOCKS,
            'cptDefaults'     => self::CPT_DEFAULTS,
            'palette'         => $palette,
            'pages'           => $pages_list,
            'licensed'          => LicenseManager::is_active(),
            'licenseData'       => LicenseManager::get_display_data(),
            'licenseServerMode'      => LicenseServer::is_server_mode(),
            'licenseAdminUrl'        => LicenseServer::is_server_mode() ? \rest_url( 'g2rd/v1/license-admin' ) : null,
            'googleReviewsClearUrl'  => \rest_url( 'g2rd/v1/google-reviews/cache' ),
        ];
    }
}
