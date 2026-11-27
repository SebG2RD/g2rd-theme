<?php
/**
 * Title: Page — Prestations
 * Slug: g2rd-creapub/page-prestations
 * Categories: creapub-pages
 * Description: Bandeau zèbre, les prestations du CPT en rangées alternées (photo à la une, titre, extrait, lien), process, partenaires et bandeau devis.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","className":"creapub-zebre","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-zebre"><!-- wp:heading {"level":1,"textAlign":"center","className":"creapub-zebre__title"} -->
<h1 class="wp-block-heading has-text-align-center creapub-zebre__title">Nos prestations</h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-intro gsap-fade-in","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-intro gsap-fade-in"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Print, signalétique, marquage véhicule, textile et objets publicitaires : <em>un seul atelier à Vernon</em> pour concevoir, fabriquer et poser toute votre communication.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"g2rd-creapub/bande-prestations"} /-->

<!-- wp:group {"align":"full","className":"creapub-section creapub-prest-section","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-prest-section"><!-- wp:query {"queryId":4244,"query":{"perPage":20,"pages":0,"offset":0,"postType":"prestations","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"creapub-prest-query"} -->
<div class="wp-block-query creapub-prest-query"><!-- wp:post-template {"className":"creapub-prest-rows"} -->
<!-- wp:group {"className":"creapub-prest-row","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-prest-row"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","sizeSlug":"large","className":"creapub-prest-row__img gsap-slide-left"} /-->

<!-- wp:group {"className":"creapub-prest-row__body gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-prest-row__body gsap-slide-right"><!-- wp:post-title {"level":2,"isLink":true,"className":"creapub-prest-row__title"} /-->

<!-- wp:post-excerpt {"moreText":"Voir les réalisations","showMoreOnNewLine":true,"excerptLength":45,"className":"creapub-prest-row__excerpt"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"g2rd-creapub/process-etapes"} /-->

<!-- wp:pattern {"slug":"g2rd-creapub/partenaires"} /-->

<!-- wp:pattern {"slug":"g2rd-creapub/bande-cta"} /-->
