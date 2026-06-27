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
<!-- wp:group {"align":"full","backgroundColor":"primary","textColor":"white","className":"is-style-section-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-dark has-primary-background-color has-white-color has-background has-text-color" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained","contentSize":"680px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- Pill eyebrow -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
		<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">●</mark>&nbsp; Ce qu'ils disent</p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 2.8rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.9rem, 3vw, 2.8rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Ils nous ont fait <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">confiance</mark></h2>
		<!-- /wp:heading -->

	</div>
	<!-- /wp:group -->

	<!-- wp:g2rd/carousel {"postType":"post","slidesPerView":"3","autoplay":true,"loop":true,"effect":"slide","showCaptions":true,"postIds":[]} /-->

	<!-- Fallback si pas encore de posts configurés : 3 cards témoignages statiques -->
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|l"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:var(--wp--custom--radius--l);background-color:color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent);padding-top:var(--wp--preset--spacing--m);padding-bottom:var(--wp--preset--spacing--m);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent);font-size:3rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">G2RD a complètement transformé notre présence en ligne. Notre site génère 3× plus de contacts qu'avant.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Marie Dupont — Atelier Dupont</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|l"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:var(--wp--custom--radius--l);background-color:color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent);padding-top:var(--wp--preset--spacing--m);padding-bottom:var(--wp--preset--spacing--m);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent);font-size:3rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Équipe à l'écoute, livrables de qualité et respect des délais. Notre boutique WooCommerce tourne à merveille.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Thomas Bernard — TechStore Pro</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|l"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:var(--wp--custom--radius--l);background-color:color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent);padding-top:var(--wp--preset--spacing--m);padding-bottom:var(--wp--preset--spacing--m);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--secondary) 55%, transparent);font-size:3rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Un accompagnement personnalisé du début à la fin. G2RD a su comprendre nos enjeux et livrer un site parfait.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p class="has-secondary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-weight:700;font-size:0.95rem">Sophie Martin — Cabinet Martin</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
