<?php

/**
 * Gestion du mode sombre (Dark Mode)
 *
 * Cette classe gère l'activation/désactivation du mode sombre,
 * la sauvegarde de la préférence utilisateur et l'intégration
 * avec les variations de thème WordPress.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe pour gérer le dark mode
 *
 * Cette classe permet de :
 * - Détecter la préférence système de l'utilisateur
 * - Sauvegarder le choix de l'utilisateur dans un cookie/localStorage
 * - Appliquer automatiquement le mode sombre via une classe CSS sur le body
 * - Fournir un toggle pour basculer manuellement
 * - Synchroniser l'état via AJAX (optionnel pour les utilisateurs connectés)
 *
 * @package G2RD
 * @since 1.0.0
 */
class DarkMode
{
    /**
     * Clé pour stocker la préférence utilisateur
     */
    private const PREFERENCE_KEY = 'g2rd_dark_mode';

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
     * Enregistre tous les hooks nécessaires
     *
     * @since 1.0.0
     * @return void
     */
    public function registerHooks(): void
    {
        \add_action('wp_enqueue_scripts', [$this, 'enqueueDarkModeAssets']);
        \add_filter('body_class', [$this, 'addDarkModeBodyClass']);
        \add_action('wp_ajax_g2rd_toggle_dark_mode', [$this, 'toggleDarkMode']);
        \add_action('wp_ajax_nopriv_g2rd_toggle_dark_mode', [$this, 'toggleDarkMode']);
    }

    /**
     * Charge le script JavaScript et les styles CSS pour le dark mode
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueueDarkModeAssets(): void
    {
        $dir_path = \get_template_directory();
        $dir_uri  = \get_template_directory_uri();

        // Charger Dashicons côté front (icônes du bouton toggle)
        \wp_enqueue_style('dashicons');

        // Styles du dark mode
        $css_path = $dir_path . '/assets/css/dark-mode.css';
        \wp_enqueue_style(
            'g2rd-dark-mode',
            $dir_uri . '/assets/css/dark-mode.css',
            [],
            file_exists($css_path) ? filemtime($css_path) : $this->theme_version
        );

        // Script du dark mode (chargé en différé via ScriptsManager::addDeferAttribute)
        $js_path = $dir_path . '/assets/js/dark-mode.js';
        \wp_enqueue_script(
            'g2rd-dark-mode',
            $dir_uri . '/assets/js/dark-mode.js',
            [],
            file_exists($js_path) ? filemtime($js_path) : $this->theme_version,
            true
        );

        // Passer les données nécessaires au script
        \wp_localize_script('g2rd-dark-mode', 'g2rdDarkMode', [
            'ajaxUrl'      => \admin_url('admin-ajax.php'),
            'nonce'        => \wp_create_nonce('g2rd_dark_mode_nonce'),
            'preferenceKey' => self::PREFERENCE_KEY,
            'isUserLogged' => \is_user_logged_in(),
        ]);
    }

    /**
     * Ajoute une classe au body si le dark mode est actif (basé sur le cookie)
     *
     * Cela évite le FOUC (flash of unstyled content) en appliquant la classe
     * dès le rendu serveur lorsque le cookie est présent.
     *
     * @since 1.0.0
     * @param array $classes Les classes existantes du body
     * @return array Les classes mises à jour
     */
    public function addDarkModeBodyClass(array $classes): array
    {
        $cookie_val = isset($_COOKIE[self::PREFERENCE_KEY])
            ? \sanitize_text_field(\wp_unslash($_COOKIE[self::PREFERENCE_KEY]))
            : '';
        if ('enabled' === $cookie_val) {
            $classes[] = 'dark-mode-active';
        }

        return $classes;
    }

    /**
     * Gère le toggle du dark mode via AJAX (optionnel)
     *
     * Permet de sauvegarder la préférence en base pour les utilisateurs connectés.
     *
     * @since 1.0.0
     * @return void
     */
    public function toggleDarkMode(): void
    {
        \check_ajax_referer('g2rd_dark_mode_nonce', 'nonce');

        $current_state = isset($_POST['enabled'])
            ? \sanitize_text_field(\wp_unslash($_POST['enabled']))
            : 'disabled';

        // Valider la valeur reçue
        if (!\in_array($current_state, ['enabled', 'disabled'], true)) {
            \wp_send_json_error(['message' => 'Valeur invalide.'], 400);
            return;
        }

        // Sauvegarder en base pour les utilisateurs connectés
        if (\is_user_logged_in()) {
            \update_user_meta(\get_current_user_id(), self::PREFERENCE_KEY, $current_state);
        }

        \wp_send_json_success([
            'message' => 'Préférence sauvegardée.',
            'state'   => $current_state,
        ]);
    }
}
