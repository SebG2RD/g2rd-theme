<?php
/**
 * Title: Template E-commerce
 * Slug: g2rd-theme/template-ecommerce
 * Description: Page d'accueil boutique en ligne — hero promo, catégories, produits vedettes, réassurance, newsletter
 * Categories: featured, banner
 * Keywords: ecommerce, boutique, woocommerce, produits, shop
 * Viewport Width: 1400
 * Block Types: core/group
 * Inserter: true
 * Text Domain: g2rd
 */
?>

<!-- Hero e-commerce -->
<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|l"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
			<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">🎉 Soldes — jusqu'à -40%</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":1,"textColor":"white","style":{"typography":{"fontSize":"clamp(2.2rem, 4.5vw, 3.8rem)","lineHeight":"1.1","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
			<h1 class="wp-block-heading has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(2.2rem, 4.5vw, 3.8rem);line-height:1.1;font-weight:800">La boutique qui<br><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">vous ressemble</mark></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"lineHeight":"1.75","fontSize":"1.1rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:1.1rem;line-height:1.75">Découvrez notre sélection de produits soigneusement choisis. Livraison rapide, retours gratuits sous 30 jours.</p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"},"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
				<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"1.05rem"},"border":{"radius":"4px"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700;font-size:1.05rem">Voir les offres →</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline","textColor":"white","style":{"border":{"color":"rgba(250,250,250,0.6)","radius":"4px","width":"1px"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="border-radius:4px;border-width:1px;border-color:rgba(250,250,250,0.6)">Notre catalogue</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
			<!-- wp:paragraph {"textColor":"white","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
			<p class="has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:0.875rem;opacity:0.7">🚚 Livraison offerte dès 50€ · 🔄 Retours gratuits 30j · 🔒 Paiement sécurisé</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","style":{"border":{"radius":"1rem"}}} -->
			<figure class="wp-block-image size-large" style="border-radius:1rem"><img alt="Produit vedette" /></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- Réassurance e-commerce -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|m","bottom":"var:preset|spacing|m","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}},"color":{"background":"#F5F4F2"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding:var(--wp--preset--spacing--m);background-color:#F5F4F2">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p style="font-size:1.6rem;line-height:1">🚚</p><!-- /wp:paragraph -->
				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.9rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.9rem">Livraison rapide</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Dès 50€ d'achat</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p style="font-size:1.6rem;line-height:1">🔄</p><!-- /wp:paragraph -->
				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.9rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.9rem">Retours gratuits</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">30 jours pour changer d'avis</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p style="font-size:1.6rem;line-height:1">🔒</p><!-- /wp:paragraph -->
				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.9rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.9rem">Paiement sécurisé</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">CB, PayPal, Apple Pay</p><!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|xs"}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.6rem","lineHeight":"1"}}} --><p style="font-size:1.6rem;line-height:1">💬</p><!-- /wp:paragraph -->
				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"textColor":"primary","style":{"typography":{"fontWeight":"700","fontSize":"0.9rem"}}} --><p class="has-primary-color has-text-color" style="font-weight:700;font-size:0.9rem">Support client</p><!-- /wp:paragraph -->
					<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size">Lun–Ven, 9h–18h</p><!-- /wp:paragraph -->
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

<!-- Produits / FilterableGrid -->
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
	<!-- wp:paragraph {"align":"center","textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
	<p class="has-text-align-center has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Boutique</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center","level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 2.8rem)","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|l"}}}} -->
	<h2 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--l);font-size:clamp(1.8rem, 3vw, 2.8rem);font-weight:800">Nos <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">produits vedettes</mark></h2>
	<!-- /wp:heading -->
	<!-- wp:pattern {"slug":"g2rd-theme/card-G2RD"} /-->
</div>
<!-- /wp:group -->

<!-- Témoignages -->
<!-- wp:pattern {"slug":"g2rd-theme/section-temoignages"} /-->

<!-- Newsletter -->
<!-- wp:group {"align":"full","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">
	<!-- wp:group {"style":{"layout":{"selfStretch":"fixed","flexSize":"600px"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"clamp(1.6rem, 2.5vw, 2.2rem)","fontWeight":"800"}}} -->
		<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:clamp(1.6rem, 2.5vw, 2.2rem);font-weight:800">-10% sur votre <mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-secondary-color">première commande</mark></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
		<p class="has-text-align-center has-white-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);line-height:1.7">Inscrivez-vous à notre newsletter et recevez votre code promo immédiatement.</p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
		<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
			<!-- wp:button {"backgroundColor":"secondary","textColor":"primary","style":{"border":{"radius":"4px"},"typography":{"fontWeight":"700"}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-primary-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:700">S'inscrire et recevoir -10%</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
