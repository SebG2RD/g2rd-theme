<?php
/**
 * Title: Section Magic — Claire
 * Slug: g2rd-theme/section-magic-light
 * Description: Section fond clair avec dégradé crème et touches dorées — idéale pour les grilles de contenu, équipes ou portfolios
 * Categories: featured, text
 * Keywords: section, claire, light, magic, g2rd, titre, heading
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","className":"g2rd-magic-section is-style-magic-light","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","left":"var:preset|spacing|s","right":"var:preset|spacing|s"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull g2rd-magic-section is-style-magic-light" style="padding-top:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--s);padding-bottom:var(--wp--preset--spacing--xl);padding-left:var(--wp--preset--spacing--s)">

	<!-- wp:group {"className":"g2rd-magic-heading","layout":{"type":"constrained"}} -->
	<div class="wp-block-group g2rd-magic-heading">

		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.82rem","fontWeight":"800","letterSpacing":"0.08em","textTransform":"uppercase"}}} -->
		<p class="has-text-align-center" style="font-size:0.82rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase">Catégorie</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"800"}}} -->
		<h2 class="wp-block-heading has-text-align-center" style="font-weight:800">Titre de la section</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">Description de la section — remplacez ce texte par votre contenu.</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"placeholder":"Ajoutez vos blocs ici (grille, filterable-grid, query…)"} -->
	<p></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)"><!-- wp:button {"className":"is-style-neomorphic"} -->
	<div class="wp-block-button is-style-neomorphic"><a class="wp-block-button__link wp-element-button" href="#">En savoir plus</a></div>
	<!-- /wp:button --></div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
