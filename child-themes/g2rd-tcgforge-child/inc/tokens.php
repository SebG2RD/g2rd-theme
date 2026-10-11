<?php
/**
 * Tokens graphiques TCGForge.
 *
 * Source : tcgForge.json, à la racine du thème PARENT (seul fichier ajouté au
 * parent). Les valeurs sont converties en custom properties :
 *   - couleurs  → --wp--preset--color--tcgf-{nom} (mêmes slugs que theme.json)
 *   - le reste  → --tcgf-{groupe}-{nom}
 *
 * Si tcgForge.json est absent ou invalide (le GitHub Updater remplace tout le
 * dossier du parent à chaque mise à jour), on retombe sur des valeurs
 * identiques : le rendu ne change pas.
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

/**
 * Valeurs de repli, identiques à tcgForge.json et à theme.json.
 *
 * @return array<string,array<string,string>>
 */
function default_tokens(): array {
	return array(
		'colors'     => array(
			'noir'         => '#09090B',
			'surface'      => '#18181B',
			'surface2'     => '#27272A',
			'bordure'      => '#3F3F46',
			'bordureForte' => '#71717A',
			'blanc'        => '#FAFAFA',
			'attenue'      => '#A1A1AA',
			'violet'       => '#7C3AED',
			'violetFonce'  => '#6D28D9',
			'violetClair'  => '#A855F7',
			'lien'         => '#C084FC',
			'or'           => '#F59E0B',
			'orFonce'      => '#B45309',
			'succes'       => '#4ADE80',
			'danger'       => '#F87171',
		),
		'layout'     => array(
			'contentWidth'   => '760px',
			'wideWidth'      => '1320px',
			'sectionSpacing' => 'clamp(3.5rem, 8vw, 6.5rem)',
			'gutter'         => 'clamp(1rem, 4vw, 2.5rem)',
			'headerHeight'   => '4.25rem',
		),
		'radius'     => array(
			'xs'    => '4px',
			's'     => '8px',
			'm'     => '12px',
			'l'     => '18px',
			'carte' => '4.5% / 3.2%',
			'full'  => '999px',
		),
		'typography' => array(
			'display'       => 'clamp(2.5rem, 7vw, 5.25rem)',
			'h1'            => 'clamp(2.1rem, 5vw, 3.5rem)',
			'h2'            => 'clamp(1.65rem, 3.6vw, 2.6rem)',
			'h3'            => 'clamp(1.2rem, 2vw, 1.5rem)',
			'body'          => '1.0625rem',
			'small'         => '0.9rem',
			'meta'          => '0.8rem',
			'trackingTitre' => '-0.025em',
			'trackingLabel' => '0.04em',
		),
		'animation'  => array(
			'fast'   => '0.16s',
			'base'   => '0.28s',
			'easing' => 'cubic-bezier(0.2, 0.8, 0.2, 1)',
		),
	);
}

/**
 * Lit tcgForge.json dans le thème parent. Cache invalidé par la date du fichier.
 *
 * @return array<string,array<string,string>>
 */
function tokens(): array {
	static $tokens = null;
	if ( null !== $tokens ) {
		return $tokens;
	}

	$defaults = default_tokens();
	$path     = \get_template_directory() . '/tcgForge.json';

	if ( ! \is_readable( $path ) ) {
		$tokens = $defaults;
		return $tokens;
	}

	$mtime     = (int) \filemtime( $path );
	$cache_key = 'tcgforge_tokens_' . $mtime;
	$cached    = \get_transient( $cache_key );
	if ( \is_array( $cached ) ) {
		$tokens = $cached;
		return $tokens;
	}

	$data = \wp_json_file_decode( $path, array( 'associative' => true ) );
	if ( ! \is_array( $data ) ) {
		$tokens = $defaults;
		return $tokens;
	}

	$tokens = $defaults;
	foreach ( \array_keys( $defaults ) as $group ) {
		if ( isset( $data[ $group ] ) && \is_array( $data[ $group ] ) ) {
			$tokens[ $group ] = \array_merge( $defaults[ $group ], $data[ $group ] );
		}
	}

	\set_transient( $cache_key, $tokens, DAY_IN_SECONDS );

	return $tokens;
}

/**
 * Convertit camelCase en kebab-case : violetClair → violet-clair, surface2 → surface-2.
 */
function token_slug( string $key ): string {
	$slug = (string) \preg_replace( '/([a-z])([A-Z0-9])/', '$1-$2', $key );
	return \strtolower( $slug );
}

/**
 * Bloc :root des custom properties (valeurs filtrées).
 */
function tokens_css(): string {
	$prefixes = array(
		'layout'     => 'layout-',
		'radius'     => 'radius-',
		'typography' => 'type-',
		'animation'  => 'anim-',
	);

	$lines = array();
	foreach ( tokens() as $group => $values ) {
		foreach ( $values as $key => $value ) {
			if ( ! \is_scalar( $value ) ) {
				continue;
			}
			// Une custom property n'accepte ici qu'un jeu de caractères restreint.
			$safe = (string) \preg_replace( '/[^a-zA-Z0-9#%,.()\/\s\-]/', '', (string) $value );
			if ( '' === $safe ) {
				continue;
			}
			$slug = token_slug( (string) $key );
			if ( 'colors' === $group ) {
				if ( ! \preg_match( '/^#[0-9a-fA-F]{3,8}$/', $safe ) ) {
					continue;
				}
				$lines[] = \sprintf( '--wp--preset--color--tcgf-%s:%s;', $slug, $safe );
			} elseif ( isset( $prefixes[ $group ] ) ) {
				$lines[] = \sprintf( '--tcgf-%s%s:%s;', $prefixes[ $group ], $slug, $safe );
			}
		}
	}

	return ':root{' . \implode( '', $lines ) . '}';
}

/**
 * Mêmes tokens dans l'éditeur de blocs.
 */
\add_action(
	'enqueue_block_assets',
	function (): void {
		if ( ! \is_admin() ) {
			return;
		}
		\wp_register_style( 'tcgforge-tokens', false, array(), VERSION );
		\wp_enqueue_style( 'tcgforge-tokens' );
		\wp_add_inline_style( 'tcgforge-tokens', tokens_css() );
	}
);
