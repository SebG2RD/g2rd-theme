<?php

/**
 * Fichier principal du thème G2RD
 *
 * Ce fichier sert de point d'entrée pour le thème et initie les différentes
 * classes et fonctionnalités du thème.
 *
 * @package G2RD
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

// Slug de la catégorie des blocs personnalisés (défini une seule fois ici)
if ( ! \defined( 'G2RD_BLOCK_CATEGORY' ) ) {
    \define( 'G2RD_BLOCK_CATEGORY', 'g2rd-blocks' );
}

// Inclure les fichiers des classes principales
require_once __DIR__ . '/classes/class-theme-options.php';
require_once __DIR__ . '/classes/class-theme-setup.php';
require_once __DIR__ . '/classes/class-shortcode.php';
require_once __DIR__ . '/classes/class-block-editor-autoload.php';
require_once __DIR__ . '/classes/class-theme-admin.php';
require_once __DIR__ . '/classes/class-gsap-animations.php';
require_once __DIR__ . '/classes/class-json-config.php';
require_once __DIR__ . '/classes/class-scripts-manager.php';
require_once __DIR__ . '/classes/class-particules-effect.php';
require_once __DIR__ . '/classes/class-clickable-articles.php';
require_once __DIR__ . '/classes/class-license-manager.php';
require_once __DIR__ . '/classes/class-github-updater.php';
require_once __DIR__ . '/classes/class-portfolio-query.php';
require_once __DIR__ . '/classes/class-custom-post-types-portfolio.php';
require_once __DIR__ . '/classes/class-custom-post-types-prestations.php';
require_once __DIR__ . '/classes/class-custom-post-types-qui-sommes-nous.php';
require_once __DIR__ . '/classes/class-block-patterns.php';
require_once __DIR__ . '/classes/class-block-styles.php';
require_once __DIR__ . '/classes/class-block-categories.php';
require_once __DIR__ . '/classes/class-glass-effect.php';
require_once __DIR__ . '/classes/class-dark-mode.php';
require_once __DIR__ . '/classes/class-carousel-assets.php';
require_once __DIR__ . '/classes/class-filterable-grid.php';
require_once __DIR__ . '/classes/class-block-editor-enhancements.php';
require_once __DIR__ . '/classes/class-coming-soon.php';
require_once __DIR__ . '/classes/class-fluent-cart-support.php';
require_once __DIR__ . '/classes/class-conditional-menu.php';
require_once __DIR__ . '/classes/class-api-connector.php';

/**
 * Initialise toutes les composantes du thème
 *
 * @return void
 */
function bootstrap_theme(): void
{
    // Charger les traductions
    \load_theme_textdomain( 'g2rd', \get_template_directory() . '/languages' );

    // Liste des classes à initialiser (toujours actives)
    $classes = [
        ThemeSetup::class,
        BlockEditorAutoload::class,
        ScriptsManager::class,
        BlockPatterns::class,
        BlockStyles::class,
        BlockCategories::class,
        PortfolioQuery::class,
        ThemeAdmin::class,
        ThemeOptions::class,
        // CPTs conditionnels (activables/désactivables depuis Options G2RD)
        ...( ThemeOptions::isCPTEnabled( 'portfolio' )       ? [ CPT_Portfolio::class ]       : [] ),
        ...( ThemeOptions::isCPTEnabled( 'prestations' )     ? [ CPT_Prestations::class ]     : [] ),
        ...( ThemeOptions::isCPTEnabled( 'qui-sommes-nous' ) ? [ CPT_QuiSommesNous::class ]   : [] ),
        Shortcode::class,
        JsonConfig::class,
        CarouselAssets::class,
        FilterableGrid::class,
        BlockEditorEnhancements::class,
        ComingSoon::class,
        FluentCartSupport::class,
        ConditionalMenu::class,
        ApiConnector::class,
    ];

    // Fonctionnalités optionnelles (activables/désactivables depuis la page d'options)
    if ( ThemeOptions::isFeatureEnabled( 'gsap_animations' ) ) {
        $classes[] = GSAPAnimations::class;
    }
    if ( ThemeOptions::isFeatureEnabled( 'particles_effect' ) ) {
        $classes[] = ParticlesEffect::class;
    }
    if ( ThemeOptions::isFeatureEnabled( 'glass_effect' ) ) {
        $classes[] = GlassEffect::class;
    }
    if ( ThemeOptions::isFeatureEnabled( 'clickable_articles' ) ) {
        $classes[] = ClickableArticles::class;
    }
    if ( ThemeOptions::isFeatureEnabled( 'dark_mode' ) ) {
        $classes[] = DarkMode::class;
    }

    // Initialiser le gestionnaire de licences
    $license_manager = new LicenseManager();
    $license_manager->registerHooks();

    // Initialiser le gestionnaire de mises à jour GitHub
    new GitHubUpdater( $license_manager );

    // Initialiser les autres classes
    foreach ( $classes as $class ) {
        ( new $class() )->registerHooks();
    }
}

// Démarrer le thème
\add_action( 'after_setup_theme', __NAMESPACE__ . '\bootstrap_theme' );

/**
 * Resynchronise les template parts FSE depuis le filesystem après activation du thème.
 *
 * Supprime les posts wp_template_part / wp_template en statut trash ou auto-draft
 * associés à ce thème (sous les deux casses possibles du slug), afin que WordPress
 * les recharge proprement depuis les fichiers du dossier /parts/ et /templates/.
 *
 * Sans ce hook, une réinstallation ou mise à jour laisse des entrées DB orphelines
 * qui déclenchent l'erreur "Template part has been deleted or is unavailable".
 *
 * @return void
 */
function g2rd_sync_fse_templates(): void {
    // Nettoyer sous les deux casses possibles (G2RD-theme et g2rd-theme)
    $slugs = [ \get_stylesheet(), 'G2RD-theme', 'g2rd-theme' ];

    // Supprimer uniquement les entrées orphelines (trash / auto-draft).
    // Les posts publish sont gérés séparément via g2rd_recreate_fse_templates().
    $stale_posts = \get_posts( [
        'post_type'      => [ 'wp_template_part', 'wp_template' ],
        'posts_per_page' => -1,
        'post_status'    => [ 'trash', 'auto-draft' ],
        'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            [
                'taxonomy' => 'wp_theme',
                'field'    => 'name',
                'terms'    => $slugs,
                'operator' => 'IN',
            ],
        ],
    ] );

    foreach ( $stale_posts as $post ) {
        \wp_delete_post( $post->ID, true ); // Suppression définitive (bypass trash)
    }

    // Purger les caches WordPress liés aux thèmes et templates
    \wp_clean_themes_cache();

    if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
        \WP_Theme_JSON_Resolver::clean_cached_data();
    }
}

\add_action( 'after_switch_theme', __NAMESPACE__ . '\g2rd_sync_fse_templates' );

/**
 * Resynchronisation forcée une seule fois après restauration manuelle des fichiers.
 * Se déclenche au prochain chargement admin et ne s'exécute plus ensuite.
 *
 * @return void
 */
function g2rd_sync_fse_once(): void {
    $sync_version = 'g2rd_sync_v3';
    if ( \get_transient( $sync_version ) ) {
        return;
    }
    \delete_transient( 'g2rd_sync_done' );
    \delete_transient( 'g2rd_sync_v2' );
    g2rd_sync_fse_templates();
    \set_transient( $sync_version, true, DAY_IN_SECONDS * 30 );
}
\add_action( 'admin_init', __NAMESPACE__ . '\g2rd_sync_fse_once' );

/**
 * Recrée en DB les template parts et templates FSE manquants depuis le filesystem.
 *
 * get_block_templates() ne persiste pas en DB — cette fonction insère directement
 * les wp_template_part et wp_template manquants via wp_insert_post, ce qui permet
 * au REST API (/wp/v2/template-parts/) de les trouver.
 *
 * @return void
 */
function g2rd_recreate_fse_templates(): void {
    if ( \get_transient( 'g2rd_tpl_recreated_v2' ) ) {
        return;
    }

    \delete_transient( 'g2rd_tpl_recreated_v1' );

    $theme_slug = \get_stylesheet();
    $theme_dir  = \get_template_directory();

    // ── Template parts (parts/*.html) ───────────────────────────────────────
    $parts_config = [
        'header'       => [ 'title' => 'Header',                          'area' => 'header' ],
        'header-color' => [ 'title' => 'Header Couleur',                  'area' => 'header' ],
        'footer'       => [ 'title' => 'Footer',                          'area' => 'footer' ],
        'sidebar'      => [ 'title' => 'Sidebar',                         'area' => 'uncategorized' ],
        'newsletter'   => [ 'title' => 'Inscription à la newsletter',     'area' => '' ],
    ];

    foreach ( $parts_config as $slug => $meta ) {
        $file = $theme_dir . '/parts/' . $slug . '.html';
        if ( ! file_exists( $file ) ) {
            continue;
        }

        // Vérifier si déjà en DB
        $existing = \get_posts( [
            'post_type'      => 'wp_template_part',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'post_name__in'  => [ $slug ],
            'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => 'wp_theme',
                    'field'    => 'name',
                    'terms'    => [ $theme_slug ],
                ],
            ],
        ] );

        if ( ! empty( $existing ) ) {
            continue;
        }

        $content = (string) file_get_contents( $file );
        $post_id = \wp_insert_post( [
            'post_title'   => $meta['title'],
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'wp_template_part',
        ] );

        if ( $post_id && ! \is_wp_error( $post_id ) ) {
            \wp_set_object_terms( $post_id, $theme_slug, 'wp_theme' );

            if ( ! empty( $meta['area'] ) ) {
                \wp_set_object_terms( $post_id, $meta['area'], 'wp_template_part_area' );
            }
        }
    }

    // ── Templates (templates/*.html) ────────────────────────────────────────
    $template_files = \glob( $theme_dir . '/templates/*.html' ) ?: [];

    foreach ( $template_files as $file ) {
        $slug = basename( $file, '.html' );

        $existing = \get_posts( [
            'post_type'      => 'wp_template',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'post_name__in'  => [ $slug ],
            'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => 'wp_theme',
                    'field'    => 'name',
                    'terms'    => [ $theme_slug ],
                ],
            ],
        ] );

        if ( ! empty( $existing ) ) {
            continue;
        }

        $content = (string) file_get_contents( $file );
        $post_id = \wp_insert_post( [
            'post_title'   => ucfirst( str_replace( '-', ' ', $slug ) ),
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'wp_template',
        ] );

        if ( $post_id && ! \is_wp_error( $post_id ) ) {
            \wp_set_object_terms( $post_id, $theme_slug, 'wp_theme' );
        }
    }

    // Purger les caches
    \wp_clean_themes_cache();
    if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
        \WP_Theme_JSON_Resolver::clean_cached_data();
    }

    \set_transient( 'g2rd_tpl_recreated_v2', true, DAY_IN_SECONDS * 30 );
}
\add_action( 'admin_init', __NAMESPACE__ . '\g2rd_recreate_fse_templates', 20 );

// Inclusion explicite de la page d'options (hors namespace, à la fin)
require_once \get_template_directory() . '/includes/license-init.php';
