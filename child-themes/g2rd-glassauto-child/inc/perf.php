<?php
/**
 * Performance du site public.
 *
 *  - Ultimate Member charge ses feuilles de style et scripts (FontAwesome,
 *    jQuery UI, modale…) sur toutes les pages : on ne les garde que sur ses
 *    propres pages (connexion, compte, mot de passe, profil) et sur le
 *    portail partenaires, qui en dépend pour la connexion.
 *  - Les photos du thème sont servies avec un srcset (variantes 800 et
 *    1200 px générées à côté de l'original) ajouté au rendu du bloc Image :
 *    le markup enregistré dans l'éditeur reste celui du cœur.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

/**
 * La requête courante a-t-elle besoin d'Ultimate Member ?
 */
function page_needs_um(): bool {
	if ( \is_admin() || ( \is_user_logged_in() && \current_user_can( 'edit_posts' ) ) ) {
		return true; // Édition, aperçus : on ne touche à rien.
	}
	if ( ! \is_singular() ) {
		return false;
	}
	$post = \get_post();
	if ( ! $post ) {
		return false;
	}
	// Une extension (G2RD Business Connect) peut déclarer qu'une page a besoin d'UM.
	if ( \apply_filters( 'g2rd_glassauto_page_needs_um', false, $post ) ) {
		return true;
	}
	// Pages du portail partenaires (gabarit du plugin métier).
	if ( 'portal' === \get_page_template_slug( $post ) ) {
		return true;
	}
	// Pages cœur d'Ultimate Member (connexion, compte, mot de passe, profil, inscription, membres).
	if ( \function_exists( 'um_is_core_page' ) ) {
		foreach ( array( 'login', 'account', 'password-reset', 'user', 'register', 'members', 'logout' ) as $page ) {
			if ( \um_is_core_page( $page ) ) {
				return true;
			}
		}
	}
	// Shortcodes ou blocs UM dans le contenu.
	$content = (string) $post->post_content;
	return \has_shortcode( $content, 'ultimatemember' ) || \has_shortcode( $content, 'ultimatemember_login' ) || \has_shortcode( $content, 'ultimatemember_account' ) || \has_shortcode( $content, 'ultimatemember_password' ) || false !== \strpos( $content, 'wp:um-block' );
}

\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( page_needs_um() ) {
			return;
		}
		foreach ( \wp_styles()->queue as $handle ) {
			if ( 0 === \strpos( $handle, 'um_' ) || 0 === \strpos( $handle, 'um-' ) ) {
				\wp_dequeue_style( $handle );
			}
		}
		foreach ( \wp_scripts()->queue as $handle ) {
			if ( 0 === \strpos( $handle, 'um_' ) || 0 === \strpos( $handle, 'um-' ) ) {
				\wp_dequeue_script( $handle );
			}
		}
	},
	200
);

// Modale UM injectée dans le pied de page (lien « javascript:void(0) » non explorable).
\add_action(
	'wp',
	static function (): void {
		if ( page_needs_um() || ! \function_exists( 'UM' ) ) {
			return;
		}
		$modal = \UM()->classes['um\frontend\modal'] ?? null;
		if ( \is_object( $modal ) ) {
			\remove_action( 'wp_footer', array( $modal, 'load_modal_content' ), (int) \apply_filters( 'um_core_includes_modals_priority', 9 ) );
		}
	},
	20
);

/**
 * Ajoute srcset/sizes aux images du thème qui ont des variantes.
 *
 * @param string $html Rendu du bloc.
 * @return string
 */
function image_srcset( string $html ): string {
	$dir_uri = \get_stylesheet_directory_uri() . '/assets/img/photos/';
	$dir     = \get_stylesheet_directory() . '/assets/img/photos/';
	if ( false === \strpos( $html, $dir_uri ) || false !== \strpos( $html, 'srcset=' ) ) {
		return $html;
	}
	return (string) \preg_replace_callback(
		'#<img([^>]*?)\ssrc="' . \preg_quote( $dir_uri, '#' ) . '([a-z0-9-]+)\.webp"([^>]*)>#',
		static function ( array $m ) use ( $dir, $dir_uri ): string {
			$name   = $m[2];
			$srcset = array();
			foreach ( array( 800, 1200 ) as $w ) {
				if ( \is_readable( $dir . $name . '-' . $w . '.webp' ) ) {
					$srcset[] = $dir_uri . $name . '-' . $w . '.webp ' . $w . 'w';
				}
			}
			if ( empty( $srcset ) ) {
				return $m[0];
			}
			$size     = \getimagesize( $dir . $name . '.webp' );
			$srcset[] = $dir_uri . $name . '.webp ' . ( $size ? (int) $size[0] : 1600 ) . 'w';
			$full     = false !== \strpos( $m[1] . $m[3], 'ga-hero' ) || false !== \strpos( $m[1] . $m[3], 'alignfull' );
			$sizes    = $full ? '100vw' : '(max-width: 760px) 100vw, (max-width: 1312px) 50vw, 656px';
			return '<img' . $m[1] . ' src="' . $dir_uri . $name . '.webp" srcset="' . \esc_attr( \implode( ', ', $srcset ) ) . '" sizes="' . \esc_attr( $sizes ) . '"' . $m[3] . '>';
		},
		$html
	);
}
\add_filter( 'render_block_core/image', __NAMESPACE__ . '\image_srcset', 20 );
\add_filter( 'render_block_core/cover', __NAMESPACE__ . '\image_srcset', 20 );

/**
 * Le bloc Navigation de l'en-tête pointe toujours vers « Navigation
 * principale » (créée par l'installateur). Sans cela, WordPress prend la
 * navigation la plus récente, ou en fabrique une de secours à partir de la
 * liste des pages (menu alphabétique « Accessibilité, Accueil, Actualités… »).
 *
 * @param array<string,mixed> $parsed Bloc analysé.
 * @return array<string,mixed>
 */
function navigation_ref( array $parsed ): array {
	if ( 'core/navigation' !== ( $parsed['blockName'] ?? '' ) || ! empty( $parsed['attrs']['ref'] ) ) {
		return $parsed;
	}
	if ( false === \strpos( (string) ( $parsed['attrs']['className'] ?? '' ), 'ga-nav' ) ) {
		return $parsed;
	}
	static $ref = null;
	if ( null === $ref ) {
		$found = \get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'name'           => 'navigation-principale',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$ref   = empty( $found ) ? 0 : (int) $found[0];
	}
	if ( $ref > 0 ) {
		$parsed['attrs']['ref'] = $ref;
	}
	return $parsed;
}
\add_filter( 'render_block_data', __NAMESPACE__ . '\navigation_ref', 10 );
