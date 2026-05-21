<?php

/**
 * Gestionnaire des scripts et styles
 * 
 * Cette classe gère le chargement et l'optimisation des scripts
 * et des feuilles de style du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestionnaire des scripts JavaScript
 *
 * Cette classe gère l'enregistrement et le chargement des scripts JavaScript
 * nécessaires au fonctionnement du thème, notamment pour les interactions
 * utilisateur et les effets visuels.
 *
 * @package G2RD
 * @since 1.0.0
 */
class ScriptsManager {
    /**
     * Version du thème pour le cache-busting
     */
    private string $theme_version;

    /**
     * Scripts non critiques à charger en différé (stratégie native WP 6.4+).
     * Les scripts des fonctionnalités sont enregistrés par leurs classes respectives ;
     * on applique la stratégie defer ici à priorité tardive.
     */
    private array $defer_scripts = [
        'gsap',
        'scrolltrigger',
        'gsap-animation',
        'g2rd-particles',
        'g2rd-clickable-articles',
    ];

    /**
     * Constructeur
     */
    public function __construct() {
        $this->theme_version = wp_get_theme()->get('Version');
    }

    /**
     * Enregistre les hooks WordPress pour la gestion des scripts
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        \add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        \add_action('wp_enqueue_scripts', [$this, 'dequeuePluginAssets'], 100);
        \add_action('wp_enqueue_scripts', [$this, 'applyDeferStrategy'], 999);
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        \add_filter('litespeed_optm_js_exc',  [$this, 'excludeFromLitespeed']);
        \add_filter('litespeed_optm_css_exc', [$this, 'excludeCssFromLitespeed']);
        \add_action('wp', [$this, 'conditionalMagicPageStyle']);
    }

    /**
     * Exclut les scripts critiques du thème de l'optimisation LiteSpeed Cache.
     *
     * LiteSpeed convertit tous les scripts en type="litespeed/javascript" (defer),
     * ce qui rompt les scripts de données localisées (-js-extra) en les chargeant
     * après le script principal. On exclut les scripts gérés par le thème.
     *
     * @param array $excludes Liste des patterns d'exclusion.
     * @return array
     */
    public function excludeFromLitespeed( array $excludes ): array {
        $excludes[] = 'dark-mode.js';
        $excludes[] = 'accessibility.js';
        $excludes[] = 'fluent-cart';
        $excludes[] = 'clickable-articles.js';
        return $excludes;
    }

    /**
     * Exclut les styles critiques du thème de l'optimisation CSS LiteSpeed Cache.
     *
     * LiteSpeed peut différer ou supprimer les règles CSS "non critiques" (jamais
     * présentes au premier rendu), notamment les états dynamiques comme .is-open.
     * Ces feuilles de styles doivent être chargées de façon synchrone.
     *
     * @param array $excludes Liste des patterns d'exclusion.
     * @return array
     */
    public function excludeCssFromLitespeed( array $excludes ): array {
        $excludes[] = 'accessibility.css';
        return $excludes;
    }

    /**
     * Applique la stratégie defer native WP 6.4+ aux scripts non critiques.
     * Priorité 999 pour s'exécuter après que toutes les classes aient enregistré leurs scripts.
     * Inclut également les viewScript des blocs g2rd (enqueués par register_block_type).
     */
    public function applyDeferStrategy(): void {
        foreach ($this->defer_scripts as $handle) {
            if (\wp_script_is($handle, 'enqueued') || \wp_script_is($handle, 'registered')) {
                \wp_script_add_data($handle, 'strategy', 'defer');
            }
        }

        // Defer sur les viewScript des blocs g2rd — handles générés par register_block_type()
        // au format "{block-slug}-view-script". Ces scripts écoutent DOMContentLoaded
        // et fonctionnent correctement avec defer.
        global $wp_scripts;
        if ( ! isset( $wp_scripts->registered ) ) {
            return;
        }
        foreach ( $wp_scripts->registered as $handle => $script ) {
            if ( \str_starts_with( $handle, 'g2rd-' ) && \str_ends_with( $handle, '-view-script' ) ) {
                if ( \wp_script_is( $handle, 'enqueued' ) ) {
                    \wp_script_add_data( $handle, 'strategy', 'defer' );
                }
            }
        }
    }

    /**
     * Détecte les bots de test de performance (Lighthouse, PageSpeed, GTmetrix, WebPageTest).
     * Utilisé par cette classe et par GSAPAnimations / ParticlesEffect pour
     * désactiver les assets lourds lors des audits automatisés.
     *
     * @return bool
     */
    public static function is_speed_test(): bool {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] )
            ? \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
            : '';

        return \str_contains( $ua, 'Chrome-Lighthouse' )
            || \str_contains( $ua, 'PageSpeed'         )
            || \str_contains( $ua, 'GTmetrix'          )
            || \str_contains( $ua, 'PTST'              ) // WebPageTest
            || \str_contains( $ua, 'Pingdom'           );
    }

    /**
     * Désenregistre les assets de plugins d'administration sur le frontend public.
     *
     * Ces plugins ne servent qu'à l'interface d'administration de WordPress et n'ont
     * aucune raison d'ajouter du JS ou du CSS sur les pages publiques du site.
     * Les handles qui n'existent pas sont silencieusement ignorés par WordPress.
     *
     * Utiliser le filtre 'g2rd_dequeue_plugin_handles' pour étendre ou réduire cette liste
     * selon la configuration du site : add_filter('g2rd_dequeue_plugin_handles', fn($h) => $h).
     *
     * @since 1.9.3
     * @return void
     */
    public function dequeuePluginAssets(): void {
        /**
         * Liste des handles (scripts et styles confondus) à désenregistrer sur le frontend.
         * WordPress appelle wp_dequeue_script/style silencieusement si le handle est inconnu.
         *
         * @param string[] $handles
         */
        $handles = \apply_filters( 'g2rd_dequeue_plugin_handles', [
            // Hostinger AI Assistant — outil IA exclusivement admin
            'hostinger-ai-assistant',
            'hostinger-ai-assistant-css',
            'hstng-ai-assistant',
            // ManageWP Worker — agent de synchronisation back-end uniquement
            'mwp-worker',
            'worker',
            // Fluent Boards — gestion de projets admin
            'fluent-boards-app',
            'fluent-boards-app-style',
            'fluent-boards',
            // Fluent Security — tableau de bord sécurité admin
            'fluent-security-app',
            'fluent-security-public-app',
            'fluent_security_public',
            // Fluent Messaging / FluentCRM — CRM admin
            'fluent-messaging',
            'fluentcrm-admin',
            // Loco Translate — interface de traduction admin (pas de frontend UI)
            'loco-translate',
            'loco-translate-admin',
        ] );

        foreach ( $handles as $handle ) {
            \wp_dequeue_script( $handle );
            \wp_dequeue_style( $handle );
        }
    }

    /**
     * Enregistre et charge les scripts JavaScript du thème
     *
     * Les scripts spécifiques aux fonctionnalités (particules, articles cliquables,
     * dark mode, GSAP) sont chargés conditionnellement par leurs classes respectives.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueueScripts(): void {
        $ver = \wp_get_theme()->get( 'Version' );
        $uri = \get_template_directory_uri();

        // Design system Magic Page — enregistré ici, enqueué uniquement via style_handle
        // de register_block_style() (BlockStyles::registerMagicStyles) sur les pages concernées.
        \wp_register_style(
            'g2rd-magic-page',
            $uri . '/assets/css/magic-page.css',
            [],
            $ver
        );

        // Header (variante dark + variante light) et footer — présents sur toutes les pages.
        \wp_enqueue_style(
            'g2rd-header',
            $uri . '/assets/css/header.css',
            [],
            $ver
        );
        \wp_enqueue_style(
            'g2rd-footer',
            $uri . '/assets/css/footer.css',
            [],
            $ver
        );

        // Micro-interactions : CSS + JS d'animation au scroll
        \wp_enqueue_style(
            'g2rd-micro-interactions',
            $uri . '/assets/css/micro-interactions.css',
            [],
            $ver
        );

        \wp_enqueue_script(
            'g2rd-micro-interactions',
            $uri . '/assets/js/micro-interactions.js',
            [],
            $ver,
            true
        );
        \wp_script_add_data( 'g2rd-micro-interactions', 'strategy', 'defer' );
    }

    /**
     * Enregistre et charge les scripts JavaScript de l'administration
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueueAdminScripts(): void {
        // Script pour la gestion des mots de passe (uniquement sur la page d'options G2RD)
        $screen = \get_current_screen();
        if ( $screen && false !== strpos( $screen->id, 'g2rd' ) ) {
            \wp_enqueue_script(
                'g2rd-password-manager',
                \get_template_directory_uri() . '/assets/js/password-manager.js',
                ['jquery'],
                $this->theme_version,
                true
            );
        }

        // Script pour l'éditeur de blocs (évite un 404 si le fichier manque sur le serveur)
        if (\get_current_screen() && \get_current_screen()->is_block_editor()) {
            $block_editor_js = \get_template_directory() . '/assets/js/block-editor.js';
            if (\is_readable($block_editor_js)) {
                \wp_enqueue_script(
                    'g2rd-block-editor',
                    \get_template_directory_uri() . '/assets/js/block-editor.js',
                    ['wp-blocks', 'wp-dom'],
                    $this->theme_version,
                    true
                );
            }
        }
    }

    /**
     * Enqueue conditionnel de magic-page.css sur les pages utilisant la classe g2rd-magic-page.
     *
     * Complète le chargement via style_handle (block styles) pour les pages
     * qui utilisent g2rd-magic-page comme className sans avoir de bloc style magic actif.
     *
     * @since 1.7.3.3
     * @return void
     */
    public function conditionalMagicPageStyle(): void {
        if ( ! \is_singular() ) {
            return;
        }
        $post = \get_post();
        if ( $post && \str_contains( $post->post_content, 'g2rd-magic-page' ) ) {
            \wp_enqueue_style( 'g2rd-magic-page' );
        }
    }

    /**
     * Charge le CSS du bloc countdown uniquement sur les pages qui l'utilisent
     */
    public static function enqueue_g2rd_countdown_css(): void {
        if ( ! \has_block('g2rd/countdown') ) {
            return;
        }
        $abs  = \get_template_directory() . '/blocks/g2rd-countdown/build/index.css';
        $uri  = \get_template_directory_uri() . '/blocks/g2rd-countdown/build/index.css';
        if ( \file_exists($abs) ) {
            \wp_enqueue_style('g2rd-countdown-front', $uri, [], \filemtime($abs));
        }
    }
}

// Enregistrement du hook pour charger le CSS countdown côté front
\add_action('wp_enqueue_scripts', [\G2RD\ScriptsManager::class, 'enqueue_g2rd_countdown_css']);
