<?php
/**
 * Gestion du type de contenu personnalisé Qui sommes-nous
 * 
 * Cette classe gère l'enregistrement et la configuration du type de contenu Qui sommes-nous.
 *
 * @package G2RD
 * @since 1.0.2
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Classe CPT_QuiSommesNous
 * 
 * Gère le type de contenu personnalisé Qui sommes-nous et ses métadonnées.
 */
class CPT_QuiSommesNous {
    /**
     * Enregistre les hooks nécessaires
     *
     * @since 1.0.2
     * @return void
     */
    public function register_hooks(): void {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerPostMeta']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_qui-sommes-nous', [$this, 'saveMeta']);
        add_filter('the_content', [$this, 'appendMemberProfile']);
    }

    /**
     * Ajoute la section « Le profil » (Expérience, Soft skills, Méthodologie,
     * Objectif, Stack technique) après le contenu, sur les pages membre.
     *
     * Approche robuste via le filtre the_content : indépendante du markup du
     * template → survit aux ré-enregistrements dans l'éditeur de site (là où la
     * précédente section en block bindings était perdue).
     *
     * @since 1.26.1
     * @param string $content Contenu du post.
     * @return string
     */
    public function appendMemberProfile( $content ) {
        if ( ! \is_singular( 'qui-sommes-nous' ) || ! \in_the_loop() || ! \is_main_query() ) {
            return $content;
        }

        $post_id    = \get_the_ID();
        $experience = \get_post_meta( $post_id, '_experience_dev', true );
        $soft       = \get_post_meta( $post_id, '_soft_skills', true );
        $metho      = \get_post_meta( $post_id, '_methodologie', true );
        $objectif   = \get_post_meta( $post_id, '_objectif', true );
        $icones     = \get_post_meta( $post_id, '_icones_images', true );

        $fields = [
            [ \__( 'Expérience en développement', 'g2rd' ), $experience ],
            [ \__( 'Soft skills', 'g2rd' ),                 $soft ],
            [ \__( 'Méthodologie', 'g2rd' ),                $metho ],
            [ \__( 'Objectif', 'g2rd' ),                    $objectif ],
        ];

        $has_icones = \is_array( $icones ) && ! empty( $icones );
        $has_field  = $experience || $soft || $metho || $objectif;
        if ( ! $has_field && ! $has_icones ) {
            return $content;
        }

        $cards = '';
        foreach ( $fields as $field ) {
            if ( empty( $field[1] ) ) {
                continue;
            }
            $cards .= '<div class="wp-block-group is-style-card" style="padding:var(--wp--preset--spacing--l)">'
                . '<h3 class="wp-block-heading has-primary-color has-text-color" style="margin:0 0 .6rem;font-size:1.125rem;font-weight:700">' . \esc_html( $field[0] ) . '</h3>'
                . '<p class="has-muted-color has-text-color" style="margin:0;line-height:1.7">' . \wp_kses_post( $field[1] ) . '</p>'
                . '</div>';
        }

        $icons_row = '';
        if ( $has_icones ) {
            $imgs = '';
            foreach ( $icones as $url ) {
                if ( ! empty( $url ) ) {
                    $imgs .= '<img src="' . \esc_url( $url ) . '" alt="" width="48" height="48" loading="lazy" style="height:48px;width:auto" />';
                }
            }
            if ( $imgs ) {
                $icons_row = '<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin:var(--wp--preset--spacing--m) 0 var(--wp--preset--spacing--s);font-size:.8125rem;font-weight:700;letter-spacing:3px;text-transform:uppercase">' . \esc_html__( 'Stack technique', 'g2rd' ) . '</h3>'
                    . '<div style="display:flex;flex-wrap:wrap;gap:var(--wp--preset--spacing--s, 1rem);justify-content:center;align-items:center">' . $imgs . '</div>';
            }
        }

        $section = '<div class="g2rd-member-profile" style="margin-top:var(--wp--preset--spacing--xl);padding-top:var(--wp--preset--spacing--l);border-top:1px solid var(--wp--preset--color--border)">'
            . '<p class="has-s-font-size" style="display:inline-block;border-radius:999px;color:var(--wp--preset--color--primary);background-color:var(--wp--preset--color--secondary);padding:.4rem .9rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;margin:0 0 var(--wp--preset--spacing--s)">' . \esc_html__( 'Le profil', 'g2rd' ) . '</p>'
            . '<h2 class="wp-block-heading has-primary-color has-text-color" style="margin:0 0 var(--wp--preset--spacing--l);font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;letter-spacing:-.02em">' . \esc_html__( 'Parcours & méthode', 'g2rd' ) . '</h2>'
            . '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(18rem,1fr));gap:var(--wp--preset--spacing--m)">' . $cards . '</div>'
            . $icons_row
            . '</div>';

        return $content . $section;
    }

    /**
     * Enregistre les métadonnées via register_post_meta() pour l'accès REST et l'éditeur de blocs.
     *
     * @since 1.2.3
     * @return void
     */
    public function registerPostMeta(): void {
        foreach (['_experience_dev', '_soft_skills', '_methodologie', '_objectif'] as $key) {
            register_post_meta('qui-sommes-nous', $key, [
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback' => fn() => \current_user_can('edit_posts'),
            ]);
        }

        register_post_meta('qui-sommes-nous', '_icones_images', [
            'show_in_rest'  => [
                'schema' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string', 'format' => 'uri'],
                ],
            ],
            'single'            => true,
            'type'              => 'array',
            'sanitize_callback' => fn( $urls ) => array_values(
                array_filter(
                    array_map( 'esc_url_raw', (array) $urls )
                )
            ),
            'auth_callback' => fn() => \current_user_can('edit_posts'),
        ]);
    }

    /**
     * Enregistre le type de contenu Qui sommes-nous
     *
     * @since 1.0.2
     * @return void
     */
    public function registerPostType(): void {
        $s = \G2RD\ThemeOptions::getCPTSettings('qui-sommes-nous');

        $labels = [
            'name'          => $s['plural'],
            'singular_name' => $s['singular'],
            'all_items'     => $s['all_items'],
            // translators: %s : nom singulier du CPT (ex. "Membre").
            'add_new_item'  => \sprintf(\__('Ajouter un %s', 'g2rd'), \mb_strtolower($s['singular'])),
            // translators: %s : nom singulier du CPT (ex. "Membre").
            'edit_item'     => \sprintf(\__('Modifier le %s', 'g2rd'), \mb_strtolower($s['singular'])),
            'menu_name'     => $s['plural'],
        ];
        $args = [
            'labels'            => $labels,
            'public'            => true,
            'show_in_rest'      => (bool) $s['show_in_rest'],
            'has_archive'       => (bool) $s['has_archive'],
            'supports'          => ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'excerpt'],
            'menu_position'     => (int) $s['menu_position'],
            'menu_icon'         => \sanitize_text_field($s['menu_icon']),
            'capability_type'   => 'post',
            'map_meta_cap'      => true,
            'hierarchical'      => false,
            'rewrite'           => ['slug' => \sanitize_title($s['slug'])],
            'query_var'         => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
        ];
        register_post_type('qui-sommes-nous', $args);

        if (!empty($s['tax_enabled'])) {
            $tax_labels = [
                'name'          => $s['tax_plural'],
                'singular_name' => $s['tax_singular'],
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'add_new_item'  => \sprintf(\__('Ajouter un %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'new_item_name' => \sprintf(\__('Nouveau %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
                // translators: %s : nom singulier de la taxonomie (ex. "Catégorie").
                'parent_item'   => \sprintf(\__('%s parent', 'g2rd'), $s['tax_singular']),
            ];
            register_taxonomy('categories-qui-sommes-nous', 'qui-sommes-nous', [
                'labels'            => $tax_labels,
                'public'            => true,
                'show_in_rest'      => true,
                'hierarchical'      => true,
                'rewrite'           => ['slug' => \sanitize_title($s['tax_slug'])],
                'show_admin_column' => true,
            ]);
        }
    }

    /**
     * Ajoute la boîte de métadonnées
     *
     * @since 1.0.2
     * @return void
     */
    public function addMetaBox(): void {
        add_meta_box(
            'qui_sommes_nous_info',
            'Informations du membre',
            [$this, 'renderMetaBox'],
            'qui-sommes-nous',
            'normal',
            'high'
        );
    }

    /**
     * Affiche la boîte de métadonnées
     *
     * @since 1.0.2
     * @param \WP_Post $post
     * @return void
     */
    public function renderMetaBox($post): void {
        $experience = get_post_meta($post->ID, '_experience_dev', true);
        $soft_skills = get_post_meta($post->ID, '_soft_skills', true);
        $methodologie = get_post_meta($post->ID, '_methodologie', true);
        $objectif = get_post_meta($post->ID, '_objectif', true);
        $images = get_post_meta($post->ID, '_icones_images', true);
        if (!is_array($images)) {
            $images = [];
        }
        wp_nonce_field('qui_sommes_nous_nonce', 'qui_sommes_nous_nonce');
        echo '<p><label for="experience_dev">Expérience en développement :</label></p>';
        echo '<p><textarea id="experience_dev" name="experience_dev" style="width: 100%;" rows="4">' . esc_textarea($experience) . '</textarea></p>';
        echo '<p><label for="soft_skills">Soft skills :</label></p>';
        echo '<p><textarea id="soft_skills" name="soft_skills" style="width: 100%;" rows="4">' . esc_textarea($soft_skills) . '</textarea></p>';
        echo '<p><label for="methodologie">Méthodologie :</label></p>';
        echo '<p><textarea id="methodologie" name="methodologie" style="width: 100%;" rows="4">' . esc_textarea($methodologie) . '</textarea></p>';
        echo '<p><label for="objectif">Objectif :</label></p>';
        echo '<p><textarea id="objectif" name="objectif" style="width: 100%;" rows="4">' . esc_textarea($objectif) . '</textarea></p>';
        echo '<div class="member-icones-container">';
        echo '<p><label>Icônes supplémentaires :</label></p>';
        echo '<div id="member-icones-list">';
        foreach ($images as $index => $image) {
            echo '<div class="member-image-item">';
            echo '<div class="media-item postbox">';
            echo '<div class="media-item-preview">';
            if (!empty($image)) {
                echo '<img src="' . esc_attr($image) . '" alt="Aperçu" class="media-preview" />';
            }
            echo '</div>';
            echo '<div class="media-item-inputs">';
            echo '<input type="text" name="icones_images[]" value="' . esc_attr($image) . '" class="regular-text" />';
            echo '<button type="button" class="button media-button select-media">Sélectionner</button>';
            echo '<button type="button" class="button media-button remove-image">Supprimer</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-primary add-image">Ajouter une icône</button>';
        echo '</div>';
        // JS pour gestion des images (reprendre le JS du code source)
        echo '<script>
jQuery(document).ready(function($){
    function openMediaSelector(button){
        var frame=wp.media({title:"Sélectionner une icône",multiple:false,library:{type:"image"}});
        frame.on("select",function(){
            var attachment=frame.state().get("selection").first().toJSON();
            var mediaItem=$(button).closest(".media-item");
            mediaItem.find("input").val(attachment.url);
            var previewContainer=mediaItem.find(".media-item-preview");
            if(previewContainer.length===0){
                previewContainer=$("<div class=\"media-item-preview\"></div>");
                mediaItem.prepend(previewContainer);
            }
            previewContainer.html("<img src=\'"+attachment.url+"\' alt=\'Aperçu\' class=\'media-preview\' />");
        });
        frame.open();
    }
    $(".add-image").on("click",function(){
        var newImage=\'<div class="member-image-item"><div class="media-item postbox"><div class="media-item-preview"></div><div class="media-item-inputs"><input type="text" name="icones_images[]" value="" class="regular-text" /><button type="button" class="button media-button select-media">Sélectionner</button><button type="button" class="button media-button remove-image">Supprimer</button></div></div></div>\';
        $("#member-icones-list").append(newImage);
    });
    $(document).on("click",".select-media",function(){openMediaSelector(this);});
    $(document).on("click",".remove-image",function(){$(this).closest(".member-image-item").remove();});
});
</script>';
    }

    /**
     * Sauvegarde les métadonnées
     *
     * @since 1.0.2
     * @param int $post_id
     * @return void
     */
    public function saveMeta($post_id): void {
        if (!isset($_POST['qui_sommes_nous_nonce']) || !wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['qui_sommes_nous_nonce'])), 'qui_sommes_nous_nonce')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $fields = [
            'experience_dev' => '_experience_dev',
            'soft_skills' => '_soft_skills',
            'methodologie' => '_methodologie',
            'objectif' => '_objectif'
        ];
        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_textarea_field(wp_unslash($_POST[$field])));
            }
        }
        if (isset($_POST['icones_images'])) {
            $images = array_values(
                array_filter(
                    array_map( 'esc_url_raw', (array) \wp_unslash( $_POST['icones_images'] ) )
                )
            );
            update_post_meta($post_id, '_icones_images', $images);
        } else {
            update_post_meta($post_id, '_icones_images', []);
        }
    }

}
