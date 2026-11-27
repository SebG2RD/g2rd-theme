<?php
/**
 * Title: Créa Pub — Hero accueil animé
 * Slug: g2rd-creapub/hero-accueil
 * Categories: creapub
 * Description: Vidéo d'origine du logo dessiné au pinceau (cadrée sans sa phrase incrustée), puis H1 réel tapé à la machine avec pinceau rose, pitch et deux appels à l'action. Texte complet dans le DOM (SEO), vidéo figée sur sa dernière image si l'utilisateur réduit les animations.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","className":"creapub-hero creapub-hero--video","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-hero creapub-hero--video"><!-- wp:video {"autoplay":true,"controls":false,"muted":true,"playsInline":true,"className":"creapub-hero-video"} -->
<figure class="wp-block-video creapub-hero-video"><video autoplay muted src="<?php echo \esc_url( \G2RD_CreaPub\media_url( 'video-logo' ) ); ?>" playsinline></video></figure>
<!-- /wp:video -->

<!-- wp:heading {"level":1,"textAlign":"center"} -->
<h1 class="wp-block-heading has-text-align-center">Plongez dans notre univers <strong class="creapub-brush">créatif.</strong></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"creapub-pitch"} -->
<p class="has-text-align-center creapub-pitch">Print, enseignes, marquage véhicule, textile et objets publicitaires : depuis 12 ans, nous <em>concevons et fabriquons</em> votre communication dans notre atelier de Vernon.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Demander un devis gratuit</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#prestations">Nos prestations</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
