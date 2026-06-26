<?php
/**
 * Title: Section Services
 * Slug: g2rd-theme/section-services
 * Description: Grille 3 colonnes de feature-cards — icône lime, titre, description et lien. Façon wp-manager.
 * Categories: featured, text
 * Keywords: services, prestations, grille, cards, features
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
		<p class="has-text-align-center has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Nos expertises</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 2.8rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.9rem, 3vw, 2.8rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Des services pensés pour votre <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">réussite en ligne</mark></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","textColor":"muted","style":{"typography":{"lineHeight":"1.7","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.7">Chaque projet est unique. Nous adaptons notre approche à vos objectifs, votre secteur et vos clients pour créer une présence digitale qui génère de vrais résultats.</p>
		<!-- /wp:paragraph -->

	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">
				<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"0.6rem","bottom":"0.6rem","left":"0.6rem","right":"0.6rem"},"margin":{"bottom":"var:preset|spacing|xs"}},"dimensions":{"minHeight":"0"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
				<div class="wp-block-group has-secondary-background-color has-background" style="border-radius:var(--wp--custom--radius--m);margin-bottom:var(--wp--preset--spacing--xs);padding:0.6rem;width:fit-content"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:1.6rem;line-height:1">🎨</p><!-- /wp:paragraph --></div>
				<!-- /wp:group -->

				<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.25rem;font-weight:700">Création de site web</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Site vitrine, e-commerce ou application web sur mesure. Des interfaces modernes, rapides et optimisées pour vos visiteurs.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p style="margin-top:var(--wp--preset--spacing--s);font-weight:700"><a href="https://g2rd.fr/contact/">En savoir plus →</a></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">
				<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"0.6rem","bottom":"0.6rem","left":"0.6rem","right":"0.6rem"},"margin":{"bottom":"var:preset|spacing|xs"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
				<div class="wp-block-group has-secondary-background-color has-background" style="border-radius:var(--wp--custom--radius--m);margin-bottom:var(--wp--preset--spacing--xs);padding:0.6rem;width:fit-content"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:1.6rem;line-height:1">🚀</p><!-- /wp:paragraph --></div>
				<!-- /wp:group -->

				<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.25rem;font-weight:700">Référencement SEO</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Stratégie SEO technique et éditoriale pour positionner votre site sur Google. Audit, optimisation on-page et suivi mensuel.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p style="margin-top:var(--wp--preset--spacing--s);font-weight:700"><a href="https://g2rd.fr/contact/">En savoir plus →</a></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">
				<!-- wp:group {"style":{"border":{"radius":"var:custom|radius|m"},"spacing":{"padding":{"top":"0.6rem","bottom":"0.6rem","left":"0.6rem","right":"0.6rem"},"margin":{"bottom":"var:preset|spacing|xs"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
				<div class="wp-block-group has-secondary-background-color has-background" style="border-radius:var(--wp--custom--radius--m);margin-bottom:var(--wp--preset--spacing--xs);padding:0.6rem;width:fit-content"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p class="has-text-align-center" style="font-size:1.6rem;line-height:1">🛒</p><!-- /wp:paragraph --></div>
				<!-- /wp:group -->

				<!-- wp:heading {"level":3,"textColor":"primary","style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading has-primary-color has-text-color" style="font-size:1.25rem;font-weight:700">E-commerce WooCommerce</h3><!-- /wp:heading -->
				<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} --><p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);line-height:1.65">Boutique en ligne performante avec WooCommerce. Paiements sécurisés, gestion produits et expérience d'achat optimisée.</p><!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} --><p style="margin-top:var(--wp--preset--spacing--s);font-weight:700"><a href="https://g2rd.fr/contact/">En savoir plus →</a></p><!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
