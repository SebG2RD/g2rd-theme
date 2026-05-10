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

class GitHubUpdater {
    /**
     * URL du dépôt GitHub (page publique)
     *
     * @since 1.0.0
     * @var string
     */
    private string $github_url = 'https://github.com/SebG2RD/g2rd-theme';

    /**
     * Endpoint API GitHub pour les releases
     *
     * @since 1.2.1
     * @var string
     */
    private const GITHUB_API_URL = 'https://api.github.com/repos/SebG2RD/g2rd-theme/releases/latest';

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
    public function __construct( LicenseManager $license_manager ) {
        $this->license_manager = $license_manager;
        $this->register_hooks();
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
    public function register_hooks() {
        \add_filter('pre_set_site_transient_update_themes', [$this, 'checkForUpdates']);
        \add_filter('themes_api', [$this, 'getThemeInfo'], 10, 3);
        \add_filter('upgrader_source_selection', [$this, 'preventThemeRename'], 10, 4);
        \add_action('admin_footer-update-core.php', [$this, 'injectUpdateDetailsLink']);
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
    public function checkForUpdates($transient) {
        // Mises à jour réservées aux licences actives
        if (!$this->license_manager->isLicenseValid()) {
            return $transient;
        }

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
                'theme'        => $theme_slug,
                'new_version'  => $latest_version,
                'url'          => \self_admin_url( 'theme-install.php?tab=theme-information&theme=' . rawurlencode( $theme_slug ) ),
                'package'      => $this->get_download_url($release_data),
                'requires'     => '6.5',
                'requires_php' => '8.0',
                'last_updated' => $release_data['published_at'],
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
    public function getThemeInfo($false, $action, $args) {
        if (!$this->license_manager->isLicenseValid()) {
            return $false;
        }

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

        $latest_version   = ltrim( (string) $release_data['tag_name'], 'v' );
        $changelog_html   = $this->formatChangelog( (string) ( $release_data['body'] ?? '' ) );
        $description_html = '<p>' . \esc_html__( 'Thème WordPress Full Site Editing moderne et flexible pour les agences web.', 'g2rd' ) . '</p>';

        $info                 = new \stdClass();
        $info->name           = 'G2RD Theme';
        $info->slug           = $theme_slug;
        $info->version        = $latest_version;
        $info->author         = 'Sebastien GERARD';
        $info->author_profile = 'https://github.com/SebG2RD';
        $info->last_updated   = (string) ( $release_data['published_at'] ?? '' );
        $info->homepage       = $this->github_url;
        $info->requires       = '6.5';
        $info->requires_php   = '8.0';
        $info->download_link  = $this->get_download_url( $release_data );
        $info->sections       = [
            'description' => $description_html,
            'changelog'   => $changelog_html,
        ];

        return $info;
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
     * Normalise le dossier source extrait vers le slug du thème actif.
     *
     * WordPress peut passer soit le dossier temp externe (WP < 6.4) soit le
     * dossier interne déjà détecté (WP ≥ 6.4). Cette méthode gère les deux cas
     * en cherchant style.css à la racine puis un niveau plus bas.
     *
     * @param string        $source        Chemin temporaire (externe ou interne)
     * @param string        $remote_source Chemin du zip téléchargé (non utilisé)
     * @param \WP_Upgrader  $upgrader      Instance de l'upgrader (non utilisé)
     * @param array<string, mixed> $args   Arguments (contient 'theme' = slug)
     * @return string|\WP_Error Chemin normalisé ou WP_Error si échec
     */
    public function preventThemeRename( string $source, string $remote_source, \WP_Upgrader $upgrader, array $args ): string|\WP_Error // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    {
        if ( ! isset( $args['theme'] ) ) {
            return $source;
        }

        $theme_slug = basename( \get_template_directory() );

        if ( $args['theme'] !== $theme_slug ) {
            return $source;
        }

        $wp_filesystem = $this->getFilesystem();
        if ( ! $wp_filesystem ) {
            return new \WP_Error(
                'g2rd_filesystem_unavailable',
                \__( 'Le système de fichiers WordPress est indisponible pour le renommage du thème.', 'g2rd' )
            );
        }

        $source = \trailingslashit( $source );

        // Cas 1 : $source contient directement style.css (WP ≥ 6.4, dossier interne)
        if ( $wp_filesystem->is_file( $source . 'style.css' ) ) {
            $source_dir = basename( \untrailingslashit( $source ) );
            if ( $source_dir === $theme_slug ) {
                return $source;
            }
            return $this->moveToSlug( $source, $theme_slug, $wp_filesystem );
        }

        // Cas 2 : $source est le dossier temp externe (WP < 6.4)
        // Chercher un sous-dossier contenant style.css
        $entries = $wp_filesystem->dirlist( $source );
        if ( ! empty( $entries ) ) {
            foreach ( $entries as $file => $filedata ) {
                if ( 'd' !== $filedata['type'] ) {
                    continue;
                }
                $inner = \trailingslashit( $source . $file );
                if ( $wp_filesystem->is_file( $inner . 'style.css' ) ) {
                    if ( $file === $theme_slug ) {
                        return $inner;
                    }
                    return $this->moveToSlug( $inner, $theme_slug, $wp_filesystem );
                }
            }
        }

        return $source;
    }

    /**
     * Renomme un dossier source vers le slug du thème.
     *
     * @param string               $source       Chemin source avec trailing slash.
     * @param string               $theme_slug   Slug cible.
     * @param \WP_Filesystem_Base  $filesystem   Instance filesystem WP.
     * @return string|\WP_Error Nouveau chemin ou WP_Error.
     */
    private function moveToSlug( string $source, string $theme_slug, \WP_Filesystem_Base $filesystem ): string|\WP_Error {
        $new_source = \trailingslashit( dirname( \untrailingslashit( $source ) ) ) . $theme_slug;

        if ( $filesystem->is_dir( $new_source ) ) {
            $filesystem->delete( $new_source, true );
        }

        if ( ! $filesystem->move( $source, $new_source ) ) {
            return new \WP_Error(
                'g2rd_rename_failed',
                \sprintf(
                    /* translators: 1: ancien chemin, 2: nouveau chemin */
                    \__( 'Impossible de renommer le dossier du thème de %1$s vers %2$s.', 'g2rd' ),
                    \esc_html( $source ),
                    \esc_html( $new_source )
                )
            );
        }

        return \trailingslashit( $new_source );
    }

    /**
     * Injecte le lien « Afficher les détails » dans la ligne du thème sur la page des mises à jour.
     *
     * WordPress affiche ce lien automatiquement pour les plugins mais pas pour les thèmes
     * tiers. On l'injecte via JavaScript en ciblant la ligne du thème par son slug.
     *
     * @since 1.10.6
     * @return void
     */
    public function injectUpdateDetailsLink(): void {
        $theme_slug  = basename( \get_template_directory() );
        $update_data = \get_site_transient( 'update_themes' );

        if ( empty( $update_data->response[ $theme_slug ]['new_version'] ) ) {
            return;
        }

        $new_version = (string) $update_data->response[ $theme_slug ]['new_version'];
        $details_url = \add_query_arg(
            [
                'TB_iframe' => 'true',
                'width'     => 1024,
                'height'    => 800,
            ],
            \self_admin_url( 'theme-install.php?tab=theme-information&theme=' . rawurlencode( $theme_slug ) )
        );

        printf(
            '<script>
            ( function() {
                var slug  = %1$s;
                var url   = %2$s;
                var label = %3$s;
                var checkbox = document.querySelector( \'input[name="checked[]"][value="\' + slug + \'"]\' );
                if ( ! checkbox ) { return; }
                var row = checkbox.closest( "tr" );
                if ( ! row ) { return; }
                var td = row.querySelector( ".plugin-description p, .column-description p" );
                if ( ! td || td.querySelector( ".g2rd-details-link" ) ) { return; }
                var a = document.createElement( "a" );
                a.href      = url;
                a.className = "thickbox open-plugin-details-modal g2rd-details-link";
                a.setAttribute( "data-slug", slug );
                a.textContent = label;
                td.appendChild( document.createTextNode( " " ) );
                td.appendChild( a );
            } )();
            </script>',
            \wp_json_encode( $theme_slug ),
            \wp_json_encode( $details_url ),
            \wp_json_encode(
                sprintf(
                    /* translators: %s: numéro de version */
                    \__( 'Afficher les détails de la version %s.', 'g2rd' ),
                    $new_version
                )
            )
        );
    }

    /**
     * Convertit le corps Markdown d'une release GitHub en HTML sécurisé.
     *
     * @param string $markdown Corps de la release (Markdown GitHub).
     * @return string HTML échappé prêt à l'affichage dans le Thickbox WordPress.
     */
    private function formatChangelog( string $markdown ): string {
        $lines   = explode( "\n", $markdown );
        $html    = '';
        $in_list = false;

        foreach ( $lines as $line ) {
            $line = rtrim( $line );

            if ( preg_match( '/^### (.+)$/', $line, $m ) ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h4>' . \esc_html( $m[1] ) . '</h4>';
            } elseif ( preg_match( '/^## (.+)$/', $line, $m ) ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h3>' . \esc_html( $m[1] ) . '</h3>';
            } elseif ( preg_match( '/^[-*] (.+)$/', $line, $m ) ) {
                if ( ! $in_list ) {
                    $html   .= '<ul>';
                    $in_list = true;
                }
                $item = \esc_html( $m[1] );
                $item = (string) preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item ); // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird -- remplacement statique, pas de modificateur /e
                $html .= '<li>' . $item . '</li>';
            } elseif ( '' === $line ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
            } else {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $para  = \esc_html( $line );
                $para  = (string) preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $para ); // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird -- remplacement statique, pas de modificateur /e
                $html .= '<p>' . $para . '</p>';
            }
        }

        if ( $in_list ) {
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Extrait l'URL de téléchargement depuis les données de release GitHub.
     *
     * Préfère le fichier .zip uploadé comme asset de release (structure correcte
     * avec dossier wrapper g2rd-theme/) au zipball auto-généré par GitHub
     * (dont le dossier racine est nommé {owner}-{repo}-{sha}).
     *
     * @param array<string, mixed> $release_data Données de release de l'API GitHub.
     * @return string URL de téléchargement.
     */
    private function get_download_url( array $release_data ): string {
        if (!empty($release_data['assets'])) {
            foreach ((array) $release_data['assets'] as $asset) {
                if (!empty($asset['browser_download_url'])
                    && !empty($asset['name'])
                    && \str_ends_with((string) $asset['name'], '.zip')
                ) {
                    return (string) $asset['browser_download_url'];
                }
            }
        }

        return (string) ($release_data['zipball_url'] ?? '');
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
