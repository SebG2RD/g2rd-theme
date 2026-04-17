<?php
/**
 * Title: Section Témoignages Carousel (blocs G2RD)
 * Slug: g2rd-theme/section-temoignages-carousel
 * Description: Témoignages clients en carousel Swiper avec le bloc g2rd/carousel — autoplay, effet coverflow
 * Categories: testimonials, featured
 * Keywords: témoignages, avis, carousel, slider, g2rd, swiper
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/carousel
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- wp:paragraph {"align":"center","textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
		<p class="has-text-align-center has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Ce qu'ils disent</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 3rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.8rem, 3vw, 3rem);font-weight:800;line-height:1.2">Ils nous ont fait <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">confiance</mark></h2>
		<!-- /wp:heading -->

	</div>
	<!-- /wp:group -->

	<!-- wp:g2rd/carousel {"postType":"post","slidesPerView":"3","autoplay":true,"loop":true,"effect":"slide","showCaptions":true,"postIds":[]} /-->

	<!-- Fallback si pas encore de posts configurés : 3 cards témoignages statiques -->
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"style":{"color":{"background":"rgba(250,250,250,0.08)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;padding:var(--wp--preset--spacing--m);background-color:rgba(250,250,250,0.08)">
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.5rem","lineHeight":"0.8","fontWeight":"800"}}} -->
				<p class="has-secondary-color has-text-color" style="font-size:2.5rem;line-height:0.8;font-weight:800">"</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
				<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">G2RD a complètement transformé notre présence en ligne. Notre site génère 3x plus de contacts qu'avant.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Marie Dupont — Atelier Dupont</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"style":{"color":{"background":"rgba(250,250,250,0.08)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;padding:var(--wp--preset--spacing--m);background-color:rgba(250,250,250,0.08)">
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.5rem","lineHeight":"0.8","fontWeight":"800"}}} -->
				<p class="has-secondary-color has-text-color" style="font-size:2.5rem;line-height:0.8;font-weight:800">"</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
				<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Équipe à l'écoute, livrables de qualité et respect des délais. Notre boutique WooCommerce tourne à merveille.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Thomas Bernard — TechStore Pro</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"style":{"color":{"background":"rgba(250,250,250,0.08)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;padding:var(--wp--preset--spacing--m);background-color:rgba(250,250,250,0.08)">
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontSize":"2.5rem","lineHeight":"0.8","fontWeight":"800"}}} -->
				<p class="has-secondary-color has-text-color" style="font-size:2.5rem;line-height:0.8;font-weight:800">"</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
				<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Un accompagnement personnalisé du début à la fin. G2RD a su comprendre nos enjeux et livrer un site parfait.</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Sophie Martin — Cabinet Martin</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
