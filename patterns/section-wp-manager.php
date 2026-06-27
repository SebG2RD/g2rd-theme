<?php
/**
 * Title: Section wp-manager (SaaS)
 * Slug: g2rd-theme/section-wp-manager
 * Description: Présentation du SaaS wp-manager.g2rd.fr — section sombre, globe, mock dashboard natif et CTA externe.
 * Categories: call-to-action, featured
 * Keywords: wp-manager, saas, supervision, parc, maintenance, dashboard
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

		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">

			<!-- Pill eyebrow -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"left"}} -->
			<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
			<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">●</mark>&nbsp; Notre plateforme SaaS</p>
			<!-- /wp:paragraph --></div>
			<!-- /wp:group -->

			<!-- wp:heading {"level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.9rem, 3.4vw, 2.9rem)","fontWeight":"800","lineHeight":"1.12","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<h2 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.9rem, 3.4vw, 2.9rem);font-weight:800;letter-spacing:-0.02em;line-height:1.12">Pilotez tout votre parc WordPress avec <mark style="background-color:rgba(0,0,0,0);border-bottom:0.16em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">wp-manager</mark></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">Notre tableau de bord SaaS surveille en temps réel chacun de vos sites WordPress : mises à jour, failles de sécurité, sauvegardes et performances, centralisés au même endroit.</p>
			<!-- /wp:paragraph -->

			<!-- Checklist -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xs","margin":{"top":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--m)">
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.5"}}} --><p class="has-white-color has-text-color" style="line-height:1.5"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">✓</mark>&nbsp; Suivi des mises à jour cœur, thèmes et extensions</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.5"}}} --><p class="has-white-color has-text-color" style="line-height:1.5"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">✓</mark>&nbsp; Alertes de failles de sécurité (CVE) en continu</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.5"}}} --><p class="has-white-color has-text-color" style="line-height:1.5"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">✓</mark>&nbsp; Sauvegardes automatiques et restauration en 1 clic</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.5"}}} --><p class="has-white-color has-text-color" style="line-height:1.5"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">✓</mark>&nbsp; Rapports de performance et Core Web Vitals</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
				<!-- wp:button {"className":"is-style-action","style":{"typography":{"fontWeight":"700"}}} -->
				<div class="wp-block-button is-style-action"><a class="wp-block-button__link wp-element-button" href="https://wp-manager.g2rd.fr/" target="_blank" rel="noreferrer noopener" style="font-weight:700">Découvrir wp-manager →</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 40%, transparent)","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" href="https://wp-manager.g2rd.fr/" target="_blank" rel="noreferrer noopener" style="border-color:color-mix(in srgb, var(--wp--preset--color--white) 40%, transparent);border-width:1px">Voir la démo</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">

			<!-- Mock dashboard -->
			<!-- wp:group {"className":"is-style-card","style":{"spacing":{"blockGap":"var:preset|spacing|s"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">

				<!-- barre URL -->
				<!-- wp:paragraph {"style":{"typography":{"fontFamily":"ui-monospace, SFMono-Regular, Menlo, Consolas, monospace","fontSize":"0.8rem"},"color":{"background":"var:preset|color|surface"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.35rem","bottom":"0.35rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"muted"} --><p class="has-muted-color has-text-color has-background" style="border-radius:999px;background-color:var(--wp--preset--color--surface);padding-top:0.35rem;padding-right:0.9rem;padding-bottom:0.35rem;padding-left:0.9rem;font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;font-size:0.8rem">wp-manager.g2rd.fr/dashboard</p><!-- /wp:paragraph -->

				<!-- titre + badge temps réel -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xs"},"margin":{"top":"var:preset|spacing|xs"}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} -->
				<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xs)"><!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"}}} --><h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.1rem;font-weight:700">État du parc</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontSize":"0.72rem","fontWeight":"700","letterSpacing":"0.04em","textTransform":"uppercase"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.25rem","bottom":"0.25rem","left":"0.7rem","right":"0.7rem"}}}} --><p class="has-primary-color has-secondary-background-color has-text-color has-background" style="border-radius:999px;padding-top:0.25rem;padding-right:0.7rem;padding-bottom:0.25rem;padding-left:0.7rem;font-size:0.72rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase">Temps réel</p><!-- /wp:paragraph --></div>
				<!-- /wp:group -->

				<!-- 3 tuiles stats -->
				<!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|xs"}}}} -->
				<div class="wp-block-columns is-not-stacked-on-mobile">
					<!-- wp:column {"style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs","left":"var:preset|spacing|xs","right":"var:preset|spacing|xs"}}}} -->
					<div class="wp-block-column has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:var(--wp--custom--radius--m);padding-top:var(--wp--preset--spacing--xs);padding-right:var(--wp--preset--spacing--xs);padding-bottom:var(--wp--preset--spacing--xs);padding-left:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"1.6rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:1.6rem;font-weight:800;line-height:1">12</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.72rem"},"spacing":{"margin":{"top":"0.2rem","bottom":"0"}}}} --><p class="has-muted-color has-text-color" style="margin-top:0.2rem;margin-bottom:0;font-size:0.72rem">Sites suivis</p><!-- /wp:paragraph --></div>
					<!-- /wp:column -->
					<!-- wp:column {"style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs","left":"var:preset|spacing|xs","right":"var:preset|spacing|xs"}}}} -->
					<div class="wp-block-column has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:var(--wp--custom--radius--m);padding-top:var(--wp--preset--spacing--xs);padding-right:var(--wp--preset--spacing--xs);padding-bottom:var(--wp--preset--spacing--xs);padding-left:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"1.6rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:1.6rem;font-weight:800;line-height:1">4</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.72rem"},"spacing":{"margin":{"top":"0.2rem","bottom":"0"}}}} --><p class="has-muted-color has-text-color" style="margin-top:0.2rem;margin-bottom:0;font-size:0.72rem">MAJ dispo</p><!-- /wp:paragraph --></div>
					<!-- /wp:column -->
					<!-- wp:column {"style":{"border":{"color":"var:preset|color|border","width":"1px","radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|xs","left":"var:preset|spacing|xs","right":"var:preset|spacing|xs"}}}} -->
					<div class="wp-block-column has-border-color" style="border-color:var(--wp--preset--color--border);border-width:1px;border-radius:var(--wp--custom--radius--m);padding-top:var(--wp--preset--spacing--xs);padding-right:var(--wp--preset--spacing--xs);padding-bottom:var(--wp--preset--spacing--xs);padding-left:var(--wp--preset--spacing--xs)"><!-- wp:paragraph {"textColor":"danger","style":{"typography":{"fontSize":"1.6rem","fontWeight":"800","lineHeight":"1"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-danger-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:1.6rem;font-weight:800;line-height:1">2</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.72rem"},"spacing":{"margin":{"top":"0.2rem","bottom":"0"}}}} --><p class="has-muted-color has-text-color" style="margin-top:0.2rem;margin-bottom:0;font-size:0.72rem">CVE ouvertes</p><!-- /wp:paragraph --></div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->

				<!-- liste des sites -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"var:preset|spacing|xs"}}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--xs)">
					<!-- wp:group {"style":{"spacing":{"padding":{"top":"0.55rem","bottom":"0.55rem"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} --><div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;padding-top:0.55rem;padding-bottom:0.55rem"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"0.9rem","fontWeight":"600"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:0.9rem;font-weight:600">boutique-floralie.fr</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontSize":"0.7rem","fontWeight":"700"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"},"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-secondary-background-color has-text-color has-background" style="border-radius:999px;margin-top:0;margin-bottom:0;padding-top:0.2rem;padding-right:0.6rem;padding-bottom:0.2rem;padding-left:0.6rem;font-size:0.7rem;font-weight:700">MAJ</p><!-- /wp:paragraph --></div><!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"padding":{"top":"0.55rem","bottom":"0.55rem"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} --><div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;padding-top:0.55rem;padding-bottom:0.55rem"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"0.9rem","fontWeight":"600"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:0.9rem;font-weight:600">cabinet-mercier.com</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.7rem","fontWeight":"700"},"color":{"background":"var:preset|color|surface"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"},"margin":{"top":"0","bottom":"0"}}}} --><p class="has-muted-color has-text-color has-background" style="border-radius:999px;background-color:var(--wp--preset--color--surface);margin-top:0;margin-bottom:0;padding-top:0.2rem;padding-right:0.6rem;padding-bottom:0.2rem;padding-left:0.6rem;font-size:0.7rem;font-weight:700"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-success-color">●</mark> OK</p><!-- /wp:paragraph --></div><!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"padding":{"top":"0.55rem","bottom":"0.55rem"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} --><div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;padding-top:0.55rem;padding-bottom:0.55rem"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"0.9rem","fontWeight":"600"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:0.9rem;font-weight:600">atelier-blvd.fr</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontSize":"0.7rem","fontWeight":"700"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"},"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-secondary-background-color has-text-color has-background" style="border-radius:999px;margin-top:0;margin-bottom:0;padding-top:0.2rem;padding-right:0.6rem;padding-bottom:0.2rem;padding-left:0.6rem;font-size:0.7rem;font-weight:700">MAJ</p><!-- /wp:paragraph --></div><!-- /wp:group -->

					<!-- wp:group {"style":{"spacing":{"padding":{"top":"0.55rem","bottom":"0.55rem"}}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"nowrap"}} --><div class="wp-block-group" style="padding-top:0.55rem;padding-bottom:0.55rem"><!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontSize":"0.9rem","fontWeight":"600"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} --><p class="has-primary-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:0.9rem;font-weight:600">le-comptoir-vins.fr</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"fontSize":"0.7rem","fontWeight":"700"},"color":{"background":"var:preset|color|surface"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"},"margin":{"top":"0","bottom":"0"}}}} --><p class="has-muted-color has-text-color has-background" style="border-radius:999px;background-color:var(--wp--preset--color--surface);margin-top:0;margin-bottom:0;padding-top:0.2rem;padding-right:0.6rem;padding-bottom:0.2rem;padding-left:0.6rem;font-size:0.7rem;font-weight:700"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-success-color">●</mark> OK</p><!-- /wp:paragraph --></div><!-- /wp:group -->
				</div>
				<!-- /wp:group -->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
