<?php

/**
 * Title: Card G2RD
 * Slug: g2rd-theme/card-g2rd
 * Description: Carte service — visuel, libellé, titre et description
 * Categories: featured
 * Keywords: présentation, carte, service
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

    <!-- wp:image {"lightbox":{"enabled":false},"width":"120px","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"var:custom|radius|m"}}} -->
    <figure class="wp-block-image size-full is-resized has-custom-border" style="border-radius:var(--wp--custom--radius--m)"><img style="width:120px" alt="Illustration du service" /></figure>
    <!-- /wp:image -->

    <!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}},"fontSize":"s"} -->
    <p class="has-accent-color has-text-color has-s-font-size" style="margin-top:var(--wp--preset--spacing--s);font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Services</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.3rem","fontWeight":"700"},"spacing":{"margin":{"top":"0.25rem"}}}} -->
    <h3 class="wp-block-heading has-primary-color has-text-color" style="margin-top:0.25rem;font-size:1.3rem;font-weight:700">Création de Sites Internet</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
    <p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Des sites modernes et performants pour votre entreprise.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
