<?php
/**
 * Title: Accueil — Sélections de produits
 * Slug: tcgforge/selections-produits
 * Categories: tcgforge-boutique
 * Description: Rayons dynamiques de l'accueil. Chaque rayon lit le catalogue WooCommerce et disparaît tant qu'il est vide : aucun produit, prix ou statut n'est simulé.
 *
 * @package G2RD_TCGForge
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"tcgf-section tcgf-shelves","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull tcgf-section tcgf-shelves"><!-- wp:tcgforge/products {"type":"mis-en-avant","title":"Dernières sorties","limit":4,"align":"wide","link":"/nouveautes/"} /-->

<!-- wp:tcgforge/products {"type":"precommandes","title":"Précommandes ouvertes","limit":4,"align":"wide","link":"/precommandes/","className":"tcgf-shelf\u002d\u002dor"} /-->

<!-- wp:tcgforge/products {"type":"nouveautes","title":"Nouveautés","limit":8,"align":"wide","link":"/nouveautes/"} /-->

<!-- wp:tcgforge/products {"type":"meilleures-ventes","title":"Meilleures ventes","limit":4,"align":"wide","link":"/boutique/"} /-->

<!-- wp:tcgforge/products {"type":"categorie","title":"Cartes et produits TCG","limit":8,"align":"wide","link":"/boutique/","category":"pokemon,yu-gi-oh,one-piece,magic,lorcana,autres-tcg"} /-->

<!-- wp:tcgforge/products {"type":"categorie","title":"Accessoires TCG","limit":4,"align":"wide","link":"/accessoires/","category":"accessoires"} /-->

<!-- wp:tcgforge/products {"type":"categorie","title":"Figurines et collectibles","limit":4,"align":"wide","link":"/figurines/","category":"figurines"} /-->

<!-- wp:tcgforge/products {"type":"categorie","title":"Produits dérivés","limit":4,"align":"wide","link":"/produits-derives/","category":"produits-derives"} /--></section>
<!-- /wp:group -->
