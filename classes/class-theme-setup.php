<?php
/**
 * Classe principale pour la configuration du thème
 * 
 * Cette classe gère l'initialisation et la configuration de base du thème,
 * incluant l'enregistrement des assets, la configuration des types MIME,
 * et la mise en place des fonctionnalités du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe principale pour la configuration du thème
 * 
 * Cette classe gère l'initialisation et la configuration de base du thème,
 * incluant l'enregistrement des assets, la configuration des types MIME,
 * et la mise en place des fonctionnalités du thème.
 *
 * @package G2RD
 * @since 1.0.0
 */
class ThemeSetup {
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
     * Enregistre tous les hooks nécessaires pour le thème
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        \add_action('after_setup_theme', [$this, 'loadThemeTextdomain']);
        \add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        \add_filter('upload_mimes', [$this, 'allowMimeTypes']);
        \add_filter('wp_check_filetype_and_ext', [$this, 'allowFileTypes'], 10, 4);
        \add_filter('sanitize_file_name', 'remove_accents');
        \add_action('init', [$this, 'g2rd_register_block_patterns']);
        // send_headers s'exécute avant tout output HTML → header() fonctionne correctement
        \add_action('send_headers', [$this, 'addSecurityHeaders']);
        \add_action('wp_head', [$this, 'addPreloadLinks'], 2);

        $this->setupFeatures();
    }

    /**
     * Ajoute les en-têtes de sécurité HTTP.
     *
     * Hooké sur send_headers (avant tout output HTML) pour que header() soit effectif.
     */
    public function addSecurityHeaders(): void {
        if (\is_admin()) {
            return;
        }

        \header('X-Content-Type-Options: nosniff');
        \header('X-Frame-Options: SAMEORIGIN');
        \header('Referrer-Policy: strict-origin-when-cross-origin');
        // Permissions-Policy : désactiver les APIs sensibles non utilisées
        \header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    /**
     * Ajoute les liens de préchargement pour les ressources critiques
     */
    public function addPreloadLinks(): void {
        if (\is_admin()) {
            return;
        }

        $uri = \get_template_directory_uri();

        // Précharger la police principale du thème (fetchpriority=high pour LCP)
        echo '<link rel="preload" href="' . \esc_url($uri) . '/assets/fonts/Inter_28pt-Regular.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">' . "\n";

        // Préconnexion au CDN Typed.js uniquement si le bloc est présent sur la page
        if (\has_block('g2rd/typed')) {
            echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>' . "\n";
            echo '<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">' . "\n";
        }
    }

    /**
     * Enregistre et charge les styles et scripts principaux du thème
     *
     * @since 1.0.0
     * @return void
     */
    public function registerAssets(): void {
        // Styles principaux avec version du thème
        \wp_enqueue_style('main', \get_stylesheet_uri(), [], $this->theme_version);

        // Styles d'accessibilité
        \wp_enqueue_style(
            'g2rd-accessibility',
            \get_template_directory_uri() . '/assets/css/accessibility.css',
            [],
            $this->theme_version
        );

        // Scripts d'accessibilité avec chargement différé (stratégie native WP 6.4+)
        \wp_enqueue_script(
            'g2rd-accessibility',
            \get_template_directory_uri() . '/assets/js/accessibility.js',
            [],
            $this->theme_version,
            true
        );
        \wp_script_add_data('g2rd-accessibility', 'strategy', 'defer');

        // Ajouter les données localisées pour les scripts
        wp_localize_script('g2rd-accessibility', 'g2rdData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('g2rd-nonce')
        ]);
    }

    /**
     * Ajoute le support pour les fichiers SVG et WebP
     *
     * @since 1.0.0
     * @param array $mimes Liste des types MIME autorisés
     * @return array Liste mise à jour des types MIME
     */
    public function allowMimeTypes($mimes): array {
        $mimes['svg'] = 'image/svg+xml';
        $mimes['webp'] = 'image/webp';
        $mimes['avif'] = 'image/avif';

        return $mimes;
    }

    /**
     * Configure la validation des types de fichiers pour SVG et WebP
     *
     * @since 1.0.0
     * @param array $types Types de fichiers
     * @param string $file Chemin du fichier
     * @param string $filename Nom du fichier
     * @param array $mimes Types MIME
     * @return array Types de fichiers mis à jour
     */
    public function allowFileTypes($types, $file, $filename, $mimes): array {
        if (\str_ends_with($filename, '.webp')) {
            $types['ext']  = 'webp';
            $types['type'] = 'image/webp';
        } elseif (\str_ends_with($filename, '.avif')) {
            $types['ext']  = 'avif';
            $types['type'] = 'image/avif';
        }

        return $types;
    }

    /**
     * Configure les fonctionnalités de base du thème
     *
     * @since 1.0.0
     * @return void
     */
    public function setupFeatures(): void {
        # Retirer la suggestion de blocs
        \remove_theme_support('core-block-patterns');

        # Ajouter des fonctionnalités
        \add_theme_support('editor-styles');
        \add_theme_support('responsive-embeds');
        \add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

        # Désactiver l'ancienne API XML RPC
        \add_filter('xmlrpc_enabled', '__return_false');

        # Retirer les scripts des Emojis
        \remove_action('admin_print_styles', 'print_emoji_styles');
        \remove_action('wp_head', 'print_emoji_detection_script', 7);
        \remove_action('admin_print_scripts', 'print_emoji_detection_script');
        \remove_action('wp_print_styles', 'print_emoji_styles');
        \remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        \remove_filter('the_content_feed', 'wp_staticize_emoji');
        \remove_filter('comment_text_rss', 'wp_staticize_emoji');

        # Désactiver les fonctionnalités inutiles
        \remove_action('wp_head', 'wp_generator');
        \remove_action('wp_head', 'wlwmanifest_link');
        \remove_action('wp_head', 'rsd_link');
        \remove_action('wp_head', 'wp_shortlink_wp_head');

        # Supprimer les liens REST API du <head> et des en-têtes HTTP (inutiles pour le front)
        \remove_action('wp_head', 'rest_output_link_wp_head', 10);
        \remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        \remove_action('template_redirect', 'rest_output_link_header', 11);
    }

    /**
     * Enregistre les catégories de patterns de blocs personnalisés
     *
     * @since 1.0.0
     * @return void
     */
    public function g2rd_register_block_patterns(): void {
        // Enregistrer les catégories
        $categories = [
            'design' => \__('Design', 'g2rd'),
            'card' => \__('Card', 'g2rd'),
            'hero' => \__('Hero', 'g2rd'),
            'info' => \__('Info', 'g2rd'),
            'posts' => \__('Posts', 'g2rd'),
            'header' => \__('Header', 'g2rd'),
            'footer' => \__('Footer', 'g2rd'),
            'widgets' => \__('Widgets', 'g2rd')
        ];

        foreach ($categories as $slug => $label) {
            \register_block_pattern_category($slug, ['label' => $label]);
        }
    }

    /**
     * Charge les traductions du thème
     *
     * @since 1.0.2
     * @return void
     */
    public function loadThemeTextdomain(): void {
        load_theme_textdomain('g2rd', get_template_directory() . '/languages');
    }
}
