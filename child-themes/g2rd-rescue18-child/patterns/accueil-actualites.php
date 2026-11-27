<?php
/**
 * Title: Accueil — À la une
 * Slug: rescue18/accueil-actualites
 * Categories: rescue18
 * Description: Trois articles récents (le premier en grand format), puis quatre publications en ligne. Boucles de requête natives.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","anchor":"actualites","align":"full","className":"r18-section r18-section--blanc","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="actualites" class="wp-block-group alignfull r18-section r18-section--blanc"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">01 / À la une</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Le terrain.<br>L'expertise. <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">L'info.</mark></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Dossiers techniques, reportages et culture du secours, rédigés par des sapeurs-pompiers.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"r18-more"} -->
<p class="r18-more"><a href="<?php echo esc_url( home_url( '/articles/' ) ); ?>">Tous les articles</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"r18-news"} -->
<div class="wp-block-query r18-news"><!-- wp:post-template -->
<!-- wp:group {"className":"r18-card","layout":{"type":"default"}} -->
<div class="wp-block-group r18-card"><!-- wp:post-featured-image {"sizeSlug":"large"} /-->
<!-- wp:group {"className":"r18-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group r18-card__body"><!-- wp:post-terms {"term":"category"} /-->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt {"excerptLength":22} /-->
<!-- wp:group {"className":"r18-meta-line","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-group r18-meta-line"><!-- wp:post-author-name /-->
<!-- wp:post-date /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
<!-- wp:query {"queryId":2,"query":{"perPage":4,"pages":0,"offset":3,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"r18-latest"} -->
<div class="wp-block-query r18-latest"><!-- wp:post-template -->
<!-- wp:group {"className":"r18-row-card","layout":{"type":"default"}} -->
<div class="wp-block-group r18-row-card"><!-- wp:post-terms {"term":"category"} /-->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-date /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</section>
<!-- /wp:group -->
