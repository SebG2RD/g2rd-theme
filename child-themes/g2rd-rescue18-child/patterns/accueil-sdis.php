<?php
/**
 * Title: Accueil — Pompiers de France
 * Slug: rescue18/accueil-sdis
 * Categories: rescue18
 * Description: Carte des SDIS, recherche par département et dernières actualités des services.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","className":"r18-section","align":"full","anchor":"sdis","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="sdis" class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Pompiers de France</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Ton SDIS en un clic</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Carte des 99 services, actualités et offres d’emploi, mises à jour chaque jour.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/recrutement/' ) ); ?>">Devenir pompier</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/annuaire {"variante":"accueil","align":"wide"} /-->
<!-- wp:rescue18/fil-sdis {"nombre":3} /-->
</section>
<!-- /wp:group -->
