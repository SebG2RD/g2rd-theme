<?php
/**
 * Title: Section FAQ + Résumé GEO
 * Slug: g2rd-theme/section-faq-geo
 * Description: Combinaison optimale pour le GEO : résumé IA en tête de section + FAQ accordéon avec schema FAQPage et JSON-LD
 * Categories: text, featured
 * Keywords: faq, geo, résumé, schema, seo, ia, questions, réponses, json-ld
 * Viewport Width: 1400
 * Block Types: core/group, g2rd/geo-summary, g2rd/faq
 * Inserter: true
 * Text Domain: g2rd
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl","right":"var:preset|spacing|m","left":"var:preset|spacing|m"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);padding-right:var(--wp--preset--spacing--m);padding-left:var(--wp--preset--spacing--m)">

	<!-- wp:paragraph {"textColor":"secondary","style":{"typography":{"fontWeight":"600","letterSpacing":"0.12em","textTransform":"uppercase"}},"fontSize":"s"} -->
	<p class="has-secondary-color has-text-color has-s-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">Questions fréquentes</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"textColor":"primary","style":{"typography":{"fontSize":"clamp(1.8rem, 2.5vw, 2.5rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"top":"var:preset|spacing|xs","bottom":"var:preset|spacing|m"}}}} -->
	<h2 class="wp-block-heading has-primary-color has-text-color" style="margin-top:var(--wp--preset--spacing--xs);margin-bottom:var(--wp--preset--spacing--m);font-size:clamp(1.8rem, 2.5vw, 2.5rem);font-weight:800;line-height:1.2">Vos questions, nos réponses</h2>
	<!-- /wp:heading -->

	<!-- wp:g2rd/geo-summary {"summary":"Ce bloc résume les points clés de votre page pour les moteurs IA (Google SGE, Bing Copilot). Remplacez ce texte par un résumé concis de 3 à 5 phrases couvrant le sujet principal, les services proposés et la valeur ajoutée de votre offre.","keyPoints":["Point clé n°1 — à personnaliser","Point clé n°2 — à personnaliser","Point clé n°3 — à personnaliser"]} /-->

	<!-- wp:g2rd/faq {"items":[{"question":"Quelle est la première question ?","answer":"Voici la réponse à la première question. Elle doit être complète, précise et apporter une vraie valeur à l'internaute. Visez au minimum 30 mots par réponse pour un bon score GEO."},{"question":"Quelle est la deuxième question ?","answer":"Voici la réponse à la deuxième question. Pensez à utiliser un langage naturel et à anticiper les vraies questions de vos visiteurs — ce sont celles que les IA citent le plus facilement."},{"question":"Quelle est la troisième question ?","answer":"Voici la réponse à la troisième question. Une FAQ de 5 questions minimum avec des réponses détaillées maximise votre visibilité dans les résultats enrichis de Google et les réponses d'IA."}],"optimizeForGEO":true,"openFirst":true,"iconType":"chevron","borderColor":"rgba(47,66,93,0.1)","borderRadius":8} /-->

</div>
<!-- /wp:group -->
