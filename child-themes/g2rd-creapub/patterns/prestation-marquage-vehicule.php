<?php
/**
 * Title: Prestation — Marquage véhicule
 * Slug: g2rd-creapub/prestation-marquage-vehicule
 * Categories: creapub-pages
 * Description: Contenu de la prestation au design du site actuel : intro, réalisations du portfolio de la même catégorie (agrandissables), vidéo, savoir-faire, matériaux, délais et FAQ. Structure identique au gabarit du CPT.
 */
?>
<!-- wp:paragraph {"align":"center","className":"creapub-intro-text gsap-fade-in"} -->
<p class="has-text-align-center creapub-intro-text gsap-fade-in">Le <em>marquage véhicule</em> transforme votre flotte en véritable support publicitaire mobile. Du simple lettrage au covering total, chaque trajet devient une opportunité de visibilité pour votre marque.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"creapub-section-heading gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center creapub-section-heading gsap-slide-up">Nos réalisations</h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":4242,"query":{"perPage":12,"pages":0,"offset":0,"postType":"portfolio","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"creapub-realisations-auto"} -->
<div class="wp-block-query creapub-realisations-auto"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"aspectRatio":"4/3","sizeSlug":"large","className":"gsap-zoom-in"} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Les réalisations arrivent bientôt.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:heading {"textAlign":"center","className":"creapub-section-heading gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center creapub-section-heading gsap-slide-up">Technique de pose</h2>
<!-- /wp:heading -->

<!-- wp:video {"className":"creapub-video gsap-zoom-in"} -->
<figure class="wp-block-video creapub-video gsap-zoom-in"><video controls src="<?php echo \esc_url( \G2RD_CreaPub\media_url( 'video-vehicule' ) ); ?>" playsinline></video></figure>
<!-- /wp:video -->
<!-- wp:heading {"level":2,"className":"creapub-savoir-faire"} --><h2 class="wp-block-heading creapub-savoir-faire">Notre <strong>savoir-faire</strong></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Un véhicule marqué, c'est des milliers de contacts visuels par jour pour un coût unique. Du simple lettrage aux portières au covering intégral, nous concevons un habillage adapté à votre métier et à votre budget — utilitaires, flottes, voitures, remorques.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>La pose se fait dans notre atelier de Vernon, à l'abri, par des applicateurs formés : pas de bulles, pas de mauvaises surprises, et une dépose propre en fin de vie du film.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Matériaux &amp; <strong>techniques</strong></h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Films coulés et polymères Hexis, Orafol, Avery, Orajet</li><!-- /wp:list-item --><!-- wp:list-item --><li>Lettrage adhésif, impression + lamination anti-UV, découpe à la forme</li><!-- /wp:list-item --><!-- wp:list-item --><li>Pose en atelier, véhicule restitué prêt à rouler</li><!-- /wp:list-item --></ul><!-- /wp:list -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Délais <strong>indicatifs</strong></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>1 à 2 semaines entre la validation du BAT et la pose ; immobilisation du véhicule de quelques heures (lettrage) à 2 jours (covering complet).</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Questions <strong>fréquentes</strong></h2><!-- /wp:heading -->
<!-- wp:details {"summary":"Le marquage abîme-t-il la peinture ?"} --><details class="wp-block-details"><summary>Le marquage abîme-t-il la peinture ?</summary><!-- wp:paragraph --><p>Non : les films professionnels que nous utilisons protègent la carrosserie et se retirent proprement, sans résidus.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details {"summary":"Combien de temps dure un covering ?"} --><details class="wp-block-details"><summary>Combien de temps dure un covering ?</summary><!-- wp:paragraph --><p>5 à 7 ans en extérieur pour des films coulés laminés, selon l'exposition et l'entretien.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details {"summary":"Marquez-vous les flottes d'entreprise ?"} --><details class="wp-block-details"><summary>Marquez-vous les flottes d'entreprise ?</summary><!-- wp:paragraph --><p>Oui, avec un gabarit reproductible sur tous vos véhicules et un planning qui limite l'immobilisation de votre parc.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
