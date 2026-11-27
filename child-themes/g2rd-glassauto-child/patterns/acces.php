<?php
/**
 * Title: Venir au centre (plan et fiche)
 * Slug: glassauto/acces
 * Categories: glassauto
 * Description: Plan du quartier en fond (bloc glassauto/plan-acces) avec la fiche adresse, horaires résumés, itinéraire et téléphone.
 *
 * @package G2RD_GlassAuto
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-locate","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-locate" id="acces"><!-- wp:glassauto/plan-acces /-->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"className":"ga-locate__card ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-locate__card ga-corner"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Accès</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Venir au centre<span class="ga-dot">.</span></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>{{nom}}</strong><br>{{rue}}<br>{{cp}} {{ville}}</p>
<!-- /wp:paragraph -->

<!-- wp:glassauto/horaires {"resume":true} /-->

<!-- wp:buttons {"className":"ga-btn-sm"} -->
<div class="wp-block-buttons ga-btn-sm"><!-- wp:button {"className":"ga-btn-itineraire"} -->
<div class="wp-block-button ga-btn-itineraire"><a class="wp-block-button__link wp-element-button" href="{{itineraire}}">Itinéraire</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline ga-btn-tel"} -->
<div class="wp-block-button is-style-outline ga-btn-tel"><a class="wp-block-button__link wp-element-button" href="{{tel-lien}}">{{tel}}</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
