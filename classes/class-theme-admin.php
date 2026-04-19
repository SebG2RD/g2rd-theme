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
     * Enregistre tous les hooks nécessaires pour la personnalisation de l'admin
     *
     * @since 1.0.0
     * @return void
     */
    /**
     * Enregistre les hooks WordPress de l'administration (hors page de connexion).
     *
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'admin_enqueue_scripts', [ $this, 'registerAdminAssets' ] );
        \add_action( 'admin_head', [ $this, 'outputAdminColorVars' ], 20 );
        \add_filter( 'manage_posts_columns', [ $this, 'addFeaturedImageColumn' ] );
        \add_action( 'manage_posts_custom_column', [ $this, 'displayFeaturedImageColumn' ], 10, 2 );
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
     * Retourne l'URL du logo G2RD utilisé dans la barre d'administration.
     *
     * @return string
     */
    private function getLogoUrl(): string {
        return \get_template_directory_uri() . '/assets/img/Nouveau-logo-G2RD-Agence-Web-blanc-Horizontale@3x.png';
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
            'admin_bg'       => '#2f425d',
            'admin_text'     => '#f0f0f0',
            'btn_bg'         => '#d4a373',
            'btn_text'       => '#f0f0f0',
            'btn_bg_hover'   => '#c4935c',
            'btn_text_hover' => '#f0f0f0',
        ];
        $default_slugs = [
            'admin_bg'       => 'primary',
            'admin_text'     => 'white',
            'btn_bg'         => 'secondary',
            'btn_text'       => 'white',
            'btn_bg_hover'   => 'secondary',
            'btn_text_hover' => 'white',
        ];

        $saved = (array) \get_option('g2rd_admin_colors', []);

        $resolve = function (string $key) use ($saved, $default_slugs, $palette_map, $fallbacks): string {
            $slug = $saved[$key] ?? $default_slugs[$key];
            return $palette_map[$slug] ?? $fallbacks[$key];
        };

        $bg              = $resolve('admin_bg');
        $text            = $resolve('admin_text');
        $btn_bg          = $resolve('btn_bg');
        $btn_text        = $resolve('btn_text');
        $btn_bg_hover    = $resolve('btn_bg_hover');
        $btn_text_hover  = $resolve('btn_text_hover');
        $submenu_bg      = $this->darkenHex($bg, 25);

        \printf(
            '<style id="g2rd-admin-colors">
:root {
    --primary-color: %1$s;
    --secondary-color: %2$s;
    --text-light: %3$s;
    --menu-submenu-bg: %4$s;
    --menu-highlight-color: %2$s;
}
.wp-core-ui .button-primary {
    background: %2$s !important;
    border-color: %2$s !important;
    color: %5$s !important;
    box-shadow: none !important;
    text-shadow: none !important;
}
.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus,
.wp-core-ui .button-primary:active {
    background: %6$s !important;
    border-color: %6$s !important;
    color: %7$s !important;
    box-shadow: none !important;
    text-shadow: none !important;
}
</style>',
            \esc_attr($bg),
            \esc_attr($btn_bg),
            \esc_attr($text),
            \esc_attr($submenu_bg),
            \esc_attr($btn_text),
            \esc_attr($btn_bg_hover),
            \esc_attr($btn_text_hover)
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
