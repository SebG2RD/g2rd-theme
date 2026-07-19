<?php
/**
 * Gestion des styles de blocs
 *
 * Cette classe enregistre les variations de styles de blocs du thème
 * (design system « Magic Page » et « WP Manager »).
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestion des styles de blocs
 *
 * Enregistre les variations de styles de blocs (core/group, core/button).
 * L'habillage est défini soit dans theme.json (variations WP Manager,
 * tokens uniquement), soit via la feuille magic-page (chargée par style_handle).
 *
 * @package G2RD
 * @since 1.0.0
 */
class BlockStyles {

    /**
     * Enregistre les hooks nécessaires
     */
    public function register_hooks(): void {
        \add_action('init', [$this, 'registerMagicStyles']);
        \add_action('init', [$this, 'registerSectionStyles']);
    }

    /**
     * Enregistre les styles de blocs du design system Magic Page.
     * Le CSS (g2rd-magic-page) est chargé via style_handle — WordPress
     * l'enqueue uniquement sur les pages qui rendent un bloc avec ce style.
     */
    public function registerMagicStyles(): void {
        \register_block_style(
            'core/group',
            [
                'name'         => 'magic-dark',
                'label'        => \__( 'Magic — Sombre', 'g2rd' ),
                'style_handle' => 'g2rd-magic-page',
            ]
        );
        \register_block_style(
            'core/group',
            [
                'name'         => 'magic-light',
                'label'        => \__( 'Magic — Claire', 'g2rd' ),
                'style_handle' => 'g2rd-magic-page',
            ]
        );
        \register_block_style(
            'core/button',
            [
                'name'         => 'neomorphic',
                'label'        => \__( 'Néomorphique', 'g2rd' ),
                'style_handle' => 'g2rd-magic-page',
            ]
        );
        \register_block_style(
            'core/button',
            [
                'name'         => 'soft-pressed',
                'label'        => \__( 'Doré pressé', 'g2rd' ),
                'style_handle' => 'g2rd-magic-page',
            ]
        );
    }

    /**
     * Variations de styles « WP Manager » (sections et cartes).
     *
     * Ces variations ne portent PAS de CSS : leur habillage est défini
     * exclusivement dans theme.json → styles.blocks.<bloc>.variations.<nom>
     * (FSE-first, tokens uniquement). On se contente de les déclarer pour
     * qu'elles apparaissent dans le sélecteur de l'éditeur.
     *
     * @since 1.23.0
     */
    public function registerSectionStyles(): void {
        $group_variations = [
            'section-dark' => \__( 'Section sombre', 'g2rd' ),
            'card'         => \__( 'Carte', 'g2rd' ),
            'card-dark'    => \__( 'Carte sombre', 'g2rd' ),
            'card-action'  => \__( 'Carte action', 'g2rd' ),
        ];
        foreach ( $group_variations as $name => $label ) {
            \register_block_style( 'core/group', [ 'name' => $name, 'label' => $label ] );
        }

        $button_variations = [
            'action' => \__( 'Action (dégradé)', 'g2rd' ),
            'ghost'  => \__( 'Ghost', 'g2rd' ),
        ];
        foreach ( $button_variations as $name => $label ) {
            \register_block_style( 'core/button', [ 'name' => $name, 'label' => $label ] );
        }
    }
}
