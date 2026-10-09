<?php
/**
 * Title: Derniers articles
 * Slug: g2rd-child/blog
 * Categories: g2rd-refonte
 * Description: Trois derniers articles du blog (Query Loop).
 */
?>
<!-- wp:group {"tagName":"section","className":"g2rd-blog g2rd-section g2rd-theme-surface","layout":{"type":"default"}} -->
<section class="wp-block-group g2rd-blog g2rd-section g2rd-theme-surface"><!-- wp:group {"className":"g2rd-wide","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-wide"><!-- wp:group {"className":"g2rd-section-head g2rd-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-section-head g2rd-reveal"><!-- wp:paragraph {"className":"g2rd-meta"} -->
<p class="g2rd-meta">Actualités</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Dernières tendances numériques</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Nos articles sur le digital, WordPress, le SEO et les bonnes pratiques web.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":22,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"g2rd-card-grid"} -->
<!-- wp:post-featured-image /-->

<!-- wp:group {"className":"g2rd-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-card__body"><!-- wp:post-date /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"excerptLength":22} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
