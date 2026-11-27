<?php
/**
 * Charte, réglages forcés du parent, assets et petits filtres de rendu.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Fonctionnalités du parent forcées depuis l'enfant, sans toucher au parent.
 *
 * - Mode sombre : la direction validée est claire (fond blanc, gris doux) ;
 *   la bascule du parent réécrirait la palette et les contrastes.
 * - GSAP : activé. Le parent importe GSAP et ScrollTrigger, l'enfant s'en sert
 *   pour les apparitions (assets/js/animations.js), sans second import.
 * - Particules et verre : effets du parent sans usage ici.
 *
 * Désactivable : add_filter( 'rescue18_force_parent_features', '__return_false' ).
 *
 * @param mixed $features Valeur de l'option g2rd_theme_features.
 * @return array<string,mixed>
 */
function force_parent_features( $features ): array {
	$features = \is_array( $features ) ? $features : array();
	if ( \apply_filters( 'rescue18_force_parent_features', true ) ) {
		$features['dark_mode']        = false;
		$features['gsap_animations']  = true; // GSAP et ScrollTrigger du parent : animations d'apparition.
		$features['particles_effect'] = false;
		$features['glass_effect']     = false;
	}
	return $features;
}
\add_filter( 'option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );
\add_filter( 'default_option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );

/**
 * Types de contenu du parent désactivés : portfolio, prestations et
 * qui-sommes-nous n'ont pas de sens sur un média et publieraient des
 * archives concurrentes des pages existantes.
 *
 * @param mixed $cpts Valeur de l'option g2rd_cpt_settings.
 * @return array<string,array<string,mixed>>
 */
function force_parent_cpts( $cpts ): array {
	$cpts = \is_array( $cpts ) ? $cpts : array();
	foreach ( array( 'portfolio', 'prestations', 'qui-sommes-nous' ) as $key ) {
		$cpts[ $key ]            = \is_array( $cpts[ $key ] ?? null ) ? $cpts[ $key ] : array();
		$cpts[ $key ]['enabled'] = false;
	}
	return $cpts;
}
\add_filter( 'option_g2rd_cpt_settings', __NAMESPACE__ . '\force_parent_cpts' );
\add_filter( 'default_option_g2rd_cpt_settings', __NAMESPACE__ . '\force_parent_cpts' );

/**
 * Lit un fichier JSON du thème enfant (mis en cache pour la requête).
 *
 * @param string $rel Chemin relatif au thème enfant.
 * @return array<string,mixed>
 */
function child_json( string $rel ): array {
	static $cache = array();
	if ( ! isset( $cache[ $rel ] ) ) {
		$path          = \get_stylesheet_directory() . '/' . $rel;
		$data          = \is_readable( $path ) ? \wp_json_file_decode( $path, array( 'associative' => true ) ) : null;
		$cache[ $rel ] = \is_array( $data ) ? $data : array();
	}
	return $cache[ $rel ];
}

/**
 * Applique la charte Rescue 18 dès l'activation.
 *
 * - settings : palette, dégradés, polices, tailles, espacements et ombres du
 *   theme.json de l'enfant sont réinjectés après la composition du parent.
 *   Sans eux, les variables --wp--preset--color--r18-* ne sont pas générées
 *   (cache de composition, version du parent) et les contrastes s'effondrent.
 * - styles : la variation styles/rescue18.json (un theme.json d'enfant
 *   n'apporte de façon fiable que des settings, voir CLAUDE.md du parent).
 *
 * @param \WP_Theme_JSON_Data $theme_json Données theme.json du thème.
 * @return \WP_Theme_JSON_Data
 */
function apply_default_variation( $theme_json ) {
	if ( ! \is_object( $theme_json ) || ! \method_exists( $theme_json, 'update_with' ) ) {
		return $theme_json;
	}
	$settings  = child_json( 'theme.json' )['settings'] ?? array();
	$variation = child_json( 'styles/rescue18.json' )['styles'] ?? array();
	$data      = array( 'version' => 3 );
	if ( \is_array( $settings ) && ! empty( $settings ) ) {
		$data['settings'] = \array_intersect_key( $settings, \array_flip( array( 'color', 'typography', 'spacing', 'shadow', 'custom', 'layout' ) ) );
		// Les chemins « file:./ » des polices ne sont résolus que pour le fichier lu par WordPress.
		foreach ( $data['settings']['typography']['fontFamilies'] ?? array() as $i => $family ) {
			foreach ( $family['fontFace'] ?? array() as $j => $face ) {
				$data['settings']['typography']['fontFamilies'][ $i ]['fontFace'][ $j ]['src'] = \array_map(
					static fn( $src ) => \str_starts_with( (string) $src, 'file:./' ) ? \get_stylesheet_directory_uri() . '/' . \substr( (string) $src, 7 ) : $src,
					(array) ( $face['src'] ?? array() )
				);
			}
		}
	}
	if ( \is_array( $variation ) && ! empty( $variation ) ) {
		$data['styles'] = $variation;
	}
	return \count( $data ) > 1 ? $theme_json->update_with( $data ) : $theme_json;
}
\add_filter( 'wp_theme_json_data_theme', __NAMESPACE__ . '\apply_default_variation', 20 );

/**
 * Feuilles de style globales, dans l'ordre de chargement.
 * boutique.css est chargée à part, uniquement sur les pages WooCommerce.
 *
 * @return string[]
 */
function stylesheets(): array {
	return array( 'global.css', 'icons.css', 'header-footer.css', 'accueil.css', 'pages.css' );
}

/**
 * URL d'un fichier du thème enfant, versionnée par sa date de modification.
 *
 * @param string $rel Chemin relatif au thème.
 * @return array{0:string,1:string}|null URL et version, ou null si absent.
 */
function asset( string $rel ): ?array {
	$path = \get_stylesheet_directory() . '/' . $rel;
	if ( ! \is_readable( $path ) ) {
		return null;
	}
	return array( \get_stylesheet_directory_uri() . '/' . $rel, (string) \filemtime( $path ) );
}

/**
 * Supports, traductions et styles de l'éditeur.
 */
\add_action(
	'after_setup_theme',
	static function (): void {
		\load_child_theme_textdomain( TEXTDOMAIN, \get_stylesheet_directory() . '/languages' );
		\add_theme_support( 'woocommerce' );
		\add_theme_support( 'wc-product-gallery-zoom' );
		\add_theme_support( 'wc-product-gallery-lightbox' );
		// L'éditeur affiche les mêmes styles que le front.
		\add_editor_style( \array_map( static fn( string $f ): string => 'assets/css/' . $f, \array_merge( stylesheets(), array( 'boutique.css' ) ) ) );
	}
);

/**
 * Scripts enregistrés une fois ; chargés seulement là où un bloc les demande.
 */
\add_action(
	'init',
	static function (): void {
		$scripts = array(
			'rescue18-scene'        => 'assets/js/scene.js',
			'rescue18-bibliotheque' => 'assets/js/bibliotheque.js',
			'rescue18-quiz'         => 'assets/js/quiz.js',
			'rescue18-podcasts'     => 'assets/js/podcasts.js',
		);
		foreach ( $scripts as $handle => $rel ) {
			$a = asset( $rel );
			if ( null !== $a ) {
				\wp_register_script(
					$handle,
					$a[0],
					array(),
					$a[1],
					array(
						'strategy'  => 'defer',
						'in_footer' => true,
					)
				);
			}
		}
	}
);

/**
 * Styles et script commun du front.
 */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$prev = '';
		foreach ( stylesheets() as $file ) {
			$a = asset( 'assets/css/' . $file );
			if ( null === $a ) {
				continue;
			}
			$handle = 'rescue18-' . \basename( $file, '.css' );
			\wp_enqueue_style( $handle, $a[0], '' !== $prev ? array( $prev ) : array(), $a[1] );
			$prev = $handle;
		}

		if ( \function_exists( 'is_woocommerce' ) && ( \is_woocommerce() || \is_cart() || \is_checkout() || \is_account_page() ) ) {
			$a = asset( 'assets/css/boutique.css' );
			if ( null !== $a ) {
				\wp_enqueue_style( 'rescue18-boutique', $a[0], array( $prev ), $a[1] );
			}
		}

		$vis = asset( 'assets/js/visionneuse.js' );
		if ( null !== $vis && \is_singular( array( 'post', 'page', 'r18_document', 'r18_texte' ) ) ) {
			\wp_enqueue_script( 'rescue18-visionneuse', $vis[0], array(), $vis[1], array( 'strategy' => 'defer', 'in_footer' => true ) );
		}

		$js = asset( 'assets/js/rescue18.js' );
		if ( null !== $js ) {
			\wp_enqueue_script(
				'rescue18',
				$js[0],
				array(),
				$js[1],
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	},
	20
);

/**
 * Préchargement de la police et du casque (LCP du hero d'accueil).
 */
\add_action(
	'wp_head',
	static function (): void {
		$font = asset( 'assets/fonts/inter-var.woff2' );
		if ( null !== $font ) {
			\printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin fetchpriority="high">' . "\n", \esc_url( $font[0] ) );
		}
		if ( \is_front_page() ) {
			$img = asset( 'assets/img/casque-rescue18.webp' );
			if ( null !== $img ) {
				\printf( '<link rel="preload" href="%s" as="image" type="image/webp" fetchpriority="high">' . "\n", \esc_url( $img[0] ) );
			}
		}
	},
	2
);

/**
 * Rendus de blocs natifs : un seul callback pour le hook render_block.
 *
 * - Groupe « r18-stage » (scène du casque) : charge scene.js à la demande.
 * - Image « r18-helmet » : image LCP, jamais en lazy-load, décorative.
 * - Logo absent : logotype typographique RESCUE 18 de la maquette.
 *
 * @param string               $html  Rendu du bloc.
 * @param array<string,mixed>  $block Bloc analysé.
 * @return string
 */
function render_block_filters( $html, $block ) {
	$html  = (string) $html;
	$name  = $block['blockName'] ?? '';
	$class = (string) ( $block['attrs']['className'] ?? '' );

	if ( 'core/group' === $name && \str_contains( $class, 'r18-stage' ) && ! \is_admin() ) {
		\wp_enqueue_script( 'rescue18-scene' );
	}

	if ( 'core/image' === $name && \str_contains( $class, 'r18-helmet' ) ) {
		$html = \str_replace( array( ' loading="lazy"', 'decoding="async"' ), array( '', 'decoding="sync"' ), $html );
		$html = \str_replace( '<img ', '<img fetchpriority="high" draggable="false" ', $html );
	}

	if ( 'core/site-logo' === $name ) {
		$html = logo( $html );
	}

	return $html;
}
\add_filter( 'render_block', __NAMESPACE__ . '\render_block_filters', 10, 2 );

/**
 * Logo : tant qu'aucun logo n'est défini dans Identité du site, le bloc
 * core/site-logo ne rend rien. On affiche alors le logotype typographique de
 * officiel Rescue 18 livré avec le thème (version claire et version blanche). Dès qu'un logo
 * est déposé dans Identité du site, WordPress reprend la main.
 *
 * @param string $html Rendu du bloc.
 * @return string
 */
function logo( string $html ): string {
	$name  = \get_bloginfo( 'name' );
	$name  = '' !== $name ? $name : 'Rescue 18';
	/* translators: %s : nom du site. */
	$label = \sprintf( \__( '%s, retour à l\'accueil', 'g2rd-rescue18-child' ), $name );

	if ( '' === \trim( $html ) ) {
		$img = static fn( string $f, string $c ): string => '<img class="r18-brand__img ' . $c . '" src="' . \esc_url( \get_stylesheet_directory_uri() . '/assets/img/' . $f ) . '" width="560" height="111" alt="" decoding="async">';
		return \sprintf(
			'<div class="wp-block-site-logo r18-brand"><a href="%1$s" class="r18-brand__link" rel="home" aria-label="%2$s">%3$s%4$s</a></div>',
			\esc_url( \home_url( '/' ) ),
			\esc_attr( $label ),
			$img( 'logo-rescue18.png', 'is-clair' ),
			$img( 'logo-rescue18-blanc.png', 'is-sombre' )
		);
	}

	$html = \str_replace( 'alt=""', 'alt="' . \esc_attr( $name ) . '"', $html );
	if ( ! \str_contains( $html, 'aria-label=' ) ) {
		$html = \str_replace( 'class="custom-logo-link"', 'class="custom-logo-link" aria-label="' . \esc_attr( $label ) . '"', $html );
	}
	return $html;
}

/**
 * Classe de corps : garde des sélecteurs du thème.
 */
\add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'r18';
		return $classes;
	}
);

/**
 * Styles de blocs et catégories de compositions.
 *
 * Boutons : rouge (défaut via la variation), sombre, blanc, lien fléché.
 * Groupes : carte claire, section sombre.
 */
\add_action(
	'init',
	static function (): void {
		$registry = \WP_Block_Styles_Registry::get_instance();
		$styles   = array(
			'core/button' => array(
				'r18-sombre' => \__( 'Sombre', 'g2rd-rescue18-child' ),
				'r18-blanc'  => \__( 'Blanc', 'g2rd-rescue18-child' ),
				'r18-lien'   => \__( 'Lien fléché', 'g2rd-rescue18-child' ),
			),
			'core/group'  => array(
				'r18-carte'  => \__( 'Carte Rescue 18', 'g2rd-rescue18-child' ),
				'r18-sombre' => \__( 'Section sombre Rescue 18', 'g2rd-rescue18-child' ),
			),
		);
		foreach ( $styles as $block => $list ) {
			foreach ( $list as $name => $label ) {
				if ( ! $registry->is_registered( $block, $name ) ) {
					\register_block_style(
						$block,
						array(
							'name'  => $name,
							'label' => $label,
						)
					);
				}
			}
		}

		\register_block_pattern_category( 'rescue18', array( 'label' => \__( 'Rescue 18 — Sections', 'g2rd-rescue18-child' ) ) );
		\register_block_pattern_category( 'rescue18-pages', array( 'label' => \__( 'Rescue 18 — Pages', 'g2rd-rescue18-child' ) ) );
	}
);

/**
 * Nettoyage de l'en-tête HTML (balises inutiles).
 */
\remove_action( 'wp_head', 'wp_generator' );
\remove_action( 'wp_head', 'wlwmanifest_link' );
\remove_action( 'wp_head', 'rsd_link' );

/* Apparitions animées : GSAP et ScrollTrigger importés par le thème parent
   (poignées gsap et scrolltrigger), après leur enregistrement par le parent. */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$anim = asset( 'assets/js/animations.js' );
		if ( null !== $anim && \wp_script_is( 'scrolltrigger', 'registered' ) ) {
			\wp_enqueue_script( 'rescue18-animations', $anim[0], array( 'gsap', 'scrolltrigger' ), $anim[1], array( 'in_footer' => true ) );
		}
	},
	50
);

/* Icône du site : logo rond Rescue 18 tant qu'aucune icône n'est définie dans Identité du site. */
\add_action(
	'wp_head',
	static function (): void {
		if ( \has_site_icon() ) {
			return;
		}
		$u = \get_stylesheet_directory_uri() . '/assets/img/';
		echo '<link rel="icon" href="' . \esc_url( $u . 'r18-icone-32.png' ) . '" sizes="32x32">' . "\n";
		echo '<link rel="icon" href="' . \esc_url( $u . 'r18-icone-512.png' ) . '" sizes="512x512">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . \esc_url( $u . 'r18-icone-180.png' ) . '">' . "\n";
	},
	99
);
