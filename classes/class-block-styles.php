<?php
/**
 * Gestion des styles de blocs
 * 
 * Cette classe gère l'enregistrement et le chargement des styles de blocs
 * personnalisés pour le thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des styles de blocs
 * 
 * Cette classe gère l'enregistrement automatique des styles de blocs,
 * leur chargement optimisé et leur mise en cache.
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockStyles
{
    /**
     * Clé de cache pour les styles de blocs
     */
    private const CACHE_KEY = 'g2rd_block_styles';

    /**
     * Durée de validité du cache en secondes (24 heures)
     */
    private const CACHE_DURATION = 86400;

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
     * Enregistre les hooks nécessaires
     */
    public function registerHooks(): void
    {
        \add_action('init', [$this, 'registerBlockStyles']);
        \add_action('switch_theme', [$this, 'clearStylesCache']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueueBlockStyles']);
    }

    /**
     * Nettoie le cache des styles lors du changement de thème
     */
    public function clearStylesCache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }

    /**
     * Enregistre les styles de blocs
     */
    public function registerBlockStyles(): void
    {
        $styles = $this->getBlockStyles();

        foreach ($styles as $block_name => $block_styles) {
            foreach ($block_styles as $style_name => $style_properties) {
                \register_block_style($block_name, [
                    'name' => $style_name,
                    'label' => $style_properties['label'],
                    'inline_style' => $this->optimizeCSS($style_properties['css']),
                    'style_handle' => "g2rd-{$block_name}-{$style_name}"
                ]);
            }
        }
    }

    /**
     * Charge les styles de blocs
     */
    public function enqueueBlockStyles(): void
    {
        if (\is_admin()) {
            return;
        }

        $styles = $this->getBlockStyles();

        foreach ($styles as $block_name => $block_styles) {
            foreach ($block_styles as $style_name => $style_properties) {
                if (isset($style_properties['css'])) {
                    \wp_add_inline_style(
                        "g2rd-{$block_name}-{$style_name}",
                        $this->optimizeCSS($style_properties['css'])
                    );
                }
            }
        }
    }

    /**
     * Récupère les styles de blocs depuis le cache ou les fichiers
     */
    private function getBlockStyles(): array
    {
        $styles = \get_transient(self::CACHE_KEY);

        if (false === $styles) {
            $styles = $this->loadStylesFromDirectory();
            \set_transient(self::CACHE_KEY, $styles, self::CACHE_DURATION);
        }

        return $styles;
    }

    /**
     * Charge les styles depuis le répertoire styles/
     */
    private function loadStylesFromDirectory(): array
    {
        $styles = [];
        $styles_dir = \get_template_directory() . '/styles/';

        if (!is_dir($styles_dir)) {
            return $styles;
        }

        $style_files = \glob($styles_dir . '*.json');

        foreach ($style_files as $file) {
            $style_data = json_decode(file_get_contents($file), true);
            
            if (is_array($style_data) && isset($style_data['blockName'])) {
                $block_name = $style_data['blockName'];
                $style_name = basename($file, '.json');
                
                $styles[$block_name][$style_name] = [
                    'label' => $style_data['label'] ?? $style_name,
                    'css' => $style_data['css'] ?? '',
                    'attributes' => $style_data['attributes'] ?? []
                ];
            }
        }

        return $styles;
    }

    /**
     * Optimise le CSS
     */
    private function optimizeCSS(string $css): string
    {
        // Supprimer les commentaires
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Supprimer les espaces inutiles
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css);
        
        // Supprimer les points-virgules inutiles
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
} 