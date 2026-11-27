<?php
/**
 * Title: Page — Recrutement
 * Slug: rescue18/page-recrutement
 * Categories: rescue18-pages
 * Description: Six parcours pour devenir pompier, calendrier des concours, offres et recrutements en cours dans les SDIS.
 * Post Types: page
 *
 * @package G2RD_Rescue18
 */

\defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","className":"r18-phero","align":"full","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section class="wp-block-group alignfull r18-phero"><!-- wp:rescue18/fil-ariane /-->
<!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Recrutement</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Devenir <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-r18-rouge-color">pompier.</mark></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Volontaire, professionnel, militaire à Paris ou marin à Marseille : choisis ton parcours. Les offres et les dates arrivent ici toutes seules, depuis les sources officielles.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section","align":"full","anchor":"parcours","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="parcours" class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Parcours</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Par où commencer ?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Six chemins, du plus courant au plus spécialisé.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tools","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tools"><!-- wp:group {"className":"r18-tool r18-i--users","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--users"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Sapeur-pompier volontaire</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Le plus courant : près de 8 pompiers sur 10 sont volontaires. Dès 16 ans, à côté de ton métier ou de tes études, dans la caserne près de chez toi.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( G2RD_Rescue18\Annuaire\reglages()['lien_spv'] ); ?>">S’informer</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--grad","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--grad"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Jeune sapeur-pompier</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Pour les collégiens et lycéens : une formation souvent le mercredi ou le samedi, du sport et l’esprit d’équipe. Renseigne-toi auprès du SDIS de ton département.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/sdis/' ) ); ?>">Trouver mon SDIS</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--shield","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--shield"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Sapeur-pompier professionnel</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>On devient sapeur-pompier professionnel par concours, ouvert par arrêté. Repère les dates dans le calendrier, puis prépare les épreuves.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/recrutement/concours/' ) ); ?>">Voir le calendrier</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--flame","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--flame"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Brigade de Paris (BSPP)</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Militaire de l’Armée de Terre, à Paris et dans la petite couronne. Engagement direct, sans concours. Conditions d’âge et d’aptitude sur le site officiel.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( G2RD_Rescue18\Annuaire\reglages()['lien_bspp'] ); ?>">Site de la BSPP</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--compass","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--compass"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Marins-pompiers de Marseille (BMPM)</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Marin de la Marine nationale, pompier à Marseille. Engagement par le recrutement de la Marine.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( G2RD_Rescue18\Annuaire\reglages()['lien_bmpm'] ); ?>">Recrutement Marine</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"r18-tool r18-i--briefcase","layout":{"type":"default"}} -->
<div class="wp-block-group r18-tool r18-i--briefcase"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Métiers des SDIS</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Mécanicien, infirmier, informaticien, gestionnaire : les SDIS recrutent aussi sans concours de pompier. Toutes les offres en cours.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"r18-tool__go"} -->
<p class="r18-tool__go"><a href="<?php echo esc_url( home_url( '/recrutement/offres-emploi/' ) ); ?>">Voir les offres</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section r18-section--gris","align":"full","anchor":"calendrier","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="calendrier" class="wp-block-group alignfull r18-section r18-section--gris"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Calendrier</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Concours et campagnes de recrutement</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Les prochaines dates, triées de la plus proche à la plus lointaine.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/recrutement/concours/' ) ); ?>">Tout le calendrier</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/concours {"nombre":4} /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section","align":"full","anchor":"offres","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="offres" class="wp-block-group alignfull r18-section"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">Offres d’emploi</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Les SDIS recrutent</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Postes publiés cette semaine sur Choisir le Service Public.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/recrutement/offres-emploi/' ) ); ?>">Toutes les offres</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/offres {"nombre":5} /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"r18-section r18-section--gris","align":"full","anchor":"en-cours","layout":{"type":"constrained","contentSize":"1254px"}} -->
<section id="en-cours" class="wp-block-group alignfull r18-section r18-section--gris"><!-- wp:group {"className":"r18-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group r18-head"><!-- wp:group {"className":"r18-head__txt","layout":{"type":"default"}} -->
<div class="wp-block-group r18-head__txt"><!-- wp:paragraph {"className":"r18-eyebrow"} -->
<p class="r18-eyebrow">En ce moment</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"className":"r18-h2"} -->
<h2 class="wp-block-heading r18-h2">Recrutements annoncés par les SDIS</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"r18-lead"} -->
<p class="r18-lead">Campagnes de volontaires, journées portes ouvertes, sélections JSP.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:rescue18/fil-sdis {"filtre":"recrutement","nombre":6} /-->
</section>
<!-- /wp:group -->
