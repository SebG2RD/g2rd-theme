<?php
/**
 * Title: Section CTA
 * Slug: g2rd-theme/section-cta
 * Description: Bandeau appel à l'action pleine largeur — titre fort, texte et bouton de conversion
 * Categories: call-to-action, featured
 * Keywords: cta, appel à l'action, conversion, contact, devis
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"layout":{"selfStretch":"fixed","flexSize":"720px"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">

		<!-- wp:paragraph {"align":"center","textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
		<p class="has-text-align-center has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Passons à l'action</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 3rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.8rem, 3vw, 3rem);font-weight:800;line-height:1.2">Prêt à lancer votre projet ?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">Discutons de votre projet lors d'un appel de 30 minutes sans engagement. Nous analyserons vos besoins et vous proposerons la meilleure approche.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

			<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"1.05rem"},"border":{"radius":"4px"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2rem","left":"2rem"}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700;font-size:1.05rem;padding-top:1rem;padding-bottom:1rem;padding-right:2rem;padding-left:2rem">Demander un devis gratuit</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"rgba(250,250,250,0.5)","radius":"4px","width":"1px"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2rem","left":"2rem"}}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:rgba(250,250,250,0.5);padding-top:1rem;padding-bottom:1rem;padding-right:2rem;padding-left:2rem">📞 Nous appeler</a></div>
			<!-- /wp:button -->

		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"fontSize":"0.85rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:0.85rem;opacity:0.7">Réponse garantie sous 24h · Sans engagement · Devis offert</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
