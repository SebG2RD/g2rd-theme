<?php
/**
 * Title: Section Magic — Sombre
 * Slug: g2rd-theme/section-magic-dark
 * Description: Section fond sombre avec dégradé bleu, grille SVG et effet glassmorphism — idéale pour les CTA, stats ou contenus mis en valeur
 * Categories: featured, text
 * Keywords: section, sombre, dark, magic, g2rd, titre, heading
 * Viewport Width: 1400
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","className":"g2rd-magic-section is-style-magic-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","left":"var:preset|spacing|s","right":"var:preset|spacing|s"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull g2rd-magic-section is-style-magic-dark" style="padding-top:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--s);padding-bottom:var(--wp--preset--spacing--xl);padding-left:var(--wp--preset--spacing--s)">

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

	<!-- wp:paragraph {"placeholder":"Ajoutez vos blocs ici (compteurs, cartes, témoignages…)"} -->
	<p></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)"><!-- wp:button {"className":"is-style-soft-pressed"} -->
	<div class="wp-block-button is-style-soft-pressed"><a class="wp-block-button__link wp-element-button" href="#">Appel à l'action</a></div>
	<!-- /wp:button --></div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
