<?php
/**
 * Gestion des catégories de blocs
 * 
 * Cette classe gère l'enregistrement et le chargement des catégories de blocs
 * personnalisées pour le thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des catégories de blocs
 * 
 * Cette classe gère l'enregistrement automatique des catégories de blocs,
 * leur organisation et leur mise en cache.
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockCategories
{
    /**
     * Clé de cache pour les catégories de blocs
     */
    private const CACHE_KEY = 'g2rd_block_categories_v2';

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
        \add_filter('block_categories_all', [$this, 'registerBlockCategories'], 10, 2);
        \add_action('switch_theme', [$this, 'clearCategoriesCache']);
        \add_action('init', [$this, 'registerCustomBlockCategories']);
    }

    /**
     * Nettoie le cache des catégories lors du changement de thème
     */
    public function clearCategoriesCache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }

    /**
     * Enregistre les catégories de blocs personnalisées
     */
    public function registerCustomBlockCategories(): void
    {
        $categories = $this->getBlockCategories();

        foreach ($categories as $category) {
            if ($this->isValidCategory($category)) {
                \register_block_pattern_category(
                    $category['slug'],
                    [
                        'label' => $category['label'],
                        'description' => $category['description'] ?? ''
                    ]
                );
            }
        }
    }

    /**
     * Enregistre les catégories de blocs dans l'éditeur
     * Les catégories personnalisées sont placées en premier
     */
    public function registerBlockCategories(array $categories, \WP_Block_Editor_Context $context): array
    {
        $custom_categories = $this->getBlockCategories();
        $custom_categories_formatted = [];

        foreach ($custom_categories as $category) {
            if ($this->isValidCategory($category)) {
                $custom_categories_formatted[] = [
                    'slug' => $category['slug'],
                    'title' => $category['label'],
                    'description' => $category['description'] ?? '',
                    'icon' => $category['icon'] ?? null
                ];
            }
        }

        // Placer les catégories personnalisées en premier
        return array_merge($custom_categories_formatted, $categories);
    }

    /**
     * Récupère les catégories de blocs depuis le cache ou les fichiers
     */
    private function getBlockCategories(): array
    {
        $categories = \get_transient(self::CACHE_KEY);

        if (false === $categories) {
            $categories = $this->loadCategoriesFromDirectory();
            \set_transient(self::CACHE_KEY, $categories, self::CACHE_DURATION);
        }

        return $categories;
    }

    /**
     * Charge les catégories depuis le répertoire categories/
     */
    private function loadCategoriesFromDirectory(): array
    {
        $categories = [];
        $categories_dir = \get_template_directory() . '/categories/';

        if (!is_dir($categories_dir)) {
            return $categories;
        }

        $category_files = \glob($categories_dir . '*.json');

        foreach ($category_files as $file) {
            $category_data = json_decode(file_get_contents($file), true);
            
            if (is_array($category_data) && isset($category_data['slug'])) {
                $categories[] = [
                    'slug' => $category_data['slug'],
                    'label' => $category_data['label'],
                    'description' => $category_data['description'] ?? '',
                    'icon' => $category_data['icon'] ?? null,
                    'keywords' => $category_data['keywords'] ?? [],
                    'priority' => $category_data['priority'] ?? 999
                ];
            }
        }

        // Trier les catégories par priorité (plus faible = plus haut)
        usort($categories, function($a, $b) {
            return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
        });

        return $categories;
    }

    /**
     * Vérifie si une catégorie est valide
     */
    private function isValidCategory(array $category): bool
    {
        $required_fields = ['slug', 'label'];

        foreach ($required_fields as $field) {
            if (!isset($category[$field]) || empty($category[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ajoute des attributs de performance aux catégories
     */
    private function addPerformanceAttributes(array $category): array
    {
        // Ajouter des attributs de performance si nécessaire
        if (isset($category['icon'])) {
            $category['icon'] = $this->optimizeIcon($category['icon']);
        }

        return $category;
    }

    /**
     * Optimise l'icône de la catégorie
     */
    private function optimizeIcon(string $icon): string
    {
        // Optimiser le SVG si c'est une icône SVG
        if (strpos($icon, '<svg') !== false) {
            // Supprimer les commentaires
            $icon = preg_replace('/<!--(.|\s)*?-->/', '', $icon);
            
            // Supprimer les espaces inutiles
            $icon = preg_replace('/\s+/', ' ', $icon);
            
            // Supprimer les attributs inutiles
            $icon = preg_replace('/\s+version="[^"]*"/', '', $icon);
            $icon = preg_replace('/\s+xmlns="[^"]*"/', '', $icon);
        }

        return trim($icon);
    }
} 