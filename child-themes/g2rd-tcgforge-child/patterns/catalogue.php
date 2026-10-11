<?php
/**
 * Title: Catalogue — Liste de produits filtrable
 * Slug: tcgforge/catalogue
 * Categories: tcgforge-boutique
 * Description: Corps des pages boutique, catégorie, étiquette et attribut : fil d'Ariane, filtres (jeu, extension, langue, type, prix, disponibilité), tri, grille et pagination.
 * Inserter: no
 *
 * @package G2RD_TCGForge
 */
?>
<!-- wp:group {"align":"full","className":"tcgf-section tcgf-catalog","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tcgf-section tcgf-catalog"><!-- wp:group {"align":"wide","className":"tcgf-catalog__head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide tcgf-catalog__head"><!-- wp:woocommerce/breadcrumbs /-->

<!-- wp:query-title {"type":"archive","showPrefix":false,"level":1} /-->

<!-- wp:term-description /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/store-notices {"align":"wide"} /-->

<!-- wp:columns {"align":"wide","className":"tcgf-catalog__grid"} -->
<div class="wp-block-columns alignwide tcgf-catalog__grid"><!-- wp:column {"width":"260px","className":"tcgf-catalog__aside"} -->
<div class="wp-block-column tcgf-catalog__aside" style="flex-basis:260px"><!-- wp:woocommerce/product-filters {"overlayMode":"mobile","className":"tcgf-filters"} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters tcgf-filters"><!-- wp:heading {"level":2,"className":"tcgf-filters__title"} -->
<h2 class="wp-block-heading tcgf-filters__title">Filtrer</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips --></div>
<!-- /wp:woocommerce/product-filter-active -->

<!-- wp:woocommerce/product-filter-attribute {"className":"tcgf-filter\u002d\u002djeu"} -->
<div class="wp-block-woocommerce-product-filter-attribute tcgf-filter--jeu"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Jeu</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute -->

<!-- wp:woocommerce/product-filter-attribute {"className":"tcgf-filter\u002d\u002dextension"} -->
<div class="wp-block-woocommerce-product-filter-attribute tcgf-filter--extension"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Extension</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute -->

<!-- wp:woocommerce/product-filter-attribute {"className":"tcgf-filter\u002d\u002dlangue"} -->
<div class="wp-block-woocommerce-product-filter-attribute tcgf-filter--langue"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Langue</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute -->

<!-- wp:woocommerce/product-filter-attribute {"className":"tcgf-filter\u002d\u002dtype-de-produit"} -->
<div class="wp-block-woocommerce-product-filter-attribute tcgf-filter--type-de-produit"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Type de produit</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-attribute -->

<!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Prix</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider --></div>
<!-- /wp:woocommerce/product-filter-price -->

<!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"0.625rem","top":"0"}}}} -->
<h3 class="wp-block-heading" style="margin-top:0;margin-bottom:0.625rem">Disponibilité</h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list --></div>
<!-- /wp:woocommerce/product-filter-status --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"tcgf-catalog__results"} -->
<div class="wp-block-column tcgf-catalog__results"><!-- wp:group {"className":"tcgf-catalog__toolbar","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group tcgf-catalog__toolbar"><!-- wp:woocommerce/product-results-count /-->

<!-- wp:woocommerce/catalog-sorting /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":24,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"convertedFromProducts":false,"queryContextIncludes":["collection"],"className":"tcgf-collection"} -->
<div class="wp-block-woocommerce-product-collection tcgf-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail"} /-->

<!-- wp:tcgforge/product-badges /-->

<!-- wp:tcgforge/product-facts {"only":["jeu","extension","langue"]} /-->

<!-- wp:post-title {"level":2,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price /-->

<!-- wp:tcgforge/product-release {"compact":true} /-->

<!-- wp:woocommerce/product-button {"textAlign":"left"} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"className":"tcgf-empty","layout":{"type":"default"}} -->
<div class="wp-block-group tcgf-empty"><!-- wp:heading {"className":"tcgf-empty__title"} -->
<h2 class="wp-block-heading tcgf-empty__title">Aucun produit ne correspond</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tcgf-empty__text"} -->
<p class="tcgf-empty__text">Retirez un filtre, changez de rayon ou lancez une recherche par nom de carte, d'extension ou de jeu.</p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"Rechercher un produit","showLabel":false,"placeholder":"Rechercher un produit","buttonText":"Rechercher","query":{"post_type":"product"}} /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"tcgf-btn-ghost"} -->
<div class="wp-block-button tcgf-btn-ghost"><a class="wp-block-button__link wp-element-button" href="/boutique/">Voir toute la boutique</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
