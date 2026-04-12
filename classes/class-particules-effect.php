<?php
/**
 * Gestion des effets de particules
 * 
 * Cette classe gère l'intégration et la configuration des effets
 * de particules animées dans le thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestionnaire de l'effet de particules
 *
 * Cette classe gère l'ajout et la configuration de l'effet de particules
 * interactif dans les blocs de type group, ainsi que les contrôles
 * associés dans l'éditeur de blocs.
 *
 * @package G2RD
 * @since 1.0.0
 */
class ParticlesEffect {
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
     * Enregistre les hooks nécessaires pour l'effet de particules
     */
    public function register_hooks(): void {
        \add_action('wp_enqueue_scripts', [$this, 'registerFrontendScripts']);
        \add_action('enqueue_block_editor_assets', [$this, 'registerEditorControls'], 5);
        \add_filter('render_block', [$this, 'addParticlesAttribute'], 10, 2);
        \add_action('admin_head', [$this, 'addEditorStyles']);
    }

    /**
     * Enregistre et charge le script de particules sur le frontend
     *
     * @since 1.0.0
     * @return void
     */
    public function registerFrontendScripts(): void {
        if (\is_admin()) {
            return;
        }

        $script_path = \get_template_directory() . '/assets/js/g2rd-particles.js';
        $version     = file_exists($script_path) ? filemtime($script_path) : $this->theme_version;

        \wp_enqueue_script(
            'g2rd-particles',
            \get_template_directory_uri() . '/assets/js/g2rd-particles.js',
            [],
            $version,
            true
        );
    }

    /**
     * Enregistre et charge les contrôles de l'effet de particules dans l'éditeur
     */
    public function registerEditorControls(): void {
        // Enregistrer le script de contrôle avec les dépendances correctes
        \wp_enqueue_script(
            'g2rd-particles-sidebar',
            \get_template_directory_uri() . '/assets/js/g2rd-particles-sidebar.js',
            [
                'wp-blocks',
                'wp-dom-ready',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'wp-compose',
                'wp-data',
                'wp-i18n',
                'wp-hooks',
            ],
            $this->theme_version,
            true
        );
    }
    
    /**
     * Ajoute les styles CSS pour les contrôles de l'effet de particules dans l'éditeur
     */
    public function addEditorStyles(): void {
        if (!\is_admin() || !function_exists('get_current_screen') || get_current_screen()->base !== 'post') {
            return;
        }
        
        echo '<style>
            /* Assurez-vous que le panneau d\'effet de particules apparaît avant le panneau GSAP */
            .g2rd-particles-panel {
                order: 9 !important;
            }
            
            /* Donner un aspect unique au panneau */
            .g2rd-particles-panel .components-toggle-control .components-base-control__field {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .g2rd-particles-panel .components-panel__body-title {
                color: #0073aa;
            }
        </style>';
    }

    /**
     * Ajoute l'attribut data-particles aux blocs de type group
     */
    public function addParticlesAttribute(string $block_content, array $block): string {
        // Ne s'applique qu'aux blocs de type group
        if ($block['blockName'] !== 'core/group') {
            return $block_content;
        }

        // Vérifier si l'attribut particlesEffect est activé
        $has_particles = isset($block['attrs']['particlesEffect']) && $block['attrs']['particlesEffect'] === true;

        if ($has_particles) {
            // Récupérer les attributs de personnalisation
            $color   = isset($block['attrs']['particlesColor']) ? $block['attrs']['particlesColor'] : '#cccccc';
            $speed   = isset($block['attrs']['particlesSpeed']) ? $block['attrs']['particlesSpeed'] : 4.5;
            $opacity = isset($block['attrs']['particlesOpacity']) ? $block['attrs']['particlesOpacity'] : 0.6;

            // Ajouter les attributs data-* pour la personnalisation
            $block_content = preg_replace(
                '/<div/',
                sprintf(
                    '<div data-particles="true" data-particles-color="%s" data-particles-speed="%s" data-particles-opacity="%s"',
                    esc_attr($color),
                    esc_attr($speed),
                    esc_attr($opacity)
                ),
                $block_content,
                1
            );
        }

        return $block_content;
    }
} 
