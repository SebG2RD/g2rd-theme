<?php
/**
 * Gestion des articles cliquables
 *
 * Cette classe gère la fonctionnalité des articles cliquables,
 * permettant de rendre l'ensemble d'un article interactif.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des articles cliquables
 *
 * Cette classe gère l'ajout et la configuration de la fonctionnalité
 * de clic sur les articles dans les blocs de type group.
 *
 * @package G2RD
 * @since 1.0.0
 */
class ClickableArticles {
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
     * Enregistre les hooks nécessaires pour les articles cliquables
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void {
        \add_action( 'wp_enqueue_scripts',      [ $this, 'registerFrontendScripts' ] );
        \add_action( 'enqueue_block_editor_assets', [ $this, 'registerEditorScripts' ] );
        \add_filter( 'render_block',            [ $this, 'addClickableAttribute' ], 10, 2 );
        \add_action( 'wp_head',                 [ $this, 'outputCursorStyle' ], 20 );
    }

    /**
     * Enregistre et charge les scripts pour le frontend
     *
     * @since 1.0.0
     * @return void
     */
    public function registerFrontendScripts(): void {
        // Charger le script uniquement sur le frontend
        if (!\is_admin()) {
            $script_path = \get_template_directory() . '/assets/js/clickable-articles.js';
            $version = file_exists($script_path) ? filemtime($script_path) : $this->theme_version;

            \wp_enqueue_script(
                'g2rd-clickable-articles',
                \get_template_directory_uri() . '/assets/js/clickable-articles.js',
                [],
                $version,
                true
            );

            // Ajouter les données localisées pour l'accessibilité
            \wp_localize_script('g2rd-clickable-articles', 'g2rdClickableData', [
                'isMobile' => wp_is_mobile(),
                'prefersReducedMotion' => $this->shouldReduceMotion(),
                'keyboardNavigation' => true
            ]);
        }
    }

    /**
     * Vérifie si l'utilisateur préfère les mouvements réduits
     */
    private function shouldReduceMotion(): bool {
        if (isset($_COOKIE['prefers-reduced-motion'])) {
            return \sanitize_text_field(\wp_unslash($_COOKIE['prefers-reduced-motion'])) === 'true';
        }
        return false;
    }

    /**
     * Enregistre et charge les contrôles dans l'éditeur
     *
     * @since 1.0.0
     * @return void
     */
    public function registerEditorScripts(): void {
        $script_path = \get_template_directory() . '/assets/js/g2rd-clickable-articles-sidebar.js';
        $version = file_exists($script_path) ? filemtime($script_path) : $this->theme_version;

        \wp_enqueue_script(
            'g2rd-clickable-articles-sidebar',
            \get_template_directory_uri() . '/assets/js/g2rd-clickable-articles-sidebar.js',
            [
                'wp-blocks',
                'wp-dom-ready',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'wp-compose',
                'wp-data',
                'wp-i18n',
                'wp-hooks',
            ],
            $version,
            true
        );
    }

    /**
     * Ajoute l'attribut data-clickable-articles au premier tag du bloc.
     *
     * Utilise WP_HTML_Tag_Processor (WP 6.2+) pour une modification fiable,
     * quel que soit l'ordre des classes ou la structure de layout (WP 6.6+).
     *
     * @since 1.0.0
     * @param string               $block_content Contenu HTML du bloc.
     * @param array<string, mixed> $block         Données du bloc.
     * @return string Contenu HTML modifié.
     */
    /**
     * Injecte cursor:pointer pour les éléments cliquables (indépendant du JS).
     *
     * @return void
     */
    public function outputCursorStyle(): void {
        if ( \is_admin() ) {
            return;
        }
        echo "<style>[data-clickable-articles=\"true\"]{cursor:pointer}</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- valeur CSS statique, aucune donnée utilisateur
    }

    /**
     * Ajoute l'attribut data-clickable-articles au wrapper du bloc.
     *
     * Utilise WP_HTML_Tag_Processor (WP 6.2+) : next_tag() sans filtre de classe
     * pour cibler le premier tag du bloc (toujours le wrapper) quel que soit le
     * layout ou les classes générées par WordPress 6.6+.
     *
     * @since 1.0.0
     * @param string               $block_content Contenu HTML du bloc.
     * @param array<string, mixed> $block         Données du bloc.
     * @return string Contenu HTML modifié.
     */
    public function addClickableAttribute( string $block_content, array $block ): string {
        if ( 'core/group' !== $block['blockName'] && 'core/columns' !== $block['blockName'] ) {
            return $block_content;
        }

        if ( empty( $block['attrs']['clickableArticles'] ) ) {
            return $block_content;
        }

        $processor = new \WP_HTML_Tag_Processor( $block_content );

        if ( $processor->next_tag() ) {
            $processor->set_attribute( 'data-clickable-articles', 'true' );
            $processor->set_attribute( 'tabindex', '0' );
            return $processor->get_updated_html();
        }

        return $block_content;
    }
}
