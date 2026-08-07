<?php
/**
 * Title: Section Hero
 * Slug: g2rd-theme/section-hero
 * Description: Hero sombre façon SaaS — pill, titre avec mot surligné, double CTA, stats et visuel. Structure inspirée de wp-manager.
 * Categories: banner, featured
 * Keywords: hero, banner, cta, accueil, conversion, dashboard
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","textColor":"white","className":"is-style-section-dark g2rd-globe-bg","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-dark g2rd-globe-bg has-primary-background-color has-white-color has-background has-text-color" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">

		<!-- wp:column {"verticalAlignment":"center","width":"56%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:56%">

			<!-- Pill eyebrow -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
			<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">●</mark>&nbsp; Agence web WordPress</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"lineHeight":"1.08","fontWeight":"800","fontSize":"clamp(2.4rem, 5vw, 4rem)","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(2.4rem, 5vw, 4rem);font-weight:800;letter-spacing:-0.02em;line-height:1.08">Votre site web,<br>conçu pour <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">convertir.</mark></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.15rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.15rem;line-height:1.75">De la conception de votre identité visuelle à la réalisation de votre site WordPress sur mesure. Une expertise de plus de 5 ans au service de votre croissance.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

				<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="font-weight:700">Démarrer mon projet →</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 35%, transparent)","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-color:color-mix(in srgb, var(--wp--preset--color--white) 35%, transparent);border-width:1px">Voir nos réalisations</a></div>
				<!-- /wp:button -->

			</div>
			<!-- /wp:buttons -->

			<!-- wp:separator {"backgroundColor":"blue-soft","style":{"spacing":{"margin":{"top":"var:preset|spacing|l","bottom":"var:preset|spacing|m"}}}} -->
			<hr class="wp-block-separator has-text-color has-blue-soft-color has-alpha-channel-opacity has-blue-soft-background-color has-background" style="margin-top:var(--wp--preset--spacing--l);margin-bottom:var(--wp--preset--spacing--m)" />
			<!-- /wp:separator -->

			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
			<div class="wp-block-columns">

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.4rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-secondary-color has-text-color" style="font-size:2.4rem;font-weight:800;line-height:1;margin-top:0;margin-bottom:0">150+</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"fontWeight":"600","letterSpacing":"0.08em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0.25rem"}}}} --><p class="has-white-color has-text-color has-s-font-size" style="margin-top:0.25rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Sites livrés</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.4rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-secondary-color has-text-color" style="font-size:2.4rem;font-weight:800;line-height:1;margin-top:0;margin-bottom:0">5 ans</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"fontWeight":"600","letterSpacing":"0.08em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0.25rem"}}}} --><p class="has-white-color has-text-color has-s-font-size" style="margin-top:0.25rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">D'expérience</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.4rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-secondary-color has-text-color" style="font-size:2.4rem;font-weight:800;line-height:1;margin-top:0;margin-bottom:0">98%</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"fontWeight":"600","letterSpacing":"0.08em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0.25rem"}}}} --><p class="has-white-color has-text-color has-s-font-size" style="margin-top:0.25rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Clients satisfaits</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

			</div>
			<!-- /wp:columns -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"44%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:44%">

			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","style":{"border":{"radius":"var:custom|radius|l"},"shadow":"var:preset|shadow|magic-xl"}} -->
			<figure class="wp-block-image size-large has-custom-border" style="border-radius:var(--wp--custom--radius--l);box-shadow:var(--wp--preset--shadow--magic-xl)"><img alt="Aperçu d'un site web réalisé par G2RD Agence Web" /></figure>
			<!-- /wp:image -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
