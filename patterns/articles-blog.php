<?php

/**
 * Title: Articles Blog
 * Slug: G2RD-theme/articles-blog
 * Description: Affichage des articles du blog en grille
 * Categories: hero
 * Keywords: blog, posts, query, loop
 * Viewport Width: 1400
 * Block Types: core/query
 * Post Types:
 * Inserter: true
 * Text Domain: g2rd
 */
?>

<!-- wp:group {"metadata":{"categories":["hero"],"patternName":"articles-blog","name":"Articles du blog"},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Nos dernières actualités</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":0,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"layout":{"type":"grid","columnCount":2}} -->
<!-- wp:post-featured-image {"style":{"border":{"radius":{"topLeft":"1rem","topRight":"1rem","bottomLeft":"0rem","bottomRight":"0rem"}}}} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|xs","left":"var:preset|spacing|xs","top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--xs);padding-right:var(--wp--preset--spacing--xs);padding-bottom:var(--wp--preset--spacing--xs);padding-left:var(--wp--preset--spacing--xs)"><!-- wp:post-title {"textAlign":"center","level":3} /-->

<!-- wp:post-date {"textAlign":"center"} /-->

<!-- wp:post-excerpt {"textAlign":"center","excerptLength":25} /-->

<!-- wp:read-more /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"paginationArrow":"chevron","layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>Il n'y a pas d'article.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->