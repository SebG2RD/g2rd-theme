<?php
/**
 * Title: Accueil — Podcasts
 * Slug: rescue18/accueil-podcasts
 * Categories: rescue18
 * Description: Panneau SSUAP Le Podcast et derniers épisodes avec lecteur intégré.
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","anchor":"podcasts","align":"full","className":"r18-section r18-section--blanc","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="podcasts" class="wp-block-group alignfull r18-section r18-section--blanc"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">07 / Podcasts</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Écouter.<br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">Apprendre sur le terrain.</mark></h2>
<!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"r18-more"} -->
<p class="r18-more"><a href="<?php echo esc_url( home_url( '/ssuap-le-podcast/' ) ); ?>">Tous les épisodes</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-pod-grid","layout":{"type":"default"}} -->
<div class="wp-block-group r18-pod-grid"><!-- wp:group {"className":"r18-pod-panel","layout":{"type":"default"}} -->
<div class="wp-block-group r18-pod-panel"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">SSUAP Le Podcast</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Des épisodes courts pour les bases du secours à personne.</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Bilans, gestes, transmission à la régulation et pédagogie de terrain, pour secouristes, sapeurs-pompiers et formateurs.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<div class="r18-wave" aria-hidden="true"><i style="height:40%"></i><i style="height:70%"></i><i style="height:50%"></i><i style="height:90%"></i><i style="height:60%"></i><i style="height:80%"></i><i style="height:45%"></i><i style="height:75%"></i><i style="height:30%"></i><i style="height:65%"></i><i style="height:85%"></i><i style="height:40%"></i><i style="height:55%"></i><i style="height:70%"></i><i style="height:35%"></i><i style="height:60%"></i><i style="height:80%"></i><i style="height:50%"></i><i style="height:30%"></i><i style="height:65%"></i></div>
<!-- /wp:html -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"r18-arrow"} -->
<div class="wp-block-button r18-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/ssuap-le-podcast/' ) ); ?>">Écouter le dernier épisode</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-r18-blanc"} -->
<div class="wp-block-button is-style-r18-blanc"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/ssuap-le-podcast/' ) ); ?>">Tous les épisodes</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:rescue18/episodes {"variante":"accueil","nombre":4} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
