<?php
/**
 * Title: Section Hero Animé (blocs G2RD)
 * Slug: g2rd-theme/section-hero-animated
 * Description: Hero avec effet particules g2rd, animation typed et compteurs animés — expérience visuelle premium
 * Categories: banner, featured
 * Keywords: hero, particules, typed, counter, animé, g2rd
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/typed, g2rd/counter
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","data-particles":"true","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"},"className":"g2rd-hero-particles"} -->
<div class="wp-block-group alignfull has-primary-background-color has-background g2rd-hero-particles" data-particles="true" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">

		<!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">

			<!-- Pill eyebrow -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"left"}} -->
			<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
			<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">●</mark>&nbsp; Agence web WordPress</p>
			<!-- /wp:paragraph --></div>
			<!-- /wp:group -->

			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"lineHeight":"1.1","fontWeight":"800","fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(2.2rem, 4.5vw, 3.8rem);line-height:1.1;font-weight:800">Votre agence web pour</h1>
			<!-- /wp:heading -->

			<!-- wp:g2rd/typed {"strings":["créer votre site vitrine","booster votre SEO","lancer votre boutique","développer votre marque"],"typeSpeed":60,"backSpeed":40,"loop":true,"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|s"}},"typography":{"fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)","fontWeight":"800","color":"var(--wp--preset--color--secondary)"}}} /-->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">De la conception à la mise en ligne, G2RD conçoit des sites WordPress performants qui génèrent de vrais résultats.</p>
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

			<!-- wp:separator {"backgroundColor":"secondary","style":{"spacing":{"margin":{"top":"var:preset|spacing|l","bottom":"var:preset|spacing|m"}}}} -->
			<hr class="wp-block-separator has-text-color has-secondary-color has-secondary-background-color has-background" style="margin-top:var(--wp--preset--spacing--l);margin-bottom:var(--wp--preset--spacing--m)" />
			<!-- /wp:separator -->

			<!-- Compteurs animés G2RD -->
			<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
			<div class="wp-block-columns">

				<!-- wp:column -->
				<div class="wp-block-column"><!-- wp:g2rd/counter {"endingNumber":150,"numberSuffix":"+","enableIcon":false,"numberColor":"var(--wp--preset--color--secondary)","title":"Sites livrés","titleColor":"var(--wp--preset--color--white)","numberFontSize":"2.2rem","titleFontSize":"0.9rem"} -->
				<div style="text-align:center;margin:0px 0px 0px 0px" class="wp-block-g2rd-counter g2rd-counter layout-number icon-top" data-start="0" data-end="150" data-decimals="0" data-prefix="" data-suffix="+" data-duration="2000" data-thousands="comma"><div class="counter-content"><div class="counter-number-wrapper"><span class="counter-number" style="color:var(--wp--preset--color--secondary);font-size:2.2rem">0</span><span class="counter-suffix" style="margin-left:0px;color:var(--wp--preset--color--secondary);font-size:2.2rem">+</span></div><h3 class="counter-title" style="color:var(--wp--preset--color--white);font-size:0.9rem">Sites livrés</h3></div></div>
				<!-- /wp:g2rd/counter --></div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column"><!-- wp:g2rd/counter {"endingNumber":5,"numberSuffix":"ans","animationDuration":1500,"enableIcon":false,"numberColor":"var(--wp--preset--color--secondary)","title":"D'expérience","titleColor":"var(--wp--preset--color--white)","numberFontSize":"2.2rem","titleFontSize":"0.9rem"} -->
				<div style="text-align:center;margin:0px 0px 0px 0px" class="wp-block-g2rd-counter g2rd-counter layout-number icon-top" data-start="0" data-end="5" data-decimals="0" data-prefix="" data-suffix="ans" data-duration="1500" data-thousands="comma"><div class="counter-content"><div class="counter-number-wrapper"><span class="counter-number" style="color:var(--wp--preset--color--secondary);font-size:2.2rem">0</span><span class="counter-suffix" style="margin-left:0px;color:var(--wp--preset--color--secondary);font-size:2.2rem">ans</span></div><h3 class="counter-title" style="color:var(--wp--preset--color--white);font-size:0.9rem">D'expérience</h3></div></div>
				<!-- /wp:g2rd/counter --></div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column"><!-- wp:g2rd/counter {"endingNumber":98,"animationDuration":2500,"enableIcon":false,"numberColor":"var(--wp--preset--color--secondary)","title":"Clients satisfaits","titleColor":"var(--wp--preset--color--white)","numberFontSize":"2.2rem","titleFontSize":"0.9rem"} -->
				<div style="text-align:center;margin:0px 0px 0px 0px" class="wp-block-g2rd-counter g2rd-counter layout-number icon-top" data-start="0" data-end="98" data-decimals="0" data-prefix="" data-suffix="%" data-duration="2500" data-thousands="comma"><div class="counter-content"><div class="counter-number-wrapper"><span class="counter-number" style="color:var(--wp--preset--color--secondary);font-size:2.2rem">0</span><span class="counter-suffix" style="margin-left:0px;color:var(--wp--preset--color--secondary);font-size:2.2rem">%</span></div><h3 class="counter-title" style="color:var(--wp--preset--color--white);font-size:0.9rem">Clients satisfaits</h3></div></div>
				<!-- /wp:g2rd/counter --></div>
				<!-- /wp:column -->

			</div>
			<!-- /wp:columns -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">

			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"scroll-animation","style":{"border":{"radius":"1rem"}}} -->
			<figure class="wp-block-image size-large scroll-animation" style="border-radius:1rem"><img alt="G2RD Agence Web" /></figure>
			<!-- /wp:image -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
