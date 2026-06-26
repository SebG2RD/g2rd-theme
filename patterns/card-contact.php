<?php
/**
 * Title: Card Contact
 * Slug: G2RD-theme/card-contact
 * Description: Une carte de contact sombre avec coordonnées et liens sociaux
 * Categories: card
 * Keywords: contact, coordonnées, réseaux sociaux, email, téléphone, adresse
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"className":"is-style-card-dark","style":{"spacing":{"blockGap":"var:preset|spacing|s"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-card-dark"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"textColor":"white","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
<p class="has-white-color has-text-color has-medium-font-size" style="font-style:normal;font-weight:700">Contactez-nous</p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"var(--wp--preset--color--white)","className":"is-style-default","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|xs","left":"var:preset|spacing|xs"}}},"layout":{"type":"flex"}} -->
<ul class="wp-block-social-links has-icon-color is-style-default"><!-- wp:social-link {"url":"https://twitter.com","service":"twitter"} /-->

<!-- wp:social-link {"url":"https://facebook.com","service":"facebook"} /-->

<!-- wp:social-link {"url":"https://linkedin.com","service":"linkedin"} /-->

<!-- wp:social-link {"url":"https://youtube.com","service":"youtube"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:separator {"backgroundColor":"blue-soft","className":"is-style-wide"} -->
<hr class="wp-block-separator has-text-color has-blue-soft-color has-alpha-channel-opacity has-blue-soft-background-color has-background is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.06em","textTransform":"uppercase"}},"fontSize":"s"} -->
<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.06em;text-transform:uppercase">Email</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"white"} -->
<p class="has-white-color has-text-color">mail@example.com</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:separator {"backgroundColor":"blue-soft","className":"is-style-wide"} -->
<hr class="wp-block-separator has-text-color has-blue-soft-color has-alpha-channel-opacity has-blue-soft-background-color has-background is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.06em","textTransform":"uppercase"}},"fontSize":"s"} -->
<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.06em;text-transform:uppercase">Téléphone</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"white"} -->
<p class="has-white-color has-text-color">815-420-2024</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:separator {"backgroundColor":"blue-soft","className":"is-style-wide"} -->
<hr class="wp-block-separator has-text-color has-blue-soft-color has-alpha-channel-opacity has-blue-soft-background-color has-background is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.06em","textTransform":"uppercase"}},"fontSize":"s"} -->
<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.06em;text-transform:uppercase">Adresse</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"white"} -->
<p class="has-white-color has-text-color">1234 Theme Street<br>San Francisco, CA 94070</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
