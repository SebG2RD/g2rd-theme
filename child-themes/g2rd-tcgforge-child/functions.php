<?php
/**
 * G2RD — TCGForge : thème enfant de la boutique TCGForge.
 *
 * Rôles de ce fichier :
 *  1. Charger les modules du thème enfant (inc/).
 *  2. Déclarer le support WooCommerce et les styles d'éditeur.
 *  3. Appliquer la charte TCGForge par défaut (styles/tcgforge.json).
 *  4. Charger les feuilles de style et le script du front.
 *
 * Aucune intégration fournisseur ici : flux produits, stocks, prix,
 * commandes fournisseurs et suivi sont gérés hors du thème.
 *
 * @package G2RD_TCGForge
 * @license EUPL-1.2
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

const VERSION = '1.2.1';

require_once __DIR__ . '/inc/tokens.php';
require_once __DIR__ . '/inc/product-data.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/withdrawal.php';

/**
 * Le site est sombre par nature : le mode sombre du thème parent (bascule
 * flottante + réécriture des variables de palette) n'a pas de sens ici et
 * casserait les contrastes. On le coupe depuis l'enfant, sans toucher au parent.
 *
 * Désactivable : add_filter( 'tcgforge_disable_parent_dark_mode', '__return_false' );
 *
 * @param mixed $features Valeur de l'option g2rd_theme_features.
 * @return array<string,mixed>
 */
function force_parent_features( $features ): array {
	$features = \is_array( $features ) ? $features : array();
	if ( \apply_filters( 'tcgforge_disable_parent_dark_mode', true ) ) {
		$features['dark_mode'] = false;
	}
	return $features;
}
\add_filter( 'option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );
\add_filter( 'default_option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );

/**
 * Supports du thème.
 */
\add_action(
	'after_setup_theme',
	function (): void {
		\load_child_theme_textdomain( 'g2rd-tcgforge-child', \get_stylesheet_directory() . '/languages' );

		// WooCommerce : templates blocs + galerie produit.
		\add_theme_support( 'woocommerce' );
		\add_theme_support( 'wc-product-gallery-zoom' );
		\add_theme_support( 'wc-product-gallery-lightbox' );
		\add_theme_support( 'wc-product-gallery-slider' );

		// L'éditeur affiche les mêmes styles que le front.
		\add_editor_style(
			array(
				'assets/css/global.css',
				'assets/css/header-footer.css',
				'assets/css/home.css',
				'assets/css/catalog.css',
				'assets/css/product.css',
				'assets/css/tcg.css',
				'assets/css/pages.css',
				'assets/css/woocommerce.css',
			)
		);
	}
);

/**
 * Applique la charte TCGForge sans action manuelle.
 *
 * Le parent recompose theme.json (settings + styles + variations) en priorité 10.
 * Un theme.json d'enfant n'apporte de façon fiable que des `settings` : les
 * `styles` de la charte vivent donc dans styles/tcgforge.json. On les fusionne
 * ici, après le parent, pour que le site soit sombre dès l'activation. La
 * variation reste disponible dans le sélecteur de styles de l'éditeur.
 *
 * @param \WP_Theme_JSON_Data $theme_json Données theme.json du thème.
 * @return \WP_Theme_JSON_Data
 */
function apply_default_variation( $theme_json ) {
	static $variation = null;

	if ( null === $variation ) {
		$variation = array();
		$path      = \get_stylesheet_directory() . '/styles/tcgforge.json';
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
 * Indique si une feuille doit être chargée sur la requête en cours.
 */
function is_store_context(): bool {
	if ( ! \function_exists( 'is_woocommerce' ) ) {
		return false;
	}
	return \is_woocommerce() || \is_cart() || \is_checkout() || \is_account_page();
}

/**
 * Feuilles de style du front.
 *
 * global + header-footer + tcg + catalog partout (les cartes produit et les
 * badges apparaissent sur l'accueil, les pages de licence, la fiche produit et
 * le panier). Le reste est chargé selon la page.
 */
\add_action(
	'wp_enqueue_scripts',
	function (): void {
		$uri = \get_stylesheet_directory_uri();
		$dir = \get_stylesheet_directory();

		$css = static function ( string $handle, string $file, array $deps = array() ) use ( $uri, $dir ): void {
			$path = $dir . '/assets/css/' . $file;
			if ( \is_readable( $path ) ) {
				\wp_enqueue_style( $handle, $uri . '/assets/css/' . $file, $deps, (string) \filemtime( $path ) );
			}
		};

		$css( 'tcgforge-global', 'global.css' );
		\wp_add_inline_style( 'tcgforge-global', tokens_css() );

		$css( 'tcgforge-header-footer', 'header-footer.css', array( 'tcgforge-global' ) );
		$css( 'tcgforge-tcg', 'tcg.css', array( 'tcgforge-global' ) );
		$css( 'tcgforge-catalog', 'catalog.css', array( 'tcgforge-global', 'tcgforge-tcg' ) );

		if ( \is_front_page() ) {
			$css( 'tcgforge-home', 'home.css', array( 'tcgforge-global' ) );
		} else {
			$css( 'tcgforge-pages', 'pages.css', array( 'tcgforge-global' ) );
		}

		if ( \class_exists( 'WooCommerce' ) ) {
			// Notices, formulaires, mini-panier : présents sur toutes les pages.
			$css( 'tcgforge-woocommerce', 'woocommerce.css', array( 'tcgforge-global' ) );

			if ( \function_exists( 'is_product' ) && \is_product() ) {
				$css( 'tcgforge-product', 'product.css', array( 'tcgforge-global', 'tcgforge-catalog' ) );
			}
		}

		$js = $dir . '/assets/js/tcgforge.js';
		if ( \is_readable( $js ) ) {
			\wp_enqueue_script(
				'tcgforge',
				$uri . '/assets/js/tcgforge.js',
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
 * Préchargement de la police des titres (LCP du hero).
 */
\add_action(
	'wp_head',
	function (): void {
		$font = \get_stylesheet_directory() . '/assets/fonts/plus-jakarta-sans-var.woff2';
		if ( \is_readable( $font ) ) {
			\printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				\esc_url( \get_stylesheet_directory_uri() . '/assets/fonts/plus-jakarta-sans-var.woff2' )
			);
		}
	},
	2
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
		$classes[] = 'tcgf';
		if ( \function_exists( 'is_product' ) && \is_product() && is_preorder( \get_queried_object_id() ) ) {
			$classes[] = 'tcgf-product-preorder';
		}
		return $classes;
	}
);

/**
 * Logo : tant qu'aucun logo n'est défini dans Réglages › Identité du site,
 * le bloc core/site-logo ne rend rien. On affiche alors le logo TCGForge
 * livré avec le thème. Dès qu'un logo est choisi, WordPress reprend la main.
 *
 * @param string $html Rendu du bloc.
 * @return string
 */
\add_filter(
	'render_block_core/site-logo',
	static function ( $html ) {
		$html = (string) $html;
		$name = \get_bloginfo( 'name' );
		$name = '' !== $name ? $name : 'TCGForge';
		/* translators: %s : nom du site. */
		$label = \sprintf( \__( '%s, retour à l\'accueil', 'g2rd-tcgforge-child' ), $name );

		if ( '' === \trim( $html ) ) {
			$src = \get_stylesheet_directory_uri() . '/assets/img/logo-tcgforge.svg';
			return \sprintf(
				'<div class="wp-block-site-logo tcgf-logo"><a href="%1$s" class="custom-logo-link" rel="home" aria-label="%2$s"><img src="%3$s" class="custom-logo" alt="%4$s" width="172" height="36" decoding="async" /></a></div>',
				\esc_url( \home_url( '/' ) ),
				\esc_attr( $label ),
				\esc_url( $src ),
				\esc_attr( $name )
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
 * Compte à rebours : le bloc g2rd/countdown ne s'affiche que si une vraie
 * date future est renseignée. Une date vide, invalide ou passée retire le
 * bloc du rendu. Aucun compte à rebours fictif possible.
 *
 * @param string               $html  Rendu du bloc.
 * @param array<string,mixed>  $block Bloc analysé.
 * @return string
 */
\add_filter(
	'render_block_g2rd/countdown',
	static function ( $html, $block ) {
		$end = isset( $block['attrs']['endDate'] ) ? (string) $block['attrs']['endDate'] : '';
		if ( '' === $end ) {
			return '';
		}
		try {
			$date = new \DateTimeImmutable( $end, \wp_timezone() );
		} catch ( \Exception $e ) {
			return '';
		}
		return $date->getTimestamp() > \time() ? (string) $html : '';
	},
	10,
	2
);

/**
 * Catégories de patterns.
 */
\add_action(
	'init',
	function (): void {
		\register_block_pattern_category( 'tcgforge', array( 'label' => \__( 'TCGForge — Sections', 'g2rd-tcgforge-child' ) ) );
		\register_block_pattern_category( 'tcgforge-pages', array( 'label' => \__( 'TCGForge — Pages', 'g2rd-tcgforge-child' ) ) );
		\register_block_pattern_category( 'tcgforge-boutique', array( 'label' => \__( 'TCGForge — Boutique', 'g2rd-tcgforge-child' ) ) );
	}
);
