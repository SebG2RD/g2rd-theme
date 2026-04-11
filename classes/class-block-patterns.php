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
        \delete_transient(self::CACHE_KEY . '_' . md5($this->theme_version));
    }

    /**
     * Enregistre les catégories de motifs de blocs
     */
    public function registerBlockPatternCategories(): void
    {
        $categories = [
            'g2rd-layout'     => [
                'label'       => __('G2RD Layouts', 'g2rd'),
                'description' => __('Layouts de mise en page G2RD', 'g2rd'),
            ],
            'g2rd-sections'   => [
                'label'       => __('G2RD Sections', 'g2rd'),
                'description' => __('Sections de contenu G2RD', 'g2rd'),
            ],
            'g2rd-components' => [
                'label'       => __('G2RD Components', 'g2rd'),
                'description' => __('Composants réutilisables G2RD', 'g2rd'),
            ],
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
        // Clé versionnée pour invalider le cache automatiquement après une mise à jour
        $cache_key = self::CACHE_KEY . '_' . md5($this->theme_version);
        $patterns  = \get_transient($cache_key);

        if (false === $patterns) {
            $patterns = $this->loadPatternsFromDirectory();
            \set_transient($cache_key, $patterns, self::CACHE_DURATION);
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
     *
     * Utilise ob_start()/ob_get_clean() pour capturer le contenu HTML sans
     * l'envoyer directement au navigateur, et get_file_data() pour les métadonnées.
     */
    private function loadPatternsFromDirectory(): array
    {
        $patterns    = [];
        $pattern_dir = \get_template_directory() . '/patterns/';

        if (!is_dir($pattern_dir)) {
            return $patterns;
        }

        $pattern_files = \glob($pattern_dir . '*.php');

        if (false === $pattern_files || empty($pattern_files)) {
            return $patterns;
        }

        foreach ($pattern_files as $file) {
            // Lire les métadonnées depuis le header du fichier (sans l'exécuter)
            $headers = \get_file_data($file, [
                'title'       => 'Title',
                'slug'        => 'Slug',
                'description' => 'Description',
                'categories'  => 'Categories',
                'keywords'    => 'Keywords',
                'inserter'    => 'Inserter',
                'block_types' => 'Block Types',
            ]);

            if (empty($headers['title']) || empty($headers['slug'])) {
                continue;
            }

            // Capturer le HTML sans l'envoyer au navigateur
            \ob_start();
            include $file;
            $content = \ob_get_clean();

            if (empty($content)) {
                continue;
            }

            $properties = [
                'title'       => $headers['title'],
                'content'     => $content,
                'description' => $headers['description'] ?? '',
                'inserter'    => 'false' !== strtolower($headers['inserter'] ?? 'true'),
            ];

            if (!empty($headers['categories'])) {
                $properties['categories'] = array_map(
                    'trim',
                    explode(',', $headers['categories'])
                );
            }

            if (!empty($headers['keywords'])) {
                $properties['keywords'] = array_map(
                    'trim',
                    explode(',', $headers['keywords'])
                );
            }

            if (!empty($headers['block_types'])) {
                $properties['blockTypes'] = array_map(
                    'trim',
                    explode(',', $headers['block_types'])
                );
            }

            $patterns[] = [
                'name'       => $headers['slug'],
                'properties' => $properties,
            ];
        }

        return $patterns;
    }

    /**
     * Vérifie si un motif est valide
     */
    private function isValidPattern(array $pattern): bool
    {
        if (empty($pattern['name'])) {
            return false;
        }

        foreach (['title', 'content'] as $field) {
            if (empty($pattern['properties'][$field])) {
                return false;
            }
        }

        return true;
    }

}
