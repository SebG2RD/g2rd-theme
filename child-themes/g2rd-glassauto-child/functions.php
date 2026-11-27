<?php
/**
 * G2RD — GlassAuto Vernon : thème enfant du centre GlassAuto de Vernon.
 *
 * Rôles de ce fichier :
 *  1. Charger les modules du thème enfant (inc/).
 *  2. Neutraliser ce qui, dans le parent, n'a pas de sens ici (mode sombre,
 *     types de contenu « portfolio » et « prestations » dont l'archive
 *     entrerait en conflit avec la page Prestations).
 *  3. Appliquer la charte GlassAuto par défaut (styles/glassauto.json).
 *  4. Charger les feuilles de style et le script du front, et les mêmes
 *     styles dans l'éditeur.
 *
 * Le portail des apporteurs d'affaires, la réservation et les commissions
 * sont dans l'extension G2RD Business Connect, jamais dans le thème.
 *
 * @package G2RD_GlassAuto
 * @license EUPL-1.2
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

const VERSION    = '0.1.0';
const TEXTDOMAIN = 'g2rd-glassauto-child';

require_once __DIR__ . '/inc/centre.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/seo-extras.php';
require_once __DIR__ . '/inc/forms.php';
require_once __DIR__ . '/inc/perf.php';
require_once __DIR__ . '/inc/tracking.php';

if ( \defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once __DIR__ . '/inc/class-cli-command.php';
}

/**
 * Fonctionnalités du parent forcées depuis l'enfant, sans toucher au parent.
 *
 * - Mode sombre : la charte GlassAuto est claire par nature ; la bascule du
 *   parent réécrirait la palette et casserait les contrastes validés.
 * - GSAP et particules : aucun usage sur ce site, on évite un CDN inutile.
 *
 * Désactivable : add_filter( 'glassauto_force_parent_features', '__return_false' ).
 *
 * @param mixed $features Valeur de l'option g2rd_theme_features.
 * @return array<string,mixed>
 */
function force_parent_features( $features ): array {
	$features = \is_array( $features ) ? $features : array();
	if ( \apply_filters( 'glassauto_force_parent_features', true ) ) {
		$features['dark_mode']        = false;
		$features['gsap_animations']  = false;
		$features['particles_effect'] = false;
		$features['glass_effect']     = false;
	}
	return $features;
}
\add_filter( 'option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );
\add_filter( 'default_option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );

/**
 * Types de contenu du parent désactivés.
 *
 * Le CPT « prestations » du parent publie une archive sur /prestations/, qui
 * entrerait en conflit avec la page Prestations du site. Les prestations
 * GlassAuto sont des pages (une par vitrage), pas des entrées de CPT.
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
 * Feuilles de style du thème, dans l'ordre de chargement.
 *
 * @return string[]
 */
function stylesheets(): array {
	return array( 'global.css', 'icons.css', 'header-footer.css', 'sections.css' );
}

/**
 * Supports du thème et styles de l'éditeur.
 */
\add_action(
	'after_setup_theme',
	function (): void {
		\load_child_theme_textdomain( TEXTDOMAIN, \get_stylesheet_directory() . '/languages' );

		// L'éditeur affiche les mêmes styles que le front.
		\add_editor_style( \array_map( static fn( string $f ): string => 'assets/css/' . $f, stylesheets() ) );
	}
);

/**
 * Applique la charte GlassAuto sans action manuelle.
 *
 * Le parent recompose theme.json (settings + styles + variations) en priorité 10.
 * Un theme.json d'enfant n'apporte de façon fiable que des `settings` : les
 * `styles` de la charte vivent donc dans styles/glassauto.json. On les fusionne
 * ici, après le parent, pour que la charte soit active dès l'activation du
 * thème. La variation reste disponible dans le sélecteur de styles.
 *
 * @param \WP_Theme_JSON_Data $theme_json Données theme.json du thème.
 * @return \WP_Theme_JSON_Data
 */
function apply_default_variation( $theme_json ) {
	static $variation = null;

	if ( null === $variation ) {
		$variation = array();
		$path      = \get_stylesheet_directory() . '/styles/glassauto.json';
		if ( \is_readable( $path ) ) {
			$data = \wp_json_file_decode( $path, array( 'associative' => true ) );
			if ( \is_array( $data ) && isset( $data['styles'] ) && \is_array( $data['styles'] ) ) {
				$variation = $data['styles'];
			}
		}
	}

	if ( empty( $variation ) || ! \is_object( $theme_json ) || ! \method_exists( $theme_json, 'update_with' ) ) {
		return $theme_json;
	}

	return $theme_json->update_with(
		array(
			'version' => 3,
			'styles'  => $variation,
		)
	);
}
\add_filter( 'wp_theme_json_data_theme', __NAMESPACE__ . '\apply_default_variation', 20 );

/**
 * Styles de blocs de la charte.
 *
 * Les groupes « section-dark », « card », « card-dark » et « card-action »
 * sont déjà enregistrés par le parent (BlockStyles::registerSectionStyles) et
 * restylés par styles/glassauto.json. On n'enregistre ici que les styles de
 * bouton propres à GlassAuto ; « outline » existe dans le cœur.
 */
\add_action(
	'init',
	function (): void {
		$styles   = array(
			'rdv'           => \__( 'Prendre rendez-vous (fuchsia)', 'g2rd-glassauto-child' ),
			'turquoise'     => \__( 'Turquoise', 'g2rd-glassauto-child' ),
			'contour-clair' => \__( 'Contour clair (fond sombre)', 'g2rd-glassauto-child' ),
			'blanc'         => \__( 'Blanc (bandeau turquoise)', 'g2rd-glassauto-child' ),
		);
		$registry = \WP_Block_Styles_Registry::get_instance();
		foreach ( $styles as $name => $label ) {
			if ( ! $registry->is_registered( 'core/button', $name ) ) {
				\register_block_style(
					'core/button',
					array(
						'name'  => $name,
						'label' => $label,
					)
				);
			}
		}
	}
);

/**
 * Feuilles de style et script du front.
 *
 * Tout est chargé sur toutes les pages : les sections (hero, prestations,
 * FAQ, zone…) sont des blocs natifs portés par des classes, présents sur la
 * plupart des pages, et le total reste léger. Les ressources du portail
 * partenaires sont chargées par l'extension métier, uniquement sur ses pages.
 */
\add_action(
	'wp_enqueue_scripts',
	function (): void {
		$uri  = \get_stylesheet_directory_uri();
		$dir  = \get_stylesheet_directory();
		$prev = '';

		// Production : une seule feuille minifiée (assets/css/glassauto.min.css,
		// régénérée par bin/build-css.sh). SCRIPT_DEBUG garde les 4 sources.
		$min = $dir . '/assets/css/glassauto.min.css';
		if ( ! ( \defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ) && \is_readable( $min ) ) {
			\wp_enqueue_style( 'glassauto-sections', $uri . '/assets/css/glassauto.min.css', array(), (string) \filemtime( $min ) );
		}

		foreach ( \wp_style_is( 'glassauto-sections', 'enqueued' ) ? array() : stylesheets() as $file ) {
			$path = $dir . '/assets/css/' . $file;
			if ( ! \is_readable( $path ) ) {
				continue;
			}
			$handle = 'glassauto-' . \basename( $file, '.css' );
			\wp_enqueue_style( $handle, $uri . '/assets/css/' . $file, '' !== $prev ? array( $prev ) : array(), (string) \filemtime( $path ) );
			$prev = $handle;
		}

		$js = $dir . '/assets/js/glassauto.js';
		if ( \is_readable( $js ) ) {
			\wp_enqueue_script(
				'glassauto',
				$uri . '/assets/js/glassauto.js',
				array(),
				(string) \filemtime( $js ),
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
 * Préchargement des polices réellement utilisées (LCP du hero, surtout sur mobile) :
 * titres (outfit-latin, servie sous le nom « Dunbar Tall ») et texte (Roboto Flex).
 */
\add_action(
	'wp_head',
	function (): void {
		foreach ( [ 'outfit-latin.woff2', 'roboto-flex-latin.woff2' ] as $file ) {
			if ( \is_readable( \get_stylesheet_directory() . '/assets/fonts/' . $file ) ) {
				\printf(
					'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
					\esc_url( \get_stylesheet_directory_uri() . '/assets/fonts/' . $file )
				);
			}
		}
	},
	2
);

/**
 * Retire le préchargement des polices du thème parent (Inter, Plus Jakarta Sans) :
 * le thème enfant ne les utilise pas, elles retardaient l'affichage sur mobile.
 */
\add_action(
	'wp_head',
	function (): void {
		global $wp_filter;
		if ( empty( $wp_filter['wp_head'] ) || ! isset( $wp_filter['wp_head']->callbacks[2] ) ) {
			return;
		}
		foreach ( $wp_filter['wp_head']->callbacks[2] as $cb ) {
			$fn = $cb['function'] ?? null;
			if ( is_array( $fn ) && is_object( $fn[0] ) && 'addPreloadLinks' === ( $fn[1] ?? '' ) && false !== strpos( get_class( $fn[0] ), 'ThemeSetup' ) ) {
				\remove_action( 'wp_head', $fn, 2 );
			}
		}
	},
	1
);

/**
 * Classe de corps : sert de garde aux sélecteurs du thème.
 *
 * @param string[] $classes Classes du body.
 * @return string[]
 */
\add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'ga';
		return $classes;
	}
);

/**
 * Logo : tant qu'aucun logo n'est défini dans Réglages › Identité du site,
 * le bloc core/site-logo ne rend rien. On affiche alors le logotype
 * typographique provisoire (« GlassAuto. Vernon »), identique à la maquette.
 * Dès que le fichier vectoriel officiel est déposé, WordPress reprend la main.
 *
 * @param string $html Rendu du bloc.
 * @return string
 */
\add_filter(
	'render_block_core/site-logo',
	static function ( $html ) {
		$html = (string) $html;
		$name = \get_bloginfo( 'name' );
		$name = '' !== $name ? $name : 'GlassAuto Vernon';
		/* translators: %s : nom du site. */
		$label = \sprintf( \__( '%s, retour à l\'accueil', 'g2rd-glassauto-child' ), $name );

		if ( '' === \trim( $html ) ) {
			return \sprintf(
				'<div class="wp-block-site-logo ga-logo"><a href="%1$s" class="ga-logo__link" rel="home" aria-label="%2$s"><span class="ga-logo__word" aria-hidden="true">GlassAuto<span class="ga-dot">.</span></span><span class="ga-logo__loc" aria-hidden="true">Vernon</span></a></div>',
				\esc_url( \home_url( '/' ) ),
				\esc_attr( $label )
			);
		}

		// Logo de la médiathèque sans texte alternatif : on garantit un intitulé.
		$html = \str_replace( 'alt=""', 'alt="' . \esc_attr( $name ) . '"', $html );
		if ( false === \strpos( $html, 'aria-label=' ) ) {
			$html = \str_replace( 'class="custom-logo-link"', 'class="custom-logo-link" aria-label="' . \esc_attr( $label ) . '"', $html );
		}
		return $html;
	}
);

/**
 * Catégories de compositions.
 */
\add_action(
	'init',
	function (): void {
		\register_block_pattern_category( 'glassauto', array( 'label' => \__( 'GlassAuto — Sections', 'g2rd-glassauto-child' ) ) );
		\register_block_pattern_category( 'glassauto-pages', array( 'label' => \__( 'GlassAuto — Pages', 'g2rd-glassauto-child' ) ) );
	}
);
