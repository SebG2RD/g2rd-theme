<?php
/**
 * Gestion de l'effet de verre (glassmorphism)
 * 
 * Cette classe gère l'intégration et la configuration de l'effet de verre
 * sur les blocs de type group, columns et rows.
 *
 * @package G2RD
 * @since 1.0.2
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestionnaire de l'effet de verre
 *
 * Cette classe gère l'ajout et la configuration de l'effet de verre
 * sur les blocs, ainsi que les contrôles associés dans l'éditeur.
 *
 * @package G2RD
 * @since 1.0.2
 */
class GlassEffect
{
    /**
     * Version du thème pour le cache-busting
     */
    private string $theme_version;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->theme_version = wp_get_theme()->get('Version');
    }

    /**
     * Enregistre les hooks nécessaires pour l'effet de verre
     */
    public function registerHooks(): void
    {
        \add_action('enqueue_block_editor_assets', [$this, 'registerEditorControls'], 5);
        \add_filter('render_block', [$this, 'addGlassAttribute'], 10, 2);
        \add_action('admin_head', [$this, 'addEditorStyles']);
    }

    /**
     * Enregistre et charge les contrôles de l'effet de verre dans l'éditeur
     */
    public function registerEditorControls(): void
    {
        \wp_enqueue_script(
            'g2rd-glass-sidebar',
            \get_template_directory_uri() . '/assets/js/g2rd-glass-sidebar.js',
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
     * Ajoute les styles CSS pour les contrôles de l'effet de verre dans l'éditeur
     */
    public function addEditorStyles(): void
    {
        if (!\is_admin() || !function_exists('get_current_screen') || get_current_screen()->base !== 'post') {
            return;
        }
        
        echo '<style>
            .g2rd-glass-panel {
                order: 10 !important;
            }
            
            .g2rd-glass-panel .components-base-control {
                margin-bottom: 24px;
            }

            .g2rd-glass-panel .components-base-control:last-child {
                margin-bottom: 0;
            }

            .g2rd-glass-panel .components-base-control__help {
                margin-top: 4px;
            }

            .g2rd-glass-panel .components-color-picker {
                margin-top: 8px;
            }
        </style>';
    }

    /**
     * Ajoute l'attribut data-glass aux blocs
     */
    public function addGlassAttribute(string $block_content, array $block): string
    {
        // Ne s'applique qu'aux blocs de type group, columns et rows
        if (!in_array($block['blockName'], ['core/group', 'core/columns', 'core/row'])) {
            return $block_content;
        }

        // Vérifier si l'attribut glassEffect est activé
        $has_glass = isset($block['attrs']['glassEffect']) && $block['attrs']['glassEffect'] === true;

        if ($has_glass) {
            // Récupérer les attributs personnalisés
            $opacity = $block['attrs']['glassOpacity'] ?? 0.2;
            $blur = $block['attrs']['glassBlur'] ?? 5;
            $border_radius = $block['attrs']['glassBorderRadius'] ?? 16;
            $border_color = $block['attrs']['glassBorderColor'] ?? 'rgba(255, 255, 255, 0.3)';
            $shadow_color = $block['attrs']['glassShadowColor'] ?? 'rgba(0, 0, 0, 0.1)';

            // Style glass à ajouter
            $glass_style = sprintf(
                'background: rgba(255, 255, 255, %s); border-radius: %spx; box-shadow: 0 4px 30px %s; backdrop-filter: blur(%spx); -webkit-backdrop-filter: blur(%spx); border: 1px solid %s;',
                $opacity,
                $border_radius,
                $shadow_color,
                $blur,
                $blur,
                $border_color
            );

            if (preg_match('/<div([^>]*)style="([^"]*)"/i', $block_content)) {
                // Il y a déjà un style inline, on fusionne
                $block_content = preg_replace_callback(
                    '/<div([^>]*)style="([^"]*)"/i',
                    function ($matches) use ($glass_style) {
                        $new_style = trim($matches[2]);
                        if (substr($new_style, -1) !== ';') {
                            $new_style .= ';';
                        }
                        $new_style .= ' ' . $glass_style;
                        // Ajoute data-glass="true" si absent
                        if (strpos($matches[1], 'data-glass="true"') === false) {
                            return '<div' . $matches[1] . 'style="' . $new_style . '" data-glass="true"';
                        } else {
                            return '<div' . $matches[1] . 'style="' . $new_style . '"';
                        }
                    },
                    $block_content,
                    1
                );
            } else {
                // Pas de style inline, on ajoute le style glass uniquement
                $block_content = preg_replace(
                    '/<div/',
                    '<div data-glass="true" style="' . esc_attr($glass_style) . '"',
                    $block_content,
                    1
                );
            }
        }

        return $block_content;
    }
} 