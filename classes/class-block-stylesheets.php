<?php
/**
 * Gestion des feuilles de style des blocs
 *
 * Cette classe gère l'enregistrement et le chargement des feuilles de style
 * personnalisées pour les blocs du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des feuilles de style des blocs
 *
 * Utilise wp_enqueue_block_style() (WP 6.3+) pour charger les styles de blocs
 * aussi bien sur le frontend que dans le canvas iframé de l'éditeur de blocs
 * (compatible WP 7.0+).
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockStylesheets {
    /**
     * Clé de cache pour les feuilles de style
     */
    private const CACHE_KEY = 'g2rd_block_stylesheets';

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
    public function __construct() {
        $this->theme_version = wp_get_theme()->get('Version');
    }

    /**
     * Enregistre les hooks nécessaires
     */
    public function register_hooks(): void {
        \add_action('init', [$this, 'registerBlockStylesheets']);
        \add_action('switch_theme', [$this, 'clearStylesheetsCache']);
    }

    /**
     * Nettoie le cache des feuilles de style lors du changement de thème
     */
    public function clearStylesheetsCache(): void {
        \delete_transient(self::CACHE_KEY);
    }

    /**
     * Enregistre et enfile les feuilles de style des blocs via wp_enqueue_block_style().
     * Ce mécanisme charge les styles sur le frontend ET dans le canvas iframé de l'éditeur
     * (contrairement à admin_enqueue_scripts qui ne fonctionne pas dans l'iframe WP 7.0+).
     */
    public function registerBlockStylesheets(): void {
        $stylesheets = $this->getBlockStylesheets();

        foreach ($stylesheets as $block_name => $stylesheet) {
            if (!$this->isValidStylesheet($stylesheet)) {
                continue;
            }

            $handle  = 'g2rd-' . \sanitize_title($block_name);
            $version = $this->getStylesheetVersion($stylesheet['src']);

            \wp_enqueue_block_style($block_name, [
                'handle' => $handle,
                'src'    => $stylesheet['src'],
                'deps'   => $stylesheet['deps'] ?? [],
                'ver'    => $version,
                'media'  => $stylesheet['media'] ?? 'all',
            ]);

            // Styles inline additionnels rattachés au même handle
            if (!empty($stylesheet['inline'])) {
                \wp_add_inline_style($handle, $this->optimizeCSS($stylesheet['inline']));
            }
        }
    }

    /**
     * Récupère les feuilles de style depuis le cache ou les fichiers
     */
    private function getBlockStylesheets(): array {
        $stylesheets = \get_transient(self::CACHE_KEY);

        if (false === $stylesheets) {
            $stylesheets = $this->loadStylesheetsFromDirectory();
            \set_transient(self::CACHE_KEY, $stylesheets, self::CACHE_DURATION);
        }

        return $stylesheets;
    }

    /**
     * Charge les feuilles de style depuis le répertoire stylesheets/
     */
    private function loadStylesheetsFromDirectory(): array {
        $stylesheets = [];
        $stylesheets_dir = \get_template_directory() . '/stylesheets/';

        if (!is_dir($stylesheets_dir)) {
            return $stylesheets;
        }

        $stylesheet_files = \glob($stylesheets_dir . '*.json');

        foreach ($stylesheet_files as $file) {
            $stylesheet_data = json_decode(file_get_contents($file), true);

            if (is_array($stylesheet_data) && isset($stylesheet_data['blockName'])) {
                $block_name = $stylesheet_data['blockName'];
                $stylesheets[$block_name] = [
                    'src'    => $stylesheet_data['src'],
                    'deps'   => $stylesheet_data['deps'] ?? [],
                    'media'  => $stylesheet_data['media'] ?? 'all',
                    'inline' => $stylesheet_data['inline'] ?? null,
                ];
            }
        }

        return $stylesheets;
    }

    /**
     * Vérifie si une feuille de style est valide
     */
    private function isValidStylesheet(array $stylesheet): bool {
        return isset($stylesheet['src']) && !empty($stylesheet['src']);
    }

    /**
     * Obtient la version d'une feuille de style
     */
    private function getStylesheetVersion(string $src): string {
        $file_path = \get_template_directory() . str_replace(
            \get_template_directory_uri(),
            '',
            $src
        );

        return file_exists($file_path) ? (string) filemtime($file_path) : $this->theme_version;
    }

    /**
     * Optimise le CSS
     */
    private function optimizeCSS(string $css): string {
        // Supprimer les commentaires — délimiteur ! volontaire, pas de modificateur /e
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css); // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird -- délimiteur ! sans modificateur /e, CSS interne

        // Supprimer les espaces inutiles
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css);

        // Supprimer les points-virgules inutiles
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }
}
