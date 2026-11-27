<?php
/**
 * Compatibilité avec la synchronisation FSE du thème parent.
 *
 * G2RD\FseSync::recreate_fse_templates() recopie en base les parties et
 * gabarits du dossier PARENT sous le slug du thème ACTIF, à chaque nouvelle
 * version. Pour un thème enfant, ces copies masquent les fichiers de l'enfant :
 * en-tête, pied de page, gabarits index, single, page, archive, home, search
 * et 404 du parent s'affichent à la place de ceux de Rescue 18.
 *
 * Sans toucher au parent :
 * 1. l'option de version du parent est tenue à jour avant son passage
 *    (admin_init, priorité 19 contre 20), la recopie ne se déclenche plus ;
 * 2. les copies déjà créées et jamais modifiées sont retirées, une fois par
 *    version de l'enfant. Une partie retouchée dans l'éditeur de site est gardée.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Empêche la recopie des gabarits parents pour le thème enfant.
 */
\add_action(
	'admin_init',
	static function (): void {
		$version = (string) \wp_get_theme()->get( 'Version' );
		if ( \get_option( 'g2rd_recreate_version' ) !== $version ) {
			\update_option( 'g2rd_recreate_version', $version, false );
		}
	},
	19
);

/**
 * Normalise un contenu de gabarit pour comparaison.
 *
 * @param string $html Contenu.
 * @return string
 */
function normalize_template( string $html ): string {
	return (string) \preg_replace( '/\s+/', '', \wp_unslash( $html ) );
}

/**
 * Retire les copies de gabarits parents enregistrées sous le slug de l'enfant.
 *
 * @return string[] Journal.
 */
function purge_parent_template_copies(): array {
	$parent = \get_template_directory();
	$posts  = \get_posts(
		array(
			'post_type'      => array( 'wp_template', 'wp_template_part' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- opération ponctuelle.
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( \get_stylesheet() ),
				),
			),
		)
	);
	$log    = array();
	foreach ( $posts as $post ) {
		$dir  = 'wp_template' === $post->post_type ? 'templates' : 'parts';
		$file = $parent . '/' . $dir . '/' . $post->post_name . '.html';
		if ( ! \is_readable( $file ) ) {
			continue;
		}
		$same      = normalize_template( (string) \file_get_contents( $file ) ) === normalize_template( $post->post_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$untouched = $post->post_modified_gmt === $post->post_date_gmt;
		if ( $same || $untouched ) {
			\wp_delete_post( $post->ID, true );
			$log[] = "Copie du parent retirée : $dir/{$post->post_name}";
		}
	}
	if ( $log ) {
		\wp_clean_themes_cache();
		if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}
	return $log;
}

/**
 * Nettoyage automatique, une fois par version du thème enfant.
 */
\add_action(
	'admin_init',
	static function (): void {
		if ( \get_option( 'rescue18_fse_clean' ) === VERSION ) {
			return;
		}
		purge_parent_template_copies();
		\update_option( 'rescue18_fse_clean', VERSION, false );
	},
	21
);


/* WooCommerce insère son mini-panier et son lien de compte dans l'en-tête.
   Le thème a son panier (bloc rescue18/panier) et place le compte avec les
   autres icônes : on retire les insertions automatiques. */
\add_filter(
	'hooked_block_types',
	static function ( array $types ): array {
		return \array_values( \array_diff( $types, array( 'woocommerce/mini-cart', 'woocommerce/customer-account' ) ) );
	},
	20
);
