<?php
/**
 * Title: Card Box
 * Slug: G2RD-theme/card-box
 * Description: Une boîte d'auteur avec photo de profil, biographie et liens sociaux
 * Categories: card
 * Keywords: auteur, profil, biographie, réseaux sociaux
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large"}},"border":{"radius":"5px"}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-tertiary-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group"><!-- wp:avatar {"size":74,"style":{"border":{"radius":"500px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-author-name {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"medium"} /-->

<!-- wp:post-author-biography /-->

<!-- wp:social-links {"iconColor":"base","iconColorValue":"var(--wp--preset--color--white)","iconBackgroundColor":"main","iconBackgroundColorValue":"var(--wp--preset--color--blue-dark)","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|small","left":"var:preset|spacing|small"}}}} -->
<ul class="wp-block-social-links has-icon-color has-icon-background-color"><!-- wp:social-link {"url":"#","service":"twitter"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"github"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
