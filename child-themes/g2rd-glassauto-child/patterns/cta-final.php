<?php
/**
 * Title: Appel à l'action final
 * Slug: glassauto/cta-final
 * Categories: glassauto
 * Description: Bandeau turquoise « Un impact sur votre pare-brise ? N'attendez pas qu'il s'étende » avec les boutons Prendre rendez-vous et Appeler.
 *
 * @package G2RD_GlassAuto
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-cta","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-cta"><!-- wp:group {"align":"wide","className":"ga-cta__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-cta__inner"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading">Un impact sur votre pare-brise ? N'attendez pas qu'il s'étende<span class="ga-dot">.</span></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Un impact peut se transformer en fissure avec les chocs thermiques et les vibrations de la route. Réservez votre créneau au centre de Vernon ou appelez-nous.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"ga-cta__actions ga-btn-lg"} -->
<div class="wp-block-buttons ga-cta__actions ga-btn-lg"><!-- wp:button {"className":"is-style-blanc ga-btn-rdv"} -->
<div class="wp-block-button is-style-blanc ga-btn-rdv"><a class="wp-block-button__link wp-element-button" href="{{reservation}}">Prendre rendez-vous</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"ga-btn-tel"} -->
<div class="wp-block-button ga-btn-tel"><a class="wp-block-button__link wp-element-button" href="{{tel-lien}}">Appeler le {{tel}}</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
