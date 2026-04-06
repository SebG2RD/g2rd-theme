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
class CPT_QuiSommesNous
{
    /**
     * Enregistre les hooks nécessaires
     *
     * @since 1.0.2
     * @return void
     */
    public function registerHooks(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_qui-sommes-nous', [$this, 'saveMeta']);
        add_filter('use_block_editor_for_post_type', [$this, 'disableGutenberg'], 10, 2);
        // Ajouter ici les hooks/metaboxes/colonnes spécifiques à qui-sommes-nous
    }

    /**
     * Enregistre le type de contenu Qui sommes-nous
     *
     * @since 1.0.2
     * @return void
     */
    public function registerPostType(): void
    {
        $s = \G2RD\ThemeOptions::getCPTSettings('qui-sommes-nous');

        $labels = [
            'name'          => $s['plural'],
            'singular_name' => $s['singular'],
            'all_items'     => $s['all_items'],
            'add_new_item'  => \sprintf(\__('Ajouter un %s', 'g2rd'), \mb_strtolower($s['singular'])),
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
                'add_new_item'  => \sprintf(\__('Ajouter un %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
                'new_item_name' => \sprintf(\__('Nouveau %s', 'g2rd'), \mb_strtolower($s['tax_singular'])),
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
    public function addMetaBox(): void
    {
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
    public function renderMetaBox($post): void
    {
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
    public function saveMeta($post_id): void
    {
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
                update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$field]));
            }
        }
        if (isset($_POST['icones_images'])) {
            $images = array_map('absint', array_filter((array) \wp_unslash($_POST['icones_images'])));
            update_post_meta($post_id, '_icones_images', $images);
        } else {
            update_post_meta($post_id, '_icones_images', []);
        }
    }

    /**
     * Désactive l'éditeur Gutenberg pour le type de contenu Qui sommes-nous
     *
     * @since 1.0.2
     * @param bool $use_block_editor
     * @param string $post_type
     * @return bool
     */
    public function disableGutenberg($use_block_editor, $post_type): bool
    {
        if ($post_type === 'qui-sommes-nous') {
            return false;
        }
        return $use_block_editor;
    }
} 