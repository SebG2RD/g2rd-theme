<?php
/**
 * Gestion des motifs de blocs
 * 
 * Cette classe gère l'enregistrement et le chargement des motifs de blocs
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
 * Gestion des motifs de blocs
 * 
 * Cette classe gère l'enregistrement automatique des motifs de blocs,
 * leur catégorisation et leur chargement optimisé.
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockPatterns
{
    /**
     * Clé de cache pour les motifs de blocs
     */
    private const CACHE_KEY = 'g2rd_block_patterns';

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
        \add_action('init', [$this, 'registerBlockPatterns']);
        \add_action('init', [$this, 'registerBlockPatternCategories']);
        \add_action('switch_theme', [$this, 'clearPatternsCache']);
    }

    /**
     * Nettoie le cache des motifs lors du changement de thème
     */
    public function clearPatternsCache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }

    /**
     * Enregistre les catégories de motifs de blocs
     */
    public function registerBlockPatternCategories(): void
    {
        $categories = [
            'g2rd-layout' => [
                'label' => __('G2RD Layouts', 'g2rd'),
                'description' => __('Layouts de mise en page G2RD', 'g2rd')
            ],
            'g2rd-sections' => [
                'label' => __('G2RD Sections', 'g2rd'),
                'description' => __('Sections de contenu G2RD', 'g2rd')
            ],
            'g2rd-components' => [
                'label' => __('G2RD Components', 'g2rd'),
                'description' => __('Composants réutilisables G2RD', 'g2rd')
            ]
        ];

        foreach ($categories as $slug => $category) {
            \register_block_pattern_category($slug, $category);
        }
    }

    /**
     * Enregistre les motifs de blocs
     */
    public function registerBlockPatterns(): void
    {
        // Essayer de récupérer les motifs depuis le cache
        $patterns = \get_transient(self::CACHE_KEY);

        if (false === $patterns) {
            $patterns = $this->loadPatternsFromDirectory();
            \set_transient(self::CACHE_KEY, $patterns, self::CACHE_DURATION);
        }

        foreach ($patterns as $pattern) {
            if ($this->isValidPattern($pattern)) {
                \register_block_pattern(
                    $pattern['name'],
                    $pattern['properties']
                );
            }
        }
    }

    /**
     * Charge les motifs depuis le répertoire patterns/
     */
    private function loadPatternsFromDirectory(): array
    {
        $patterns = [];
        $pattern_dir = \get_template_directory() . '/patterns/';

        if (!is_dir($pattern_dir)) {
            return $patterns;
        }

        $pattern_files = \glob($pattern_dir . '*.php');

        foreach ($pattern_files as $file) {
            $pattern_data = include $file;
            
            if (is_array($pattern_data) && isset($pattern_data['name'])) {
                $patterns[] = [
                    'name' => $pattern_data['name'],
                    'properties' => $pattern_data
                ];
            }
        }

        return $patterns;
    }

    /**
     * Vérifie si un motif est valide
     */
    private function isValidPattern(array $pattern): bool
    {
        $required_fields = ['name', 'title', 'content'];

        foreach ($required_fields as $field) {
            if (!isset($pattern['properties'][$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ajoute des attributs de performance aux motifs
     */
    private function addPerformanceAttributes(array $pattern): array
    {
        if (isset($pattern['properties']['content'])) {
            // Ajouter des attributs de chargement différé aux images
            $pattern['properties']['content'] = preg_replace(
                '/<img(.*?)>/',
                '<img$1 loading="lazy" decoding="async">',
                $pattern['properties']['content']
            );

            // Optimiser les classes CSS
            $pattern['properties']['content'] = preg_replace(
                '/class="([^"]*)\s{2,}([^"]*)"/',
                'class="$1 $2"',
                $pattern['properties']['content']
            );
        }

        return $pattern;
    }
} 