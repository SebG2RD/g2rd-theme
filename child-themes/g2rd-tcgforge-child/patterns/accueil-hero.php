<?php
/**
 * Title: Accueil — Hero
 * Slug: tcgforge/accueil-hero
 * Categories: tcgforge
 * Description: Titre de la boutique, recherche produit et accès aux nouveautés. Décor en CSS, sans visuel de licence.
 *
 * @package G2RD_TCGForge
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"tcgf-section tcgf-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull tcgf-section tcgf-hero"><!-- wp:columns {"align":"wide","className":"tcgf-hero__grid"} -->
<div class="wp-block-columns alignwide tcgf-hero__grid"><!-- wp:column {"width":"58%","className":"tcgf-hero__content"} -->
<div class="wp-block-column tcgf-hero__content" style="flex-basis:58%"><!-- wp:heading {"level":1,"className":"tcgf-hero__title"} -->
<h1 class="wp-block-heading tcgf-hero__title">Cartes à collectionner, accessoires et objets de collection</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tcgf-lead"} -->
<p class="tcgf-lead">Boosters, displays, coffrets, protections, figurines et produits dérivés. Pokémon, Yu-Gi-Oh!, One Piece Card Game, Magic: The Gathering, Disney Lorcana et d'autres jeux, réunis dans une boutique française pensée pour les joueurs et les collectionneurs.</p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"Rechercher un produit","showLabel":false,"placeholder":"Nom d'une carte, d'une extension, d'un jeu","buttonText":"Rechercher","query":{"post_type":"product"},"className":"tcgf-hero__search"} /-->

<!-- wp:buttons {"className":"tcgf-hero__actions"} -->
<div class="wp-block-buttons tcgf-hero__actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/nouveautes/">Voir les nouveautés</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"tcgf-btn-ghost"} -->
<div class="wp-block-button tcgf-btn-ghost"><a class="wp-block-button__link wp-element-button" href="/boutique/">Parcourir la boutique</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"tcgf-hero__visual"} -->
<div class="wp-block-column tcgf-hero__visual"><!-- wp:group {"className":"tcgf-fan","layout":{"type":"default"}} -->
<div class="wp-block-group tcgf-fan"></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
