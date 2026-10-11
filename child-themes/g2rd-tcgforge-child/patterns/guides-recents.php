<?php
/**
 * Title: Guides récents
 * Slug: tcgforge/guides-recents
 * Categories: tcgforge
 * Description: Les trois derniers articles publiés, en requête dynamique.
 *
 * @package G2RD_TCGForge
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"tcgf-section tcgf-guides","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull tcgf-section tcgf-guides"><!-- wp:group {"align":"wide","className":"tcgf-section__head tcgf-section__head\u002d\u002dsplit","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide tcgf-section__head tcgf-section__head--split"><!-- wp:heading -->
<h2 class="wp-block-heading">Guides et conseils</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tcgf-more"} -->
<p class="tcgf-more"><a href="/conseils/">Tous les guides</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":41,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false},"align":"wide","className":"tcgf-posts"} -->
<div class="wp-block-query alignwide tcgf-posts"><!-- wp:post-template {"className":"tcgf-post-grid","layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"excerptLength":22} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"className":"tcgf-empty__text"} -->
<p class="tcgf-empty__text">Les premiers guides sont en cours de rédaction.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></section>
<!-- /wp:group -->
