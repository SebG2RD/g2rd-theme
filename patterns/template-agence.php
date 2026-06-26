<?php
/**
 * Title: Template Agence / Studio
 * Slug: g2rd-theme/template-agence
 * Description: Page d'accueil pour agence web, studio créatif, cabinet conseil — portfolio, équipe, processus
 * Categories: featured, banner
 * Keywords: agence, studio, créatif, conseil, portfolio, équipe
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>

<!-- Hero agence -->
<!-- wp:pattern {"slug":"g2rd-theme/section-hero"} /-->

<!-- Clients / Partenaires -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|l","bottom":"var:preset|spacing|l","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"var(--wp--preset--color--cream)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--l);padding-bottom:var(--wp--preset--spacing--l);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m);background-color:var(--wp--preset--color--cream)">
	<!-- wp:paragraph {"align":"center","textColor":"primary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase","fontSize":"0.8rem"}}} -->
	<p class="has-text-align-center has-primary-color has-text-color" style="font-weight:600;letter-spacing:0.1em;text-transform:uppercase;font-size:0.8rem">Ils nous font confiance</p>
	<!-- /wp:paragraph -->
	<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|s"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center","verticalAlignment":"center"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--s)">
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"1.1rem","letterSpacing":"0.05em"},"spacing":{"padding":{"right":"2rem","left":"2rem"}}}} --><p style="font-weight:700;font-size:1.1rem;letter-spacing:0.05em;padding-right:2rem;padding-left:2rem;opacity:0.4">Client A</p><!-- /wp:paragraph -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"1.1rem","letterSpacing":"0.05em"},"spacing":{"padding":{"right":"2rem","left":"2rem"}}}} --><p style="font-weight:700;font-size:1.1rem;letter-spacing:0.05em;padding-right:2rem;padding-left:2rem;opacity:0.4">Client B</p><!-- /wp:paragraph -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"1.1rem","letterSpacing":"0.05em"},"spacing":{"padding":{"right":"2rem","left":"2rem"}}}} --><p style="font-weight:700;font-size:1.1rem;letter-spacing:0.05em;padding-right:2rem;padding-left:2rem;opacity:0.4">Client C</p><!-- /wp:paragraph -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"1.1rem","letterSpacing":"0.05em"},"spacing":{"padding":{"right":"2rem","left":"2rem"}}}} --><p style="font-weight:700;font-size:1.1rem;letter-spacing:0.05em;padding-right:2rem;padding-left:2rem;opacity:0.4">Client D</p><!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- Services -->
<!-- wp:pattern {"slug":"g2rd-theme/section-services-g2rd"} /-->

<!-- Portfolio -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"var(--wp--preset--color--cream)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m);background-color:var(--wp--preset--color--cream)">
	<!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
	<p class="has-text-align-center has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Portfolio</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 2.8rem)","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|l"}}}} -->
	<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--l);font-size:clamp(1.8rem, 3vw, 2.8rem);font-weight:800">Nos <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">dernières réalisations</mark></h2>
	<!-- /wp:heading -->
	<!-- wp:pattern {"slug":"g2rd-theme/grid-portfolio"} /-->
	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--l)">
		<!-- wp:button {"className":"is-style-outline","borderColor":"primary","textColor":"primary","style":{"border":{"radius":"4px"}}} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-primary-color has-text-color wp-element-button" style="border-radius:4px">Voir tous nos projets</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

<!-- Processus / Méthode -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
	<!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
	<p class="has-text-align-center has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Notre approche</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 2.8rem)","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|l"}}}} -->
	<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--l);font-size:clamp(1.8rem, 3vw, 2.8rem);font-weight:800">Un processus <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">éprouvé</mark></h2>
	<!-- /wp:heading -->
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">
		<!-- Étape 1 -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}},"border":{"top":{"color":"var:preset|color|accent","width":"4px"},"radius":"0 0 8px 8px"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s);border-top:4px solid var(--wp--preset--color--accent);border-radius:0 0 8px 8px">
				<!-- wp:heading {"level":3,"textColor":"accent","style":{"typography":{"fontSize":"3rem","fontWeight":"900","lineHeight":"1"}}} --><h3 class="wp-block-heading has-accent-color has-text-color" style="font-size:3rem;font-weight:900;line-height:1">01</h3><!-- /wp:heading -->
				<!-- wp:heading {"level":4,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h4 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Découverte</h4><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Analyse de vos besoins, objectifs et cible. Brief détaillé et proposition stratégique.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- Étape 2 -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}},"border":{"top":{"color":"var:preset|color|accent","width":"4px"},"radius":"0 0 8px 8px"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s);border-top:4px solid var(--wp--preset--color--accent);border-radius:0 0 8px 8px">
				<!-- wp:heading {"level":3,"textColor":"accent","style":{"typography":{"fontSize":"3rem","fontWeight":"900","lineHeight":"1"}}} --><h3 class="wp-block-heading has-accent-color has-text-color" style="font-size:3rem;font-weight:900;line-height:1">02</h3><!-- /wp:heading -->
				<!-- wp:heading {"level":4,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h4 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Design</h4><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Maquettes Figma validées ensemble. Charte graphique, UX et identité visuelle.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- Étape 3 -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}},"border":{"top":{"color":"var:preset|color|accent","width":"4px"},"radius":"0 0 8px 8px"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s);border-top:4px solid var(--wp--preset--color--accent);border-radius:0 0 8px 8px">
				<!-- wp:heading {"level":3,"textColor":"accent","style":{"typography":{"fontSize":"3rem","fontWeight":"900","lineHeight":"1"}}} --><h3 class="wp-block-heading has-accent-color has-text-color" style="font-size:3rem;font-weight:900;line-height:1">03</h3><!-- /wp:heading -->
				<!-- wp:heading {"level":4,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h4 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Développement</h4><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Intégration WordPress, blocs Gutenberg, SEO technique et performance.</p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- Étape 4 -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|s","left":"var:preset|spacing|s"}},"border":{"top":{"color":"var:preset|color|accent","width":"4px"},"radius":"0 0 8px 8px"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="padding:var(--wp--preset--spacing--m) var(--wp--preset--spacing--s);border-top:4px solid var(--wp--preset--color--accent);border-radius:0 0 8px 8px">
				<!-- wp:heading {"level":3,"textColor":"accent","style":{"typography":{"fontSize":"3rem","fontWeight":"900","lineHeight":"1"}}} --><h3 class="wp-block-heading has-accent-color has-text-color" style="font-size:3rem;font-weight:900;line-height:1">04</h3><!-- /wp:heading -->
				<!-- wp:heading {"level":4,"textColor":"primary","style":{"typography":{"fontSize":"1.1rem","fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><h4 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:1.1rem;font-weight:700">Livraison</h4><!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Formation, mise en ligne, suivi des performances et support continu.</p><!-- /wp:paragraph -->
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
