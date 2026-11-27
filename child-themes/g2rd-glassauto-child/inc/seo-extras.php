<?php
/**
 * Compléments SEO du site GlassAuto Vernon.
 *
 *  - icônes du site (favicon, Apple, Android) servies par le thème tant
 *    qu'aucune icône n'est définie dans Réglages › Général ;
 *  - image de partage par défaut en JPG (les réseaux ignorent le SVG) ;
 *  - schéma : pas de nom alternatif identique au nom, description de
 *    l'organisation renseignée ;
 *  - liens vers les réseaux sociaux du centre (comptes saisis dans SEOPress
 *    › Réseaux sociaux : une saisie alimente le pied de page et le sameAs) ;
 *  - liens de partage sans script ni cookie sous les articles ;
 *  - corrections au rendu du contenu déjà publié (alt du héros, URL https,
 *    ancres « En savoir plus », titres des services).
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

/**
 * URL d'un fichier du dossier assets/img/icons.
 *
 * @param string $file Nom du fichier.
 * @return string
 */
function icon_url( string $file ): string {
	return \get_stylesheet_directory_uri() . '/assets/img/icons/' . $file;
}

/* --- Icônes du site ------------------------------------------------------- */
\add_action(
	'wp_head',
	static function (): void {
		if ( \has_site_icon() ) {
			return;
		}
		$v = (string) \filemtime( \get_stylesheet_directory() . '/assets/img/icons/icon-512.png' );
		\printf( '<link rel="icon" href="%s" sizes="any">' . "\n", \esc_url( icon_url( 'favicon.ico' ) . '?v=' . $v ) );
		\printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", \esc_url( icon_url( 'icon.svg' ) . '?v=' . $v ) );
		\printf( '<link rel="icon" href="%s" sizes="192x192" type="image/png">' . "\n", \esc_url( icon_url( 'icon-192.png' ) . '?v=' . $v ) );
		\printf( '<link rel="apple-touch-icon" href="%s" sizes="180x180">' . "\n", \esc_url( icon_url( 'icon-180.png' ) . '?v=' . $v ) );
		echo '<meta name="theme-color" content="#2F3237">' . "\n";
	},
	2
);

/* --- Image de partage par défaut ------------------------------------------ */
\add_filter(
	'seopress_social_og_thumb',
	static function ( $html ) {
		$html = (string) $html;
		if ( false === \stripos( $html, '.svg' ) ) {
			return $html;
		}
		$img = \esc_url( icon_url( 'og-default.jpg' ) );
		$alt = \esc_attr__( 'Remplacement de pare-brise au centre GlassAuto de Vernon', 'g2rd-glassauto-child' );
		return '<meta property="og:image" content="' . $img . '">' . "\n"
			. '<meta property="og:image:secure_url" content="' . $img . '">' . "\n"
			. '<meta property="og:image:width" content="1200">' . "\n"
			. '<meta property="og:image:height" content="630">' . "\n"
			. '<meta property="og:image:alt" content="' . $alt . '">' . "\n";
	}
);

/* --- Schéma : doublons de nom et description ------------------------------ */
\add_filter(
	'seopress_schemas_website',
	static function ( $schema ) {
		if ( \is_array( $schema ) && isset( $schema['name'], $schema['alternateName'] ) && $schema['alternateName'] === $schema['name'] ) {
			unset( $schema['alternateName'] );
		}
		return $schema;
	}
);

// Organisation : la charge utile JSON arrive ici une fois les variables résolues.
\add_filter(
	'seopress_schemas_organization_html',
	static function ( $json ) {
		if ( ! \is_string( $json ) || '' === \trim( $json ) ) {
			return $json;
		}
		$data = \json_decode( $json, true );
		if ( ! \is_array( $data ) ) {
			return $json;
		}
		$name = (string) ( $data['name'] ?? '' );
		if ( isset( $data['alternateName'] ) && $data['alternateName'] === $name ) {
			unset( $data['alternateName'] );
		}
		if ( empty( $data['description'] ) || $data['description'] === $name ) {
			$data['description'] = \__( 'Centre de réparation et de remplacement de pare-brise et de vitrages automobiles à Vernon (Eure), agréé par la plupart des assurances.', 'g2rd-glassauto-child' );
		}
		$out = \wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
		return false !== $out ? $out : $json;
	}
);

/* --- Réseaux sociaux du centre --------------------------------------------- */

/**
 * Comptes saisis dans SEOPress › Réseaux sociaux.
 *
 * @return array<int,array{0:string,1:string}> Liste [ libellé, URL ].
 */
function reseaux(): array {
	$o = \get_option( 'seopress_social_option_name', array() );
	$o = \is_array( $o ) ? $o : array();
	$map = array(
		'seopress_social_accounts_facebook'  => 'Facebook',
		'seopress_social_accounts_instagram' => 'Instagram',
		'seopress_social_accounts_youtube'   => 'YouTube',
		'seopress_social_accounts_linkedin'  => 'LinkedIn',
		'seopress_social_accounts_pinterest' => 'Pinterest',
	);
	$out = array();
	foreach ( $map as $key => $label ) {
		$url = \trim( (string) ( $o[ $key ] ?? '' ) );
		if ( '' !== $url && \wp_http_validate_url( $url ) ) {
			$out[] = array( $label, $url );
		}
	}
	$tw = \trim( (string) ( $o['seopress_social_accounts_twitter'] ?? '' ) );
	if ( '' !== $tw ) {
		$out[] = array( 'X', 0 === \strpos( $tw, 'http' ) ? $tw : 'https://x.com/' . \ltrim( $tw, '@' ) );
	}
	$extra = \preg_split( '/\R/', (string) ( $o['seopress_social_accounts_extra'] ?? '' ) ) ?: array();
	foreach ( $extra as $url ) {
		$url = \trim( $url );
		if ( '' === $url || ! \wp_http_validate_url( $url ) ) {
			continue;
		}
		$host  = (string) \preg_replace( '/^www\./', '', (string) \wp_parse_url( $url, PHP_URL_HOST ) );
		$label = \ucfirst( \explode( '.', $host )[0] );
		if ( \str_contains( $host, 'tiktok' ) ) {
			$label = 'TikTok';
		} elseif ( \preg_match( '/google\.|g\.page|goo\.gl/', $host ) ) {
			$label = 'Google';
		}
		$out[] = array( $label, $url );
	}
	return $out;
}

\add_shortcode(
	'glassauto_reseaux',
	static function (): string {
		$liens = reseaux();
		if ( empty( $liens ) ) {
			return '';
		}
		$nom   = (string) centre_get( 'nom' );
		$items = '';
		foreach ( $liens as [ $label, $url ] ) {
			$items .= \sprintf(
				'<li><a href="%1$s" rel="noopener me" aria-label="%2$s">%3$s</a></li>',
				\esc_url( $url ),
				/* translators: 1 : réseau social, 2 : nom du centre. */
				\esc_attr( \sprintf( \__( '%1$s de %2$s', 'g2rd-glassauto-child' ), $label, $nom ) ),
				\esc_html( $label )
			);
		}
		return '<nav class="ga-social" aria-label="' . \esc_attr__( 'Réseaux sociaux', 'g2rd-glassauto-child' ) . '"><ul class="ga-social__list">' . $items . '</ul></nav>';
	}
);

/* --- Partage des articles (sans script ni cookie) -------------------------- */
\add_filter(
	'render_block_core/post-content',
	static function ( $html ) {
		if ( ! \is_singular( 'post' ) ) {
			return $html;
		}
		$url   = \get_permalink();
		$titre = \wp_strip_all_tags( \get_the_title() );
		if ( ! $url ) {
			return $html;
		}
		$liens = array(
			'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . \rawurlencode( $url ),
			'WhatsApp' => 'https://wa.me/?text=' . \rawurlencode( $titre . ' ' . $url ),
			'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . \rawurlencode( $url ),
			'E-mail'   => 'mailto:?subject=' . \rawurlencode( $titre ) . '&body=' . \rawurlencode( $url ),
		);
		$items = '';
		foreach ( $liens as $label => $href ) {
			$items .= \sprintf(
				'<li><a href="%1$s" rel="noopener nofollow" aria-label="%2$s">%3$s</a></li>',
				\esc_url( $href ),
				/* translators: %s : réseau ou e-mail. */
				\esc_attr( \sprintf( \__( 'Partager cet article : %s', 'g2rd-glassauto-child' ), $label ) ),
				\esc_html( $label )
			);
		}
		return $html . '<aside class="ga-share" aria-label="' . \esc_attr__( 'Partager cet article', 'g2rd-glassauto-child' ) . '"><p class="ga-share__title">' . \esc_html__( 'Partager cet article', 'g2rd-glassauto-child' ) . '</p><ul class="ga-share__list">' . $items . '</ul></aside>';
	}
);

/* --- Corrections au rendu du contenu déjà publié --------------------------- */

// Image du héros : texte alternatif et URL en https.
\add_filter(
	'render_block_core/image',
	static function ( $html ) {
		$html = (string) $html;
		if ( \is_ssl() ) {
			$html = \str_replace( 'src="http://' . \wp_parse_url( \home_url(), PHP_URL_HOST ), 'src="https://' . \wp_parse_url( \home_url(), PHP_URL_HOST ), $html );
		}
		if ( \str_contains( $html, 'hero-suv-pare-brise' ) && \str_contains( $html, 'alt=""' ) ) {
			$html = \str_replace( 'alt=""', 'alt="' . \esc_attr__( 'Remplacement de pare-brise sur un SUV au centre GlassAuto de Vernon', 'g2rd-glassauto-child' ) . '"', $html );
		}
		return $html;
	}
);

// Ancres « En savoir plus » des cartes prestations : intitulé explicite.
\add_filter(
	'render_block_core/paragraph',
	static function ( $html ) {
		$html = (string) $html;
		if ( ! \str_contains( $html, 'ga-link' ) || ! \str_contains( $html, 'En savoir plus' ) ) {
			return $html;
		}
		$labels = array(
			'/prestations/reparation-impact/'             => "Voir la réparation d'impact",
			'/prestations/remplacement-pare-brise/'       => 'Voir le remplacement de pare-brise',
			'/prestations/vitres-laterales/'              => 'Voir les vitres latérales',
			'/prestations/lunette-arriere/'               => 'Voir la lunette arrière',
			'/prestations/vitrages-specifiques/#toit-ouvrant' => 'Voir le toit ouvrant et panoramique',
			'/prestations/vitrages-specifiques/'          => 'Voir les vitrages spécifiques',
			'/prestations/retroviseurs/'                  => 'Voir les rétroviseurs',
			'/prestations/optiques-de-phares/'            => 'Voir les optiques de phares',
			'/prestations/calibrage-adas/'                => 'Voir le calibrage des caméras',
		);
		foreach ( $labels as $href => $label ) {
			$html = \str_replace( '<a href="' . $href . '">En savoir plus</a>', '<a href="' . $href . '">' . \esc_html( $label ) . '</a>', $html );
		}
		return $html;
	}
);

// Services de l'accueil : intitulés en paragraphe (moins de titres, rendu identique).
\add_filter(
	'render_block_core/group',
	static function ( $html, $block ) {
		$html = (string) $html;
		$cls  = (string) ( $block['attrs']['className'] ?? '' );
		if ( ! \str_contains( $cls, 'ga-service__body' ) ) {
			return $html;
		}
		return (string) \preg_replace( '#<h3 class="wp-block-heading">(.*?)</h3>#s', '<p class="ga-service__title">$1</p>', $html );
	},
	10,
	2
);
