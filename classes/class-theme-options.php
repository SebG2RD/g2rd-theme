<?php
/**
 * Page d'options du thème G2RD
 *
 * Gère la page d'administration permettant d'activer/désactiver
 * les fonctionnalités du thème et les blocs Gutenberg personnalisés.
 * Accessible depuis Apparence → Options G2RD (administrateurs uniquement).
 *
 * @package G2RD
 * @since   1.1.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 */

namespace G2RD;

/**
 * Gestion de la page d'options du thème
 *
 * @package G2RD
 * @since   1.1.0
 */
class ThemeOptions {
    /** @var string Clé wp_options pour les fonctionnalités du thème */
    private const OPTION_FEATURES = 'g2rd_theme_features';

    /** @var string Clé wp_options pour la liste des blocs désactivés */
    private const OPTION_BLOCKS = 'g2rd_disabled_blocks';

    /**
     * Marqueur : migration « tableau de prix » ajouté à la liste des blocs gérés (une fois par site).
     *
     * @var string
     */
    private const OPTION_PRICING_BLOCK_SYNC = 'g2rd_pricing_block_sync_v1';

    /** @var string Clé wp_options pour les couleurs de l'interface admin */
    private const OPTION_COLORS = 'g2rd_admin_colors';

    /** @var string Clé wp_options pour les paramètres des CPT */
    private const OPTION_CPTS = 'g2rd_cpt_settings';

    /** @var string Clé wp_options pour le mode « Bientôt disponible » */
    private const OPTION_COMING_SOON = 'g2rd_coming_soon';

    /**
     * Paramètres par défaut de chaque CPT.
     * Clé principale = post_type slug (immuable). Toutes les valeurs sont
     * modifiables depuis la page d'options, sauf la clé elle-même.
     */
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

    /**
     * Slugs de couleur par défaut (doivent correspondre à la palette theme.json).
     * Clé = identifiant du slot, valeur = slug de couleur.
     */
    private const DEFAULT_COLOR_SLUGS = [
        'admin_bg'   => 'primary',
        'admin_text' => 'white',
        'btn_bg'     => 'secondary',
        'btn_text'   => 'white',
    ];

    /** @var string Slug de la page admin (déjà référencé dans functions.php) */
    private const PAGE_SLUG = 'g2rd-theme-settings';

    /**
     * Fonctionnalités optionnelles du thème.
     * Clé = identifiant interne, valeur = [label, description].
     */
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
        'dark_mode' => [
            'label'       => 'Mode sombre (Dark Mode)',
            'description' => 'Ajoute un bouton flottant pour basculer en mode sombre. Respecte la préférence système.',
        ],
        'enable_ai' => [
            'label'       => 'Intégration IA / MCP (WordPress Abilities API)',
            'description' => 'Expose les CPTs et la configuration du thème à des outils IA compatibles MCP (Claude Desktop, Cursor…). Requiert WordPress 6.9+. <strong>Désactivé par défaut</strong> — activez uniquement si vous utilisez un client MCP de confiance.',
        ],
        'patterns_require_license' => [
            'label'       => 'Compositions G2RD nécessitent une licence',
            'description' => 'Masque les compositions utilisant des blocs G2RD personnalisés lorsque la licence n\'est pas active.',
        ],
    ];

    /**
     * Fonctionnalités désactivées par défaut (opt-in).
     * Toutes les clés non listées ici sont activées par défaut.
     */
    private const FEATURE_DEFAULTS = [
        'enable_ai'                => false,
        'patterns_require_license' => false,
    ];

    /**
     * Blocs Gutenberg personnalisés du thème.
     * Clé = name dans block.json, valeur = [title, icon].
     */
    private const BLOCKS = [
        'g2rd/advanced-heading'  => ['title' => 'Titre avancé',          'icon' => 'heading'],
        'g2rd/advanced-list'     => ['title' => 'Liste avancée',          'icon' => 'editor-ul'],
        'g2rd/charts'            => ['title' => 'Graphiques',             'icon' => 'chart-bar'],
        'g2rd/dynamic-content'   => ['title' => 'Contenu dynamique',     'icon' => 'database'],
        'g2rd/faq'               => ['title' => 'FAQ Accordéon',         'icon' => 'editor-help'],
        'g2rd/breadcrumb'        => ['title' => 'Fil d\'Ariane',         'icon' => 'arrow-right-alt'],
        'g2rd/card'              => ['title' => 'Carte',                 'icon' => 'id-alt'],
        'g2rd/carousel'          => ['title' => 'Carrousel',             'icon' => 'slides'],
        'g2rd/code'              => ['title' => 'Bloc de code',          'icon' => 'editor-code'],
        'g2rd/countdown'         => ['title' => 'Compte à rebours',      'icon' => 'clock'],
        'g2rd/counter'           => ['title' => 'Compteur animé',        'icon' => 'chart-bar'],
        'g2rd/device-mockup'     => ['title' => 'Device Mockup',         'icon' => 'smartphone'],
        'g2rd/filterable-grid'   => ['title' => 'Grille filtrable',      'icon' => 'grid-view'],
        'g2rd/icon-box'          => ['title' => 'Icône Box',             'icon' => 'star-filled'],
        'g2rd/info'              => ['title' => 'Bloc Info',             'icon' => 'info'],
        'g2rd/map'               => ['title' => 'Carte interactive',     'icon' => 'location'],
        'g2rd/marquee'           => ['title' => 'Marquee (défilement)',  'icon' => 'text'],
        'g2rd/modal'             => ['title' => 'Fenêtre modale',        'icon' => 'editor-expand'],
        'g2rd/pricing-table'     => ['title' => 'Tableau de prix',       'icon' => 'money'],
        'g2rd/progress-bar'      => ['title' => 'Barre de progression',  'icon' => 'minus'],
        'g2rd/share-buttons'     => ['title' => 'Boutons de partage',    'icon' => 'share'],
        'g2rd/slider'            => ['title' => 'Slider',                'icon' => 'images-alt2'],
        'g2rd/sliding-panel'     => ['title' => 'Panneau coulissant',    'icon' => 'arrow-right-alt2'],
        'g2rd/table-of-contents' => ['title' => 'Table des matières',   'icon' => 'list-view'],
        'g2rd/toggle-content'    => ['title' => 'Toggle Content',        'icon' => 'plus-alt2'],
        'g2rd/toolbars'          => ['title' => 'Toolbars',              'icon' => 'admin-tools'],
        'g2rd/typed'             => ['title' => 'Texte animé (Typed)',   'icon' => 'edit'],
        'g2rd/block-api'         => ['title' => 'Connecteur API',         'icon' => 'rest-api'],
        'g2rd/container'         => ['title' => 'Conteneur responsive',    'icon' => 'layout'],
        'g2rd/bases'             => ['title' => 'Blocs de base G2RD',    'icon' => 'layout'],
    ];

    /** @var string|false|null Hook suffix stocké après add_theme_page(). */
    private $hook_suffix = null;

    // -------------------------------------------------------------------------
    // Helpers statiques
    // -------------------------------------------------------------------------

    /**
     * Indique si une fonctionnalité du thème est activée.
     * Par défaut, les fonctionnalités sont activées sauf celles listées dans FEATURE_DEFAULTS.
     *
     * @param  string $key Identifiant de la fonctionnalité.
     * @return bool
     */
    public static function isFeatureEnabled(string $key): bool {
        $features = (array) \get_option(self::OPTION_FEATURES, []);
        if ( isset( $features[$key] ) ) {
            return (bool) $features[$key];
        }
        // Respecter la valeur par défaut définie dans FEATURE_DEFAULTS (opt-in = false).
        // Toute clé absente de FEATURE_DEFAULTS est considérée activée par défaut.
        return self::FEATURE_DEFAULTS[$key] ?? true;
    }

    /**
     * Indique si un bloc est désactivé dans la page d'options.
     *
     * @param  string $block_name Nom complet du bloc (ex : "g2rd/carousel").
     * @return bool
     */
    public static function isBlockDisabled(string $block_name): bool {
        $disabled = (array) \get_option(self::OPTION_BLOCKS, []);
        return \in_array($block_name, $disabled, true);
    }

    /**
     * Retourne les paramètres fusionnés (défauts + sauvegardés) d'un CPT.
     *
     * @param  string $cpt_key Identifiant du post type (ex : "portfolio").
     * @return array<string, mixed>
     */
    public static function getCPTSettings(string $cpt_key): array {
        $defaults  = self::CPT_DEFAULTS[$cpt_key] ?? [];
        $saved_all = (array) \get_option(self::OPTION_CPTS, []);
        $saved_cpt = \is_array($saved_all[$cpt_key] ?? null) ? $saved_all[$cpt_key] : [];
        return \array_merge($defaults, $saved_cpt);
    }

    /**
     * Indique si un CPT est activé.
     *
     * @param  string $cpt_key Identifiant du post type.
     * @return bool
     */
    public static function isCPTEnabled(string $cpt_key): bool {
        return (bool) (self::getCPTSettings($cpt_key)['enabled'] ?? true);
    }

    // -------------------------------------------------------------------------
    // Hooks WordPress
    // -------------------------------------------------------------------------

    /**
     * Enregistre tous les hooks nécessaires.
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action('admin_init', [$this, 'maybeSyncPricingTableBlock'], 1);
        \add_action('admin_menu', [$this, 'registerAdminPage']);
        \add_action('admin_post_g2rd_save_options', [$this, 'saveOptions']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Si tous les autres blocs G2RD étaient déjà désactivés mais pas le tableau de prix
     * (ancienne version sans ce bloc dans la liste), on ajoute ce bloc aux désactivés une fois.
     *
     * @return void
     */
    public function maybeSyncPricingTableBlock(): void {
        if (\get_option(self::OPTION_PRICING_BLOCK_SYNC, '')) {
            return;
        }

        $pricing = 'g2rd/pricing-table';
        if (!isset(self::BLOCKS[$pricing])) {
            \update_option(self::OPTION_PRICING_BLOCK_SYNC, '1');
            return;
        }

        $disabled = (array) \get_option(self::OPTION_BLOCKS, []);

        if (\in_array($pricing, $disabled, true)) {
            \update_option(self::OPTION_PRICING_BLOCK_SYNC, '1');
            return;
        }

        foreach (\array_keys(self::BLOCKS) as $name) {
            if ($name === $pricing) {
                continue;
            }
            if (!\in_array($name, $disabled, true)) {
                \update_option(self::OPTION_PRICING_BLOCK_SYNC, '1');
                return;
            }
        }

        $disabled[] = $pricing;
        \update_option(self::OPTION_BLOCKS, $disabled);
        \update_option(self::OPTION_PRICING_BLOCK_SYNC, '1');
    }

    /**
     * Ajoute la page sous Apparence dans le menu WordPress.
     *
     * @return void
     */
    public function registerAdminPage(): void {
        $this->hook_suffix = \add_theme_page(
            \__('Options du thème G2RD', 'g2rd'),
            \__('Options G2RD', 'g2rd'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    /**
     * Charge le CSS et le JS uniquement sur la page d'options.
     *
     * Utilise le hook suffix retourné par add_theme_page() pour une comparaison fiable.
     *
     * @param  string $hook Identifiant de la page admin courante.
     * @return void
     */
    public function enqueueAssets(string $hook): void {
        if ( ! $this->hook_suffix || $hook !== $this->hook_suffix ) {
            return;
        }

        $dir_uri  = \get_template_directory_uri();
        $dir_path = \get_template_directory();

        \wp_enqueue_style(
            'g2rd-admin-options',
            $dir_uri . '/assets/css/admin-options.css',
            [ 'dashicons' ],
            \filemtime( $dir_path . '/assets/css/admin-options.css' )
        );

        \wp_enqueue_script(
            'g2rd-admin-options',
            $dir_uri . '/assets/js/admin-options.js',
            [],
            \filemtime( $dir_path . '/assets/js/admin-options.js' ),
            true
        );
    }

    // -------------------------------------------------------------------------
    // Sauvegarde
    // -------------------------------------------------------------------------

    /**
     * Traite la soumission du formulaire (action POST admin).
     *
     * @return void
     */
    public function saveOptions(): void {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Accès refusé.', 'g2rd'), 403);
        }

        \check_admin_referer('g2rd_save_options', 'g2rd_nonce');

        // --- Fonctionnalités ---
        $features = [];
        foreach (\array_keys(self::FEATURES) as $key) {
            $features[$key] = isset($_POST['features'][$key]) ? 1 : 0;
        }
        \update_option(self::OPTION_FEATURES, $features);

        // --- Blocs désactivés (case décochée = bloc désactivé) ---
        // Modification refusée si la licence n'est pas active.
        if (LicenseManager::is_active()) {
            $disabled_blocks = [];
            foreach (\array_keys(self::BLOCKS) as $block_name) {
                if (!isset($_POST['blocks'][$block_name])) {
                    $disabled_blocks[] = \sanitize_text_field($block_name);
                }
            }
            \update_option(self::OPTION_BLOCKS, $disabled_blocks);
        }

        // --- Couleurs admin ---
        $palette_raw = \wp_get_global_settings(['color', 'palette', 'theme']);
        $valid_slugs = \array_column((array) $palette_raw, 'slug');
        $colors      = [];
        foreach (\array_keys(self::DEFAULT_COLOR_SLUGS) as $slot) {
            $submitted    = isset($_POST['colors'][$slot])
                ? \sanitize_text_field(\wp_unslash($_POST['colors'][$slot]))
                : '';
            $colors[$slot] = \in_array($submitted, $valid_slugs, true)
                ? $submitted
                : self::DEFAULT_COLOR_SLUGS[$slot];
        }
        \update_option(self::OPTION_COLORS, $colors);

        // --- CPT ---
        $cpts = [];
        foreach (\array_keys(self::CPT_DEFAULTS) as $cpt_key) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- chaque champ est sanitisé individuellement ci-dessous via sanitize_text_field/sanitize_title/absint
            $cpt_raw  = isset($_POST['cpts'][$cpt_key]) ? \wp_unslash($_POST['cpts'][$cpt_key]) : null;
            $cpt_post = \is_array($cpt_raw) ? $cpt_raw : [];
            $def = self::CPT_DEFAULTS[$cpt_key];
            $cpts[$cpt_key] = [
                'enabled'       => !empty($cpt_post['enabled']),
                'singular'      => \sanitize_text_field(\wp_unslash($cpt_post['singular']     ?? $def['singular'])),
                'plural'        => \sanitize_text_field(\wp_unslash($cpt_post['plural']       ?? $def['plural'])),
                'all_items'     => \sanitize_text_field(\wp_unslash($cpt_post['all_items']    ?? $def['all_items'])),
                'slug'          => \sanitize_title(\wp_unslash($cpt_post['slug']              ?? $def['slug'])),
                'menu_icon'     => \sanitize_text_field(\wp_unslash($cpt_post['menu_icon']    ?? $def['menu_icon'])),
                'menu_position' => \absint($cpt_post['menu_position']                         ?? $def['menu_position']),
                'has_archive'   => !empty($cpt_post['has_archive']),
                'show_in_rest'  => !empty($cpt_post['show_in_rest']),
                'tax_enabled'   => !empty($cpt_post['tax_enabled']),
                'tax_singular'  => \sanitize_text_field(\wp_unslash($cpt_post['tax_singular'] ?? $def['tax_singular'])),
                'tax_plural'    => \sanitize_text_field(\wp_unslash($cpt_post['tax_plural']   ?? $def['tax_plural'])),
                'tax_slug'      => \sanitize_title(\wp_unslash($cpt_post['tax_slug']          ?? $def['tax_slug'])),
            ];
        }
        \update_option(self::OPTION_CPTS, $cpts);

        // --- Mode « Bientôt disponible » ---
        $coming_soon = [
            'enabled' => !empty($_POST['coming_soon']['enabled']),
            'page_id' => \absint($_POST['coming_soon']['page_id'] ?? 0),
        ];
        \update_option(self::OPTION_COMING_SOON, $coming_soon);

        // --- Mode Business ---
        $business_type = \sanitize_text_field(\wp_unslash($_POST['g2rd_business_type'] ?? ''));
        $allowed_types = [ 'vitrine', 'leads', 'ecommerce', '' ];
        \update_option( 'g2rd_business_type', \in_array( $business_type, $allowed_types, true ) ? $business_type : '' );

        // --- Mode Client ---
        \update_option( 'g2rd_client_mode', !empty( $_POST['g2rd_client_mode'] ) ? 1 : 0 );
        $client_msg = \sanitize_textarea_field( \wp_unslash( $_POST['g2rd_client_mode_message'] ?? '' ) );
        \update_option( 'g2rd_client_mode_message', $client_msg );

        // --- SEO Helper ---
        \update_option( 'g2rd_seo_helper', !empty( $_POST['g2rd_seo_helper'] ) ? 1 : 0 );

        // Planifier le vidage des règles de réécriture (slugs CPT potentiellement modifiés)
        \update_option('g2rd_needs_rewrite_flush', 1);

        $allowed_tabs = [ 'configuration', 'contenu', 'editeur', 'clients', 'maintenance' ];
        $tab          = isset( $_POST['_tab'] ) ? \sanitize_key( \wp_unslash( $_POST['_tab'] ) ) : 'configuration';
        $tab          = \in_array( $tab, $allowed_tabs, true ) ? $tab : 'configuration';

        \wp_safe_redirect(
            \add_query_arg(
                [ 'page' => self::PAGE_SLUG, 'tab' => $tab, 'saved' => '1' ],
                \admin_url( 'themes.php' )
            )
        );
        exit;
    }

    // -------------------------------------------------------------------------
    // Rendu de la page
    // -------------------------------------------------------------------------

    /**
     * Point d'entrée du rendu : structure générale de la page.
     *
     * @return void
     */
    public function renderPage(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        $tabs = [
            'configuration' => [ 'label' => \__( 'Configuration', 'g2rd' ),    'icon' => 'admin-settings' ],
            'contenu'       => [ 'label' => \__( 'Contenu', 'g2rd' ),           'icon' => 'database' ],
            'editeur'       => [ 'label' => \__( 'Éditeur', 'g2rd' ),           'icon' => 'edit' ],
            'clients'       => [ 'label' => \__( 'Clients & Admin', 'g2rd' ),   'icon' => 'admin-users' ],
            'maintenance'   => [ 'label' => \__( 'Maintenance', 'g2rd' ),       'icon' => 'wrench' ],
        ];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current = isset( $_GET['tab'] ) ? \sanitize_key( \wp_unslash( $_GET['tab'] ) ) : 'configuration';
        if ( ! \array_key_exists( $current, $tabs ) ) {
            $current = 'configuration';
        }

        $base_url = \admin_url( 'themes.php?page=' . self::PAGE_SLUG );
        ?>
        <div class="wrap g2rd-options-wrap">

            <div class="g2rd-options-header">
                <h1 class="g2rd-options-title">
                    <span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
                    <?php \esc_html_e( 'Options du thème G2RD', 'g2rd' ); ?>
                </h1>
                <a href="<?php echo \esc_url( \admin_url( 'admin.php?page=g2rd-onboarding' ) ); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-welcome-learn-more" aria-hidden="true" style="vertical-align:middle;margin-right:4px;"></span>
                    <?php \esc_html_e( 'Assistant de démarrage', 'g2rd' ); ?>
                </a>
            </div>

            <?php $this->renderNotice(); ?>
            <?php \do_action( 'g2rd_options_before_form' ); ?>

            <nav class="nav-tab-wrapper g2rd-tabs-nav" aria-label="<?php \esc_attr_e( 'Sections des options G2RD', 'g2rd' ); ?>">
                <?php foreach ( $tabs as $id => $tab ) : ?>
                <a
                    href="<?php echo \esc_url( $base_url . '&tab=' . $id ); ?>"
                    class="nav-tab<?php echo $current === $id ? ' nav-tab-active' : ''; ?>"
                >
                    <span class="dashicons dashicons-<?php echo \esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
                    <?php echo \esc_html( $tab['label'] ); ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="g2rd-tab-form">
                <?php \wp_nonce_field( 'g2rd_save_options', 'g2rd_nonce' ); ?>
                <input type="hidden" name="action" value="g2rd_save_options">
                <input type="hidden" name="_tab"   value="<?php echo \esc_attr( $current ); ?>">

                <?php
                switch ( $current ) {
                    case 'configuration':
                        $this->renderBusinessModeSection();
                        $this->renderColorsSection();
                        break;
                    case 'contenu':
                        $this->renderCPTsSection();
                        break;
                    case 'editeur':
                        $this->renderFeaturesSection();
                        $this->renderBlocksSection();
                        break;
                    case 'clients':
                        $this->renderClientModeSection();
                        break;
                    case 'maintenance':
                        $this->renderComingSoonSection();
                        break;
                }
                ?>

                <div class="g2rd-submit-bar">
                    <?php \submit_button( \__( 'Enregistrer les options', 'g2rd' ), 'primary large', 'submit', false ); ?>
                </div>
            </form>

            <?php $this->renderModals(); ?>
            <?php $this->renderFooter(); ?>

        </div>
        <?php
    }

    /**
     * Affiche la notice de confirmation après enregistrement.
     *
     * @return void
     */
    private function renderNotice(): void {
        if (!isset($_GET['saved'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture d'un indicateur de redirection, pas de traitement de données
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php \esc_html_e('Options enregistrées avec succès.', 'g2rd'); ?></p>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Aide & modaux tutoriels
    // -------------------------------------------------------------------------

    /**
     * Affiche le bouton « ? » qui ouvre le modal tutoriel de la section.
     *
     * @param string $modal_id Identifiant du modal (sans le préfixe "g2rd-modal-").
     * @return void
     */
    private function renderHelpButton( string $modal_id ): void {
        $full_id = 'g2rd-modal-' . $modal_id;
        printf(
            '<button type="button" class="g2rd-help-btn" onclick="var m=document.getElementById(\'%s\');if(m&&m.showModal)m.showModal();" aria-label="%s"><span aria-hidden="true">?</span></button>',
            \esc_attr( $full_id ),
            \esc_attr__( 'Aide et tutoriel', 'g2rd' )
        );
    }

    /**
     * Retourne les données de contenu pour tous les modaux tutoriels.
     *
     * @return array<string, array{icon: string, title: string, intro: string, steps: list<array{label: string, desc: string}>, tip: string}>
     */
    private function getModalsData(): array {
        return [
            'coming-soon' => [
                'icon'  => 'clock',
                'title' => \__( 'Mode « Bientôt disponible »', 'g2rd' ),
                'intro' => \__( 'Redirige automatiquement les visiteurs non connectés vers une page dédiée pendant la construction ou la maintenance du site.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Activation', 'g2rd' ),         'desc' => \__( 'Cochez le toggle — la redirection est immédiate pour tous les visiteurs non connectés.', 'g2rd' ) ],
                    [ 'label' => \__( 'Page de destination', 'g2rd' ), 'desc' => \__( 'Sélectionnez la page à afficher (ex. « Bientôt disponible »). Créez-la dans Pages → Ajouter.', 'g2rd' ) ],
                    [ 'label' => \__( 'Accès admin', 'g2rd' ),         'desc' => \__( 'Les utilisateurs connectés (admin, éditeurs…) continuent de voir le site normalement.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Pensez à désactiver ce mode avant le lancement officiel — sans cela, vos visiteurs resteraient redirigés indéfiniment.', 'g2rd' ),
            ],
            'colors' => [
                'icon'  => 'admin-appearance',
                'title' => \__( 'Couleurs de l\'interface admin', 'g2rd' ),
                'intro' => \__( 'Personnalisez la barre d\'administration et les boutons en accord avec la charte graphique de votre thème.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Fond menu & barre', 'g2rd' ),  'desc' => \__( 'Choisissez la couleur de fond du menu latéral et de la barre d\'administration WordPress.', 'g2rd' ) ],
                    [ 'label' => \__( 'Couleur du texte', 'g2rd' ),    'desc' => \__( 'Assurez un contraste suffisant entre le texte et le fond pour une bonne lisibilité.', 'g2rd' ) ],
                    [ 'label' => \__( 'Bouton principal', 'g2rd' ),    'desc' => \__( 'Définissez le fond et le texte du bouton. L\'aperçu se met à jour en temps réel.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Les swatches affichés correspondent à la palette theme.json active — ils se mettent à jour automatiquement si vous changez de variation de style.', 'g2rd' ),
            ],
            'cpts' => [
                'icon'  => 'database',
                'title' => \__( 'Types de contenu personnalisés', 'g2rd' ),
                'intro' => \__( 'Activez des types de contenus (CPT) adaptés à votre activité. Chaque CPT ajoute une section dédiée dans le menu WordPress.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Portfolio', 'g2rd' ),       'desc' => \__( 'Vos réalisations clients avec scores Lighthouse (performance, accessibilité, SEO, bonnes pratiques).', 'g2rd' ) ],
                    [ 'label' => \__( 'Prestations', 'g2rd' ),     'desc' => \__( 'Vos services avec descriptions, tarifs et visuels — intégrables dans des grilles filtrables.', 'g2rd' ) ],
                    [ 'label' => \__( 'Qui sommes-nous', 'g2rd' ), 'desc' => \__( 'Profils membres de l\'équipe : compétences, expérience, méthodologie et objectifs.', 'g2rd' ) ],
                    [ 'label' => \__( 'Taxonomies', 'g2rd' ),      'desc' => \__( 'Chaque CPT peut avoir ses propres catégories et étiquettes configurables sur cette page.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Activez uniquement les CPTs dont vous avez besoin pour garder l\'interface WordPress épurée.', 'g2rd' ),
            ],
            'business' => [
                'icon'  => 'chart-line',
                'title' => \__( 'Mode Business', 'g2rd' ),
                'intro' => \__( 'Adapte les conseils, compositions et CTAs proposés dans l\'éditeur Gutenberg selon le modèle économique de votre site.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Site vitrine', 'g2rd' ),          'desc' => \__( 'Mise en avant des services, témoignages clients et formulaire de contact accessible.', 'g2rd' ) ],
                    [ 'label' => \__( 'Génération de leads', 'g2rd' ),   'desc' => \__( 'CTA répété 3× minimum, formulaire court et éléments de réassurance visibles.', 'g2rd' ) ],
                    [ 'label' => \__( 'E-commerce', 'g2rd' ),            'desc' => \__( 'Avis clients, garanties (livraison, retours) et offres limitées pour maximiser les conversions.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Un widget apparaît sur le tableau de bord WordPress avec des conseils personnalisés selon votre type de site.', 'g2rd' ),
            ],
            'client-mode' => [
                'icon'  => 'admin-users',
                'title' => \__( 'Mode Client & Outils admin', 'g2rd' ),
                'intro' => \__( 'Simplifie l\'interface WordPress pour vos clients non techniques et ajoute des outils d\'aide à la rédaction.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Mode Client', 'g2rd' ),          'desc' => \__( 'Masque les menus Plugins, Outils et Réglages pour les utilisateurs non-administrateurs.', 'g2rd' ) ],
                    [ 'label' => \__( 'Message d\'accueil', 'g2rd' ),   'desc' => \__( 'Affichez un message personnalisé sur le tableau de bord — idéal pour guider votre client.', 'g2rd' ) ],
                    [ 'label' => \__( 'Aide SEO', 'g2rd' ),             'desc' => \__( 'Panneau Gutenberg avec score /100 et checklist 8 points (titre, extrait, H2, images alt, contenu, image à la une, liens internes).', 'g2rd' ) ],
                ],
                'tip' => \__( 'L\'aide SEO est utile même sans le mode client — activez-la pour tous vos rédacteurs.', 'g2rd' ),
            ],
            'features' => [
                'icon'  => 'admin-plugins',
                'title' => \__( 'Fonctionnalités du thème', 'g2rd' ),
                'intro' => \__( 'Activez ou désactivez les fonctionnalités avancées du thème selon les besoins de votre projet.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Animations GSAP', 'g2rd' ),                      'desc' => \__( 'Effets de scroll professionnels avec ScrollTrigger. Désactivé automatiquement pour Google PageSpeed.', 'g2rd' ) ],
                    [ 'label' => \__( 'Particules & Glassmorphism', 'g2rd' ),            'desc' => \__( 'Fond animé sur les blocs groupe et effet verre dépoli dans l\'éditeur. À utiliser avec modération.', 'g2rd' ) ],
                    [ 'label' => \__( 'Dark Mode', 'g2rd' ),                             'desc' => \__( 'Bouton flottant pour basculer en mode sombre. Respecte automatiquement les préférences système.', 'g2rd' ) ],
                    [ 'label' => \__( 'Intégration IA / MCP', 'g2rd' ),                 'desc' => \__( 'Expose les CPTs et la configuration à des outils IA (Claude, Cursor). Requiert WordPress 6.9+. Activez uniquement avec un outil de confiance.', 'g2rd' ) ],
                    [ 'label' => \__( 'Compositions G2RD ← licence', 'g2rd' ),          'desc' => \__( 'Masque les compositions utilisant des blocs G2RD personnalisés quand la licence est inactive.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Activez uniquement ce dont vous avez besoin — chaque fonctionnalité peut impacter les performances ou l\'interface.', 'g2rd' ),
            ],
            'blocks' => [
                'icon'  => 'block-default',
                'title' => \__( 'Blocs Gutenberg G2RD', 'g2rd' ),
                'intro' => \__( 'Gérez la visibilité des blocs G2RD dans l\'éditeur Gutenberg. Une licence active est requise pour modifier ces réglages.', 'g2rd' ),
                'steps' => [
                    [ 'label' => \__( 'Désactiver les blocs inutiles', 'g2rd' ), 'desc' => \__( 'Les blocs désactivés n\'apparaissent plus dans l\'inserteur — l\'éditeur reste épuré.', 'g2rd' ) ],
                    [ 'label' => \__( 'Pages existantes', 'g2rd' ),              'desc' => \__( 'Un bloc désactivé reste présent là où il est utilisé — il affiche une alerte de récupération, sans perte de contenu.', 'g2rd' ) ],
                    [ 'label' => \__( 'Licence requise', 'g2rd' ),               'desc' => \__( 'Activez votre licence dans la section Licence G2RD FSE pour débloquer ces réglages.', 'g2rd' ) ],
                ],
                'tip' => \__( 'Pour réactiver un bloc, revenez sur cette page — le contenu existant est immédiatement restauré.', 'g2rd' ),
            ],
        ];
    }

    /**
     * Affiche un seul modal tutoriel.
     *
     * @param string                                                            $id    Identifiant du modal.
     * @param array{icon: string, title: string, intro: string, steps: list<array{label: string, desc: string}>, tip: string} $modal Données du modal.
     * @return void
     */
    private function renderSingleModal( string $id, array $modal ): void {
        $modal_id = 'g2rd-modal-' . $id;
        $title_id = $modal_id . '-title';
        ?>
        <dialog class="g2rd-modal" id="<?php echo \esc_attr( $modal_id ); ?>" aria-labelledby="<?php echo \esc_attr( $title_id ); ?>">
            <div class="g2rd-modal__box">
                <div class="g2rd-modal__header">
                    <span class="dashicons dashicons-<?php echo \esc_attr( $modal['icon'] ); ?> g2rd-modal__icon" aria-hidden="true"></span>
                    <h2 class="g2rd-modal__title" id="<?php echo \esc_attr( $title_id ); ?>"><?php echo \esc_html( $modal['title'] ); ?></h2>
                    <button type="button" class="g2rd-modal__close" onclick="this.closest('dialog').close()" aria-label="<?php \esc_attr_e( 'Fermer', 'g2rd' ); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="g2rd-modal__body">
                    <p class="g2rd-modal__intro"><?php echo \esc_html( $modal['intro'] ); ?></p>
                    <ul class="g2rd-modal__steps" role="list">
                        <?php foreach ( $modal['steps'] as $i => $step ) : ?>
                        <li class="g2rd-modal__step">
                            <span class="g2rd-modal__step-num" aria-hidden="true"><?php echo \esc_html( (string) ( $i + 1 ) ); ?></span>
                            <div class="g2rd-modal__step-body">
                                <span class="g2rd-modal__step-label"><?php echo \esc_html( $step['label'] ); ?></span>
                                <p class="g2rd-modal__step-desc"><?php echo \esc_html( $step['desc'] ); ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ( ! empty( $modal['tip'] ) ) : ?>
                    <div class="g2rd-modal__tip">
                        <strong><?php \esc_html_e( '💡 Conseil', 'g2rd' ); ?></strong>
                        <?php echo \esc_html( $modal['tip'] ); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </dialog>
        <?php
    }

    /**
     * Affiche tous les modaux tutoriels de la page d'options.
     *
     * @return void
     */
    private function renderModals(): void {
        foreach ( $this->getModalsData() as $id => $modal ) {
            $this->renderSingleModal( $id, $modal );
        }
    }

    /**
     * Affiche le footer de la page d'options.
     *
     * @return void
     */
    private function renderFooter(): void {
        ?>
        <footer class="g2rd-options-footer">
            <div class="g2rd-options-footer__inner">
                <div class="g2rd-options-footer__brand">
                    <span class="g2rd-options-footer__logo">G2RD</span>
                    <span class="g2rd-options-footer__tagline"><?php \esc_html_e( 'Agence Web', 'g2rd' ); ?></span>
                </div>
                <nav class="g2rd-options-footer__links" aria-label="<?php \esc_attr_e( 'Liens G2RD', 'g2rd' ); ?>">
                    <a href="<?php echo \esc_url( 'https://g2rd.fr' ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php \esc_html_e( 'g2rd.fr', 'g2rd' ); ?>
                        <span class="dashicons dashicons-external" aria-hidden="true"></span>
                    </a>
                    <a href="<?php echo \esc_url( 'https://g2rd.fr/support' ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php \esc_html_e( 'Support', 'g2rd' ); ?>
                    </a>
                    <a href="<?php echo \esc_url( 'https://g2rd.fr/documentation' ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php \esc_html_e( 'Documentation', 'g2rd' ); ?>
                    </a>
                </nav>
                <p class="g2rd-options-footer__copy">
                    <?php
                    printf(
                        /* translators: %s: version du thème. */
                        \esc_html__( 'G2RD Theme v%s — Développé avec ♥ par G2RD Agence Web', 'g2rd' ),
                        \esc_html( \wp_get_theme()->get( 'Version' ) )
                    );
                    ?>
                </p>
            </div>
        </footer>
        <?php
    }

    /**
     * Affiche la section « Mode Bientôt disponible ».
     *
     * @return void
     */
    private function renderComingSoonSection(): void {
        $saved   = (array) \get_option(self::OPTION_COMING_SOON, []);
        $enabled = !empty($saved['enabled']);
        $page_id = \absint($saved['page_id'] ?? 0);

        // Récupérer toutes les pages publiées
        $pages = \get_pages(['post_status' => 'publish', 'sort_column' => 'post_title']);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-clock"></span>
                <?php \esc_html_e('Mode « Bientôt disponible »', 'g2rd'); ?>
                <?php $this->renderHelpButton('coming-soon'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Quand ce mode est actif, les visiteurs non connectés sont redirigés automatiquement vers la page sélectionnée. Les utilisateurs connectés voient le site normalement.', 'g2rd'); ?>
            </p>
            <div class="g2rd-card <?php echo $enabled ? 'is-active' : 'is-inactive'; ?>" style="max-width:560px;">
                <div class="g2rd-card-body">
                    <div class="g2rd-card-info">
                        <strong><?php \esc_html_e('Activer le mode « Bientôt disponible »', 'g2rd'); ?></strong>
                        <span class="g2rd-card-desc">
                            <?php \esc_html_e('Les visiteurs non connectés verront uniquement la page choisie.', 'g2rd'); ?>
                        </span>
                    </div>
                    <label class="g2rd-toggle" title="<?php \esc_attr_e('Activer / désactiver', 'g2rd'); ?>">
                        <input
                            type="checkbox"
                            name="coming_soon[enabled]"
                            value="1"
                            id="g2rd-coming-soon-toggle"
                            <?php \checked($enabled); ?>
                        >
                        <span class="g2rd-toggle-slider"></span>
                    </label>
                </div>
                <div style="padding:0 16px 16px;" id="g2rd-coming-soon-page">
                    <label for="g2rd-coming-soon-page-id" style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">
                        <?php \esc_html_e('Page à afficher aux visiteurs', 'g2rd'); ?>
                    </label>
                    <select name="coming_soon[page_id]" id="g2rd-coming-soon-page-id" style="width:100%;max-width:360px;">
                        <option value="0"><?php \esc_html_e('— Sélectionner une page —', 'g2rd'); ?></option>
                        <?php foreach ($pages as $page) : ?>
                            <option value="<?php echo \esc_attr($page->ID); ?>" <?php \selected($page_id, $page->ID); ?>>
                                <?php echo \esc_html($page->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($page_id && $enabled) : ?>
                    <p style="margin-top:8px;font-size:12px;color:#757575;">
                        <?php
                        \printf(
                            /* translators: %s: URL de la page */
                            \esc_html__('URL : %s', 'g2rd'),
                            '<a href="' . \esc_url(\get_permalink($page_id)) . '" target="_blank">' . \esc_html(\get_permalink($page_id)) . '</a>'
                        );
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche la section "Couleurs de l'interface admin".
     *
     * Les swatches sont générés depuis la palette active du theme.json
     * (fonctionne avec les variations de styles FSE).
     *
     * @return void
     */
    private function renderColorsSection(): void {
        // Palette active (thème + variation de style éventuelle)
        $palette_raw = \wp_get_global_settings(['color', 'palette', 'theme']);
        if (!\is_array($palette_raw) || empty($palette_raw)) {
            return;
        }

        // Map slug => données de couleur
        $palette_map = [];
        foreach ($palette_raw as $item) {
            if (!empty($item['slug']) && !empty($item['color'])) {
                $palette_map[ $item['slug'] ] = $item;
            }
        }

        // Valeurs sauvegardées, avec fallback sur les défauts
        $saved    = (array) \get_option(self::OPTION_COLORS, []);
        $defaults = self::DEFAULT_COLOR_SLUGS;

        // Libellés des slots
        $slots = [
            'admin_bg'   => \__('Couleur de fond (menu & barre)', 'g2rd'),
            'admin_text' => \__('Couleur du texte', 'g2rd'),
            'btn_bg'     => \__('Couleur du bouton (fond)', 'g2rd'),
            'btn_text'   => \__('Couleur du bouton (texte)', 'g2rd'),
        ];

        // Résoudre les couleurs pour la prévisualisation initiale
        $resolve_color = function (string $key) use ($saved, $defaults, $palette_map): string {
            $slug = $saved[$key] ?? $defaults[$key];
            if (isset($palette_map[$slug])) {
                return $palette_map[$slug]['color'];
            }
            // Slug introuvable : prendre la première couleur de la palette
            $first = \reset($palette_map);
            return $first ? $first['color'] : '#2f425d';
        };

        $preview_bg       = $resolve_color('admin_bg');
        $preview_text     = $resolve_color('admin_text');
        $preview_btn_bg   = $resolve_color('btn_bg');
        $preview_btn_text = $resolve_color('btn_text');
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-appearance"></span>
                <?php \esc_html_e('Couleurs de l\'interface admin', 'g2rd'); ?>
                <?php $this->renderHelpButton('colors'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Choisissez les couleurs de l\'administration parmi la palette de votre thème. Si vous changez de thème ou de variation de style, les couleurs disponibles seront automatiquement mises à jour.', 'g2rd'); ?>
            </p>

            <div class="g2rd-color-grid">
                <?php foreach ($slots as $slot_key => $slot_label) :
                    $current_slug = $saved[$slot_key] ?? $defaults[$slot_key];
                    // Valider que le slug existe, sinon prendre le premier disponible
                    if (!isset($palette_map[$current_slug])) {
                        $current_slug = \array_key_first($palette_map);
                    }
                ?>
                <div class="g2rd-color-slot">
                    <span class="g2rd-color-slot-label"><?php echo \esc_html($slot_label); ?></span>
                    <div class="g2rd-palette-swatches" data-slot="<?php echo \esc_attr($slot_key); ?>">
                        <?php foreach ($palette_map as $slug => $color_item) :
                            $is_selected = ($slug === $current_slug);
                        ?>
                        <label
                            class="g2rd-swatch<?php echo $is_selected ? ' is-selected' : ''; ?>"
                            style="background-color: <?php echo \esc_attr($color_item['color']); ?>;"
                            title="<?php echo \esc_attr($color_item['name']); ?>"
                            data-color="<?php echo \esc_attr($color_item['color']); ?>"
                        >
                            <input
                                type="radio"
                                name="colors[<?php echo \esc_attr($slot_key); ?>]"
                                value="<?php echo \esc_attr($slug); ?>"
                                <?php \checked($is_selected); ?>
                            >
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Prévisualisation en temps réel -->
            <div class="g2rd-admin-preview" id="g2rd-admin-preview">
                <div
                    class="g2rd-preview-bar g2rd-preview-bg"
                    style="background-color: <?php echo \esc_attr($preview_bg); ?>; color: <?php echo \esc_attr($preview_text); ?>;"
                >
                    <span class="dashicons dashicons-wordpress-alt"></span>
                    <?php \esc_html_e('G2RD', 'g2rd'); ?>
                    <span style="margin-left: auto; font-size: 11px; opacity: 0.7;">
                        <?php \esc_html_e('Barre d\'administration', 'g2rd'); ?>
                    </span>
                </div>
                <div class="g2rd-preview-body">
                    <div
                        class="g2rd-preview-sidebar g2rd-preview-bg"
                        style="background-color: <?php echo \esc_attr($preview_bg); ?>; color: <?php echo \esc_attr($preview_text); ?>;"
                    >
                        <div class="g2rd-preview-item"><?php \esc_html_e('Tableau de bord', 'g2rd'); ?></div>
                        <div class="g2rd-preview-item"><?php \esc_html_e('Articles', 'g2rd'); ?></div>
                        <div class="g2rd-preview-item g2rd-preview-item--current"><?php \esc_html_e('Options G2RD', 'g2rd'); ?></div>
                    </div>
                    <div class="g2rd-preview-content">
                        <p class="g2rd-preview-label"><?php \esc_html_e('Aperçu des couleurs', 'g2rd'); ?></p>
                        <button
                            type="button"
                            class="g2rd-preview-btn"
                            style="background-color: <?php echo \esc_attr($preview_btn_bg); ?>; color: <?php echo \esc_attr($preview_btn_text); ?>; border-color: <?php echo \esc_attr($preview_btn_bg); ?>;"
                        >
                            <?php \esc_html_e('Enregistrer les options', 'g2rd'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche la section "Types de contenu personnalisés".
     *
     * @return void
     */
    private function renderCPTsSection(): void {
        $saved_all = (array) \get_option(self::OPTION_CPTS, []);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-database"></span>
                <?php \esc_html_e('Types de contenu personnalisés (CPT)', 'g2rd'); ?>
                <?php $this->renderHelpButton('cpts'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Activez, désactivez et personnalisez les types de contenu. Les changements de slug sont appliqués automatiquement à l\'enregistrement.', 'g2rd'); ?>
            </p>

            <?php foreach (self::CPT_DEFAULTS as $cpt_key => $def) :
                $s       = \array_merge($def, \is_array($saved_all[$cpt_key] ?? null) ? $saved_all[$cpt_key] : []);
                $enabled = (bool) $s['enabled'];
            ?>
            <div class="g2rd-cpt-panel <?php echo $enabled ? 'is-active' : 'is-inactive'; ?>">

                <div class="g2rd-cpt-panel-header">
                    <span class="dashicons <?php echo \esc_attr($s['menu_icon']); ?> g2rd-cpt-icon"></span>
                    <strong><?php echo \esc_html($s['plural']); ?></strong>
                    <code class="g2rd-cpt-key"><?php echo \esc_html($cpt_key); ?></code>
                    <label class="g2rd-toggle g2rd-cpt-toggle" title="<?php \esc_attr_e('Activer / désactiver ce CPT', 'g2rd'); ?>">
                        <input
                            type="checkbox"
                            class="g2rd-cpt-enabled-cb"
                            name="cpts[<?php echo \esc_attr($cpt_key); ?>][enabled]"
                            value="1"
                            <?php \checked($enabled); ?>
                        >
                        <span class="g2rd-toggle-slider"></span>
                    </label>
                </div>

                <div class="g2rd-cpt-panel-body">
                    <div class="g2rd-cpt-fields-grid">

                        <!-- Libellés -->
                        <div class="g2rd-cpt-field-group">
                            <h4 class="g2rd-cpt-group-title"><?php \esc_html_e('Libellés', 'g2rd'); ?></h4>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Singulier', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][singular]" value="<?php echo \esc_attr($s['singular']); ?>">
                            </label>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Pluriel (menu)', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][plural]" value="<?php echo \esc_attr($s['plural']); ?>">
                            </label>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Libellé liste', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][all_items]" value="<?php echo \esc_attr($s['all_items']); ?>">
                            </label>
                        </div>

                        <!-- Configuration -->
                        <div class="g2rd-cpt-field-group">
                            <h4 class="g2rd-cpt-group-title"><?php \esc_html_e('Configuration', 'g2rd'); ?></h4>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Slug URL', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][slug]" value="<?php echo \esc_attr($s['slug']); ?>">
                            </label>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Position menu', 'g2rd'); ?></span>
                                <input type="number" name="cpts[<?php echo \esc_attr($cpt_key); ?>][menu_position]" value="<?php echo \absint($s['menu_position']); ?>" min="1" max="100" style="width:70px">
                            </label>
                            <div class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Icône (Dashicons)', 'g2rd'); ?></span>
                                <div class="g2rd-icon-picker">
                                    <span class="dashicons <?php echo \esc_attr($s['menu_icon']); ?> g2rd-icon-preview-dash"></span>
                                    <input
                                        type="text"
                                        name="cpts[<?php echo \esc_attr($cpt_key); ?>][menu_icon]"
                                        value="<?php echo \esc_attr($s['menu_icon']); ?>"
                                        class="g2rd-icon-input"
                                        placeholder="dashicons-..."
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="g2rd-cpt-field-group">
                            <h4 class="g2rd-cpt-group-title"><?php \esc_html_e('Options', 'g2rd'); ?></h4>
                            <label class="g2rd-cpt-checkbox">
                                <input type="checkbox" name="cpts[<?php echo \esc_attr($cpt_key); ?>][has_archive]" value="1" <?php \checked(!empty($s['has_archive'])); ?>>
                                <span><?php \esc_html_e('Archives publiques', 'g2rd'); ?></span>
                            </label>
                            <label class="g2rd-cpt-checkbox">
                                <input type="checkbox" name="cpts[<?php echo \esc_attr($cpt_key); ?>][show_in_rest]" value="1" <?php \checked(!empty($s['show_in_rest'])); ?>>
                                <span><?php \esc_html_e('Accessible via API REST', 'g2rd'); ?></span>
                            </label>
                        </div>

                    </div><!-- .g2rd-cpt-fields-grid -->

                    <!-- Taxonomie principale -->
                    <div class="g2rd-cpt-taxonomy">
                        <div class="g2rd-cpt-tax-header">
                            <h4 class="g2rd-cpt-group-title"><?php \esc_html_e('Taxonomie principale', 'g2rd'); ?></h4>
                            <label class="g2rd-toggle" title="<?php \esc_attr_e('Activer la taxonomie', 'g2rd'); ?>">
                                <input
                                    type="checkbox"
                                    class="g2rd-tax-enabled-cb"
                                    name="cpts[<?php echo \esc_attr($cpt_key); ?>][tax_enabled]"
                                    value="1"
                                    <?php \checked(!empty($s['tax_enabled'])); ?>
                                >
                                <span class="g2rd-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="g2rd-cpt-tax-fields">
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Nom singulier', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][tax_singular]" value="<?php echo \esc_attr($s['tax_singular']); ?>">
                            </label>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Nom pluriel', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][tax_plural]" value="<?php echo \esc_attr($s['tax_plural']); ?>">
                            </label>
                            <label class="g2rd-cpt-field">
                                <span><?php \esc_html_e('Slug URL', 'g2rd'); ?></span>
                                <input type="text" name="cpts[<?php echo \esc_attr($cpt_key); ?>][tax_slug]" value="<?php echo \esc_attr($s['tax_slug']); ?>">
                            </label>
                        </div>
                    </div>

                </div><!-- .g2rd-cpt-panel-body -->
            </div><!-- .g2rd-cpt-panel -->
            <?php endforeach; ?>

        </div>
        <?php
    }

    /**
     * Affiche la section "Mode Business" (type de site).
     *
     * @return void
     */
    private function renderBusinessModeSection(): void {
        $current = \get_option( 'g2rd_business_type', '' );
        $types   = [
            ''           => \__( '— Choisissez un type —', 'g2rd' ),
            'vitrine'    => \__( '🏠 Site vitrine', 'g2rd' ),
            'leads'      => \__( '🎯 Génération de leads', 'g2rd' ),
            'ecommerce'  => \__( '🛒 E-commerce', 'g2rd' ),
        ];
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-chart-line"></span>
                <?php \esc_html_e( 'Mode Business', 'g2rd' ); ?>
                <?php $this->renderHelpButton('business'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e( 'Définissez le type de votre site pour obtenir des conseils personnalisés dans l\'éditeur Gutenberg et sur le tableau de bord.', 'g2rd' ); ?>
            </p>
            <div class="g2rd-field-group">
                <label for="g2rd_business_type"><strong><?php \esc_html_e( 'Type de site', 'g2rd' ); ?></strong></label>
                <select name="g2rd_business_type" id="g2rd_business_type" class="regular-text">
                    <?php foreach ( $types as $val => $label ) : ?>
                    <option value="<?php echo \esc_attr( $val ); ?>" <?php \selected( $current, $val ); ?>>
                        <?php echo \esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche la section "Mode Client & Outils admin" (mode client + SEO helper).
     *
     * @return void
     */
    private function renderClientModeSection(): void {
        $enabled = (bool) \get_option( 'g2rd_client_mode', false );
        $message = (string) \get_option( 'g2rd_client_mode_message', '' );
        $seo_on  = (bool) \get_option( 'g2rd_seo_helper', true );
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-users"></span>
                <?php \esc_html_e( 'Mode Client & Outils admin', 'g2rd' ); ?>
                <?php $this->renderHelpButton('client-mode'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e( 'Simplifiez l\'interface WordPress pour vos clients non techniques et activez les outils d\'aide à la rédaction.', 'g2rd' ); ?>
            </p>

            <div class="g2rd-card <?php echo $enabled ? 'is-active' : 'is-inactive'; ?>" style="margin-bottom:12px;">
                <div class="g2rd-card-body">
                    <div class="g2rd-card-info">
                        <strong><?php \esc_html_e( 'Mode Client', 'g2rd' ); ?></strong>
                        <span class="g2rd-card-desc"><?php \esc_html_e( 'Masque les menus sensibles (plugins, outils, réglages) pour les utilisateurs non-administrateurs.', 'g2rd' ); ?></span>
                    </div>
                    <label class="g2rd-toggle" title="Mode Client">
                        <input type="checkbox" name="g2rd_client_mode" value="1" <?php \checked( $enabled ); ?>>
                        <span class="g2rd-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="g2rd-field-group" style="margin-bottom:16px;">
                <label for="g2rd_client_mode_message"><strong><?php \esc_html_e( 'Message d\'accueil client', 'g2rd' ); ?></strong></label>
                <textarea name="g2rd_client_mode_message" id="g2rd_client_mode_message" class="large-text" rows="2"
                    placeholder="<?php \esc_attr_e( 'Bienvenue dans votre espace. Contactez-nous en cas de question.', 'g2rd' ); ?>"
                ><?php echo \esc_textarea( $message ); ?></textarea>
            </div>

            <div class="g2rd-card <?php echo $seo_on ? 'is-active' : 'is-inactive'; ?>">
                <div class="g2rd-card-body">
                    <div class="g2rd-card-info">
                        <strong><?php \esc_html_e( 'Aide SEO dans l\'éditeur', 'g2rd' ); ?></strong>
                        <span class="g2rd-card-desc"><?php \esc_html_e( 'Affiche un panneau de score SEO et une checklist dans la sidebar Gutenberg.', 'g2rd' ); ?></span>
                    </div>
                    <label class="g2rd-toggle" title="SEO Helper">
                        <input type="checkbox" name="g2rd_seo_helper" value="1" <?php \checked( $seo_on ); ?>>
                        <span class="g2rd-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche la section "Fonctionnalités du thème" (toggles optionnels).
     *
     * @return void
     */
    private function renderFeaturesSection(): void {
        $features = (array) \get_option(self::OPTION_FEATURES, []);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php \esc_html_e('Fonctionnalités du thème', 'g2rd'); ?>
                <?php $this->renderHelpButton('features'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Activez ou désactivez les fonctionnalités optionnelles. Les changements sont pris en compte dès l\'enregistrement.', 'g2rd'); ?>
            </p>
            <div class="g2rd-grid">
                <?php foreach (self::FEATURES as $key => $feature) :
                    $enabled = !isset($features[$key]) || (bool) $features[$key];
                ?>
                <div class="g2rd-card <?php echo $enabled ? 'is-active' : 'is-inactive'; ?>">
                    <div class="g2rd-card-body">
                        <div class="g2rd-card-info">
                            <strong><?php echo \esc_html($feature['label']); ?></strong>
                            <span class="g2rd-card-desc"><?php echo \esc_html($feature['description']); ?></span>
                        </div>
                        <label class="g2rd-toggle" title="<?php echo \esc_attr($feature['label']); ?>">
                            <input
                                type="checkbox"
                                name="features[<?php echo \esc_attr($key); ?>]"
                                value="1"
                                <?php \checked($enabled); ?>
                            >
                            <span class="g2rd-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche la section "Blocs Gutenberg".
     *
     * @return void
     */
    private function renderBlocksSection(): void {
        $disabled     = (array) \get_option(self::OPTION_BLOCKS, []);
        $total        = \count(self::BLOCKS);
        $active_count = $total - \count($disabled);
        $licensed     = LicenseManager::is_active();
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-block-default"></span>
                <?php \esc_html_e('Blocs Gutenberg', 'g2rd'); ?>
                <?php $this->renderHelpButton('blocks'); ?>
            </h2>

            <?php if (!$licensed) : ?>
            <div class="g2rd-blocks-upsell">
                <div class="g2rd-blocks-upsell__icon"><span class="dashicons dashicons-superhero-alt" aria-hidden="true"></span></div>
                <div class="g2rd-blocks-upsell__content">
                    <strong><?php \esc_html_e('Débloquez le contrôle des blocs avec une licence G2RD', 'g2rd'); ?></strong>
                    <p><?php \esc_html_e('Activez ou désactivez chaque bloc Gutenberg G2RD pour personnaliser précisément l\'éditeur de vos clients.', 'g2rd'); ?></p>
                </div>
                <a href="<?php echo \esc_url('https://g2rd.fr'); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary g2rd-blocks-upsell__cta">
                    <?php \esc_html_e('Obtenir une licence →', 'g2rd'); ?>
                </a>
            </div>
            <?php else : ?>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Les blocs désactivés disparaissent de l\'éditeur. Les pages existantes utilisant un bloc désactivé afficheront une alerte de récupération dans Gutenberg.', 'g2rd'); ?>
            </p>
            <?php endif; ?>

            <div class="g2rd-blocks-toolbar">
                <button type="button" class="button g2rd-toggle-all" data-state="on" <?php \disabled(!$licensed); ?>>
                    <?php \esc_html_e('Tout activer', 'g2rd'); ?>
                </button>
                <button type="button" class="button g2rd-toggle-all" data-state="off" <?php \disabled(!$licensed); ?>>
                    <?php \esc_html_e('Tout désactiver', 'g2rd'); ?>
                </button>
                <span class="g2rd-blocks-count">
                    <span id="g2rd-active-count"><?php echo \esc_html($active_count); ?></span>
                    / <?php echo \esc_html($total); ?>
                    <?php \esc_html_e('blocs actifs', 'g2rd'); ?>
                </span>
            </div>

            <div class="g2rd-grid g2rd-grid--blocks">
                <?php foreach (self::BLOCKS as $block_name => $block) :
                    $active = !\in_array($block_name, $disabled, true);
                ?>
                <div class="g2rd-card <?php echo $active ? 'is-active' : 'is-inactive'; ?><?php echo !$licensed ? ' is-locked' : ''; ?>">
                    <div class="g2rd-card-body">
                        <div class="g2rd-card-info g2rd-card-info--block">
                            <span class="dashicons dashicons-<?php echo \esc_attr($block['icon']); ?> g2rd-block-icon"></span>
                            <div>
                                <strong><?php echo \esc_html($block['title']); ?></strong>
                                <span class="g2rd-block-name"><?php echo \esc_html($block_name); ?></span>
                            </div>
                        </div>
                        <?php if (!$licensed) : ?>
                        <span class="g2rd-block-lock" title="<?php \esc_attr_e('Licence requise', 'g2rd'); ?>" aria-label="<?php \esc_attr_e('Licence requise', 'g2rd'); ?>">
                            <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                        </span>
                        <?php else : ?>
                        <label class="g2rd-toggle" title="<?php echo \esc_attr($block['title']); ?>">
                            <input
                                type="checkbox"
                                class="g2rd-block-checkbox"
                                name="blocks[<?php echo \esc_attr($block_name); ?>]"
                                value="1"
                                <?php \checked($active); ?>
                            >
                            <span class="g2rd-toggle-slider"></span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
