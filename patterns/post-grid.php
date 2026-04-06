<?php

/**
 * Title: Post Grid
 * Slug: g2rd-theme/post-grid
 * Description: La liste des articles du blog en grille
 * Categories: featured
 * Keywords: post, grid, blog
 * Viewport Width: 1400
 * Block Types: core/query
 * Post Types: 
 * Inserter: false
 * Text Domain: g2rd
 * 
 * @package G2RD Theme
 * @since 1.0.0
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Mes dernières actualités en Grid</h2>
    <!-- /wp:heading -->

    <!-- wp:group {"metadata":{"categories":["posts"],"patternName":"posts-loop","name":"Liste des articles en grille"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--m);padding-bottom:var(--wp--preset--spacing--m)"><!-- wp:query {"queryId":0,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":null,"parents":[],"format":[]},"align":"wide"} -->
        <div class="wp-block-query alignwide"><!-- wp:post-template {"layout":{"type":"grid","columnCount":2}} -->
            <!-- wp:columns -->
            <div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} -->
                <div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"2/3","width":"100%","height":"100%","sizeSlug":"full","className":"img-h-100","style":{"border":{"radius":{"bottomLeft":"0rem","bottomRight":"0rem","topLeft":"0.5rem","topRight":"0.5rem"}}}} /--></div>
                <!-- /wp:column -->

                <!-- wp:column {"width":"66.66%"} -->
                <div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|xs","left":"var:preset|spacing|xs","top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
                    <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--xs);padding-right:var(--wp--preset--spacing--xs);padding-bottom:var(--wp--preset--spacing--xs);padding-left:var(--wp--preset--spacing--xs)"><!-- wp:post-title {"textAlign":"center","level":4} /-->

                        <!-- wp:post-excerpt {"textAlign":"center","excerptLength":15} /-->

                        <!-- wp:read-more /-->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
            <!-- /wp:post-template -->

            <!-- wp:query-no-results -->
            <!-- wp:paragraph {"placeholder":"Ajouter un texte ou des blocs qui s'afficheront lorsqu'une requête ne renverra aucun résultat."} -->
            <p>Il n'y a pas d'article.</p>
            <!-- /wp:paragraph -->
            <!-- /wp:query-no-results -->
        </div>
        <!-- /wp:query -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->