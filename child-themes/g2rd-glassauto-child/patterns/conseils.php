<?php
/**
 * Title: Conseils (derniers articles)
 * Slug: glassauto/conseils
 * Categories: glassauto
 * Description: Les trois derniers articles de la rubrique Actualités, en cartes, pour relier les pages de prestations aux conseils.
 *
 * @package G2RD_GlassAuto
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"ga-section ga-conseils","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ga-section ga-conseils"><!-- wp:group {"align":"wide","className":"ga-section-head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-section-head"><!-- wp:paragraph {"className":"ga-eyebrow"} -->
<p class="ga-eyebrow">Conseils</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Pour aller plus loin<span class="ga-dot">.</span></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":3,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"align":"wide","className":"ga-posts","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide ga-posts"><!-- wp:post-template {"className":"ga-posts__grid","layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"ga-post-card is-style-card","layout":{"type":"default"}} -->
<div class="wp-block-group ga-post-card is-style-card"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"ga-post-card__media"} /-->

<!-- wp:post-date {"fontSize":"xs","className":"ga-post-card__date"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"h3","className":"ga-post-card__title"} /-->

<!-- wp:post-excerpt {"excerptLength":24,"showMoreOnNewLine":false,"fontSize":"s","className":"ga-post-card__excerpt"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>Les conseils du centre arrivent bientôt.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph {"className":"ga-conseils__more"} -->
<p class="ga-conseils__more"><a class="ga-link" href="/actualites/">Tous les conseils</a></p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->
