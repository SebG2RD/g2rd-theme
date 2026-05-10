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

// 5. Fluent Forms — champs : suppr. tabindex positif, autocomplete, label sr-only
$g2rd_rgaa_ff_field_fix = function ( $html, $data = [], $form = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	// Supprime les tabindex positifs (RGAA 12.8)
	$html = \preg_replace( '/\s*tabindex="[1-9]\d*"/', '', $html );

	// Autocomplete sur les champs email (RGAA 11.13)
	if ( false !== \strpos( $html, 'type="email"' ) && false === \strpos( $html, 'autocomplete' ) ) {
		$html = \str_replace( 'type="email"', 'type="email" autocomplete="email"', $html );
	}

	// Label sr-only si le champ n'a que placeholder (RGAA 11.1)
	if (
		\is_array( $data )
		&& ! empty( $data['attributes']['placeholder'] )
		&& empty( $data['settings']['label'] )
	) {
		$label_text = $data['attributes']['placeholder'];
		$input_id   = $data['attributes']['id'] ?? '';

		if ( $input_id && false === \strpos( $html, '<label' ) ) {
			$sr   = '<label for="' . \esc_attr( $input_id ) . '" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">'
					. \esc_html( $label_text )
					. '</label>';
			$html = \preg_replace(
				'/(<input[^>]*id="' . \preg_quote( $input_id, '/' ) . '"[^>]*>)/',
				$sr . '$1',
				$html
			);
		}
	}

	return $html;
};

\add_filter( 'fluentform/rendering_field_html_input_email', $g2rd_rgaa_ff_field_fix, 10, 3 );
\add_filter( 'fluentform/rendering_field_html_input_text', $g2rd_rgaa_ff_field_fix, 10, 3 );
\add_filter( 'fluentform/rendering_field_html_textarea', $g2rd_rgaa_ff_field_fix, 10, 3 );

// 6. Fluent Forms — bouton : suppr. tabindex positif + aria-label redondant
\add_filter(
	'fluentform/rendering_field_html_button',
	function ( $html, $data = [], $form = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$html = \preg_replace( '/\s*tabindex="[1-9]\d*"/', '', $html );

		if ( \preg_match( '/aria-label="([^"]+)"/', $html, $m ) ) {
			$aria = \html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			if ( \preg_match( '/>([^<]+)<\/button>/', $html, $btn ) ) {
				if ( \mb_strtolower( $aria ) === \mb_strtolower( \trim( $btn[1] ) ) ) {
					$html = \str_replace( ' aria-label="' . $m[1] . '"', '', $html );
				}
			}
		}

		return $html;
	},
	10,
	3
);

// 7. Skip-link : tabindex="-1" sur la cible + honeypot Fluent Forms aria-hidden
\add_action(
	'wp_footer',
	function () {
		?>
		<script>
		(function(){
			var t=document.getElementById("wp--skip-link--target");
			if(t&&!t.hasAttribute("tabindex")){t.setAttribute("tabindex","-1");}
			document.querySelectorAll(".ff-hpsf-container").forEach(function(el){
				el.setAttribute("aria-hidden","true");
				var inp=el.querySelector("input");
				if(inp){inp.setAttribute("tabindex","-1");}
			});
		})();
		</script>
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
