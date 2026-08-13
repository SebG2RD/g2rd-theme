<?php
/**
 * Chargement automatique des blocs de l'éditeur
 * 
 * Cette classe gère le chargement automatique des blocs personnalisés
 * et leur intégration dans l'éditeur Gutenberg.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion de l'auto-chargement des blocs et des assets de l'éditeur
 * 
 * Cette classe gère l'enregistrement automatique des blocs personnalisés,
 * le chargement des styles de blocs et la composition du theme.json.
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockEditorAutoload {
    /**
     * Préfixe des transients du theme.json composé.
     *
     * La clé complète y ajoute une empreinte des mtimes des fichiers sources
     * (voir getThemeJsonCacheKey), d'où la purge par préfixe.
     */
    private const CACHE_PREFIX = 'g2rd_theme_json_';

    /**
     * Durée de validité du cache en secondes (24 heures)
     */
    private const CACHE_DURATION = 86400;

    /**
     * Version du thème pour le cache-busting
     */
    private string $theme_version;
    
    /**
     * Liste des blocs premium enregistrés en mode restreint (licence inactive).
     *
     * @var array<int, string>
     */
    private array $restricted_blocks = [];

    /**
     * Constructeur
     */
    public function __construct() {
        $this->theme_version = wp_get_theme()->get('Version') ?: '1.0.0';
    }

    /**
     * Enregistre tous les hooks nécessaires pour l'éditeur de blocs
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        \add_action('init', [$this, 'registerCustomBlocks']);
        \add_action('init', [$this, 'registerBlocksAssets']);
        \add_filter('wp_theme_json_data_theme', [$this, 'composeThemeJson']);
        \add_action('enqueue_block_editor_assets', [$this, 'enqueueLicenseEditorNotice']);
        \add_action('enqueue_block_editor_assets', [$this, 'localizeEffectKitsEditor'], 15);

        // Changement de thème : les transients composés ne correspondent plus à
        // rien. Purge ciblée du préfixe, sans vider l'object cache global —
        // même convention que BlockCategories, BlockPatterns et BlockStylesheets.
        \add_action('switch_theme', [$this, 'clearThemeJsonTransients']);

        // Forcer le rechargement des blocs en mode développement
        if (defined('WP_DEBUG') && WP_DEBUG) {
            \add_action('admin_init', [$this, 'clearBlockCache']);
        }
    }

    /**
     * Enregistre automatiquement les blocs personnalisés du dossier /blocks/
     *
     * @since 1.0.0
     * @return void
     */
    public function registerCustomBlocks(): void {
        $folders = \glob(\get_template_directory() . '/blocks/*/');
        $license_active = \G2RD\LicenseManager::is_active();

        foreach ($folders as $folder) {
            $block      = basename($folder);
            $block_path = \get_template_directory() . "/blocks/$block";
            $block_json = $block_path . '/block.json';

            // Ignorer les dossiers sans block.json valide
            if (!file_exists($block_json)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("G2RD: block.json introuvable pour le bloc « $block », ignoré.");
                }
                continue;
            }

            $decoded = json_decode((string) file_get_contents($block_json), true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($decoded['name'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("G2RD: block.json invalide pour le bloc « $block » : " . json_last_error_msg());
                }
                continue;
            }

            // Respecter les préférences de la page d'options du thème
            if (\G2RD\ThemeOptions::isBlockDisabled($decoded['name'])) {
                continue;
            }

            $register_args = [];

            // Mode professionnel "graceful" :
            // - Le bloc reste enregistré (édition/rendu des contenus existants préservés)
            // - Les nouvelles insertions premium sont bloquées si licence inactive.
            if (
                !$license_active
                && !empty($decoded['name'])
                && str_starts_with((string) $decoded['name'], 'g2rd/')
            ) {
                $supports = isset($decoded['supports']) && \is_array($decoded['supports'])
                    ? $decoded['supports']
                    : [];
                $supports['inserter'] = false;
                $register_args['supports'] = $supports;
                $this->restricted_blocks[] = (string) $decoded['name'];

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("G2RD: bloc « $block » enregistré en mode restreint — licence inactive.");
                }
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                // Ne pas journaliser le chemin absolu du serveur (fuite d’arborescence).
                error_log('Tentative d\'enregistrement du bloc: ' . $block);
            }

            $result = empty($register_args)
                ? \register_block_type($block_path)
                : \register_block_type($block_path, $register_args);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                if ($result) {
                    error_log("✅ Bloc enregistré avec succès: $block");
                } else {
                    error_log("❌ Échec de l'enregistrement du bloc: $block");
                }
            }
        }
    }

    /**
     * Affiche une notice claire dans l'éditeur quand la licence est inactive.
     *
     * @return void
     */
    public function enqueueLicenseEditorNotice(): void {
        if (\G2RD\LicenseManager::is_active() || empty($this->restricted_blocks)) {
            return;
        }

        \wp_enqueue_script('wp-dom-ready');

        $message = \__(
            'Licence G2RD inactive : vos blocs existants restent éditables et visibles en frontend, mais les nouvelles insertions de blocs premium sont temporairement désactivées.',
            'g2rd'
        );

        $script = "wp.domReady(function(){if(window.wp&&wp.data&&wp.data.dispatch){wp.data.dispatch('core/notices').createNotice('warning','"
            . \esc_js($message)
            . "',{isDismissible:true,id:'g2rd-license-inactive-editor-notice'});}});";

        \wp_add_inline_script('wp-dom-ready', $script);
    }

    /**
     * Expose à l’éditeur du bloc Effect Kits l’état de la licence (notice contextuelle dans edit.js).
     *
     * @return void
     */
    public function localizeEffectKitsEditor(): void {
        $registry = \WP_Block_Type_Registry::get_instance();
        if ( ! $registry->is_registered( 'g2rd/effect-kits' ) ) {
            return;
        }

        $block_type    = $registry->get_registered( 'g2rd/effect-kits' );
        $editor_handle = isset( $block_type->editor_script ) ? (string) $block_type->editor_script : '';
        if ( '' === $editor_handle || ! \wp_script_is( $editor_handle, 'registered' ) ) {
            return;
        }

        \wp_localize_script(
            $editor_handle,
            'g2rdEffectKitsEditor',
            [
                'licensed' => \G2RD\LicenseManager::is_active(),
            ]
        );
    }

    /**
     * Charge automatiquement les styles CSS des blocs
     *
     * @since 1.0.0
     * @return void
     */
    public function registerBlocksAssets(): void {
        $dir   = \get_template_directory();
        $cache_key = 'g2rd_block_css_' . md5( (string) @filemtime( $dir . '/assets/css' ) );

        /** @var array<string>|false $files */
        $files = \get_transient( $cache_key );
        if ( false === $files ) {
            $files = \glob( $dir . '/assets/css/*.css' ) ?: [];
            // Cache 24 h — invalidé automatiquement dès qu'un fichier CSS change (mtime du dossier)
            \set_transient( $cache_key, $files, DAY_IN_SECONDS );
        }

        foreach ( $files as $file ) {
            $filename   = basename( $file, '.css' );
            $block_name = str_replace( 'core-', 'core/', $filename );

            \wp_enqueue_block_style(
                $block_name,
                [
                    'handle' => "g2rd-{$filename}",
                    'src'    => \get_theme_file_uri( "assets/css/{$filename}.css" ),
                    'path'   => \get_theme_file_path( "assets/css/{$filename}.css" ),
                    'ver'    => @filemtime( $file ),
                ]
            );
        }
    }

    /**
     * Charge et décode un fichier JSON avec gestion d'erreur.
     *
     * @since 1.0.0
     * @param string $path Chemin absolu vers le fichier JSON.
     * @return array<string, mixed>|null  Tableau décodé, ou null si fichier absent/invalide.
     */
    private function loadJsonFile(string $path): ?array {
        if (!file_exists($path) || !is_readable($path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD: Fichier JSON introuvable ou illisible : ' . basename($path));
            }
            return null;
        }

        $content = file_get_contents($path);
        if (false === $content) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD: Impossible de lire le fichier JSON : ' . basename($path));
            }
            return null;
        }

        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD: JSON invalide dans ' . basename($path) . ' — ' . json_last_error_msg());
            }
            return null;
        }

        return $decoded;
    }

    /**
     * Calcule une clé de cache basée sur le mtime des fichiers JSON sources.
     * Le cache s'invalide automatiquement dès qu'un fichier est modifié.
     *
     * Toute différence suffit — un mtime plus ancien invalide aussi bien qu'un
     * plus récent — et l'ajout ou la suppression d'un fichier dans styles/
     * change la longueur de l'empreinte. Deux angles morts subsistent :
     * un filemtime() en échec se réduit à une chaîne vide (deux fichiers
     * illisibles donnent alors la même empreinte), et un déploiement qui
     * restaure des fichiers à l'identique, mtimes compris, réutilise
     * légitimement le cache précédent.
     *
     * @since 1.0.0
     * @return string
     */
    private function getThemeJsonCacheKey(): string {
        $dir  = \get_template_directory();
        $mtimes = [
            @filemtime($dir . '/theme-styles.json'),
            @filemtime($dir . '/theme-settings.json'),
        ];

        foreach (\glob($dir . '/styles/*.json') ?: [] as $f) {
            $mtimes[] = @filemtime($f);
        }

        // Invalider le cache si le theme.json du thème enfant change
        $child_dir = \get_stylesheet_directory();
        if ($child_dir !== $dir) {
            $mtimes[] = @filemtime($child_dir . '/theme.json');
        }

        return self::CACHE_PREFIX . md5(implode('_', $mtimes));
    }

    /**
     * Compose le theme.json à partir des fichiers de configuration
     *
     * Cette méthode fusionne les fichiers theme-styles.json, theme-settings.json
     * et les variations de style pour créer une configuration complète.
     * Le résultat est mis en cache via un transient WordPress.
     *
     * @since 1.0.0
     * @param \WP_Theme_JSON_Data $theme_json Données du theme.json actuel
     * @return \WP_Theme_JSON_Data Données mises à jour du theme.json
     */
    public function composeThemeJson($theme_json): mixed {
        $cache_key = $this->getThemeJsonCacheKey();

        // En production, tenter de lire depuis le cache transient
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            $cached = \get_transient($cache_key);
            if (false !== $cached) {
                return $theme_json->update_with($cached);
            }
        }

        // Charger les JSON secondaires avec gestion d'erreur défensive
        $dir            = \get_template_directory();
        $theme_styles   = $this->loadJsonFile($dir . '/theme-styles.json');
        $theme_settings = $this->loadJsonFile($dir . '/theme-settings.json');

        // Interrompre si les fichiers sources sont corrompus
        if (null === $theme_styles || null === $theme_settings) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('G2RD: Impossible de composer theme.json — fichier source invalide.');
            }
            return $theme_json;
        }

        // Fusionner le theme.json du thème enfant s'il existe.
        //
        // Seuls ses `settings` sont réinjectés ici. C'est délibéré : le filtre
        // wp_theme_json_data_theme reçoit déjà les données de l'enfant (WordPress
        // lit le theme.json du thème ACTIF, donc celui de l'enfant), et
        // update_with() fusionne $new_data par-dessus au lieu de remplacer. Sans
        // cette réinjection, theme-settings.json écraserait les réglages de
        // l'enfant. En revanche, les `styles` de l'enfant qui touchent un chemin
        // également défini dans theme-styles.json restent perdants — un thème
        // enfant n'apporte donc de manière fiable que des settings, ce que
        // documente CLAUDE.md.
        $child_dir = \get_stylesheet_directory();
        if ($child_dir !== $dir) {
            $child_json = $this->loadJsonFile($child_dir . '/theme.json');
            if (null !== $child_json) {
                $theme_settings = $this->mergeChildSettings($theme_settings, $child_json);
            }
        }

        // Charger les variations de style
        $style_files = \glob($dir . '/styles/*.json') ?: [];
        $variations  = [];

        foreach ($style_files as $style_file) {
            $style_data = $this->loadJsonFile($style_file);
            if (null !== $style_data && isset($style_data['title'])) {
                $variations[] = $style_data;
            }
        }

        $new_data = [
            'version'    => 3,
            'settings'   => $theme_settings['settings'] ?? [],
            'styles'     => $theme_styles['styles'] ?? [],
            'variations' => $variations,
        ];

        // Mettre en cache pour les prochaines requêtes (hors WP_DEBUG)
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            \set_transient($cache_key, $new_data, self::CACHE_DURATION);
        }

        return $theme_json->update_with($new_data);
    }

    /**
     * Fusionne les settings du thème enfant dans ceux du thème parent.
     *
     * Règle générale : un paramètre **défini dans l'enfant prend le dessus** ; un
     * paramètre **absent de l'enfant** conserve la valeur du parent.
     *
     * - Listes séquentielles (palette, gradients, fontSizes, fontFamilies,
     *   spacingSizes, shadows, aspectRatios…) : si l'enfant en fournit une, elle
     *   REMPLACE intégralement celle du parent (pas de fusion par index/slug).
     * - Objets associatifs (custom, layout, drapeaux color…) : fusion récursive,
     *   l'enfant écrasant clé par clé, le reste du parent étant conservé.
     *
     * @since 1.19.0
     * @param array<string,mixed> $parent Données du thème parent (theme-settings.json).
     * @param array<string,mixed> $child  Données du theme.json enfant.
     * @return array<string,mixed>
     */
    private function mergeChildSettings(array $parent, array $child): array {
        $child_settings = $child['settings'] ?? [];
        if (empty($child_settings)) {
            return $parent;
        }

        $parent['settings'] = $this->deepMergeChildSettings(
            $parent['settings'] ?? [],
            $child_settings
        );

        return $parent;
    }

    /**
     * Fusion récursive « l'enfant prend le dessus ».
     *
     * Les objets associatifs sont fusionnés clé par clé ; les listes séquentielles
     * (palette, polices…) et les valeurs scalaires de l'enfant remplacent entièrement
     * celles du parent. Une clé absente de l'enfant garde la valeur du parent.
     *
     * @since 1.19.0
     * @param array<string,mixed> $parent Valeurs du parent.
     * @param array<string,mixed> $child  Valeurs de l'enfant (prioritaires).
     * @return array<string,mixed>
     */
    private function deepMergeChildSettings(array $parent, array $child): array {
        foreach ($child as $key => $child_val) {
            $parent_val = $parent[ $key ] ?? null;

            if (
                is_array($child_val)
                && is_array($parent_val)
                && ! $this->isList($child_val)
                && ! $this->isList($parent_val)
            ) {
                // Deux objets associatifs → fusion récursive.
                $parent[ $key ] = $this->deepMergeChildSettings($parent_val, $child_val);
            } else {
                // Liste séquentielle, scalaire, ou clé absente du parent → remplacement.
                $parent[ $key ] = $child_val;
            }
        }

        return $parent;
    }

    /**
     * Indique si un tableau est une liste séquentielle (clés 0..n-1).
     * Équivalent d'array_is_list() (PHP 8.1+), réécrit pour la compat PHP 8.0.
     *
     * @param array<mixed> $array Tableau à tester.
     * @return bool
     */
    private function isList(array $array): bool {
        if ([] === $array) {
            return true;
        }
        return \array_keys($array) === \range(0, \count($array) - 1);
    }

    /**
     * Vide le cache des blocs et des transients du theme.json
     *
     * Utilise l'API WordPress (delete_transient) plutôt qu'une requête SQL directe
     * afin d'assurer la compatibilité avec les backends de cache externes (Redis, Memcached).
     *
     * @since 1.0.0
     * @return void
     */
    public function clearBlockCache(): void {
        $this->clearThemeJsonTransients();

        \wp_cache_flush();

        if (function_exists('wp_clean_themes_cache')) {
            \wp_clean_themes_cache();
        }
    }

    /**
     * Supprime les transients du theme.json composé, et rien d'autre.
     *
     * Séparé de clearBlockCache() volontairement : cette méthode-ci est branchée
     * sur switch_theme, où vider l'object cache global (wp_cache_flush) serait
     * disproportionné et pénaliserait les sites sous Redis ou Memcached.
     *
     * La clé du transient intègre l'empreinte des fichiers sources : la donnée
     * servie reste donc toujours correcte sans cette purge (voir
     * getThemeJsonCacheKey). Elle ne fait que retirer les lignes devenues
     * orphelines, que WordPress ramasserait de toute façon à leur expiration.
     *
     * @since 1.36.0
     * @return void
     */
    public function clearThemeJsonTransients(): void {
        // Suppression via l'API WordPress (compatible object cache externe) ;
        // seule la liste des clés passe par une requête directe, delete_transient()
        // ne sachant pas travailler par préfixe.
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Suppression de transients par préfixe : delete_transient() ne supporte pas les wildcards, requête directe inévitable.
        $transient_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT REPLACE(option_name, '_transient_', '') FROM {$wpdb->options}
                 WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . self::CACHE_PREFIX) . '%'
            )
        );

        foreach ($transient_keys as $key) {
            \delete_transient($key);
        }
    }
}
