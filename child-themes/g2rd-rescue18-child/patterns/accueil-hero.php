<?php
/**
 * Title: Accueil — Hero au casque 3D
 * Slug: rescue18/accueil-hero
 * Categories: rescue18
 * Description: Titre XXL à gauche, casque de sapeur-pompier en relief CSS 3D à droite, anneaux orbitaux et cinq panneaux flottants cliquables (direction validée). Perspective au curseur, mouvement coupé avec prefers-reduced-motion.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"r18-hero","layout":{"type":"default"}} -->
<section class="wp-block-group alignfull r18-hero"><!-- wp:group {"className":"r18-hero__in","layout":{"type":"default"}} -->
<div class="wp-block-group r18-hero__in"><!-- wp:group {"className":"r18-hero__copy","layout":{"type":"default"}} -->
<div class="wp-block-group r18-hero__copy"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Le média des sapeurs-pompiers</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"anchor":"hero-title","className":"r18-hero__title"} -->
<h1 id="hero-title" class="wp-block-heading r18-hero__title">AU CŒUR<br>DU <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">SECOURS.</mark></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-hero__lead"} -->
<p class="r18-hero__lead">L'information, les savoirs opérationnels et la formation dans une autre dimension. Une bibliothèque publique, pensée pour le terrain et pour apprendre.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"r18-arrow"} -->
<div class="wp-block-button r18-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/articles/' ) ); ?>">Explorer les actualités</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-r18-blanc r18-arrow"} -->
<div class="wp-block-button is-style-r18-blanc r18-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/documentations-referentiels/' ) ); ?>">Bibliothèque documentaire</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:group {"className":"r18-facts-row","layout":{"type":"default"}} -->
<div class="wp-block-group r18-facts-row"><!-- wp:paragraph {"className":"r18-fact-line"} -->
<p class="r18-fact-line"><strong>28 guides</strong>GDO et GTO en ligne</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fact-line"} -->
<p class="r18-fact-line"><strong>99 épisodes</strong>SSUAP Le Podcast</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fact-line"} -->
<p class="r18-fact-line"><strong>Accès libre</strong>Sans compte ni abonnement</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-stage","layout":{"type":"default"}} -->
<div class="wp-block-group r18-stage"><!-- wp:group {"className":"r18-stage__rotor","layout":{"type":"default"}} -->
<div class="wp-block-group r18-stage__rotor"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"r18-helmet"} -->
<figure class="wp-block-image size-full r18-helmet"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/casque-rescue18.webp' ); ?>" alt=""/></figure>
<!-- /wp:image -->
<!-- wp:paragraph {"className":"r18-fc r18-fc--1 r18-i--flame"} -->
<p class="r18-fc r18-fc--1 r18-i--flame"><a href="<?php echo esc_url( home_url( '/specialite/incendie/' ) ); ?>"><strong>Incendie</strong><small>Fiches feu, FDF, forcement</small></a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fc r18-fc--2 r18-i--med r18-fc--dark"} -->
<p class="r18-fc r18-fc--2 r18-i--med r18-fc--dark"><a href="<?php echo esc_url( home_url( '/specialite/ssuap/' ) ); ?>"><strong>SSUAP</strong><small>Secours d'urgence aux personnes</small></a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fc r18-fc--3 r18-i--file"} -->
<p class="r18-fc r18-fc--3 r18-i--file"><a href="<?php echo esc_url( home_url( '/documentations-referentiels/' ) ); ?>"><strong>Documentation</strong><small>GDO, GTO, référentiels</small></a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fc r18-fc--4 r18-i--grad r18-fc--dark"} -->
<p class="r18-fc r18-fc--4 r18-i--grad r18-fc--dark"><a href="<?php echo esc_url( home_url( '/formation/' ) ); ?>"><strong>Formation</strong><small>JSP, SPV, SPP</small></a></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-fc r18-fc--5 r18-i--car"} -->
<p class="r18-fc r18-fc--5 r18-i--car"><a href="<?php echo esc_url( home_url( '/specialite/secours-routier/' ) ); ?>"><strong>Secours routier</strong><small>IUV, désincarcération</small></a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
