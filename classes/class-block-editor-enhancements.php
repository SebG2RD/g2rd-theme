<?php

/**
 * Améliorations de l'éditeur de blocs Gutenberg
 *
 * - Dashicons dans l'iframe canvas de l'éditeur (WP 6.x)
 * - Plugin éditeur : infobulle (tooltip)
 * - Plugin éditeur : visibilité par appareil (responsive)
 * - Filtre render_block combiné : injection tooltip + classes responsive
 * - Vidage des règles de réécriture après changement de slug CPT
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe BlockEditorEnhancements
 */
class BlockEditorEnhancements {
    /**
     * Enregistre les hooks
     *
     * @return void
     */
    public function register_hooks(): void {
        // Dashicons dans l'iframe canvas
        \add_filter('block_editor_settings_all', [$this, 'injectDashiconsInCanvas']);

        // Tooltip — éditeur (la CSS frontend est chargée par renderBlock à la demande)
        \add_action('enqueue_block_editor_assets', [$this, 'enqueueTooltipEditorAssets']);

        // Visibilité responsive — panneau (cadre parent) + indicateur grisé (iframe du canvas)
        \add_action('enqueue_block_editor_assets', [$this, 'enqueueResponsiveEditorAssets']);
        \add_action('enqueue_block_assets',        [$this, 'enqueueResponsiveEditorStyle']);

        // Attributs ajoutés en JS à tous les blocs : déclarés aussi côté serveur
        \add_filter('register_block_type_args', [$this, 'registerSharedAttributes']);

        // Filtre render_block combiné (tooltip + classes responsive)
        \add_filter('render_block', [$this, 'renderBlock'], 10, 2);

        // Vider les règles de réécriture après un changement de slug CPT
        \add_action('init', [$this, 'maybeFlushRewrites'], 999);
    }

    /**
     * Déclare côté serveur les attributs que g2rd-block-responsive.js et
     * g2rd-block-tooltip.js ajoutent à tous les blocs. Sans cela, l'API REST
     * (block-renderer, additionalProperties: false) refuse l'aperçu des blocs
     * rendus par le serveur (Archives, Calendrier…) dès qu'un réglage est posé.
     * Sans valeur par défaut : rien n'est ajouté aux blocs qui ne s'en servent pas.
     *
     * @param array $args Arguments d'enregistrement du type de bloc.
     * @return array
     */
    public function registerSharedAttributes( array $args ): array {
        $args['attributes'] = ( $args['attributes'] ?? [] ) + [
            'g2rdHideDesktop'    => [ 'type' => 'boolean' ],
            'g2rdHideTablet'     => [ 'type' => 'boolean' ],
            'g2rdHideMobile'     => [ 'type' => 'boolean' ],
            'g2rdTooltipEnabled' => [ 'type' => 'boolean' ],
            'g2rdTooltipText'    => [ 'type' => 'string' ],
        ];
        return $args;
    }

    /**
     * Injecte les Dashicons dans l'iframe canvas de l'éditeur Gutenberg (WP 6.x)
     * enqueue_block_editor_assets ne cible que le frame parent, pas l'iframe canvas.
     *
     * @param array $settings
     * @return array
     */
    public function injectDashiconsInCanvas( array $settings ): array {
        $settings['styles'][] = [
            'css' => '@import url("' . \esc_url( \includes_url('css/dashicons.min.css') ) . '");',
        ];
        return $settings;
    }

    /**
     * Enqueue du script éditeur pour l'infobulle
     *
     * @return void
     */
    public function enqueueTooltipEditorAssets(): void {
        $dir     = \get_template_directory();
        $dir_uri = \get_template_directory_uri();
        \wp_enqueue_script(
            'g2rd-block-tooltip',
            $dir_uri . '/assets/js/g2rd-block-tooltip.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            \filemtime( $dir . '/assets/js/g2rd-block-tooltip.js' ),
            true
        );
    }

    /**
     * Enqueue du script et CSS éditeur pour la visibilité responsive
     *
     * @return void
     */
    public function enqueueResponsiveEditorAssets(): void {
        $dir     = \get_template_directory();
        $dir_uri = \get_template_directory_uri();
        \wp_enqueue_script(
            'g2rd-block-responsive',
            $dir_uri . '/assets/js/g2rd-block-responsive.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            \filemtime( $dir . '/assets/js/g2rd-block-responsive.js' ),
            true
        );
        $this->enqueueResponsiveEditorStyle();
    }

    /**
     * CSS éditeur de la visibilité responsive : panneau Inspector (cadre parent)
     * et blocs masqués grisés dans le canvas. Accrochée aussi à
     * enqueue_block_assets, seul hook qui atteint l'iframe du canvas (WP 6.3+).
     *
     * @return void
     */
    public function enqueueResponsiveEditorStyle(): void {
        if ( ! \is_admin() ) {
            return;
        }
        $dir = \get_template_directory();
        \wp_enqueue_style(
            'g2rd-block-responsive-editor',
            \get_template_directory_uri() . '/assets/css/g2rd-block-responsive-editor.css',
            [],
            \filemtime( $dir . '/assets/css/g2rd-block-responsive-editor.css' )
        );
    }

    /**
     * Charge une feuille du thème au moment du rendu d'un bloc qui en a besoin.
     * En FSE le gabarit est rendu avant wp_head : la feuille part dans le <head>,
     * y compris pour les blocs des template parts, gabarits et compositions.
     *
     * @param string $handle Handle (= nom du fichier dans assets/css/).
     * @return void
     */
    private function enqueueFrontStyle( string $handle ): void {
        $file = '/assets/css/' . $handle . '.css';
        \wp_enqueue_style(
            $handle,
            \get_template_directory_uri() . $file,
            [],
            \filemtime( \get_template_directory() . $file )
        );
    }

    /**
     * Filtre render_block combiné : injecte l'attribut data-g2rd-tooltip
     * et les classes de visibilité responsive sur l'élément racine du bloc.
     *
     * @param string $block_content
     * @param array  $block
     * @return string
     */
    public function renderBlock( string $block_content, array $block ): string {
        $attrs = $block['attrs'] ?? [];

        $tooltip = ! empty( $attrs['g2rdTooltipEnabled'] ) && ! empty( $attrs['g2rdTooltipText'] )
            ? (string) $attrs['g2rdTooltipText']
            : '';
        $classes = \array_keys( \array_filter( [
            'g2rd-hide-desktop' => ! empty( $attrs['g2rdHideDesktop'] ),
            'g2rd-hide-tablet'  => ! empty( $attrs['g2rdHideTablet'] ),
            'g2rd-hide-mobile'  => ! empty( $attrs['g2rdHideMobile'] ),
        ] ) );

        if ( '' === $tooltip && ! $classes ) {
            return $block_content;
        }

        // Le HTML d'un bloc statique commence par un saut de ligne, et un bloc
        // dynamique peut émettre un <style>/<script> avant son enveloppe :
        // on cible le premier vrai élément via l'API HTML de WordPress.
        $tags = new \WP_HTML_Tag_Processor( $block_content );
        do {
            if ( ! $tags->next_tag() ) {
                return $block_content;
            }
        } while ( \in_array( $tags->get_tag(), [ 'STYLE', 'SCRIPT', 'LINK' ], true ) );

        if ( '' !== $tooltip ) {
            $tags->set_attribute( 'data-g2rd-tooltip', $tooltip ); // échappé par l'API HTML.
            $this->enqueueFrontStyle( 'g2rd-block-tooltip' );
        }
        foreach ( $classes as $class ) {
            $tags->add_class( $class );
        }
        if ( $classes ) {
            $this->enqueueFrontStyle( 'g2rd-block-responsive' );
        }

        return $tags->get_updated_html();
    }

    /**
     * Enqueue du CSS frontend pour l'infobulle
     *
     * @deprecated Plus accrochée : renderBlock() charge la feuille à la demande,
     *             y compris pour les blocs des gabarits et template parts.
     * @return void
     */
    public function enqueueTooltipFrontendAssets(): void {
        // Charger uniquement si au moins un bloc utilise l'infobulle sur cette page
        if ( ! $this->pageHasTooltip() ) {
            return;
        }

        $dir     = \get_template_directory();
        $dir_uri = \get_template_directory_uri();
        \wp_enqueue_style(
            'g2rd-block-tooltip',
            $dir_uri . '/assets/css/g2rd-block-tooltip.css',
            [],
            \filemtime( $dir . '/assets/css/g2rd-block-tooltip.css' )
        );
    }

    /**
     * Détecte si la page courante contient au moins un bloc avec infobulle activée.
     *
     * @deprecated Utilisée seulement par enqueueTooltipFrontendAssets() (désaccrochée) :
     *             ne voit pas les gabarits ni les template parts.
     * @return bool
     */
    private function pageHasTooltip(): bool {
        if ( ! \is_singular() ) {
            return false;
        }
        $post = \get_post();
        if ( ! $post ) {
            return false;
        }
        // Recherche rapide dans le contenu brut avant le parsing complet des blocs
        return \str_contains( $post->post_content, '"g2rdTooltipEnabled":true' );
    }

    /**
     * Enqueue du CSS frontend pour la visibilité responsive
     *
     * @deprecated Plus accrochée : renderBlock() charge la feuille à la demande,
     *             y compris pour les blocs des gabarits et template parts.
     * @return void
     */
    public function enqueueResponsiveFrontendAssets(): void {
        // Charger uniquement si des classes de visibilité responsive sont utilisées
        if ( ! $this->pageHasResponsiveVisibility() ) {
            return;
        }

        $dir     = \get_template_directory();
        $dir_uri = \get_template_directory_uri();
        \wp_enqueue_style(
            'g2rd-block-responsive',
            $dir_uri . '/assets/css/g2rd-block-responsive.css',
            [],
            \filemtime( $dir . '/assets/css/g2rd-block-responsive.css' )
        );
    }

    /**
     * Détecte si la page courante contient des blocs avec visibilité responsive G2RD.
     *
     * @deprecated Utilisée seulement par enqueueResponsiveFrontendAssets() (désaccrochée) :
     *             ne voit pas les gabarits ni les template parts.
     * @return bool
     */
    private function pageHasResponsiveVisibility(): bool {
        if ( ! \is_singular() ) {
            // Sur les archives, on charge par sécurité (blocs dans le template FSE)
            return true;
        }
        $post = \get_post();
        if ( ! $post ) {
            return false;
        }
        return \str_contains( $post->post_content, 'g2rdHideDesktop' )
            || \str_contains( $post->post_content, 'g2rdHideTablet' )
            || \str_contains( $post->post_content, 'g2rdHideMobile' );
    }

    /**
     * Vide les règles de réécriture si un changement de slug CPT a été détecté.
     *
     * @return void
     */
    public function maybeFlushRewrites(): void {
        if ( \get_option('g2rd_needs_rewrite_flush') ) {
            \update_option('g2rd_needs_rewrite_flush', 0);
            \flush_rewrite_rules(false);
        }
    }
}
