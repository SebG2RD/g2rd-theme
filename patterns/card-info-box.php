<?php

/**
 * Title: Card Info Box
 * Slug: G2RD-theme/card-info-box
 * Description: Une carte pour afficher une information — icône lime, titre et description
 * Categories: featured
 * Keywords: card, info, box
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 *
 * @package G2RD Theme
 * @since 1.0.0
 */

?>

<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-card">

    <!-- wp:group {"style":{"border":{"radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"0.7rem","bottom":"0.7rem","left":"0.7rem","right":"0.7rem"},"margin":{"bottom":"var:preset|spacing|xs"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
    <div class="wp-block-group has-secondary-background-color has-background" style="border-radius:var(--wp--custom--radius--m);margin-bottom:var(--wp--preset--spacing--xs);padding:0.7rem;width:fit-content"><!-- wp:image {"width":"36px","sizeSlug":"full","linkDestination":"none"} -->
    <figure class="wp-block-image size-full is-resized"><img alt="Icône illustrative" style="width:36px" /></figure>
    <!-- /wp:image --></div>
    <!-- /wp:group -->

    <!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.2rem","fontWeight":"700"}}} -->
    <h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.2rem;font-weight:700">Titre de l'information</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
    <p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Décrivez ici l'information à mettre en avant pour vos visiteurs.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
