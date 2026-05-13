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

// Charger l'autoloader Composer si disponible (PSR-4 + classmap).
// Fallback : requires statiques pour les distributions ZIP sans vendor/ (production).
if ( \file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Distributions ZIP sans vendor/ : chargement dynamique depuis classes/.
    // realpath() résout les liens symboliques et confirme l'existence du répertoire.
    $g2rd_classes_dir = \realpath( __DIR__ . '/classes' );

    if ( false !== $g2rd_classes_dir ) {
        // Passe 1 — ordre de chargement critique (dépendances inter-classes).
        // FseSync en tête : instanciée avant bootstrap_theme().
        $g2rd_priority = [
            'class-fse-sync.php',               // instanciée avant bootstrap_theme
            'class-json-config.php',            // lue par ThemeOptions au chargement
            'class-theme-options.php',          // consultée partout dans bootstrap_theme
            'class-block-categories.php',       // doit précéder l'enregistrement des blocs
            'class-block-editor-autoload.php',  // enregistre les blocs Gutenberg
            'class-scripts-manager.php',        // dépend de ThemeOptions
            'class-license-manager.php',        // instancié directement dans bootstrap_theme
            'class-github-updater.php',         // dépend de LicenseManager
            // MCP stack — ordre de dépendances : encryption → limiter/audit/tokens → gate → queue → abilities → server → admin-api → sp5
            'class-mcp-encryption.php',
            'class-mcp-rate-limiter.php',
            'class-mcp-audit-log.php',
            'class-mcp-token-manager.php',
            'class-mcp-security-gate.php',
            'class-mcp-confirmation-queue.php',
            'class-mcp-abilities.php',
            'class-mcp-server.php',
            'class-mcp-admin-api.php',
            'class-mcp-anomaly-detector.php',
            'class-mcp-js-bridge.php',
            'class-mcp-assistant.php',
        ];

        // Préfixe calculé une fois hors des boucles.
        $g2rd_prefix = $g2rd_classes_dir . DIRECTORY_SEPARATOR;
        $g2rd_loaded = [];

        // phpcs:disable PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt
        // Justification : realpath() valide l'existence de chaque chemin et résout les
        // liens symboliques ; str_starts_with() confine au répertoire classes/ ;
        // basename() bloque toute traversée de chemin (ex. "../"). PHPCS ne peut pas
        // analyser statiquement le retour de realpath(), d'où la désactivation ciblée.
        foreach ( $g2rd_priority as $g2rd_file ) {
            $g2rd_full = \realpath( $g2rd_prefix . \basename( $g2rd_file ) );
            if ( false === $g2rd_full || ! \str_starts_with( $g2rd_full, $g2rd_prefix ) ) {
                continue;
            }
            require_once $g2rd_full;
            $g2rd_loaded[] = \basename( $g2rd_full );
        }

        // Passe 2 — toutes les autres classes (ordre alphabétique, pas de dépendances critiques).
        // Le pattern glob /class-*.php garantit déjà l'extension ; realpath() confirme
        // l'existence et str_starts_with() assure le confinement au répertoire classes/.
        foreach ( \glob( $g2rd_classes_dir . '/class-*.php' ) ?: [] as $g2rd_path ) {
            $g2rd_real = \realpath( $g2rd_path );
            if ( false === $g2rd_real || ! \str_starts_with( $g2rd_real, $g2rd_prefix ) ) {
                continue;
            }
            if ( ! \in_array( \basename( $g2rd_real ), $g2rd_loaded, true ) ) {
                require_once $g2rd_real;
            }
        }
        // phpcs:enable PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt
    }
}

// Enregistrer le hook after_switch_theme avant after_setup_theme
( new FseSync() )->register_switch_hook();

// Migrations MCP — tables SQL créées à l'activation du thème.
require_once __DIR__ . '/migrations/001-mcp-tables.php';
\add_action( 'after_switch_theme', __NAMESPACE__ . '\g2rd_mcp_run_migration_001' );

require_once __DIR__ . '/migrations/002-mcp-confirmation-queue.php';
\add_action( 'after_switch_theme', __NAMESPACE__ . '\g2rd_mcp_run_migration_002' );

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
        AgentDiscovery::class,
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
        PerformanceCache::class,
        PerformanceAudit::class,
        PerformanceCSS::class,
        PerformanceImages::class,
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

    // Bloc Pin Scroll — gestion licence + feature toggle + assets GSAP
    new PinScroll( $license_manager );

    // Mode client (simplifie l'admin WP pour les utilisateurs non techniques)
    ( new Client_Mode() )->register_hooks();

    // Assistant d'intégration (onboarding au premier démarrage)
    ( new Onboarding() )->register_hooks();

    // Aide SEO légère dans l'éditeur Gutenberg
    ( new SEO_Helper() )->register_hooks();

    // Mode Business — conseils adaptés au type de site
    ( new Business_Mode() )->register_hooks();

    // Avis Google — endpoint REST + cache transient
    GoogleReviews::init();

    // Module GEO Analyzer — scoring Generative Engine Optimization dans l'éditeur
    ( new GeoAnalyzer() )->register_hooks();

    // Personnalisation de la page de connexion WordPress
    ( new LoginCustomizer() )->register_hooks();

    // Synchronisation FSE templates/template-parts (admin_init hooks)
    ( new FseSync() )->register_hooks();

    // Serveur MCP — endpoint REST + dispatcher JSON-RPC 2.0
    ( new McpServer() )->register_hooks();

    // API MCP admin — endpoints REST pour la page d'options (tokens, audit, queue, anomalies)
    ( new McpAdminApi() )->register_hooks();

    // MCP JS Bridge — badge barre d'admin + middleware X-G2RD-Screen
    ( new McpJsBridge() )->register_hooks();

    // MCP Assistant — panneau sidebar Gutenberg (administrateurs uniquement)
    ( new McpAssistant() )->register_hooks();

    // Initialiser les autres classes
    foreach ( $classes as $class ) {
        ( new $class() )->register_hooks();
    }
}

// Démarrer le thème
\add_action( 'after_setup_theme', __NAMESPACE__ . '\bootstrap_theme' );

// Inclusion explicite de la page d'options (hors namespace, à la fin)
require_once \get_template_directory() . '/includes/license-init.php';

// Correctifs d'accessibilité RGAA (hors namespace — filtres globaux WordPress)
require_once \get_theme_file_path( 'inc/rgaa-accessibility.php' );
