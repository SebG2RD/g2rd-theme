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
class BlockEditorEnhancements
{
    /**
     * Enregistre les hooks
     *
     * @return void
     */
    public function register_hooks(): void
    {
        // Dashicons dans l'iframe canvas
        \add_filter('block_editor_settings_all', [$this, 'injectDashiconsInCanvas']);

        // Tooltip — éditeur + frontend
        \add_action('enqueue_block_editor_assets', [$this, 'enqueueTooltipEditorAssets']);
        \add_action('wp_enqueue_scripts',          [$this, 'enqueueTooltipFrontendAssets']);

        // Visibilité responsive — éditeur + frontend
        \add_action('enqueue_block_editor_assets', [$this, 'enqueueResponsiveEditorAssets']);
        \add_action('wp_enqueue_scripts',          [$this, 'enqueueResponsiveFrontendAssets']);

        // Filtre render_block combiné (tooltip + classes responsive)
        \add_filter('render_block', [$this, 'renderBlock'], 10, 2);

        // Vider les règles de réécriture après un changement de slug CPT
        \add_action('init', [$this, 'maybeFlushRewrites'], 999);
    }

    /**
     * Injecte les Dashicons dans l'iframe canvas de l'éditeur Gutenberg (WP 6.x)
     * enqueue_block_editor_assets ne cible que le frame parent, pas l'iframe canvas.
     *
     * @param array $settings
     * @return array
     */
    public function injectDashiconsInCanvas( array $settings ): array
    {
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
    public function enqueueTooltipEditorAssets(): void
    {
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
     * Enqueue du CSS frontend pour l'infobulle
     *
     * @return void
     */
    public function enqueueTooltipFrontendAssets(): void
    {
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
     * @return bool
     */
    private function pageHasTooltip(): bool
    {
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
     * Enqueue du script et CSS éditeur pour la visibilité responsive
     *
     * @return void
     */
    public function enqueueResponsiveEditorAssets(): void
    {
        $dir     = \get_template_directory();
        $dir_uri = \get_template_directory_uri();
        \wp_enqueue_script(
            'g2rd-block-responsive',
            $dir_uri . '/assets/js/g2rd-block-responsive.js',
            ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            \filemtime( $dir . '/assets/js/g2rd-block-responsive.js' ),
            true
        );
        \wp_enqueue_style(
            'g2rd-block-responsive-editor',
            $dir_uri . '/assets/css/g2rd-block-responsive-editor.css',
            [],
            \filemtime( $dir . '/assets/css/g2rd-block-responsive-editor.css' )
        );
    }

    /**
     * Enqueue du CSS frontend pour la visibilité responsive
     *
     * @return void
     */
    public function enqueueResponsiveFrontendAssets(): void
    {
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
     * @return bool
     */
    private function pageHasResponsiveVisibility(): bool
    {
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
     * Filtre render_block combiné : injecte l'attribut data-g2rd-tooltip
     * et les classes de visibilité responsive sur le premier tag HTML du bloc.
     *
     * @param string $block_content
     * @param array  $block
     * @return string
     */
    public function renderBlock( string $block_content, array $block ): string
    {
        $attrs = $block['attrs'] ?? [];

        // ── Infobulle ─────────────────────────────────────────────────────────
        if ( ! empty( $attrs['g2rdTooltipEnabled'] ) && ! empty( $attrs['g2rdTooltipText'] ) ) {
            $tooltip       = \esc_attr( $attrs['g2rdTooltipText'] );
            $block_content = \preg_replace(
                '/^(<[a-zA-Z][a-zA-Z0-9]*)(\s|>)/',
                '$1 data-g2rd-tooltip="' . $tooltip . '"$2',
                $block_content,
                1
            ) ?? $block_content;
        }

        // ── Visibilité responsive ─────────────────────────────────────────────
        $classes = [];
        if ( ! empty( $attrs['g2rdHideDesktop'] ) ) {
            $classes[] = 'g2rd-hide-desktop';
        }
        if ( ! empty( $attrs['g2rdHideTablet'] ) ) {
            $classes[] = 'g2rd-hide-tablet';
        }
        if ( ! empty( $attrs['g2rdHideMobile'] ) ) {
            $classes[] = 'g2rd-hide-mobile';
        }

        if ( ! empty( $classes ) ) {
            $extra = \implode( ' ', $classes );
            if ( \preg_match( '/^(<[a-zA-Z][a-zA-Z0-9]*\s[^>]*class=")([^"]*)(")/', $block_content ) ) {
                $block_content = \preg_replace(
                    '/^(<[a-zA-Z][a-zA-Z0-9]*\s[^>]*class=")([^"]*)(")/i',
                    '$1$2 ' . $extra . '$3',
                    $block_content,
                    1
                ) ?? $block_content;
            } else {
                $block_content = \preg_replace(
                    '/^(<[a-zA-Z][a-zA-Z0-9]*)(\s|>)/',
                    '$1 class="' . $extra . '"$2',
                    $block_content,
                    1
                ) ?? $block_content;
            }
        }

        return $block_content;
    }

    /**
     * Vide les règles de réécriture si un changement de slug CPT a été détecté.
     *
     * @return void
     */
    public function maybeFlushRewrites(): void
    {
        if ( \get_option('g2rd_needs_rewrite_flush') ) {
            \update_option('g2rd_needs_rewrite_flush', 0);
            \flush_rewrite_rules(false);
        }
    }
}
