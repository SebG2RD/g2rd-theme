<?php
/**
 * Title: Section Hero
 * Slug: g2rd-theme/section-hero
 * Description: Section hero pleine largeur — titre accrocheur, description, double CTA et indicateurs de confiance
 * Categories: banner, featured
 * Keywords: hero, banner, cta, accueil, conversion
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","textColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-white-color has-background has-text-color" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">

		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">

			<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
			<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Agence web WordPress</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"lineHeight":"1.1","fontWeight":"800","fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(2.2rem, 4.5vw, 3.8rem);line-height:1.1;font-weight:800">Votre site web,<br><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">conçu pour convertir</mark></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">De la conception de votre identité visuelle à la réalisation de votre site WordPress sur mesure. Une expertise de plus de 5 ans au service de votre croissance.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

				<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700"},"border":{"radius":"4px"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700">Démarrer mon projet</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 60%, transparent)","radius":"4px","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:color-mix(in srgb, var(--wp--preset--color--white) 60%, transparent)">Voir nos réalisations</a></div>
				<!-- /wp:button -->

			</div>
			<!-- /wp:buttons -->

			<!-- wp:separator {"opacity":"css","backgroundColor":"secondary","style":{"spacing":{"margin":{"top":"var:preset|spacing|l","bottom":"var:preset|spacing|m"}}}} -->
			<hr class="wp-block-separator has-text-color has-secondary-color has-secondary-background-color has-background has-css-opacity" style="margin-top:var(--wp--preset--spacing--l);margin-bottom:var(--wp--preset--spacing--m)" />
			<!-- /wp:separator -->

			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
			<div class="wp-block-columns">

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:heading {"level":3,"textColor":"secondary","style":{"typography":{"fontSize":"2.2rem","fontWeight":"800","lineHeight":"1"}}} -->
					<h3 class="wp-block-heading has-secondary-color has-text-color" style="font-size:2.2rem;font-weight:800;line-height:1">150+</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"letterSpacing":"0.02em"}}} -->
					<p class="has-white-color has-text-color has-s-font-size" style="letter-spacing:0.02em">Sites livrés</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:heading {"level":3,"textColor":"secondary","style":{"typography":{"fontSize":"2.2rem","fontWeight":"800","lineHeight":"1"}}} -->
					<h3 class="wp-block-heading has-secondary-color has-text-color" style="font-size:2.2rem;font-weight:800;line-height:1">5 ans</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"letterSpacing":"0.02em"}}} -->
					<p class="has-white-color has-text-color has-s-font-size" style="letter-spacing:0.02em">D'expérience</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:heading {"level":3,"textColor":"secondary","style":{"typography":{"fontSize":"2.2rem","fontWeight":"800","lineHeight":"1"}}} -->
					<h3 class="wp-block-heading has-secondary-color has-text-color" style="font-size:2.2rem;font-weight:800;line-height:1">98%</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"typography":{"letterSpacing":"0.02em"}}} -->
					<p class="has-white-color has-text-color has-s-font-size" style="letter-spacing:0.02em">Clients satisfaits</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:column -->

			</div>
			<!-- /wp:columns -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">

			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","style":{"border":{"radius":"1rem"},"filter":{"duotone":"unset"}}} -->
			<figure class="wp-block-image size-large" style="border-radius:1rem"><img alt="G2RD Agence Web — site sur mesure" /></figure>
			<!-- /wp:image -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
