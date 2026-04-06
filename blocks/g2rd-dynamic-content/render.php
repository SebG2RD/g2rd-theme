<?php
/**
 * Rendu côté serveur du bloc Contenu dynamique G2RD
 *
 * @package G2RD
 * @var array  $attributes Attributs du bloc
 * @var string $content    Contenu interne (vide pour ce bloc)
 * @var WP_Block $block    Instance du bloc
 */

$post_type    = \sanitize_key($attributes['postType']    ?? 'post');
$posts_count  = \absint($attributes['postsPerPage']      ?? 6);
$columns      = \absint($attributes['columns']           ?? 3);
$orderby      = \sanitize_key($attributes['orderby']     ?? 'date');
$order        = \in_array($attributes['order'] ?? 'DESC', ['ASC', 'DESC'], true) ? $attributes['order'] : 'DESC';
$category_id  = \absint($attributes['categoryId']        ?? 0);
$show_image   = (bool) ($attributes['showImage']         ?? true);
$show_title   = (bool) ($attributes['showTitle']         ?? true);
$show_excerpt = (bool) ($attributes['showExcerpt']       ?? true);
$show_date    = (bool) ($attributes['showDate']          ?? true);
$show_cat     = (bool) ($attributes['showCategory']      ?? false);
$show_author  = (bool) ($attributes['showAuthor']        ?? false);
$show_more    = (bool) ($attributes['showReadMore']      ?? true);
$more_text    = \sanitize_text_field($attributes['readMoreText'] ?? __('Lire la suite', 'g2rd'));
$image_ratio  = \sanitize_text_field($attributes['imageRatio']  ?? '16/9');
$card_radius  = \absint($attributes['cardRadius']        ?? 8);
$card_shadow  = (bool) ($attributes['cardShadow']        ?? true);
$accent_color = \sanitize_hex_color($attributes['accentColor']     ?? '') ?: '';
$text_color   = \sanitize_hex_color($attributes['textColor']       ?? '') ?: '';
$bg_color     = \sanitize_hex_color($attributes['backgroundColor'] ?? '') ?: '';

// ── Requête WP_Query ─────────────────────────────────────────────────────────
$query_args = [
    'post_type'      => $post_type,
    'posts_per_page' => $posts_count,
    'orderby'        => $orderby,
    'order'          => $order,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
];

if ($category_id > 0) {
    $query_args['cat'] = $category_id;
}

$query = new \WP_Query($query_args);

if (!$query->have_posts()) {
    echo '<p class="g2rd-dynamic-content__empty">' . \esc_html__('Aucun contenu trouvé.', 'g2rd') . '</p>';
    return;
}

// ── Variables CSS ─────────────────────────────────────────────────────────────
$css_vars = '';
if ($accent_color) $css_vars .= "--g2rd-dc-accent:{$accent_color};";
if ($text_color)   $css_vars .= "--g2rd-dc-text:{$text_color};";
if ($bg_color)     $css_vars .= "--g2rd-dc-bg:{$bg_color};";

$wrapper_attrs = \get_block_wrapper_attributes([
    'class' => 'g2rd-dynamic-content',
    'style' => $css_vars ?: null,
]);

$grid_style = \esc_attr("grid-template-columns:repeat({$columns},minmax(0,1fr));");

echo '<div ' . $wrapper_attrs . '>';
echo '<div class="g2rd-dynamic-content__grid" style="' . $grid_style . '">';

while ($query->have_posts()) {
    $query->the_post();
    $post_id  = \get_the_ID();
    $permalink = \get_permalink();
    $title    = \get_the_title();
    $excerpt  = \has_excerpt() ? \get_the_excerpt() : \wp_trim_words(\get_the_content(), 20);
    $date     = \get_the_date();
    $author   = \get_the_author();

    $card_classes = 'g2rd-dynamic-content__card';
    if ($card_shadow) $card_classes .= ' has-shadow';
    $card_style = "border-radius:{$card_radius}px;";

    echo '<article class="' . \esc_attr($card_classes) . '" style="' . \esc_attr($card_style) . '">';

    // ── Image ──────────────────────────────────────────────────────────────────
    if ($show_image && \has_post_thumbnail()) {
        $ratio_css = \esc_attr("aspect-ratio:{$image_ratio};");
        echo '<a href="' . \esc_url($permalink) . '" class="g2rd-dynamic-content__image" style="' . $ratio_css . '" tabindex="-1" aria-hidden="true">';
        \the_post_thumbnail('medium_large', ['loading' => 'lazy']);
        echo '</a>';
    }

    echo '<div class="g2rd-dynamic-content__body">';

    // ── Catégorie ──────────────────────────────────────────────────────────────
    if ($show_cat) {
        $cats = \get_the_category($post_id);
        if ($cats) {
            $cat = $cats[0];
            echo '<a href="' . \esc_url(\get_category_link($cat->term_id)) . '" class="g2rd-dynamic-content__category">';
            echo \esc_html($cat->name);
            echo '</a>';
        }
    }

    // ── Titre ──────────────────────────────────────────────────────────────────
    if ($show_title) {
        echo '<h3 class="g2rd-dynamic-content__title">';
        echo '<a href="' . \esc_url($permalink) . '">' . \esc_html($title) . '</a>';
        echo '</h3>';
    }

    // ── Extrait ────────────────────────────────────────────────────────────────
    if ($show_excerpt && $excerpt) {
        echo '<p class="g2rd-dynamic-content__excerpt">' . \esc_html($excerpt) . '</p>';
    }

    // ── Méta ───────────────────────────────────────────────────────────────────
    if ($show_date || $show_author) {
        echo '<div class="g2rd-dynamic-content__meta">';
        if ($show_date)   echo '<time datetime="' . \esc_attr(\get_the_date('c')) . '">' . \esc_html($date) . '</time>';
        if ($show_author) echo '<span>' . \esc_html($author) . '</span>';
        echo '</div>';
    }

    // ── Bouton Lire la suite ───────────────────────────────────────────────────
    if ($show_more) {
        echo '<a href="' . \esc_url($permalink) . '" class="g2rd-dynamic-content__readmore">';
        echo \esc_html($more_text);
        echo '</a>';
    }

    echo '</div>'; // .g2rd-dynamic-content__body
    echo '</article>';
}

echo '</div>'; // .g2rd-dynamic-content__grid
echo '</div>'; // wrapper

\wp_reset_postdata();
