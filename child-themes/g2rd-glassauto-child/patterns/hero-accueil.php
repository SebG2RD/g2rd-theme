<?php
/**
 * Title: Accueil — Hero
 * Slug: glassauto/hero-accueil
 * Categories: glassauto
 * Description: Photo coupée en diagonale, titre H1 « Réparation et remplacement de pare-brise à Vernon », deux boutons, repères (téléphone, adresse, assurances) et étiquette « Réparer ou remplacer ? » posée sur l'impact.
 *
 * @package G2RD_GlassAuto
 */

$ga_img = get_stylesheet_directory_uri() . '/assets/img/photos/hero-suv-pare-brise.webp';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-hero is-style-section-dark","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-hero is-style-section-dark"><!-- wp:group {"className":"ga-hero__media","layout":{"type":"default"}} -->
<div class="wp-block-group ga-hero__media"><!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( $ga_img ); ?>" alt="Remplacement de pare-brise sur un SUV au centre GlassAuto de Vernon"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ga-hero__impact"} -->
<p class="ga-hero__impact"><a href="#diagnostic">Réparer ou remplacer ?</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"ga-hero__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-hero__inner"><!-- wp:group {"className":"ga-hero__text","layout":{"type":"default"}} -->
<div class="wp-block-group ga-hero__text"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Centre GlassAuto · Vernon, Eure (27)</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Réparation et remplacement de pare-brise à Vernon<span class="ga-dot">.</span></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ga-lead"} -->
<p class="ga-lead">Impact, fissure ou vitre brisée : nos techniciens interviennent sur tous les vitrages de votre véhicule, au {{rue}}. Centre agréé par la plupart des assurances, sans avance de frais si votre contrat couvre le bris de glace.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"ga-hero__actions ga-btn-big"} -->
<div class="wp-block-buttons ga-hero__actions ga-btn-big"><!-- wp:button {"className":"is-style-rdv ga-btn-rdv"} -->
<div class="wp-block-button is-style-rdv ga-btn-rdv"><a class="wp-block-button__link wp-element-button" href="{{reservation}}">Prendre rendez-vous</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-contour-clair"} -->
<div class="wp-block-button is-style-contour-clair"><a class="wp-block-button__link wp-element-button" href="/contact/">Nous contacter</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"ga-hero__facts","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group ga-hero__facts"><!-- wp:paragraph {"className":"ga-hero__fact"} -->
<p class="ga-hero__fact"><span class="ga-hero__fact-k">Téléphone</span><a href="{{tel-lien}}">{{tel}}</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-hero__fact"} -->
<p class="ga-hero__fact"><span class="ga-hero__fact-k">Adresse</span><a href="#acces">{{rue}}</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-hero__fact"} -->
<p class="ga-hero__fact"><span class="ga-hero__fact-k">Assurances</span><strong>Centre agréé</strong></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
