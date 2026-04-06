<?php
/**
 * Gestion de la configuration JSON
 * 
 * Cette classe gère le chargement et la manipulation des fichiers
 * de configuration JSON du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion de la configuration JSON du thème
 * 
 * Cette classe gère le chargement et l'application des configurations
 * définies dans le fichier configuration.json, notamment pour les blocs,
 * les patterns et les styles.
 *
 * @package G2RD
 * @since 1.0.0
 */
class JsonConfig
{
    /**
     * Données de configuration chargées depuis le fichier JSON
     */
    private array $configurationData = [];

    /**
     * Clé de cache pour les configurations
     */
    private const CACHE_KEY = 'g2rd_json_config_v2';

    /**
     * Durée de mise en cache en secondes (1 heure)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Enregistre les hooks nécessaires et charge la configuration
     */
    public function registerHooks(): void
    {
        // Charger le fichier de configuration et stocker les données
        $this->configurationData = $this->loadJsonConfig();

        // Assigner les hooks pour appliquer les différentes configurations
        foreach ($this->configurationData as $key => $data) {
            switch ($key) {
                case 'registerBlocksCategories':
                    \add_filter('block_categories_all', [$this, 'registerBlocksCategories']);
                    break;
                case 'registerPatternsCategories':
                    \add_action('init', [$this, 'registerPatternsCategories']);
                    break;
                case 'deregisterBlocks':
                    \add_filter('allowed_block_types_all', [$this, 'deregisterBlocks']);
                    break;
                case 'deregisterBlocksStylesheets':
                    \add_action('wp_enqueue_scripts', [$this, 'deregisterBlocksStylesheets']);
                    break;
                case 'deregisterBlocksStyles':
                    \add_action('enqueue_block_editor_assets', [$this, 'deregisterBlocksStyles']);
                    break;
                default:
                    break;
            }
        }

        // Nettoyer le cache lors de la sauvegarde du thème
        \add_action('after_switch_theme', [$this, 'clearConfigCache']);
    }

    /**
     * Nettoie le cache de configuration
     */
    public function clearConfigCache(): void
    {
        \delete_transient(self::CACHE_KEY);
    }

    /**
     * Enregistre de nouvelles catégories pour les blocs personnalisés
     */
    public function registerBlocksCategories($categories): array
    {
        $block_categories_to_register = $this->getConfigDataByKey('registerBlocksCategories');

        // New categories will appear at the top
        $new_categories = [];

        foreach ($block_categories_to_register as $slug => $title) {
            $new_categories[] = [
                'slug' => $slug,
                'title' => $title,
                'icon' => null
            ];
        }

        return array_merge($new_categories, $categories);
    }

    /**
     * Enregistre de nouvelles catégories de patterns
     */
    public function registerPatternsCategories(): void
    {
        $patterns = $this->getConfigDataByKey('registerPatternsCategories');

        foreach ($patterns as $name => $label) {
            $category = ['label' => $label];
            \register_block_pattern_category($name, $category);
        }
    }

    /**
     * Désactive certains blocs par défaut dans l'éditeur
     */
    public function deregisterBlocks(): array
    {
        $blocks_to_disable = $this->getConfigDataByKey('deregisterBlocks');
        $blocks = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
        return array_values(array_diff($blocks, $blocks_to_disable));
    }

    /**
     * Désactive les feuilles de styles natives de certains blocs
     */
    public function deregisterBlocksStylesheets(): void
    {
        $blocks_stylesheets_to_disable = $this->getConfigDataByKey('deregisterBlocksStylesheets');

        foreach ($blocks_stylesheets_to_disable as $block) {
            $handle = str_replace('core/', '', $block);
            \wp_dequeue_style("wp-block-$handle");
        }
    }

    /**
     * Désactive certains styles de blocs par défaut
     */
    public function deregisterBlocksStyles(): void
    {
        $blocks_styles_to_disable = $this->getConfigDataByKey('deregisterBlocksStyles');
        $script_path = \get_template_directory() . '/assets/js/unregister-blocks-styles.js';
        $version = file_exists($script_path) ? filemtime($script_path) : '1.0.0';

        // Charger le script dédié
        \wp_enqueue_script(
            'unregister-styles',
            \get_template_directory_uri() . '/assets/js/unregister-blocks-styles.js',
            ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
            $version,
            true
        );

        // Créer un objet JS pour les styles à désactiver
        $inline_js = "var disableBlocksStyles = " . json_encode($blocks_styles_to_disable) . ";\n";

        // Ajouter la variable dans la page HTML avant le script
        \wp_add_inline_script('unregister-styles', $inline_js, 'before');
    }

    /**
     * Charge et parse le fichier de configuration JSON
     */
    protected function loadJsonConfig(): array
    {
        // Essayer de récupérer depuis le cache
        $cached_config = \get_transient(self::CACHE_KEY);
        if ($cached_config !== false) {
            return $cached_config;
        }

        $filename = 'configuration.json';
        $file_path = \get_template_directory() . '/' . $filename;

        // Tester l'existence du fichier dans le thème
        if (!file_exists($file_path)) {
            return [];
        }

        // Extraire le JSON et l'interpréter
        $config_raw = file_get_contents($file_path);
        $config = json_decode($config_raw, true);

        // Vérifier que les données sont valides
        if (!is_array($config)) {
            throw new \Exception('Configuration file is not valid');
        }

        // Mettre en cache les données
        \set_transient(self::CACHE_KEY, $config, self::CACHE_DURATION);

        return $config;
    }

    /**
     * Récupère les données de configuration pour une clé donnée
     */
    protected function getConfigDataByKey($key): array
    {
        return $this->configurationData[$key] ?? [];
    }
}
