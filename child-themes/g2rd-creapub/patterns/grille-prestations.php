<?php
/**
 * Title: Créa Pub — Nos prestations
 * Slug: g2rd-creapub/grille-prestations
 * Categories: creapub
 * Description: Les prestations du CPT en cartes (photo à la une, titre, extrait, lien), suivies d'un lien contact.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","anchor":"prestations","className":"creapub-section creapub-prestations","layout":{"type":"constrained"}} -->
<div id="prestations" class="wp-block-group alignfull creapub-section creapub-prestations"><!-- wp:heading {"textAlign":"center","className":"gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center gsap-slide-up">Nos <strong>prestations</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"creapub-intro gsap-fade-in"} -->
<p class="has-text-align-center creapub-intro gsap-fade-in">Du print à l'habillage complet de votre véhicule, tout est conçu et fabriqué par notre équipe à Vernon.</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":4245,"query":{"perPage":6,"pages":0,"offset":0,"postType":"prestations","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"creapub-prest-cards"} -->
<div class="wp-block-query creapub-prest-cards"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"creapub-carte creapub-carte--photo gsap-fade-in","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-carte creapub-carte--photo gsap-fade-in"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","sizeSlug":"large","className":"gsap-zoom-in"} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"moreText":"Découvrir →","showMoreOnNewLine":true,"excerptLength":22} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:paragraph {"align":"center","className":"creapub-prest-contact"} -->
<p class="has-text-align-center creapub-prest-contact">Un projet particulier ? <a href="/contact/">Parlons-en, on trouve toujours une solution →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
