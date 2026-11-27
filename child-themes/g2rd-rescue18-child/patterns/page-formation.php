<?php
/**
 * Title: Page — Formation
 * Slug: rescue18/page-formation
 * Categories: rescue18-pages
 * Description: Parcours JSP, SPV, SPP, quiz par thème, fiches de révision, concours et préparation physique.
 * Post Types: page
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","align":"full","className":"r18-phero","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-phero"><!-- wp:rescue18/fil-ariane /-->
<!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Formation</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Réviser.<br><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">S'entraîner.</mark> Progresser.</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Parcours JSP, SPV et SPP, quiz corrigés et fiches de révision reliées aux guides officiels. Accès libre, sans compte.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","anchor":"parcours","align":"full","className":"r18-section","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="parcours" class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Parcours</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Choisir son parcours</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Trois publics, trois référentiels nationaux.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tools","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tools"><!-- wp:group {"className":"r18-tool r18-i--users","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--users"><!-- wp:heading {"level":3,"anchor":"jsp"} -->
<h3 id="jsp" class="wp-block-heading">JSP</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Jeunes sapeurs-pompiers : cycles de formation, référentiel et fiches de révision.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/public/jsp/' ) ); ?>">Ressources JSP</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--heart","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--heart"><!-- wp:heading {"level":3,"anchor":"spv"} -->
<h3 id="spv" class="wp-block-heading">SPV</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Formation initiale et continue des volontaires, modules et ressources associées.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/public/spv/' ) ); ?>">Ressources SPV</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--shield","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--shield"><!-- wp:heading {"level":3,"anchor":"spp"} -->
<h3 id="spp" class="wp-block-heading">SPP</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Formations professionnelles, grades et spécialités, avec les textes de référence.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/public/spp/' ) ); ?>">Ressources SPP</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","anchor":"quiz","align":"full","className":"r18-section r18-section--blanc","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="quiz" class="wp-block-group alignfull r18-section r18-section--blanc"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Quiz et QCM</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">S'entraîner <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">par thème.</mark></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Une question à la fois, correction immédiate et explication. Aucun score enregistré.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:query {"queryId":3,"query":{"perPage":8,"pages":0,"offset":0,"postType":"r18_quiz","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"r18-latest"} -->
<div class="wp-block-query r18-latest"><!-- wp:post-template -->
<!-- wp:group {"className":"r18-row-card","layout":{"type":"default"}} -->
<div class="wp-block-group r18-row-card"><!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt {"excerptLength":18} /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","anchor":"fiches","align":"full","className":"r18-section r18-lib-sec r18-on-dark","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="fiches" class="wp-block-group alignfull r18-section r18-lib-sec r18-on-dark"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Fiches de révision</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Synthèses reliées <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">aux guides.</mark></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Les fiches renvoient toujours vers le document officiel correspondant.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/bibliotheque {"variante":"accueil","type":"referentiel","nombre":8} /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","align":"full","className":"r18-section","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-pod-grid","layout":{"type":"default"}} -->
<div class="wp-block-group r18-pod-grid"><!-- wp:group {"anchor":"concours","className":"r18-panel","layout":{"type":"default"}} -->
<div id="concours" class="wp-block-group r18-panel"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Concours</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Préparer les concours de sapeur-pompier professionnel.</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Épreuves, programmes et calendrier, avec les textes officiels qui les encadrent.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-panel","layout":{"type":"default"}} -->
<div class="wp-block-group r18-panel"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Préparation physique</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Entraînement, sport et santé en service.</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Conseils généraux de préparation. Toute consigne d'entraînement reste validée par un professionnel.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
