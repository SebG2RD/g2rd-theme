<?php
/**
 * Title: Accueil — Partenaires
 * Slug: rescue18/accueil-partenaires
 * Categories: rescue18
 * Description: Partenaires officiels en vitrine, puis bandeau des soutiens sur deux lignes. Les logos se gèrent dans Partenaires au back-office.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"r18-partners-sec","style":{"spacing":{"padding":{"top":"70px"}}},"layout":{"type":"default"}} -->
<section class="wp-block-group alignfull r18-partners-sec" style="padding-top:70px"><!-- wp:group {"layout":{"type":"constrained","contentSize":"1254px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"center","className":"r18-eyebrow"} -->
<p class="r18-eyebrow has-text-align-center">Partenaires</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2 has-text-align-center">Ils soutiennent Rescue 18</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","className":"r18-lead r18-pt-lead"} -->
<p class="r18-lead r18-pt-lead has-text-align-center">Grâce à eux, documentation, veille et outils restent gratuits pour tous les pompiers.</p>
<!-- /wp:paragraph -->
<!-- wp:rescue18/partenaires {"niveau":"convention","variante":"vitrine"} /-->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/partenaires {"niveau":"soutien","variante":"bandeau","align":"full"} /-->
<!-- wp:group {"layout":{"type":"constrained","contentSize":"1254px"}} -->
<div class="wp-block-group"><!-- wp:buttons {"className":"r18-voies__suite r18-pt-actions","layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons r18-voies__suite r18-pt-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/partenaires/#devenir-partenaire' ) ); ?>">Devenir partenaire</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/partenaires/' ) ); ?>">Tous nos partenaires</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
