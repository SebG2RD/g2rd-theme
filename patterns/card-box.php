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
<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-card"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|m"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group"><!-- wp:avatar {"size":74,"style":{"border":{"radius":"500px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-author-name {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"textColor":"primary","fontSize":"medium"} /-->

<!-- wp:post-author-biography {"textColor":"muted"} /-->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"var(--wp--preset--color--white)","iconBackgroundColor":"primary","iconBackgroundColorValue":"var(--wp--preset--color--primary)","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|xs","left":"var:preset|spacing|xs"}}}} -->
<ul class="wp-block-social-links has-icon-color has-icon-background-color"><!-- wp:social-link {"url":"#","service":"twitter"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"github"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
