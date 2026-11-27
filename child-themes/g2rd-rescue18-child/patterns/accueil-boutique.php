<?php
/**
 * Title: Accueil — Boutique
 * Slug: rescue18/accueil-boutique
 * Categories: rescue18
 * Description: Quatre derniers produits WooCommerce, ajout au panier en un clic.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","anchor":"boutique","align":"full","className":"r18-section","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="boutique" class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">08 / Boutique</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Porter les couleurs.<br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">Soutenir le média.</mark></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Produits officiels Rescue 18, fabriqués à la demande et expédiés par Printful.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"r18-more"} -->
<p class="r18-more"><a href="<?php echo esc_url( home_url( '/boutique/' ) ); ?>">Toute la boutique</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/produits {"variante":"grille","nombre":4} /-->
</section>
<!-- /wp:group -->
