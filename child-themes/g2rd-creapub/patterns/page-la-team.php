<?php
/**
 * Title: Page — La Team
 * Slug: g2rd-creapub/page-la-team
 * Categories: creapub-pages
 * Description: Bandeau photo de l'équipe, cartes des membres (CPT Qui sommes-nous, ordre d'affichage), valeurs et frise de l'histoire de l'agence, comme sur le site actuel.
 *
 * @package G2RD_CreaPub
 */

?>
<!-- wp:group {"align":"full","className":"creapub-team-banner","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-team-banner"><?php echo \G2RD_CreaPub\image_block( 'team-banner', 'creapub-team-banner__img', 'full', false ); ?></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-team","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-team"><!-- wp:heading {"level":1,"textAlign":"center","className":"screen-reader-text"} -->
<h1 class="wp-block-heading has-text-align-center screen-reader-text">La Team</h1>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","className":"gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center gsap-slide-up">Une équipe créative au service de votre <strong>communication</strong></h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":4243,"query":{"perPage":20,"pages":0,"offset":0,"postType":"qui-sommes-nous","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"creapub-team-query"} -->
<div class="wp-block-query creapub-team-query"><!-- wp:post-template {"className":"creapub-team-grid","layout":{"type":"grid","columnCount":2}} -->
<!-- wp:group {"className":"creapub-team-card gsap-fade-in","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"stretch"}} -->
<div class="wp-block-group creapub-team-card gsap-fade-in"><!-- wp:post-featured-image {"className":"creapub-team-card__photo","sizeSlug":"large"} /-->

<!-- wp:group {"className":"creapub-team-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-team-card__body"><!-- wp:post-title {"level":3,"className":"creapub-team-card__name"} /-->

<!-- wp:separator {"className":"creapub-sep"} -->
<hr class="wp-block-separator has-alpha-channel-opacity creapub-sep"/>
<!-- /wp:separator -->

<!-- wp:post-content {"className":"creapub-team-card__text"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-valeurs","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-valeurs"><!-- wp:heading {"textAlign":"center","className":"gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center gsap-slide-up">Nos <strong>valeurs</strong></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"creapub-valeurs-grid","layout":{"type":"grid","minimumColumnWidth":"200px"}} -->
<div class="wp-block-group creapub-valeurs-grid"><!-- wp:group {"className":"creapub-valeur gsap-slide-up","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-valeur gsap-slide-up"><!-- wp:paragraph {"align":"center","className":"creapub-valeur__title creapub-ico-users"} -->
<p class="has-text-align-center creapub-valeur__title creapub-ico-users">L'humain avant tout</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"creapub-valeur__text"} -->
<p class="has-text-align-center creapub-valeur__text">Chaque projet repose sur la confiance, l’écoute et la compréhension de vos besoins.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-valeur gsap-slide-up","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-valeur gsap-slide-up"><!-- wp:paragraph {"align":"center","className":"creapub-valeur__title creapub-ico-pin"} -->
<p class="has-text-align-center creapub-valeur__title creapub-ico-pin">La proximité</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"creapub-valeur__text"} -->
<p class="has-text-align-center creapub-valeur__text">Une agence accessible, disponible et réactive, avec des échanges simples et transparents.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-valeur gsap-slide-up","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-valeur gsap-slide-up"><!-- wp:paragraph {"align":"center","className":"creapub-valeur__title creapub-ico-check"} -->
<p class="has-text-align-center creapub-valeur__title creapub-ico-check">L'engagement</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"creapub-valeur__text"} -->
<p class="has-text-align-center creapub-valeur__text">Investis à 100 %, nous vous accompagnons avec des propositions concrètes pour faire évoluer votre communication.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-valeur gsap-slide-up","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-valeur gsap-slide-up"><!-- wp:paragraph {"align":"center","className":"creapub-valeur__title creapub-ico-bulb"} -->
<p class="has-text-align-center creapub-valeur__title creapub-ico-bulb">La créativité utile</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"creapub-valeur__text"} -->
<p class="has-text-align-center creapub-valeur__text">Des idées pensées pour être belles et pour avoir un impact réel sur votre activité.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"creapub-section creapub-histoire","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull creapub-section creapub-histoire"><!-- wp:heading {"textAlign":"center","className":"gsap-slide-up"} -->
<h2 class="wp-block-heading has-text-align-center gsap-slide-up">Notre <strong>histoire</strong></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"creapub-timeline","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-timeline"><!-- wp:group {"className":"creapub-tl-item gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-right"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2014</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Création de l'agence en solo dans un local de 40 m², spécialisée dans le print.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-left","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-left"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2015</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Arrivée du premier apprenti, Mathieu, toujours présent à ce jour.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-right"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2017</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Développement de notre activité vers la conception et la pose d'adhésif sur véhicule.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-left","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-left"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2021</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Leslie, graphiste, rejoint l'équipe.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-right"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2022</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Première machine textile.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-left","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-left"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2023</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Baptiste renforce l'équipe commerciale et installation dans un local de 100 m² au bord de Seine.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-right"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2024</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Déménagement dans un local de 200 m² et arrivée de la première machine d'impression.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-left","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-left"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2025</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Renfort d’un nouveau graphiste : Adrien.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"creapub-tl-item gsap-slide-right","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-item gsap-slide-right"><!-- wp:group {"className":"creapub-tl-content","layout":{"type":"default"}} -->
<div class="wp-block-group creapub-tl-content"><!-- wp:paragraph {"className":"creapub-tl-year"} -->
<p class="creapub-tl-year">2026</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"creapub-tl-desc"} -->
<p class="creapub-tl-desc">Merci à toute l'équipe d'être présente à mes côtés et de vivre ensemble le développement de l'agence.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"g2rd-creapub/bande-cta"} /-->
