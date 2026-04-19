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
require_once __DIR__ . '/classes/class-license-manager.php';
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
require_once __DIR__ . '/classes/class-license-server.php';
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
require_once __DIR__ . '/classes/class-abilities.php';
require_once __DIR__ . '/classes/class-client-mode.php';
require_once __DIR__ . '/classes/class-onboarding.php';
require_once __DIR__ . '/classes/class-seo-helper.php';
require_once __DIR__ . '/classes/class-business-mode.php';
require_once __DIR__ . '/classes/class-login-customizer.php';
require_once __DIR__ . '/classes/class-geo-analyzer.php';
require_once __DIR__ . '/classes/class-fse-sync.php';

// Enregistrer le hook after_switch_theme avant after_setup_theme
( new FseSync() )->register_switch_hook();

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
        Conditional_Menu::class,
        ApiConnector::class,
        Abilities::class,
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

    // Gestionnaire de licences (côté client : activation, validation, UI admin)
    $license_manager = new LicenseManager();
    $license_manager->register_hooks();

    // Serveur de licences (côté g2rd.fr uniquement : endpoints REST FluentCart)
    ( new LicenseServer() )->register_hooks();

    // Gestionnaire de mises à jour GitHub (nécessite une licence active)
    new GitHubUpdater( $license_manager );

    // Mode client (simplifie l'admin WP pour les utilisateurs non techniques)
    ( new Client_Mode() )->register_hooks();

    // Assistant d'intégration (onboarding au premier démarrage)
    ( new Onboarding() )->register_hooks();

    // Aide SEO légère dans l'éditeur Gutenberg
    ( new SEO_Helper() )->register_hooks();

    // Mode Business — conseils adaptés au type de site
    ( new Business_Mode() )->register_hooks();

    // Module GEO Analyzer — scoring Generative Engine Optimization dans l'éditeur
    ( new GeoAnalyzer() )->register_hooks();

    // Personnalisation de la page de connexion WordPress
    ( new LoginCustomizer() )->register_hooks();

    // Synchronisation FSE templates/template-parts (admin_init hooks)
    ( new FseSync() )->register_hooks();

    // Initialiser les autres classes
    foreach ( $classes as $class ) {
        ( new $class() )->register_hooks();
    }
}

// Démarrer le thème
\add_action( 'after_setup_theme', __NAMESPACE__ . '\bootstrap_theme' );

// Inclusion explicite de la page d'options (hors namespace, à la fin)
require_once \get_template_directory() . '/includes/license-init.php';
