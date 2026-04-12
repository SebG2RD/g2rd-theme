<?php

/**
 * Gestion du type de contenu personnalisé Prestations
 *
 * @package G2RD
 * @since 1.0.2
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe CPT_Prestations
 *
 * Gère le type de contenu personnalisé Prestations et ses taxonomies.
 */
class CPT_Prestations {
    /**
     * Enregistre les hooks nécessaires
     *
     * @since 1.0.2
     * @return void
     */
    public function register_hooks(): void {
        \add_action('init', [$this, 'registerPostType']);
    }

    /**
     * Enregistre le type de contenu Prestations
     *
     * @since 1.0.2
     * @return void
     */
    public function registerPostType(): void {
        $s = \G2RD\ThemeOptions::getCPTSettings('prestations');

        $labels = [
            'name'          => $s['plural'],
            'singular_name' => $s['singular'],
            'all_items'     => $s['all_items'],
            // translators: %s : nom singulier du CPT prestations (ex. "Prestation").
            'add_new_item'  => \sprintf(\__('Ajouter une %s', 'g2rd'), \mb_strtolower($s['singular'])),
            // translators: %s : nom singulier du CPT prestations (ex. "Prestation").
            'edit_item'     => \sprintf(\__('Modifier la %s', 'g2rd'), \mb_strtolower($s['singular'])),
            'menu_name'     => $s['plural'],
        ];
        $args   = [
            'labels'                => $labels,
            'public'                => true,
            'show_in_rest'          => (bool) $s['show_in_rest'],
            'has_archive'           => (bool) $s['has_archive'],
            'supports'              => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes'],
            'menu_position'         => (int) $s['menu_position'],
            'menu_icon'             => \sanitize_text_field($s['menu_icon']),
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'hierarchical'          => false,
            'rewrite'               => ['slug' => \sanitize_title($s['slug'])],
            'query_var'             => true,
            'show_in_nav_menus'     => true,
            'show_in_admin_bar'     => true,
            'rest_base'             => \sanitize_title($s['slug']),
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        \register_post_type('prestations', $args);

        if (!empty($s['tax_enabled'])) {
            $tax_labels = [
                'name'          => $s['tax_plural'],
                'singular_name' => $s['tax_singular'],
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'add_new_item'  => \sprintf(\__('Ajouter %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'new_item_name' => \sprintf(\__('Nouvelle %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'parent_item'   => \sprintf(\__('%s parente', 'g2rd'), $s['tax_singular']),
            ];
            \register_taxonomy(\sanitize_title($s['tax_slug']), 'prestations', [
                'labels'            => $tax_labels,
                'public'            => true,
                'show_in_rest'      => true,
                'hierarchical'      => true,
                'rewrite'           => ['slug' => \sanitize_title($s['tax_slug'])],
                'show_admin_column' => true,
            ]);
        }
    }
}
