<?php

/**
 * Classe pour gérer les mises à jour du thème via GitHub
 *
 * Cette classe permet de vérifier et d'installer automatiquement les mises à jour
 * du thème depuis le dépôt GitHub. Elle s'intègre avec le système de mise à jour
 * natif de WordPress.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 */

namespace G2RD;

// Alias des fonctions WordPress
use function add_filter;
use function get_template_directory;
use function wp_get_theme;
use function wp_remote_get;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function rename;

class GitHubUpdater
{
    /**
     * URL du dépôt GitHub (page publique)
     *
     * @since 1.0.0
     * @var string
     */
    private string $github_url = 'https://github.com/SebG2RD/G2RD-Theme-FSE';

    /**
     * Endpoint API GitHub pour les releases
     *
     * @since 1.2.1
     * @var string
     */
    private const GITHUB_API_URL = 'https://api.github.com/repos/SebG2RD/G2RD-Theme-FSE/releases/latest';

    /**
     * Arguments communs pour wp_remote_get vers l'API GitHub
     *
     * @since 1.2.1
     * @var array<string, mixed>
     */
    private const REQUEST_ARGS = [
        'timeout' => 10,
        'headers' => [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/G2RD-Theme-Updater',
        ],
    ];

    /**
     * Instance du gestionnaire de licences
     *
     * @since 1.0.0
     * @var LicenseManager
     */
    private LicenseManager $license_manager;

    /**
     * Constructeur de la classe
     *
     * @since 1.0.0
     * @param LicenseManager $license_manager Instance du gestionnaire de licences
     */
    public function __construct( LicenseManager $license_manager )
    {
        $this->license_manager = $license_manager;
        $this->registerHooks();
    }

    /**
     * Enregistre les hooks WordPress nécessaires
     *
     * Cette méthode ajoute les filtres WordPress requis pour intégrer
     * le système de mise à jour personnalisé dans l'interface d'administration.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerHooks()
    {
        \add_filter('pre_set_site_transient_update_themes', [$this, 'checkForUpdates']);
        \add_filter('themes_api', [$this, 'getThemeInfo'], 10, 3);
        \add_filter('upgrader_source_selection', [$this, 'preventThemeRename'], 10, 4);
    }

    /**
     * Vérifie les mises à jour disponibles
     *
     * Cette méthode compare la version actuelle du thème avec la dernière
     * version disponible sur GitHub et ajoute les informations de mise à jour
     * si nécessaire.
     *
     * @since 1.0.0
     * @param object $transient Données de mise à jour WordPress
     * @return object Données de mise à jour modifiées
     */
    public function checkForUpdates($transient)
    {
        // La vérification de licence sera activée quand FluentCart sera intégré.
        // En attendant, les mises à jour fonctionnent sans licence (thème en développement).
        // TODO : décommenter quand l'API FluentCart est opérationnelle.
        // if (!$this->license_manager->isLicenseValid()) {
        //     return $transient;
        // }

        // Si les données de mise à jour ne sont pas initialisées, on retourne le transient tel quel
        if (empty($transient->checked)) {
            return $transient;
        }

        $theme_slug = basename(\get_template_directory());
        $theme_data = \wp_get_theme($theme_slug);
        $current_version = $theme_data->get('Version');

        // Récupérer les informations de la dernière version via l'API GitHub
        $response = \wp_remote_get(self::GITHUB_API_URL, self::REQUEST_ARGS);

        if (\is_wp_error($response)) {
            return $transient;
        }

        $release_data = json_decode(\wp_remote_retrieve_body($response), true);

        if (empty($release_data) || !isset($release_data['tag_name'])) {
            return $transient;
        }

        $latest_version = ltrim((string) $release_data['tag_name'], 'v');

        // Ignorer les tags vides ou malformés
        if (empty($latest_version) || !preg_match('/^\d+\.\d+/', $latest_version)) {
            return $transient;
        }

        // Comparaison stricte des versions
        if (version_compare($current_version, $latest_version, '<')) {
            // Ajout des informations de mise à jour
            $transient->response[$theme_slug] = [
                'theme' => $theme_slug,
                'new_version' => $latest_version,
                'url' => $this->github_url,
                'package' => $release_data['zipball_url'],
                'requires' => '5.0', // Version minimale de WordPress requise
                'requires_php' => '8.0', // Version minimale de PHP requise
                'last_updated' => $release_data['published_at'],
                'sections' => [
                    'description' => $release_data['body'],
                    'changelog' => $release_data['body']
                ]
            ];
        } else {
            // Si la version est identique ou plus récente, on s'assure qu'il n'y a pas de notification
            unset($transient->response[$theme_slug]);
        }

        return $transient;
    }

    /**
     * Récupère les informations du thème pour l'API WordPress
     *
     * Cette méthode fournit les informations détaillées du thème pour
     * l'interface de mise à jour de WordPress, incluant la description,
     * le changelog et le lien de téléchargement.
     *
     * @since 1.0.0
     * @param bool|object $false Valeur par défaut
     * @param string $action Type d'action demandée
     * @param object $args Arguments de la requête
     * @return array|bool Informations du thème ou false
     */
    public function getThemeInfo($false, $action, $args)
    {
        // TODO : activer quand FluentCart est intégré.
        // if (!$this->license_manager->isLicenseValid()) {
        //     return $false;
        // }

        if ($action !== 'theme_information') {
            return $false;
        }

        $theme_slug = basename(\get_template_directory());

        if ($args->slug !== $theme_slug) {
            return $false;
        }

        $response = \wp_remote_get(self::GITHUB_API_URL, self::REQUEST_ARGS);

        if (\is_wp_error($response)) {
            return $false;
        }

        $release_data = json_decode(\wp_remote_retrieve_body($response), true);

        if (empty($release_data) || !isset($release_data['tag_name'])) {
            return $false;
        }

        return [
            'name' => 'G2RD Theme',
            'slug' => $theme_slug,
            'version' => ltrim($release_data['tag_name'], 'v'),
            'author' => 'Sebastien GERARD',
            'author_profile' => 'https://github.com/SebG2RD',
            'last_updated' => $release_data['published_at'],
            'homepage' => $this->github_url,
            'sections' => [
                'description' => $release_data['body'],
                'changelog' => $release_data['body'],
            ],
            'download_link' => $release_data['zipball_url'],
        ];
    }

    /**
     * Empêche le renommage du thème lors de la mise à jour
     *
     * Cette méthode intercepte le processus de mise à jour pour conserver
     * le nom original du dossier du thème.
     *
     * @since 1.0.0
     * @param string $source Chemin du dossier source
     * @param string $remote_source URL de la source distante
     * @param \WP_Upgrader $upgrader Instance de l'upgrader
     * @param array $args Arguments supplémentaires
     * @return string Chemin du dossier source modifié
     */
    /**
     * Empêche le renommage du thème lors de la mise à jour.
     *
     * GitHub extrait le zip dans un dossier nommé `{repo}-{sha}` (ex. G2RD-Theme-FSE-abc1234).
     * Cette méthode le renomme vers le slug réel du thème actif.
     *
     * @param string        $source        Chemin temporaire du dossier extrait
     * @param string        $remote_source Chemin du zip téléchargé
     * @param \WP_Upgrader  $upgrader      Instance de l'upgrader
     * @param array<string, mixed> $args   Arguments (contient 'theme' = slug)
     * @return string|\WP_Error Chemin renommé ou WP_Error si échec
     */
    public function preventThemeRename( string $source, string $remote_source, \WP_Upgrader $upgrader, array $args ): string|\WP_Error
    {
        if (!isset($args['theme'])) {
            return $source;
        }

        $theme_slug = basename(\get_template_directory());

        if ($args['theme'] !== $theme_slug) {
            return $source;
        }

        $source_dir = basename(untrailingslashit($source));

        // Déjà au bon nom, rien à faire
        if ($source_dir === $theme_slug) {
            return $source;
        }

        $new_source = trailingslashit(dirname(untrailingslashit($source))) . $theme_slug;

        $wp_filesystem = $this->getFilesystem();

        if (!$wp_filesystem) {
            return new \WP_Error(
                'g2rd_filesystem_unavailable',
                \__('Le système de fichiers WordPress est indisponible pour le renommage du thème.', 'g2rd')
            );
        }

        // Supprimer l'éventuel dossier résiduel avant renommage
        if (is_dir($new_source)) {
            $wp_filesystem->delete($new_source, true);
        }

        // Utiliser WP_Filesystem->move() pour la compatibilité hosting restrictif
        if (!$wp_filesystem->move($source, $new_source)) {
            return new \WP_Error(
                'g2rd_rename_failed',
                \sprintf(
                    /* translators: 1: ancien chemin, 2: nouveau chemin */
                    \__('Impossible de renommer le dossier du thème de %1$s vers %2$s.', 'g2rd'),
                    \esc_html($source),
                    \esc_html($new_source)
                )
            );
        }

        return trailingslashit($new_source);
    }

    /**
     * Retourne une instance de WP_Filesystem initialisée, ou null.
     *
     * @return \WP_Filesystem_Base|null
     */
    private function getFilesystem(): ?\WP_Filesystem_Base
    {
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
    }
}
