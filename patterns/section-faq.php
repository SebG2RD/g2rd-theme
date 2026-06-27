<?php
/**
 * Title: Section FAQ
 * Slug: g2rd-theme/section-faq
 * Description: FAQ accordéon (bloc g2rd/faq) en deux colonnes — intro sticky + questions, façon wp-manager.
 * Categories: text, featured
 * Keywords: faq, questions, réponses, accordéon, aide
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/faq
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|xl"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column {"width":"38%"} -->
		<div class="wp-block-column" style="flex-basis:38%">

			<!-- wp:group {"style":{"position":{"type":"sticky","top":"6rem"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="position:sticky;top:6rem">

				<!-- Pill eyebrow -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","justifyContent":"left"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","letterSpacing":"0.1em","textTransform":"uppercase"},"border":{"radius":"999px","width":"1px","color":"color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent)"},"color":{"background":"color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent)"},"spacing":{"padding":{"top":"0.4rem","bottom":"0.4rem","left":"0.9rem","right":"0.9rem"}}},"textColor":"primary","fontSize":"s"} -->
					<p class="has-primary-color has-text-color has-s-font-size" style="border-color:color-mix(in srgb, var(--wp--preset--color--secondary) 45%, transparent);border-width:1px;border-radius:999px;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 14%, transparent);padding-top:0.4rem;padding-right:0.9rem;padding-bottom:0.4rem;padding-left:0.9rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase"><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color has-accent-color">●</mark>&nbsp; Questions fréquentes</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:heading {"level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 2.5vw, 2.5rem)","fontWeight":"800","lineHeight":"1.15","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<h2 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);font-size:clamp(1.8rem, 2.5vw, 2.5rem);font-weight:800;letter-spacing:-0.02em;line-height:1.15">Vos questions, <mark style="background-color:rgba(0,0,0,0);border-bottom:0.18em solid var(--wp--preset--color--secondary)" class="has-inline-color has-secondary-color">nos réponses</mark></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<p class="has-muted-color has-text-color" style="margin-top:var(--wp--preset--spacing--s);line-height:1.7">Une question spécifique ? Notre équipe est disponible pour vous accompagner dans votre projet.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
					<!-- wp:button {"style":{"typography":{"fontWeight":"700"},"spacing":{"padding":{"top":"0.9rem","bottom":"0.9rem","right":"1.8rem","left":"1.8rem"}}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="font-weight:700;padding-top:0.9rem;padding-bottom:0.9rem;padding-right:1.8rem;padding-left:1.8rem">Poser ma question</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"62%"} -->
		<div class="wp-block-column" style="flex-basis:62%">

			<!-- wp:g2rd/faq {"items":[{"question":"Combien coûte la création d'un site web ?","answer":"Le coût dépend de la complexité de votre projet. Un site vitrine simple démarre à partir de 1 500 €, un site avec fonctionnalités avancées entre 3 000 € et 8 000 €. Nous vous fournissons toujours un devis détaillé et sans surprise après une analyse de vos besoins."},{"question":"Quel est le délai de réalisation ?","answer":"En moyenne, un site vitrine est livré en 3 à 6 semaines. Un projet e-commerce ou sur mesure peut prendre 6 à 12 semaines. Les délais dépendent aussi de votre réactivité pour nous fournir les contenus (textes, photos)."},{"question":"Mon site sera-t-il optimisé pour le référencement ?","answer":"Oui, tous nos sites intègrent les bonnes pratiques SEO techniques dès la conception : structure HTML sémantique, meta-données, vitesse de chargement, responsive design. Nous proposons également des prestations SEO complémentaires pour booster votre visibilité."},{"question":"Pourrai-je modifier mon site moi-même après livraison ?","answer":"Absolument. Nous développons avec WordPress, le CMS le plus utilisé au monde. Vous recevrez une formation complète à l'utilisation de votre site et pourrez modifier vos contenus de façon autonome. Nous restons disponibles pour toute question."},{"question":"Proposez-vous un service de maintenance ?","answer":"Oui, nous proposons des contrats de maintenance mensuelle incluant les mises à jour WordPress, la sauvegarde automatique, la surveillance des performances et une assistance prioritaire. C'est recommandé pour garantir la sécurité et la disponibilité de votre site."}],"questionColor":"var(--wp--preset--color--primary)","iconColor":"var(--wp--custom--color--accenthover)","borderColor":"color-mix(in srgb, var(--wp--preset--color--primary) 12%, transparent)","borderRadius":8,"openFirst":true} /-->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
