<?php
/**
 * Title: Section Services (blocs G2RD)
 * Slug: g2rd-theme/section-services-g2rd
 * Description: Grille services avec le bloc g2rd/info — icône Dashicon, titre, description et layout personnalisable
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

	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|l"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--l)">

		<!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
		<p class="has-text-align-center has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Nos expertises</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 3rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
		<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.8rem, 3vw, 3rem);font-weight:800;line-height:1.2">Des services pensés pour votre <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">réussite en ligne</mark></h2>
		<!-- /wp:heading -->

	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-art","title":"Création de site web","description":"Site vitrine, e-commerce ou application web sur mesure. Interfaces modernes, rapides et optimisées pour vos visiteurs et vos conversions.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-search","title":"Référencement SEO","description":"Stratégie SEO technique et éditorial pour positionner votre site sur Google. Audit, optimisation on-page et suivi mensuel des performances.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-cart","title":"E-commerce WooCommerce","description":"Boutique en ligne performante avec WooCommerce. Paiements sécurisés, gestion produits et expérience d'achat optimisée tous supports.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|m","left":"var:preset|spacing|m"},"margin":{"top":"var:preset|spacing|m"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--m)">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-editor-code","title":"Développement sur mesure","description":"Fonctionnalités spécifiques, blocs Gutenberg custom, intégrations API. Nous développons ce dont vous avez besoin, pas plus, pas moins.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-shield","title":"Maintenance & Sécurité","description":"Mises à jour, sauvegardes automatiques, surveillance des performances. Votre site est toujours disponible, sécurisé et à jour.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:g2rd/info {"icon":"dashicons-admin-appearance","title":"Design & Identité visuelle","description":"Logo, charte graphique, univers visuel cohérent. Nous créons une identité de marque mémorable qui vous distingue de la concurrence.","backgroundColor":"var(--wp--preset--color--primary)","titleColor":"var(--wp--preset--color--white)","descriptionColor":"var(--wp--preset--color--secondary)","iconColor":"var(--wp--preset--color--white)","iconSize":40,"layout":"icon-top","gap":"16px"} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--l)">
		<!-- wp:button {"backgroundColor":"primary","textColor":"white","style":{"border":{"radius":"4px"},"typography":{"fontWeight":"600"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-white-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:600">Voir tous nos services</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
