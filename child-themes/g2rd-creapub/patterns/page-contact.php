<?php
/**
 * Title: Page — Contact
 * Slug: g2rd-creapub/page-contact
 * Categories: creapub-pages
 * Description: Bandeau photo de l'agence, formulaire de contact et coordonnées avec horaires, carte, accès à l'atelier côté Seine, zones d'intervention et FAQ.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","className":"creapub-banner creapub-banner--agence","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-banner creapub-banner--agence"><!-- wp:group {"className":"creapub-banner__overlay gsap-slide-up","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-banner__overlay gsap-slide-up"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Contactez-nous</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"creapub-banner__sub"} -->
<p class="creapub-banner__sub">Une question ? Un projet ? Notre équipe est à votre écoute pour donner vie à vos idées.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-contact","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-contact"><!-- wp:columns {"className":"creapub-contact__grid"} -->
<div class="wp-block-columns creapub-contact__grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"creapub-script-title gsap-slide-up"} -->
<h2 class="wp-block-heading creapub-script-title gsap-slide-up">Envoyez-nous un message</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[creapub_formulaire]
<!-- /wp:shortcode --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"className":"creapub-script-title gsap-slide-up"} -->
<h2 class="wp-block-heading creapub-script-title gsap-slide-up">Nos coordonnées</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"creapub-coord creapub-coord--mail gsap-fade-in"} -->
<p class="creapub-coord creapub-coord--mail gsap-fade-in"><a href="mailto:contact@creapubcommunication.fr">contact@creapubcommunication.fr</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-coord creapub-coord--tel gsap-fade-in"} -->
<p class="creapub-coord creapub-coord--tel gsap-fade-in"><a href="tel:+33232213116">02 32 21 31 16</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-coord creapub-coord--pin gsap-fade-in"} -->
<p class="creapub-coord creapub-coord--pin gsap-fade-in">56, avenue de Rouen<br>27200 VERNON</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-coord creapub-coord--clock gsap-fade-in"} -->
<p class="creapub-coord creapub-coord--clock gsap-fade-in"><strong>Horaires</strong>Du lundi au jeudi :<br>08h45 – 12h30 / 13h30 – 18h30<br><br>Vendredi :<br>08h45 – 12h30 / 13h30 – 18h00<br><br>Fermé le samedi et le dimanche</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-trouver","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-trouver"><!-- wp:heading {"textAlign":"center","className":"creapub-script-title gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center creapub-script-title gsap-slide-up">Nous trouver</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="creapub-map"><iframe src="https://www.google.com/maps?q=56%20avenue%20de%20Rouen%2C%2027200%20Vernon&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Créa Pub Communication, 56 avenue de Rouen à Vernon, sur Google Maps" allowfullscreen></iframe></div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-atelier","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-atelier"><!-- wp:heading {"textAlign":"center","className":"creapub-script-title gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center creapub-script-title gsap-slide-up">L'atelier est accessible côté Seine !</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">L'agence est visible des deux côtés. En arrivant côté Seine, vous trouverez l'atelier : il suffit de tourner à droite dans la rue Pierre Maubert pour y accéder. Stationnement facile devant l'atelier pour la pose de vos marquages.</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"columns":3,"linkTo":"none","sizeSlug":"large","className":"creapub-atelier-photos"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-cropped creapub-atelier-photos"><?php echo \G2RD_CreaPub\image_block( 'atelier-1', '', 'large', true ); ?>

<?php echo \G2RD_CreaPub\image_block( 'atelier-2', '', 'large', true ); ?>

<?php echo \G2RD_CreaPub\image_block( 'atelier-3', '', 'large', true ); ?></figure>
<!-- /wp:gallery --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-zones","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-zones"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Basés à Vernon, nous nous déplaçons largement dans la région : Eure (27), Yvelines (78), Val-d'Oise (95), Seine-Maritime (76), Eure-et-Loir (28) et jusqu'à la région parisienne. <a href="/prestations/">Découvrir nos prestations</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"g2rd-creapub/faq"} /-->
