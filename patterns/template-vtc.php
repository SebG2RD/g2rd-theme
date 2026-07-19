<?php
/**
 * Title: Template VTC / Taxi
 * Slug: g2rd-theme/template-vtc
 * Description: Page d'accueil complète pour chauffeur VTC / taxi — hero urgence, services, zone, réservation
 * Categories: featured, banner
 * Keywords: vtc, taxi, chauffeur, transport, réservation
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>

<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">

		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">

			<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
			<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">🚗 Disponible 24h/24 · 7j/7</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)","lineHeight":"1.1","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(2.2rem, 4.5vw, 3.8rem);line-height:1.1;font-weight:800">Votre chauffeur VTC<br><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">en moins de 10 min</mark></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">Transferts aéroport, gare, soirées, mariages. Véhicule haut de gamme, ponctualité garantie. Réservation en ligne ou par téléphone.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
				<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"1.05rem"},"border":{"radius":"4px"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700;font-size:1.05rem">📞 Réserver maintenant</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"color-mix(in srgb, var(--wp--preset--color--white) 60%, transparent)","radius":"4px","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:color-mix(in srgb, var(--wp--preset--color--white) 60%, transparent)">Voir les tarifs</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"padding":{"top":"var:preset|spacing|s","bottom":"var:preset|spacing|s","right":"var:preset|spacing|s","left":"var:preset|spacing|s"},"blockGap":"var:preset|spacing|s"},"border":{"radius":"8px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 30%, transparent)","width":"1px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--m);padding:var(--wp--preset--spacing--s);border-radius:8px;border:1px solid color-mix(in srgb, var(--wp--preset--color--secondary) 30%, transparent)">
				<!-- wp:paragraph {"textColor":"white","fontSize":"s"} --><p class="has-white-color has-text-color has-s-font-size">⭐⭐⭐⭐⭐ <strong>4.9/5</strong> — 320 avis Google</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:group {"style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent)"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:12px;padding:var(--wp--preset--spacing--m);background-color:color-mix(in srgb, var(--wp--preset--color--white) 8%, transparent)">
				<!-- wp:heading {"level":3,"textColor":"secondary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"}}} -->
				<h3 class="wp-block-heading has-secondary-color has-text-color" style="font-size:1.1rem;font-weight:700">Réserver une course</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"white","fontSize":"s","style":{"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|s"}}}} -->
				<p class="has-white-color has-text-color has-s-font-size" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--s)">Indiquez vos points de départ et d'arrivée pour un devis immédiat.</p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"width":100,"backgroundColor":"secondary","textColor":"primary","style":{"border":{"radius":"4px"},"typography":{"fontWeight":"700"}}} -->
					<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700">Calculer mon tarif →</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- Services VTC -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
	<p class="has-text-align-center has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Nos prestations</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 2.8rem)","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|l"}}}} -->
	<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--l);font-size:clamp(1.8rem, 3vw, 2.8rem);font-weight:800">Pour tous vos <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">déplacements</mark></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;border:1px solid color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent);padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:2.5rem;line-height:1">✈️</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Aéroport & Gare</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"s"} --><p class="has-text-align-center has-s-font-size">Paris CDG, Orly, Roissy. Suivi vol en temps réel. Attente offerte jusqu'à 60 min.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;border:1px solid color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent);padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:2.5rem;line-height:1">💼</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Affaires & Entreprise</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"s"} --><p class="has-text-align-center has-s-font-size">Réunions, séminaires, conférences. Facturation entreprise. Discrétion assurée.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)"},"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:8px;border:1px solid color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent);padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2.5rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:2.5rem;line-height:1">💒</p><!-- /wp:paragraph -->
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Mariage & Événements</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"s"} --><p class="has-text-align-center has-s-font-size">Véhicule de prestige pour vos moments importants. Décoration sur demande.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- Témoignages -->
<!-- wp:pattern {"slug":"g2rd-theme/section-temoignages"} /-->

<!-- CTA -->
<!-- wp:pattern {"slug":"g2rd-theme/section-cta"} /-->
