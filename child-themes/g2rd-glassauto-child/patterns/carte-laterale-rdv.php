<?php
/**
 * Title: Carte latérale « Prendre rendez-vous »
 * Slug: glassauto/carte-laterale-rdv
 * Categories: glassauto
 * Description: Carte collante pour les pages intérieures : coordonnées, boutons Prendre rendez-vous et Appeler, horaires résumés.
 *
 * @package G2RD_GlassAuto
 */

?>
<!-- wp:group {"tagName":"aside","className":"ga-side-card ga-corner","layout":{"type":"default"}} -->
<aside class="wp-block-group ga-side-card ga-corner"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Centre de Vernon</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Prendre rendez-vous<span class="ga-dot">.</span></h2>
<!-- /wp:heading -->

<!-- wp:glassauto/coordonnees {"elements":"adresse,telephone","style":"liste","niveauTitre":"p"} /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-rdv ga-btn-rdv"} -->
<div class="wp-block-button is-style-rdv ga-btn-rdv"><a class="wp-block-button__link wp-element-button" href="{{reservation}}">Prendre rendez-vous</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline ga-btn-tel"} -->
<div class="wp-block-button is-style-outline ga-btn-tel"><a class="wp-block-button__link wp-element-button" href="{{tel-lien}}">Appeler le centre</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:glassauto/horaires {"resume":true} /--></aside>
<!-- /wp:group -->
