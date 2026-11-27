<?php
/**
 * Title: Prestations — grille
 * Slug: glassauto/prestations-bento
 * Categories: glassauto
 * Description: Deux cartes vedettes avec photo (réparation d'impact, remplacement de pare-brise), six cartes à icône et la carte « Vitrages spécifiques » avec les véhicules pris en charge.
 *
 * @package G2RD_GlassAuto
 */

$ga_photos = get_stylesheet_directory_uri() . '/assets/img/photos/';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-section" id="prestations"><!-- wp:group {"align":"wide","className":"ga-section-head ga-section-head--split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-section-head ga-section-head--split"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Nos prestations</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Tous les vitrages de votre véhicule<span class="ga-dot">.</span></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"ga-lead"} -->
<p class="ga-lead">Du simple impact au remplacement complet, le centre de Vernon intervient sur le pare-brise, les vitres, la lunette arrière, le toit vitré, les rétroviseurs et les optiques de phares.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"ga-bento","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-bento"><!-- wp:group {"className":"ga-presta ga-presta--feature ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-presta--feature ga-corner"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $ga_photos . 'essuie-glace.webp' ); ?>" alt="Pare-brise sous la pluie, essuie-glace au premier plan"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"ga-presta__body","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta__body"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/reparation-impact/">Réparation d'impact</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Un impact de petite taille, hors du champ de vision, peut souvent être réparé sans changer le pare-brise.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/reparation-impact/">Voir la réparation d'impact</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-presta--feature ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-presta--feature ga-corner"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( $ga_photos . 'pare-brise-fissure.webp' ); ?>" alt="Pare-brise fissuré sur toute sa largeur"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"ga-presta__body","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta__body"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/remplacement-pare-brise/">Remplacement de pare-brise</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Fissure, impact mal placé ou pare-brise brisé : nous posons un vitrage adapté à votre véhicule.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/remplacement-pare-brise/">Voir le remplacement de pare-brise</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--vitre-laterale","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--vitre-laterale"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/vitres-laterales/">Vitres latérales</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Remplacement des vitres de portières et de custode, avec retrait des éclats de verre.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/vitres-laterales/">Voir les vitres latérales</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--lunette-arriere","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--lunette-arriere"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/lunette-arriere/">Lunette arrière</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Remplacement de la lunette arrière de votre véhicule, quel que soit le modèle.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/lunette-arriere/">Voir la lunette arrière</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--toit-ouvrant","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--toit-ouvrant"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/vitrages-specifiques/#toit-ouvrant">Toit ouvrant et panoramique</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Remplacement du vitrage de toit ouvrant ou de toit panoramique.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/vitrages-specifiques/#toit-ouvrant">Voir le toit ouvrant et panoramique</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--retroviseur","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--retroviseur"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/retroviseurs/">Rétroviseurs</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Remplacement du miroir ou du rétroviseur endommagé.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/retroviseurs/">Voir les rétroviseurs</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--phare","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--phare"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/optiques-de-phares/">Optiques de phares</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Rénovation des optiques ternies et remplacement des phares endommagés.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/optiques-de-phares/">Voir les optiques de phares</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-corner","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-corner"><!-- wp:group {"className":"ga-icon ga-icon--camera-adas","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--camera-adas"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/calibrage-adas/">Calibrage des caméras</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Recalibrage de la caméra d'aide à la conduite (ADAS) après le remplacement du pare-brise.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ga-link"} -->
<p class="ga-link"><a href="/prestations/calibrage-adas/">Voir le calibrage des caméras</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ga-presta ga-presta--wide","layout":{"type":"default"}} -->
<div class="wp-block-group ga-presta ga-presta--wide" id="vitrages-specifiques"><!-- wp:group {"className":"ga-icon ga-icon--vitrages-speciaux","layout":{"type":"default"}} -->
<div class="wp-block-group ga-icon ga-icon--vitrages-speciaux"></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/prestations/vitrages-specifiques/">Vitrages spécifiques</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Le centre intervient aussi sur les véhicules professionnels, de loisirs et agricoles.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"ga-vehicles"} -->
<ul class="wp-block-list ga-vehicles"><!-- wp:list-item {"className":"ga-icon--voiture"} -->
<li class="ga-icon--voiture">Voitures</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--utilitaire"} -->
<li class="ga-icon--utilitaire">Véhicules utilitaires</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--camping-car"} -->
<li class="ga-icon--camping-car">Camping-cars et vans</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--poids-lourd"} -->
<li class="ga-icon--poids-lourd">Poids lourds</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--agricole"} -->
<li class="ga-icon--agricole">Engins agricoles</li>
<!-- /wp:list-item -->

<!-- wp:list-item {"className":"ga-icon--chantier"} -->
<li class="ga-icon--chantier">Engins de chantier</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
