<?php
/**
 * Title: Section CTA Urgence (blocs G2RD)
 * Slug: g2rd-theme/section-cta-countdown
 * Description: CTA avec compte à rebours g2rd/countdown — crée l'urgence et maximise la conversion
 * Categories: call-to-action, featured
 * Keywords: cta, countdown, compte à rebours, urgence, conversion, g2rd
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/countdown
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"layout":{"selfStretch":"fixed","flexSize":"780px"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">

		<!-- Pill eyebrow -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"white","fontSize":"s"} -->
		<p class="has-white-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 40%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 12%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase">⏰&nbsp; Offre à durée limitée</p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 3rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.8rem, 3vw, 3rem);font-weight:800;line-height:1.2">-20% sur votre site web<br><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">avant la fin de ce mois</mark></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"lineHeight":"1.75"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);line-height:1.75">Places limitées — Nous n'acceptons que 3 nouveaux projets par mois pour garantir notre qualité.</p>
		<!-- /wp:paragraph -->

		<!-- Compte à rebours G2RD -->
		<!-- wp:g2rd/countdown {"endDate":"2025-12-31T23:59:59","title":"L'offre expire dans :","showDays":true,"showHours":true,"showMinutes":true,"showSeconds":true,"numberColor":"var(--wp--preset--color--secondary)","labelColor":"var(--wp--preset--color--white)","separatorColor":"color-mix(in srgb, var(--wp--preset--color--white) 30%, transparent)","style":{"spacing":{"margin":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m"}}}} /-->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">

			<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"1.1rem"},"border":{"radius":"4px"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2.5rem","left":"2.5rem"}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700;font-size:1.1rem;padding:1rem 2.5rem">Je profite de l'offre →</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 50%, transparent)","radius":"4px","width":"1px"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","right":"2rem","left":"2rem"}}}} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:color-mix(in srgb, var(--wp--preset--color--white) 50%, transparent);padding:1rem 2rem">📞 Parler à un expert</a></div>
			<!-- /wp:button -->

		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"fontSize":"0.85rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:0.85rem;opacity:0.65">Devis gratuit · Réponse sous 24h · Sans engagement</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
