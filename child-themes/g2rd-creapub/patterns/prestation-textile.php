<?php
/**
 * Title: Prestation — Textile
 * Slug: g2rd-creapub/prestation-textile
 * Categories: creapub-pages
 * Description: Contenu de la prestation au design du site actuel : intro, réalisations du portfolio de la même catégorie (agrandissables), vidéo, savoir-faire, matériaux, délais et FAQ. Structure identique au gabarit du CPT.
 */
?>
<!-- wp:paragraph {"align":"center","className":"creapub-intro-text gsap-fade-in"} -->
<p class="has-text-align-center creapub-intro-text gsap-fade-in">Le <em>textile</em> à votre image est un outil de communication efficace : il renforce votre visibilité, valorise votre image professionnelle et fédère vos équipes au quotidien.</p>
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
<h2 class="wp-block-heading has-text-align-center creapub-section-heading gsap-slide-up">Flocage textile : broderie, DTF</h2>
<!-- /wp:heading -->

<!-- wp:video {"className":"creapub-video gsap-zoom-in"} -->
<figure class="wp-block-video creapub-video gsap-zoom-in"><video controls src="<?php echo \esc_url( \G2RD_CreaPub\media_url( 'video-textile' ) ); ?>" playsinline></video></figure>
<!-- /wp:video -->
<!-- wp:heading {"level":2,"className":"creapub-savoir-faire"} --><h2 class="wp-block-heading creapub-savoir-faire">Notre <strong>savoir-faire</strong></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Une équipe bien habillée, c'est une image professionnelle sur chaque chantier et chaque salon. Nous personnalisons t-shirts, polos, sweats, softshells, parkas et vêtements de travail normés — pour artisans, clubs sportifs, associations et entreprises.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Le bon textile compte autant que le bon marquage : nous vous conseillons parmi les gammes Sol's, Clique, Projob, Craft ou Cutter &amp; Buck selon l'usage et le budget.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Matériaux &amp; <strong>techniques</strong></h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Flocage flex/flock, impression DTF quadri, sérigraphie sur volume</li><!-- /wp:list-item --><!-- wp:list-item --><li>Broderie pour un rendu premium et durable</li><!-- /wp:list-item --><!-- wp:list-item --><li>Textiles Sol's, Clique, Projob, Craft, Cutter &amp; Buck — y compris haute visibilité</li><!-- /wp:list-item --></ul><!-- /wp:list -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Délais <strong>indicatifs</strong></h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>5 à 10 jours ouvrés après validation du BAT selon quantités et technique. Réassort simplifié : vos fichiers restent archivés chez nous.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Questions <strong>fréquentes</strong></h2><!-- /wp:heading -->
<!-- wp:details {"summary":"Broderie ou flocage : que choisir ?"} --><details class="wp-block-details"><summary>Broderie ou flocage : que choisir ?</summary><!-- wp:paragraph --><p>Broderie pour la durabilité et le rendu haut de gamme (polos, vestes) ; flocage ou DTF pour les visuels détaillés et les petits budgets.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details {"summary":"Peut-on commander à l'unité ?"} --><details class="wp-block-details"><summary>Peut-on commander à l'unité ?</summary><!-- wp:paragraph --><p>Oui — du t-shirt unique à la dotation complète de vos équipes.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
<!-- wp:details {"summary":"Proposez-vous des vêtements de travail normés ?"} --><details class="wp-block-details"><summary>Proposez-vous des vêtements de travail normés ?</summary><!-- wp:paragraph --><p>Oui : gammes pro et haute visibilité (EN ISO 20471) personnalisables dans le respect des normes.</p><!-- /wp:paragraph --></details><!-- /wp:details -->
