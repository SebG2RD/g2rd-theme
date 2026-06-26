<?php

/**
 * Title: Card Presentation
 * Slug: g2rd-theme/card-presentation
 * Description: Carte de présentation d'un membre de l'équipe — photo, nom, rôle et bio
 * Categories: featured
 * Keywords: card, presentation, about, équipe
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 * @package G2RD Theme
 * @since 1.0.0
 */
?>
<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-card"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:image {"lightbox":{"enabled":false},"width":"96px","height":"96px","sizeSlug":"full","linkDestination":"none","className":"is-style-default","style":{"border":{"radius":"999px"}}} -->
<figure class="wp-block-image size-full is-resized has-custom-border is-style-default"><img style="border-radius:999px;width:96px;height:96px" alt="Photo de Sebastien Gerard"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.2rem"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} -->
<h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.25rem;font-weight:700">Sebastien GERARD</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.06em","textTransform":"uppercase"},"elements":{"link":{"color":{"text":"var:custom|color|accenthover"}}},"color":{"text":"var:custom|color|accenthover"}},"fontSize":"s"} -->
<p class="has-text-color has-link-color has-s-font-size" style="color:var(--wp--custom--color--accenthover);font-weight:600;letter-spacing:0.06em;text-transform:uppercase">Développeur web</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
<p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Sebastien assure la création de sites performants et adaptés aux besoins de nos clients.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
