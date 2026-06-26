<?php
/**
 * Title: Section FAQ
 * Slug: g2rd-theme/section-faq
 * Description: Section FAQ accordéon — questions fréquentes avec le bloc g2rd/faq et un CTA de contact
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

				<!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
				<p class="has-accent-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Questions fréquentes</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 2.5vw, 2.5rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs"}}}} -->
				<h2 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);font-size:clamp(1.8rem, 2.5vw, 2.5rem);font-weight:800;line-height:1.2">Vos questions, nos réponses</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|s"}}}} -->
				<p style="margin-top:var(--wp--preset--spacing--s);line-height:1.7">Une question spécifique ? Notre équipe est disponible pour vous accompagner dans votre projet.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
					<!-- wp:button {"backgroundColor":"primary","textColor":"white","style":{"border":{"radius":"4px"},"typography":{"fontWeight":"600"}}} -->
					<div class="wp-block-button"><a class="wp-block-button__link has-primary-background-color has-white-color has-background has-text-color wp-element-button" style="border-radius:4px;font-weight:600">Poser ma question</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"62%"} -->
		<div class="wp-block-column" style="flex-basis:62%">

			<!-- wp:g2rd/faq {"items":[{"question":"Combien coûte la création d'un site web ?","answer":"Le coût dépend de la complexité de votre projet. Un site vitrine simple démarre à partir de 1 500 €, un site avec fonctionnalités avancées entre 3 000 € et 8 000 €. Nous vous fournissons toujours un devis détaillé et sans surprise après une analyse de vos besoins."},{"question":"Quel est le délai de réalisation ?","answer":"En moyenne, un site vitrine est livré en 3 à 6 semaines. Un projet e-commerce ou sur mesure peut prendre 6 à 12 semaines. Les délais dépendent aussi de votre réactivité pour nous fournir les contenus (textes, photos)."},{"question":"Mon site sera-t-il optimisé pour le référencement ?","answer":"Oui, tous nos sites intègrent les bonnes pratiques SEO techniques dès la conception : structure HTML sémantique, meta-données, vitesse de chargement, responsive design. Nous proposons également des prestations SEO complémentaires pour booster votre visibilité."},{"question":"Pourrai-je modifier mon site moi-même après livraison ?","answer":"Absolument. Nous développons avec WordPress, le CMS le plus utilisé au monde. Vous recevrez une formation complète à l'utilisation de votre site et pourrez modifier vos contenus de façon autonome. Nous restons disponibles pour toute question."},{"question":"Proposez-vous un service de maintenance ?","answer":"Oui, nous proposons des contrats de maintenance mensuelle incluant les mises à jour WordPress, la sauvegarde automatique, la surveillance des performances et une assistance prioritaire. C'est recommandé pour garantir la sécurité et la disponibilité de votre site."}],"questionColor":"var(--wp--preset--color--primary)","iconColor":"var(--wp--preset--color--accent)","borderColor":"color-mix(in srgb, var(--wp--preset--color--primary) 12%, transparent)","borderRadius":8,"openFirst":true} /-->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
