<?php
/**
 * Administration du thème
 * 
 * Cette classe gère les fonctionnalités d'administration du thème,
 * y compris les pages de configuration et les options personnalisées.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion de l'interface d'administration WordPress
 * 
 * Cette classe personnalise l'interface d'administration WordPress,
 * notamment la page de connexion et le tableau de bord, avec le branding G2RD.
 *
 * @package G2RD
 * @since 1.0.0
 */
class ThemeAdmin {
    /**
     * Chemin du logo G2RD
     *
     * @since 1.0.0
     * @var string
     */
    private const LOGO_PATH = '/assets/img/Nouveau-logo-G2RD-Agence-Web-blanc-Horizontale@3x.png';
    
    /**
     * Chemin de l'image de fond de la page de connexion
     *
     * @since 1.0.0
     * @var string
     */
    private const BACKGROUND_IMAGE_PATH = '/assets/img/g2rd_image_admin.jpg';
    
    /**
     * URL du site G2RD
     *
     * @since 1.0.0
     * @var string
     */
    private const G2RD_WEBSITE = 'https://g2rd.fr';
    
    /**
     * Enregistre tous les hooks nécessaires pour la personnalisation de l'admin
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        // Hooks pour les styles (CSS/JS inline injectés via wp_add_inline_style/script dans les callbacks)
        \add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        \add_action('login_enqueue_scripts', [$this, 'registerLoginAssets']);

        // Hooks pour personnaliser le logo de connexion
        \add_filter('login_headerurl', [$this, 'customLoginLogoUrl']);
        \add_filter('login_headertext', [$this, 'customLoginLogoText']);

        // Hooks pour personnaliser la structure de la page de connexion
        \add_action('login_header', [$this, 'customLoginStructure'], 0);
        \add_action('login_footer', [$this, 'customLoginFooter']);

        // Hook pour injecter les variables CSS de couleurs admin personnalisées
        \add_action('admin_head', [$this, 'outputAdminColorVars'], 20);

        // Hooks pour la colonne d'image mise en avant
        \add_filter('manage_posts_columns', [$this, 'addFeaturedImageColumn']);
        \add_action('manage_posts_custom_column', [$this, 'displayFeaturedImageColumn'], 10, 2);
    }
    
    /**
     * Obtient l'URL complète du logo G2RD
     *
     * @since 1.0.0
     * @return string URL du logo
     */
    private function getLogoUrl(): string {
        return \get_template_directory_uri() . self::LOGO_PATH;
    }
    
    /**
     * Obtient l'URL complète de l'image de fond
     *
     * @since 1.0.0
     * @return string URL de l'image de fond
     */
    private function getBackgroundImageUrl(): string {
        return \get_template_directory_uri() . self::BACKGROUND_IMAGE_PATH;
    }
    
    /**
     * Enregistre et charge les styles CSS pour l'interface d'administration
     * + injecte le logo G2RD dans la barre d'administration via wp_add_inline_style.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerAdminAssets(): void {
        \wp_enqueue_style(
            'g2rd-admin',
            \get_template_directory_uri() . '/assets/css/admin.css',
            [],
            \filemtime(\get_template_directory() . '/assets/css/admin.css')
        );

        $logo_url = \esc_url( $this->getLogoUrl() );
        \wp_add_inline_style(
            'g2rd-admin',
            "#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
    background-image: url({$logo_url}) !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    background-size: contain !important;
    content: \"\" !important;
    top: 0 !important;
    width: 100% !important;
    height: 100% !important;
}
#wpadminbar #wp-admin-bar-wp-logo > .ab-item {
    padding-right: 50px !important;
}
#wpadminbar #wp-admin-bar-wp-logo > .ab-sub-wrapper {
    display: none !important;
}"
        );
    }

    /**
     * Enregistre et charge les styles CSS pour la page de connexion
     * + injecte le logo, le fond et le bouton G2RD via wp_add_inline_style/script.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerLoginAssets(): void {
        \wp_enqueue_style(
            'g2rd-login',
            \get_template_directory_uri() . '/assets/css/login.css',
            [],
            \filemtime(\get_template_directory() . '/assets/css/login.css')
        );

        $logo_url = \esc_url( $this->getLogoUrl() );
        $bg_url   = \esc_url( $this->getBackgroundImageUrl() );
        \wp_add_inline_style(
            'g2rd-login',
            ".login h1 a {
    background-image: url({$logo_url}) !important;
    background-size: contain !important;
    width: 250px !important;
    height: 70px !important;
    margin-bottom: 30px !important;
}
.login-image {
    background-image: url({$bg_url}) !important;
}
.g2rd-button {
    display: block;
    width: 100%;
    margin: 15px 0 5px;
    padding: 12px;
    background: var(--secondary-color);
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.g2rd-button:hover {
    background: var(--secondary-color-darker);
    transform: translateY(-2px);
    color: white;
}"
        );

        // Bouton de redirection G2RD — injecté via wp_add_inline_script (pas d'echo direct)
        \wp_register_script( 'g2rd-login-js', false, [], false, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
        \wp_enqueue_script( 'g2rd-login-js' );
        $g2rd_url     = \esc_js( self::G2RD_WEBSITE );
        $button_label = \esc_js( \__( 'Visiter G2RD Agence Web', 'g2rd' ) );
        \wp_add_inline_script(
            'g2rd-login-js',
            "document.addEventListener('DOMContentLoaded', function() {
    var loginForm = document.getElementById('loginform');
    if (loginForm) {
        var button = document.createElement('a');
        button.href = '{$g2rd_url}';
        button.target = '_blank';
        button.className = 'g2rd-button';
        button.textContent = '{$button_label}';
        loginForm.insertAdjacentElement('afterend', button);
    }
});"
        );
    }
    
    /**
     * Définit l'URL du logo sur la page de connexion
     *
     * @since 1.0.0
     * @return string URL de la page d'accueil
     */
    public function customLoginLogoUrl(): string {
        return \home_url('/');
    }

    /**
     * Définit le texte alternatif du logo sur la page de connexion
     *
     * @since 1.0.0
     * @return string Nom du site
     */
    public function customLoginLogoText(): string {
        return \get_bloginfo('name');
    }

    /**
     * Ajoute la structure HTML personnalisée pour la page de connexion
     *
     * @since 1.0.0
     * @return void
     */
    public function customLoginStructure(): void {
        echo '<div class="login-container">';
        echo '<div class="login-image"></div>';
    }

    /**
     * Ajoute le pied de page personnalisé pour la page de connexion
     *
     * @since 1.0.0
     * @return void
     */
    public function customLoginFooter(): void {
        echo '</div>'; // Fermeture de login-container
    }
    
    /**
     * Assombrit une couleur hexadécimale d'un montant donné par canal RGB.
     *
     * @param  string $hex    Couleur hex (avec ou sans #).
     * @param  int    $amount Valeur à soustraire (0–255).
     * @return string Couleur hex assombrie.
     */
    private function darkenHex(string $hex, int $amount = 25): string {
        $hex = \ltrim($hex, '#');
        if (\strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = \max(0, \hexdec(\substr($hex, 0, 2)) - $amount);
        $g = \max(0, \hexdec(\substr($hex, 2, 2)) - $amount);
        $b = \max(0, \hexdec(\substr($hex, 4, 2)) - $amount);
        return \sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Injecte un bloc <style> dans <head> avec les variables CSS de couleurs
     * personnalisées pour l'interface admin.
     *
     * Les couleurs sont résolues depuis les slugs sauvegardés dans wp_options
     * et la palette active du theme.json (incluant les variations de styles).
     *
     * @return void
     */
    public function outputAdminColorVars(): void {
        // Palette active (thème + variation de style)
        $palette_raw = \wp_get_global_settings(['color', 'palette', 'theme']);
        $palette_map = [];
        foreach ((array) $palette_raw as $item) {
            if (!empty($item['slug']) && !empty($item['color'])) {
                $palette_map[ $item['slug'] ] = $item['color'];
            }
        }

        // Valeurs par défaut si la palette est vide ou le slug introuvable
        $fallbacks = [
            'admin_bg'   => '#2f425d',
            'admin_text' => '#f0f0f0',
            'btn_bg'     => '#d4a373',
            'btn_text'   => '#f0f0f0',
        ];
        $default_slugs = [
            'admin_bg'   => 'primary',
            'admin_text' => 'white',
            'btn_bg'     => 'secondary',
            'btn_text'   => 'white',
        ];

        $saved = (array) \get_option('g2rd_admin_colors', []);

        $resolve = function (string $key) use ($saved, $default_slugs, $palette_map, $fallbacks): string {
            $slug = $saved[$key] ?? $default_slugs[$key];
            return $palette_map[$slug] ?? $fallbacks[$key];
        };

        $bg         = $resolve('admin_bg');
        $text       = $resolve('admin_text');
        $btn_bg     = $resolve('btn_bg');
        $btn_text   = $resolve('btn_text');
        $submenu_bg = $this->darkenHex($bg, 25);

        \printf(
            '<style id="g2rd-admin-colors">
:root {
    --primary-color: %1$s;
    --secondary-color: %2$s;
    --text-light: %3$s;
    --menu-submenu-bg: %4$s;
    --menu-highlight-color: %2$s;
}
.wp-core-ui .button-primary,
.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus,
.wp-core-ui .button-primary:active {
    background: %2$s !important;
    border-color: %2$s !important;
    color: %5$s !important;
    box-shadow: none !important;
    text-shadow: none !important;
}
</style>',
            \esc_attr($bg),
            \esc_attr($btn_bg),
            \esc_attr($text),
            \esc_attr($submenu_bg),
            \esc_attr($btn_text)
        );
    }

    /**
     * Ajoute une colonne pour l'image mise en avant dans la liste des articles
     *
     * @since 1.0.0
     * @param array $columns Les colonnes existantes
     * @return array Les colonnes modifiées
     */
    public function addFeaturedImageColumn(array $columns): array {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new_columns['featured_image'] = \__('Image mise en avant', 'g2rd');
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }

    /**
     * Affiche l'image mise en avant dans la colonne personnalisée
     *
     * @since 1.0.0
     * @param string $column_name Le nom de la colonne
     * @param int $post_id L'ID de l'article
     * @return void
     */
    public function displayFeaturedImageColumn(string $column_name, int $post_id): void {
        if ($column_name === 'featured_image') {
            if (has_post_thumbnail($post_id)) {
                $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                echo '<img src="' . esc_url($thumbnail) . '" style="width: 150px; height: 150px; object-fit: cover;" alt="Image mise en avant">';
            } else {
                echo '—';
            }
        }
    }
}

