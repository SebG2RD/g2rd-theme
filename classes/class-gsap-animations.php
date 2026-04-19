<?php

/**
 * Gestion des animations GSAP
 * 
 * Cette classe gère l'intégration et la configuration des animations
 * utilisant la bibliothèque GSAP (GreenSock Animation Platform).
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des animations GSAP (GreenSock Animation Platform)
 * 
 * Cette classe gère le chargement et la configuration des animations GSAP,
 * à la fois sur le frontend et dans l'éditeur de blocs.
 *
 * @package G2RD
 * @since 1.0.0
 */
class GSAPAnimations {
    /**
     * Version du thème pour le cache-busting
     */
    private string $theme_version;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->theme_version = wp_get_theme()->get('Version');
    }

    /**
     * Enregistre les hooks nécessaires pour les animations GSAP
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        // Charger les scripts GSAP sur le frontend
        \add_action('wp_enqueue_scripts', [$this, 'registerFrontendScripts']);

        // Charger les contrôles de bloc dans l'éditeur
        \add_action('enqueue_block_editor_assets', [$this, 'registerEditorScripts']);
        \add_action('wp_head', [$this, 'addPreloadLinks'], 1);
    }

    /**
     * Ajoute les liens de préchargement pour GSAP
     */
    public function addPreloadLinks(): void {
        if ( \is_admin() || ScriptsManager::is_speed_test() ) {
            return;
        }
        echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() ) . '/assets/js/vendor/gsap.min.js" as="script">';
        echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() ) . '/assets/js/vendor/ScrollTrigger.min.js" as="script">';
    }

    /**
     * Enregistre et charge les scripts GSAP pour le frontend
     * 
     * Charge les bibliothèques GSAP et ScrollTrigger depuis CDN,
     * ainsi que le script personnalisé d'animations.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerFrontendScripts(): void {
        // Charger GSAP uniquement sur le frontend, hors bots de performance
        if ( \is_admin() || ScriptsManager::is_speed_test() ) {
            return;
        }

        \wp_enqueue_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
            [],
            '3.12.2',
            true
        );

        \wp_enqueue_script(
            'scrolltrigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
            ['gsap'],
            '3.12.2',
            true
        );

        \wp_enqueue_script(
            'gsap-animation',
            \get_template_directory_uri() . '/assets/js/gsap-animation.js',
            ['gsap', 'scrolltrigger'],
            $this->theme_version,
            true
        );

        \wp_localize_script(
            'gsap-animation',
            'gsapData',
            [
                'isMobile'            => wp_is_mobile(),
                'prefersReducedMotion' => $this->shouldReduceMotion(),
            ]
        );
    }

    /**
     * Vérifie si l'utilisateur préfère les mouvements réduits
     */
    private function shouldReduceMotion(): bool {
        if (isset($_COOKIE['prefers-reduced-motion'])) {
            return \sanitize_text_field(\wp_unslash($_COOKIE['prefers-reduced-motion'])) === 'true';
        }
        return false;
    }

    /**
     * Enregistre et charge les scripts pour les contrôles d'animation dans l'éditeur
     * 
     * Charge le script de contrôle des blocs et ajoute les données
     * localisées pour les options d'animation disponibles.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerEditorScripts(): void {
        // Enregistrer le script de contrôle des blocs avec version du thème
        \wp_enqueue_script(
            'gsap-block-controls',
            \get_template_directory_uri() . '/assets/js/gsap-block-controls.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor'],
            $this->theme_version,
            true
        );

        // Ajouter les données localisées
        \wp_localize_script('gsap-block-controls', 'gsapBlockData', [
            'animations' => $this->getAvailableAnimations()
        ]);
    }

    /**
     * Retourne la liste des animations disponibles
     */
    private function getAvailableAnimations(): array {
        return [
            'fadeIn' => 'Apparition en fondu',
            'slideUp' => 'Glissement vers le haut',
            'slideDown' => 'Glissement vers le bas',
            'slideLeft' => 'Glissement vers la gauche',
            'slideRight' => 'Glissement vers la droite',
            'scale' => 'Mise à l\'échelle',
            'rotate' => 'Rotation',
            'zoomIn' => 'Zoom avant',
            'zoomOut' => 'Zoom arrière',
            'flip' => 'Retournement',
            'bounce' => 'Rebond',
            'stagger' => 'Effet cascade',
            'blur' => 'Flou',
            'skew' => 'Inclinaison',
            'shake' => 'Secousse',
            'pulse' => 'Pulsation',
            'wave' => 'Vague',
            'swing' => 'Balancement',
            'tada' => 'Tada',
            'wobble' => 'Oscillation'
        ];
    }
}
