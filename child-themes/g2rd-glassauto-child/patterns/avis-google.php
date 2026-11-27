<?php
/**
 * Title: Avis Google
 * Slug: glassauto/avis-google
 * Categories: glassauto
 * Description: Avis Google authentiques via le bloc Témoignage G2RD en mode Google (clé API Google Maps dans G2RD › Thème, Place ID dans Réglages › Général › GlassAuto Vernon). Sans clé ni Place ID, des emplacements vides s'affichent : aucun avis n'est jamais inventé.
 *
 * @package G2RD_GlassAuto
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-section" id="avis"><!-- wp:group {"align":"wide","className":"ga-reviews-head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-reviews-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Avis clients</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Ils nous ont confié leur véhicule<span class="ga-dot">.</span></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"ga-g-score"} -->
<p class="ga-g-score"><strong>Note Google du centre</strong>Affichée automatiquement depuis la fiche</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:glassauto/avis-google {"max":3,"noteMin":4,"align":"wide"} /-->

<!-- wp:paragraph {"align":"wide","className":"ga-reviews-note"} -->
<p class="alignwide ga-reviews-note">Cet espace affiche uniquement des avis Google authentiques, récupérés depuis la fiche Google Business Profile du centre. <a href="{{avis}}">Laisser un avis sur Google</a></p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->
