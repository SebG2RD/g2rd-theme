<?php
/**
 * Commande WP-CLI : `wp creapub install [--force]`.
 *
 * Même installation que le bouton Apparence › Installation Créa Pub.
 *
 * @package G2RD_CreaPub
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

if ( ! \class_exists( '\WP_CLI' ) ) {
	return;
}

/**
 * Installe les contenus Créa Pub (pages, prestations, catégories, navigation, réglages).
 */
final class CLI_Command {

	/**
	 * Crée les contenus manquants.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Réécrit le contenu des pages et prestations existantes (titres et statuts conservés).
	 *
	 * ## EXAMPLES
	 *
	 *     wp creapub install
	 *     wp creapub install --force
	 *
	 * @param array<int,string>    $args       Arguments positionnels (aucun).
	 * @param array<string,string> $assoc_args Options.
	 */
	public function install( array $args, array $assoc_args ): void {
		$force  = ! empty( $assoc_args['force'] );
		do {
			$report = install_all( $force );
			if ( isset( $report['suite'] ) ) {
				\WP_CLI::log( (string) $report['suite'] );
				\sleep( (int) \ceil( ( (int) ( $report['pause'] ?? 800 ) ) / 1000 ) );
			}
		} while ( isset( $report['suite'] ) );

		foreach ( $report as $section => $lines ) {
			if ( \is_array( $lines ) ) {
				foreach ( $lines as $slug => $state ) {
					\WP_CLI::log( \sprintf( '%s / %s : %s', $section, $slug, $state ) );
				}
			} else {
				\WP_CLI::log( \sprintf( '%s : %s', $section, (string) $lines ) );
			}
		}
		\WP_CLI::success( 'Installation Créa Pub terminée.' );
	}
}

\WP_CLI::add_command( 'creapub', __NAMESPACE__ . '\CLI_Command' );
