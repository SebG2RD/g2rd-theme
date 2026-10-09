<?php
/**
 * Title: Portfolio — pile de dossiers (scroll immersif)
 * Slug: g2rd-child/portfolio
 * Categories: g2rd-refonte
 * Description: Query Loop sur le CPT portfolio (6 projets) rendue en pile « dossiers » : chaque dossier se fige au scroll, le suivant glisse par-dessus (animations.js). Numéros PRJ.NN et numéros fantômes générés en CSS, jamais de contenus en dur.
 */
?>
<!-- wp:group {"tagName":"section","className":"g2rd-portfolio g2rd-showcase g2rd-section g2rd-theme-ink","layout":{"type":"default"}} -->
<section class="wp-block-group g2rd-portfolio g2rd-showcase g2rd-section g2rd-theme-ink"><!-- wp:group {"className":"g2rd-wide","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-wide"><!-- wp:group {"className":"g2rd-section-head g2rd-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-section-head g2rd-reveal"><!-- wp:paragraph {"className":"g2rd-meta"} -->
<p class="g2rd-meta">Portfolio</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Sites internet réalisés par notre agence</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Six dossiers, six clients&nbsp;: des sites en production, pensés pour durer.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":21,"query":{"perPage":6,"pages":0,"offset":0,"postType":"portfolio","order":"desc","orderBy":"date","inherit":false},"className":"g2rd-showcase__query"} -->
<div class="wp-block-query g2rd-showcase__query"><!-- wp:post-template {"className":"g2rd-showcase__stack"} -->
<!-- wp:group {"tagName":"article","className":"g2rd-case","layout":{"type":"default"}} -->
<article class="wp-block-group g2rd-case"><!-- wp:group {"className":"g2rd-case__inner","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-case__inner"><!-- wp:group {"className":"g2rd-case__info","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-case__info"><!-- wp:paragraph {"className":"g2rd-case__meta"} -->
<p class="g2rd-case__meta">en production</p>
<!-- /wp:paragraph -->

<!-- wp:post-title {"level":0,"isLink":true} /-->

<!-- wp:post-terms {"term":"site_web","className":"g2rd-case__tags"} /-->

<!-- wp:post-excerpt {"excerptLength":24,"className":"g2rd-case__desc"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"g2rd-case__frame","layout":{"type":"default"}} -->
<div class="wp-block-group g2rd-case__frame"><!-- wp:group {"className":"g2rd-case__bar","layout":{"type":"flex","justifyContent":"space-between"}} -->
<div class="wp-block-group g2rd-case__bar"><!-- wp:paragraph {"className":"g2rd-case__state"} -->
<p class="g2rd-case__state">● en ligne</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:post-featured-image /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></article>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:buttons {"className":"g2rd-showcase__more g2rd-reveal","layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons g2rd-showcase__more g2rd-reveal"><!-- wp:button {"className":"g2rd-btn g2rd-btn--ghost"} -->
<div class="wp-block-button g2rd-btn g2rd-btn--ghost"><a class="wp-block-button__link wp-element-button" href="https://g2rd.fr/portfolio/">Découvrir tous nos projets clients</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
