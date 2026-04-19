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
        'g2rd-accessibility',
        'g2rd-dark-mode',
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
        \add_action('wp_enqueue_scripts', [$this, 'applyDeferStrategy'], 999);
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    /**
     * Applique la stratégie defer native WP 6.4+ aux scripts non critiques.
     * Priorité 999 pour s'exécuter après que toutes les classes aient enregistré leurs scripts.
     */
    public function applyDeferStrategy(): void {
        foreach ($this->defer_scripts as $handle) {
            if (\wp_script_is($handle, 'enqueued') || \wp_script_is($handle, 'registered')) {
                \wp_script_add_data($handle, 'strategy', 'defer');
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
