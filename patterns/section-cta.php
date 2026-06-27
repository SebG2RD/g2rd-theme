<?php
/**
 * Title: Section CTA
 * Slug: g2rd-theme/section-cta
 * Description: Bandeau appel à l'action sombre — pill, titre fort, bouton dégradé action et bouton fantôme. Façon wp-manager.
 * Categories: call-to-action, featured
 * Keywords: cta, appel à l'action, conversion, contact, devis
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","textColor":"white","className":"is-style-section-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-dark has-primary-background-color has-white-color has-background has-text-color" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"layout":{"type":"constrained","contentSize":"720px"}} -->
	<div class="wp-block-group">

		<!-- Pill eyebrow -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
			<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">●</mark>&nbsp; Passons à l'action</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 2.8rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.9rem, 3vw, 2.8rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Prêt à lancer <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">votre projet</mark> ?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">Discutons de votre projet lors d'un appel de 30 minutes sans engagement. Nous analyserons vos besoins et vous proposerons la meilleure approche.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

			<!-- wp:button {"className":"is-style-action","style":{"typography":{"fontWeight":"700","fontSize":"1.05rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2rem","left":"2rem"}}}} -->
			<div class="wp-block-button is-style-action"><a class="wp-block-button__link wp-element-button" style="font-weight:700;font-size:1.05rem;padding-top:1rem;padding-bottom:1rem;padding-right:2rem;padding-left:2rem">Demander un devis gratuit</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 45%, transparent)","width":"1px"},"typography":{"fontSize":"1.05rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2rem","left":"2rem"}}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-width:1px;border-color:color-mix(in srgb, var(--wp--preset--color--white) 45%, transparent);font-size:1.05rem;padding-top:1rem;padding-bottom:1rem;padding-right:2rem;padding-left:2rem">Nous appeler</a></div>
			<!-- /wp:button -->

		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"fontSize":"0.85rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:0.85rem;opacity:0.75">Réponse garantie sous 24h · Sans engagement · Devis offert</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
