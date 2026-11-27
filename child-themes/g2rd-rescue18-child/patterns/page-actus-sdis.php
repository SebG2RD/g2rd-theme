<?php
/**
 * Title: Page — Actualités des SDIS
 * Slug: rescue18/page-actus-sdis
 * Categories: rescue18-pages
 * Description: Fil des actualités publiées par les SDIS, la BSPP et le BMPM, filtrable par région.
 * Post Types: page
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","className":"r18-phero","align":"full","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-phero"><!-- wp:rescue18/fil-ariane /-->
<!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Pompiers de France</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Actualités des <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">SDIS</mark></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Interventions, recrutements, événements : ce que publient les services d’incendie et de secours, réunis au même endroit. Chaque article renvoie vers le site du service.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section","align":"full","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-section"><!-- wp:rescue18/fil-sdis {"variante":"page","align":"wide"} /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section r18-section--gris","align":"full","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-section r18-section--gris"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Annuaire</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Trouver un service</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Coordonnées, carte et offres de chaque SDIS.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/sdis/' ) ); ?>">Ouvrir l’annuaire</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
