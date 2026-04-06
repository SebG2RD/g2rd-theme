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
class ThemeOptions
{
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
        'g2rd/bases'             => ['title' => 'Blocs de base G2RD',    'icon' => 'layout'],
    ];

    // -------------------------------------------------------------------------
    // Helpers statiques
    // -------------------------------------------------------------------------

    /**
     * Indique si une fonctionnalité du thème est activée.
     * Par défaut, toutes les fonctionnalités sont activées.
     *
     * @param  string $key Identifiant de la fonctionnalité.
     * @return bool
     */
    public static function isFeatureEnabled(string $key): bool
    {
        $features = (array) \get_option(self::OPTION_FEATURES, []);
        return !isset($features[$key]) || (bool) $features[$key];
    }

    /**
     * Indique si un bloc est désactivé dans la page d'options.
     *
     * @param  string $block_name Nom complet du bloc (ex : "g2rd/carousel").
     * @return bool
     */
    public static function isBlockDisabled(string $block_name): bool
    {
        $disabled = (array) \get_option(self::OPTION_BLOCKS, []);
        return \in_array($block_name, $disabled, true);
    }

    /**
     * Retourne les paramètres fusionnés (défauts + sauvegardés) d'un CPT.
     *
     * @param  string $cpt_key Identifiant du post type (ex : "portfolio").
     * @return array<string, mixed>
     */
    public static function getCPTSettings(string $cpt_key): array
    {
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
    public static function isCPTEnabled(string $cpt_key): bool
    {
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
    public function registerHooks(): void
    {
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
    public function maybeSyncPricingTableBlock(): void
    {
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
    public function registerAdminPage(): void
    {
        \add_theme_page(
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
     * @param  string $hook Identifiant de la page admin courante.
     * @return void
     */
    public function enqueueAssets(string $hook): void
    {
        if ('appearance_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        $dir_uri  = \get_template_directory_uri();
        $dir_path = \get_template_directory();

        \wp_enqueue_style(
            'g2rd-admin-options',
            $dir_uri . '/assets/css/admin-options.css',
            [],
            \filemtime($dir_path . '/assets/css/admin-options.css')
        );

        \wp_enqueue_script(
            'g2rd-admin-options',
            $dir_uri . '/assets/js/admin-options.js',
            [],
            \filemtime($dir_path . '/assets/js/admin-options.js'),
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
    public function saveOptions(): void
    {
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
        $disabled_blocks = [];
        foreach (\array_keys(self::BLOCKS) as $block_name) {
            if (!isset($_POST['blocks'][$block_name])) {
                $disabled_blocks[] = \sanitize_text_field($block_name);
            }
        }
        \update_option(self::OPTION_BLOCKS, $disabled_blocks);

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
            $cpt_post = \is_array($_POST['cpts'][$cpt_key] ?? null)
                ? $_POST['cpts'][$cpt_key]
                : [];
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

        // Planifier le vidage des règles de réécriture (slugs CPT potentiellement modifiés)
        \update_option('g2rd_needs_rewrite_flush', 1);

        \wp_safe_redirect(
            \add_query_arg(
                ['page' => self::PAGE_SLUG, 'saved' => '1'],
                \admin_url('themes.php')
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
    public function renderPage(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap g2rd-options-wrap">

            <h1 class="g2rd-options-title">
                <span class="dashicons dashicons-admin-customizer"></span>
                <?php \esc_html_e('Options du thème G2RD', 'g2rd'); ?>
            </h1>

            <?php $this->renderNotice(); ?>

            <?php \do_action('g2rd_options_before_form'); ?>

            <form method="post" action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>">
                <?php \wp_nonce_field('g2rd_save_options', 'g2rd_nonce'); ?>
                <input type="hidden" name="action" value="g2rd_save_options">

                <?php $this->renderComingSoonSection(); ?>
                <?php $this->renderColorsSection(); ?>
                <?php $this->renderCPTsSection(); ?>
                <?php $this->renderFeaturesSection(); ?>
                <?php $this->renderBlocksSection(); ?>

                <div class="g2rd-submit-bar">
                    <?php \submit_button(\__('Enregistrer les options', 'g2rd'), 'primary large', 'submit', false); ?>
                </div>
            </form>

        </div>
        <?php
    }

    /**
     * Affiche la notice de confirmation après enregistrement.
     *
     * @return void
     */
    private function renderNotice(): void
    {
        if (!isset($_GET['saved'])) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php \esc_html_e('Options enregistrées avec succès.', 'g2rd'); ?></p>
        </div>
        <?php
    }

    /**
     * Affiche la section « Mode Bientôt disponible ».
     *
     * @return void
     */
    private function renderComingSoonSection(): void
    {
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
                <div style="padding:0 16px 16px;" id="g2rd-coming-soon-page" <?php echo $enabled ? '' : 'hidden'; ?>>
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
        <script>
        (function(){
            var toggle = document.getElementById('g2rd-coming-soon-toggle');
            var panel  = document.getElementById('g2rd-coming-soon-page');
            if (!toggle || !panel) return;
            toggle.addEventListener('change', function(){
                panel.hidden = !this.checked;
            });
        })();
        </script>
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
    private function renderColorsSection(): void
    {
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
    private function renderCPTsSection(): void
    {
        $saved_all = (array) \get_option(self::OPTION_CPTS, []);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-database"></span>
                <?php \esc_html_e('Types de contenu personnalisés (CPT)', 'g2rd'); ?>
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
     * Affiche la section "Fonctionnalités du thème".
     *
     * @return void
     */
    private function renderFeaturesSection(): void
    {
        $features = (array) \get_option(self::OPTION_FEATURES, []);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php \esc_html_e('Fonctionnalités du thème', 'g2rd'); ?>
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
    private function renderBlocksSection(): void
    {
        $disabled    = (array) \get_option(self::OPTION_BLOCKS, []);
        $total       = \count(self::BLOCKS);
        $active_count = $total - \count($disabled);
        ?>
        <div class="g2rd-section">
            <h2 class="g2rd-section-title">
                <span class="dashicons dashicons-block-default"></span>
                <?php \esc_html_e('Blocs Gutenberg', 'g2rd'); ?>
            </h2>
            <p class="g2rd-section-desc">
                <?php \esc_html_e('Les blocs désactivés disparaissent de l\'éditeur. Les pages existantes utilisant un bloc désactivé afficheront une alerte de récupération dans Gutenberg.', 'g2rd'); ?>
            </p>

            <div class="g2rd-blocks-toolbar">
                <button type="button" class="button g2rd-toggle-all" data-state="on">
                    <?php \esc_html_e('Tout activer', 'g2rd'); ?>
                </button>
                <button type="button" class="button g2rd-toggle-all" data-state="off">
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
                <div class="g2rd-card <?php echo $active ? 'is-active' : 'is-inactive'; ?>">
                    <div class="g2rd-card-body">
                        <div class="g2rd-card-info g2rd-card-info--block">
                            <span class="dashicons dashicons-<?php echo \esc_attr($block['icon']); ?> g2rd-block-icon"></span>
                            <div>
                                <strong><?php echo \esc_html($block['title']); ?></strong>
                                <span class="g2rd-block-name"><?php echo \esc_html($block_name); ?></span>
                            </div>
                        </div>
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
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
