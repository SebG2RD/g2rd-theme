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

			<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
			<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Agence web WordPress</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"lineHeight":"1.1","fontWeight":"800","fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(2.2rem, 4.5vw, 3.8rem);line-height:1.1;font-weight:800">Votre agence web pour</h1>
			<!-- /wp:heading -->

			<!-- wp:g2rd/typed {"strings":["créer votre site vitrine","booster votre SEO","lancer votre boutique","développer votre marque"],"typeSpeed":60,"backSpeed":40,"loop":true,"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|s"}},"typography":{"fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)","fontWeight":"800","color":"#D4A373"}}} /-->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">De la conception à la mise en ligne, G2RD conçoit des sites WordPress performants qui génèrent de vrais résultats.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

				<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700"},"border":{"radius":"4px"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700">Démarrer mon projet</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"rgba(250,250,250,0.6)","radius":"4px","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:rgba(250,250,250,0.6)">Voir nos réalisations</a></div>
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
				<div class="wp-block-column">
					<!-- wp:g2rd/counter {"number":150,"suffix":"+","label":"Sites livrés","numberColor":"#D4A373","labelColor":"#FAFAFA","numberSize":"2.2rem","labelSize":"0.9rem","animationDuration":2000} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:g2rd/counter {"number":5,"suffix":" ans","label":"D'expérience","numberColor":"#D4A373","labelColor":"#FAFAFA","numberSize":"2.2rem","labelSize":"0.9rem","animationDuration":1500} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column -->
				<div class="wp-block-column">
					<!-- wp:g2rd/counter {"number":98,"suffix":"%","label":"Clients satisfaits","numberColor":"#D4A373","labelColor":"#FAFAFA","numberSize":"2.2rem","labelSize":"0.9rem","animationDuration":2500} /-->
				</div>
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
