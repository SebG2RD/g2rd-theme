<?php
/**
 * Title: Section Témoignages
 * Slug: g2rd-theme/section-temoignages
 * Description: Preuve sociale — 3 témoignages clients en cartes (is-style-card), citation, auteur et rôle. Façon wp-manager.
 * Categories: testimonials, featured
 * Keywords: témoignages, avis, clients, preuve sociale, reviews
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"cream","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-cream-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained","contentSize":"680px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- Pill eyebrow -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"primary","fontSize":"s"} -->
			<p class="has-primary-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">●</mark>&nbsp; Ce qu'ils disent</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 2.8rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.9rem, 3vw, 2.8rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Ils nous ont fait <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">confiance</mark></h2>
		<!-- /wp:heading -->

	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3.5rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent);font-size:3.5rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->

				<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">G2RD a complètement transformé notre présence en ligne. Notre site est désormais rapide, moderne et génère 3× plus de contacts qu'avant. Un investissement qui a vraiment payé.</p><!-- /wp:paragraph -->

				<!-- wp:separator {"backgroundColor":"secondary","className":"is-style-wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|s","bottom":"var:preset|spacing|s"}}}} --><hr class="wp-block-separator has-text-color has-secondary-color has-alpha-channel-opacity has-secondary-background-color has-background is-style-wide" style="margin-top:var(--wp--preset--spacing--s);margin-bottom:var(--wp--preset--spacing--s)" /><!-- /wp:separator -->

				<!-- wp:columns {"isStackedOnMobile":false,"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|xs"}}}} -->
				<div class="wp-block-columns are-vertically-aligned-center is-not-stacked-on-mobile">
					<!-- wp:column {"verticalAlignment":"center","width":"52px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52px">
						<!-- wp:image {"width":"52px","height":"52px","sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"radius":"999px"}}} --><figure class="wp-block-image size-thumbnail is-resized" style="border-radius:999px"><img style="width:52px;height:52px" alt="Photo de Marie Dupont" /></figure><!-- /wp:image -->
					</div>
					<!-- /wp:column -->
					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center">
						<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.95rem">Marie Dupont</p><!-- /wp:paragraph -->
						<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.85rem"}}} --><p class="has-muted-color has-text-color" style="font-size:0.85rem">Directrice — Atelier Dupont</p><!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3.5rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent);font-size:3.5rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->

				<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Équipe à l'écoute, livrables de qualité et respect des délais. Notre boutique en ligne WooCommerce tourne à merveille. Je recommande G2RD sans hésitation.</p><!-- /wp:paragraph -->

				<!-- wp:separator {"backgroundColor":"secondary","className":"is-style-wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|s","bottom":"var:preset|spacing|s"}}}} --><hr class="wp-block-separator has-text-color has-secondary-color has-alpha-channel-opacity has-secondary-background-color has-background is-style-wide" style="margin-top:var(--wp--preset--spacing--s);margin-bottom:var(--wp--preset--spacing--s)" /><!-- /wp:separator -->

				<!-- wp:columns {"isStackedOnMobile":false,"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|xs"}}}} -->
				<div class="wp-block-columns are-vertically-aligned-center is-not-stacked-on-mobile">
					<!-- wp:column {"verticalAlignment":"center","width":"52px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52px">
						<!-- wp:image {"width":"52px","height":"52px","sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"radius":"999px"}}} --><figure class="wp-block-image size-thumbnail is-resized" style="border-radius:999px"><img style="width:52px;height:52px" alt="Photo de Thomas Bernard" /></figure><!-- /wp:image -->
					</div>
					<!-- /wp:column -->
					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center">
						<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.95rem">Thomas Bernard</p><!-- /wp:paragraph -->
						<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.85rem"}}} --><p class="has-muted-color has-text-color" style="font-size:0.85rem">Fondateur — TechStore Pro</p><!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"3.5rem","lineHeight":"0.7","fontWeight":"800"},"color":{"text":"color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent)"}}} --><p style="color:color-mix(in srgb, var(--wp--preset--color--accent) 28%, transparent);font-size:3.5rem;line-height:0.7;font-weight:800" aria-hidden="true">&ldquo;</p><!-- /wp:paragraph -->

				<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"lineHeight":"1.7","fontStyle":"italic"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.7;font-style:italic">Un accompagnement personnalisé du début à la fin. G2RD a compris nos enjeux et livré un site qui reflète parfaitement l'image de notre cabinet.</p><!-- /wp:paragraph -->

				<!-- wp:separator {"backgroundColor":"secondary","className":"is-style-wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|s","bottom":"var:preset|spacing|s"}}}} --><hr class="wp-block-separator has-text-color has-secondary-color has-alpha-channel-opacity has-secondary-background-color has-background is-style-wide" style="margin-top:var(--wp--preset--spacing--s);margin-bottom:var(--wp--preset--spacing--s)" /><!-- /wp:separator -->

				<!-- wp:columns {"isStackedOnMobile":false,"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|xs"}}}} -->
				<div class="wp-block-columns are-vertically-aligned-center is-not-stacked-on-mobile">
					<!-- wp:column {"verticalAlignment":"center","width":"52px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:52px">
						<!-- wp:image {"width":"52px","height":"52px","sizeSlug":"thumbnail","linkDestination":"none","style":{"border":{"radius":"999px"}}} --><figure class="wp-block-image size-thumbnail is-resized" style="border-radius:999px"><img style="width:52px;height:52px" alt="Photo de Sophie Martin" /></figure><!-- /wp:image -->
					</div>
					<!-- /wp:column -->
					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center">
						<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.95rem">Sophie Martin</p><!-- /wp:paragraph -->
						<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.85rem"}}} --><p class="has-muted-color has-text-color" style="font-size:0.85rem">Avocate associée — Cabinet Martin</p><!-- /wp:paragraph -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
