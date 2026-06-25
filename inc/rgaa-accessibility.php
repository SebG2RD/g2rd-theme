<?php
/**
 * Correctifs d'accessibilité RGAA
 *
 * Intégré directement dans le thème — aucune dépendance au sandbox Novamira.
 * La garde RGAA_FIXES_LOADED empêche un double-chargement pendant la transition.
 *
 * @package G2RD
 * @since   1.10.8
 */

if ( \defined( 'G2RD_RGAA_FIXES_LOADED' ) ) {
	return;
}
\define( 'G2RD_RGAA_FIXES_LOADED', true );

// 1. Liens sociaux : aria-label sur l'ancre
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( 'core/social-link' !== $block['blockName'] ) {
			return $block_content;
		}

		$labels = [
			'facebook.com'  => 'Facebook (nouvelle fenêtre)',
			'linkedin.com'  => 'LinkedIn (nouvelle fenêtre)',
			'instagram.com' => 'Instagram (nouvelle fenêtre)',
			'wa.me'         => 'WhatsApp (nouvelle fenêtre)',
			'twitter.com'   => 'X / Twitter (nouvelle fenêtre)',
			'x.com'         => 'X / Twitter (nouvelle fenêtre)',
			'youtube.com'   => 'YouTube (nouvelle fenêtre)',
			'github.com'    => 'GitHub (nouvelle fenêtre)',
		];

		foreach ( $labels as $pattern => $label ) {
			if ( false !== \strpos( $block_content, $pattern ) ) {
				$block_content = \str_replace(
					'class="wp-block-social-link-anchor"',
					'class="wp-block-social-link-anchor" aria-label="' . \esc_attr( $label ) . '"',
					$block_content
				);
				break;
			}
		}

		return $block_content;
	},
	10,
	2
);

// 2. Lien "Lire la suite" : aria-label contextuel
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( 'core/read-more' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( \preg_match( '/screen-reader-text">\s*(?:&nbsp;)?\s*:?\s*(.+?)<\/span>/', $block_content, $m ) ) {
			$aria_label    = 'Lire la suite : ' . \wp_strip_all_tags( \trim( $m[1] ) );
			$block_content = \str_replace(
				'class="wp-block-read-more"',
				'class="wp-block-read-more" aria-label="' . \esc_attr( $aria_label ) . '"',
				$block_content
			);
		}

		return $block_content;
	},
	10,
	2
);

// 3. Lien politique de confidentialité : correction href vide
\add_filter(
	'the_privacy_policy_link',
	function ( $link, $url ) {
		if ( ! empty( $url ) ) {
			return $link;
		}

		$pid  = (int) \get_option( 'wp_page_for_privacy_policy' );
		$purl = $pid ? \get_permalink( $pid ) : '';

		if ( $purl ) {
			$link = \str_replace( 'href=""', 'href="' . \esc_url( $purl ) . '"', $link );
		}

		return $link;
	},
	10,
	2
);

// 4. Formulaire de commentaires : correction href vide dans le champ consentement
\add_filter(
	'comment_form_defaults',
	function ( $defaults ) {
		if ( ! isset( $defaults['consent'] ) || false === \strpos( $defaults['consent'], 'href=""' ) ) {
			return $defaults;
		}

		$pid = (int) \get_option( 'wp_page_for_privacy_policy' );
		$url = $pid ? \get_permalink( $pid ) : '';

		if ( $url ) {
			$defaults['consent'] = \str_replace( 'href=""', 'href="' . \esc_url( $url ) . '"', $defaults['consent'] );
		}

		return $defaults;
	}
);

// 7. Scripts frontend accessibilité (thème uniquement)
\add_action(
	'wp_footer',
	function () {
		?>
		<script>(function(){
			var t=document.getElementById("wp--skip-link--target");
			if(t&&!t.hasAttribute("tabindex")){t.setAttribute("tabindex","-1");}

			var cur=window.location.href.replace(/\/$/, "").replace(/#.*$/, "");
			document.querySelectorAll("nav a[href]").forEach(function(a){
				var h=a.href.replace(/\/$/, "").replace(/#.*$/, "");
				if(h===cur){a.setAttribute("aria-current","page");}
			});

			// Autocomplete sur les champs de formulaire (RGAA 11.13) — n'écrase jamais une valeur existante.
			document.querySelectorAll("form input, form textarea, form select").forEach(function(f){
				if(f.getAttribute("autocomplete"))return;
				var type=(f.getAttribute("type")||"").toLowerCase();
				if(["hidden","submit","button","checkbox","radio","file","search","password"].indexOf(type)>-1)return;
				var k=((f.name||"")+" "+(f.id||"")+" "+(f.getAttribute("placeholder")||"")).toLowerCase();
				var ac="";
				if(type==="email"||/e-?mail|courriel/.test(k))ac="email";
				else if(type==="tel"||/phone|t[eé]l|mobile|portable/.test(k))ac="tel";
				else if(type==="url"||/\burl\b|site\s?web|website/.test(k))ac="url";
				else if(/first|pr[eé]nom|given/.test(k))ac="given-name";
				else if(/last|family|nom de famille/.test(k))ac="family-name";
				else if(/soci[eé]t[eé]|company|entreprise|organi[sz]ation/.test(k))ac="organization";
				else if(/\bnom\b|full ?name|votre nom|your-name|\bname\b/.test(k))ac="name";
				if(ac)f.setAttribute("autocomplete",ac);
			});

			// Association label↔champ : un <label> sans "for" placé juste avant un champ pourvu d'un id (RGAA 11.1).
			document.querySelectorAll("form label:not([for])").forEach(function(label){
				if(label.querySelector("input,textarea,select"))return; // label englobant : déjà associé.
				var node=label.nextElementSibling,ctrl=null,guard=0;
				while(node&&guard<4){
					if(node.matches&&node.matches("input,textarea,select")){ctrl=node;break;}
					ctrl=node.querySelector?node.querySelector("input,textarea,select"):null;
					if(ctrl)break;
					node=node.nextElementSibling;guard++;
				}
				if(ctrl&&ctrl.id){
					var id=window.CSS&&CSS.escape?CSS.escape(ctrl.id):ctrl.id;
					if(!document.querySelector("label[for=\""+id+"\"]"))label.setAttribute("for",ctrl.id);
				}
			});
		})();</script>
		<?php
	}
);

// 8. Images décoratives : alt="" + role="presentation" si l'alt est vide en médiathèque
\add_filter(
	'wp_get_attachment_image_attributes',
	function ( $attr, $attachment ) {
		$alt = \get_post_meta( $attachment->ID, '_wp_alt_text', true );
		if ( '' === $alt ) {
			$attr['alt']  = '';
			$attr['role'] = 'presentation';
		}
		return $attr;
	},
	10,
	2
);

// 9. Compteur g2rd/counter : afficher la valeur finale en clair dans le HTML statique (sans JS) — RGAA 7.1
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( 'g2rd/counter' !== $block['blockName'] ) {
			return $block_content;
		}
		if ( false === \strpos( $block_content, 'counter-number' ) ) {
			return $block_content;
		}

		$attrs     = isset( $block['attrs'] ) ? $block['attrs'] : [];
		$end       = isset( $attrs['endingNumber'] ) ? (float) $attrs['endingNumber'] : 100.0;
		$decimals  = isset( $attrs['decimalPlaces'] ) ? \absint( $attrs['decimalPlaces'] ) : 0;
		$thousands = isset( $attrs['thousands'] ) ? $attrs['thousands'] : 'comma';
		$separator = 'comma' === $thousands ? ',' : ( 'space' === $thousands ? ' ' : '' );
		$formatted = \number_format( $end, $decimals, '.', $separator );

		// Remplace le contenu (valeur de départ animée) par la valeur finale réelle.
		return \preg_replace_callback(
			'/(<span class="counter-number"[^>]*>)(.*?)(<\/span>)/s',
			function ( $matches ) use ( $formatted ) {
				return $matches[1] . \esc_html( $formatted ) . $matches[3];
			},
			$block_content,
			1
		);
	},
	10,
	2
);

// 10. Navigation : remplacer les liens href="#" par une destination réelle — RGAA 6.1
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( 'core/navigation' !== $block['blockName'] ) {
			return $block_content;
		}
		if ( false === \strpos( $block_content, 'href="#"' ) ) {
			return $block_content;
		}

		$posts_page = (int) \get_option( 'page_for_posts' );
		$url        = $posts_page ? \get_permalink( $posts_page ) : \home_url( '/' );

		return \str_replace( 'href="#"', 'href="' . \esc_url( $url ) . '"', $block_content );
	},
	10,
	2
);

// 11. Emojis décoratifs doublant une information textuelle : aria-hidden — RGAA 1.2 / 10.10
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		static $text_blocks = [
			'core/paragraph' => true,
			'core/heading'   => true,
			'core/list'      => true,
			'core/list-item' => true,
			'core/verse'     => true,
			'core/button'    => true,
		];

		if ( ! isset( $text_blocks[ $block['blockName'] ] ) ) {
			return $block_content;
		}

		// Jeu d'emojis décoratifs (caractères de base ; le sélecteur de variante \x{FE0F} est optionnel).
		$emojis  = '📍📞📧📱☎✉💬🎯🤝⚡⭐🚀✨💡📝🖥🔄🔍🛡🎓⚙🎨📣🧭✆';
		$pattern = '/(?<!aria-hidden="true">)([' . \preg_quote( $emojis, '/' ) . ']\x{FE0F}?)/u';

		if ( ! \preg_match( $pattern, $block_content ) ) {
			return $block_content;
		}

		return \preg_replace_callback(
			$pattern,
			function ( $matches ) {
				return '<span aria-hidden="true">' . $matches[1] . '</span>';
			},
			$block_content
		);
	},
	10,
	2
);

// 12. Images de contenu/galerie sans attribut alt : ajoute alt="" pour éviter l'annonce du nom de fichier — RGAA 1.1
// Filet de sécurité : le texte alternatif pertinent reste à saisir dans la médiathèque (contenu).
\add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( 'core/image' !== $block['blockName'] && 'core/gallery' !== $block['blockName'] ) {
			return $block_content;
		}
		if ( false === \strpos( $block_content, '<img' ) ) {
			return $block_content;
		}

		// Cible uniquement les <img> totalement dépourvues d'attribut alt (un alt="" existant est laissé tel quel).
		return \preg_replace_callback(
			'/<img\b(?![^>]*\balt=)([^>]*?)>/i',
			function ( $matches ) {
				return '<img alt=""' . $matches[1] . '>';
			},
			$block_content
		);
	},
	10,
	2
);
