<?php
/**
 * Title: Présentation du centre
 * Slug: glassauto/centre
 * Categories: glassauto
 * Description: Collage de photos (à remplacer par les photos réelles du centre), présentation, points clés et bouton vers la page Notre centre.
 *
 * @package G2RD_GlassAuto
 */

$ga_photos = get_stylesheet_directory_uri() . '/assets/img/photos/';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-section ga-section--alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-section ga-section--alt" id="centre"><!-- wp:group {"align":"wide","className":"ga-split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-split"><!-- wp:group {"className":"ga-collage","layout":{"type":"default"}} -->
<div class="wp-block-group ga-collage"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $ga_photos . 'atelier-moderne.webp' ); ?>" alt="Atelier automobile lumineux, technicien au travail sur un véhicule"/><figcaption class="wp-element-caption">Photo d'illustration</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $ga_photos . 'remise-cles.webp' ); ?>" alt="Remise des clés d'un véhicule à son propriétaire"/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $ga_photos . 'nettoyage-interieur.webp' ); ?>" alt="Technicien nettoyant l'habitacle d'un véhicule"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Notre centre</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Un atelier de proximité, zone du Virolet<span class="ga-dot">.</span></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ga-lead"} -->
<p class="ga-lead">Le centre GlassAuto Vernon accueille particuliers et professionnels dans la zone du Virolet, à deux pas du boulevard Jean-Jaurès.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nos techniciens qualifiés interviennent sur tous les vitrages et sur tous types de véhicules, de la citadine au camping-car, en passant par les utilitaires et les engins agricoles.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"ga-facts"} -->
<ul class="wp-block-list ga-facts"><!-- wp:list-item {"className":"ga-icon--technicien"} -->
<li class="ga-icon--technicien">Techniciens qualifiés</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--lavage"} -->
<li class="ga-icon--lavage">Station de lavage sur place</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--cles"} -->
<li class="ga-icon--cles">Véhicule de courtoisie</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--camping-car"} -->
<li class="ga-icon--camping-car">Accès utilitaires et camping-cars</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/notre-centre/">Découvrir le centre</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
