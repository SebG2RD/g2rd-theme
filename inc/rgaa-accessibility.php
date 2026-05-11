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
