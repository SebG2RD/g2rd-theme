<?php
/**
 * Title: Section Services (blocs G2RD)
 * Slug: g2rd-theme/section-services-g2rd
 * Description: Grille services en cartes sombres avec le bloc g2rd/info — icône lime, titre et description. Façon wp-manager.
 * Categories: featured, text
 * Keywords: services, prestations, g2rd, info, icônes
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/info
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- Pill eyebrow -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"primary","fontSize":"s"} -->
			<p class="has-primary-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">●</mark>&nbsp; Nos expertises</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 2.8rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.9rem, 3vw, 2.8rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Des services pensés pour votre <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">réussite en ligne</mark></h2>
		<!-- /wp:heading -->

	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-art","title":"Création de site web","description":"Site vitrine, e-commerce ou application web sur mesure. Interfaces modernes, rapides et optimisées pour vos visiteurs et vos conversions.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-search","title":"Référencement SEO","description":"Stratégie SEO technique et éditoriale pour positionner votre site sur Google. Audit, optimisation on-page et suivi mensuel des performances.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-cart","title":"E-commerce WooCommerce","description":"Boutique en ligne performante avec WooCommerce. Paiements sécurisés, gestion produits et expérience d'achat optimisée tous supports.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"},"margin":{"top":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--m)">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-editor-code","title":"Développement sur mesure","description":"Fonctionnalités spécifiques, blocs Gutenberg custom, intégrations API. Nous développons ce dont vous avez besoin, pas plus, pas moins.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-shield","title":"Maintenance & Sécurité","description":"Mises à jour, sauvegardes automatiques, surveillance des performances. Votre site est toujours disponible, sécurisé et à jour.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-admin-appearance","title":"Design & Identité visuelle","description":"Logo, charte graphique, univers visuel cohérent. Nous créons une identité de marque mémorable qui vous distingue de la concurrence.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--white)","iconColor":"var(--wp--preset--color--secondary)","iconSize":40,"layout":"icon-top","gap":"16px","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","left":"var:preset|spacing|m","right":"var:preset|spacing|m"}}}} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--l)">
		<!-- wp:button {"style":{"typography":{"fontWeight":"700"},"spacing":{"padding":{"top":"0.9rem","bottom":"0.9rem","right":"1.8rem","left":"1.8rem"}}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="font-weight:700;padding-top:0.9rem;padding-bottom:0.9rem;padding-right:1.8rem;padding-left:1.8rem">Voir tous nos services →</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
