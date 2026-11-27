<?php
/**
 * Installateur WP-CLI : `wp rescue18 installer [--force]`.
 * Même logique que l'écran Outils › Préparer Rescue 18 (inc/install.php).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Commandes Rescue 18.
 */
class CLI_Command {

	/**
	 * Installe termes et pages.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Remplace le contenu des pages existantes par les compositions.
	 *
	 * @param array<int,string>    $args  Arguments.
	 * @param array<string,string> $assoc Options.
	 */
	public function installer( array $args, array $assoc ): void {
		foreach ( \array_merge( install_terms(), install_pages( isset( $assoc['force'] ) ) ) as $line ) {
			\WP_CLI::log( $line );
		}
		\WP_CLI::success( 'Rescue 18 installé.' );
	}

	/**
	 * Lance la veille documentaire (cycle complet ou source ciblée).
	 *
	 * ## OPTIONS
	 *
	 * [--source=<source>]
	 * : dgscgc, pnrs ou ensosp.
	 *
	 * [--simulation]
	 * : N'écrit rien, décrit les actions prévues dans le journal.
	 *
	 * @param array<int,string>    $args  Arguments.
	 * @param array<string,string> $assoc Options.
	 */
	public function veille( array $args, array $assoc ): void {
		$source = (string) ( $assoc['source'] ?? '' );
		if ( '' !== $source && ! isset( Veille\connecteurs()[ $source ] ) ) {
			\WP_CLI::error( 'Source inconnue : ' . $source );
		}
		if ( ! Veille\demarrer( $source, isset( $assoc['simulation'] ), 'cli' ) ) {
			\WP_CLI::error( 'Un cycle est déjà en cours.' );
		}
		do {
			\delete_transient( Veille\VERROU );
			Veille\lot();
			$c = Veille\cycle();
			\WP_CLI::log( (int) $c['pos'] . ' / ' . \count( (array) $c['file'] ) . ' pages traitées' );
		} while ( 'en_cours' === ( $c['statut'] ?? '' ) );
		foreach ( (array) ( $c['stats'] ?? array() ) as $src => $st ) {
			\WP_CLI::log( $src . ' : ' . \wp_json_encode( $st ) );
		}
		foreach ( (array) ( $c['erreurs'] ?? array() ) as $src => $errs ) {
			foreach ( (array) $errs as $e ) {
				\WP_CLI::warning( $src . ' : ' . $e );
			}
		}
		\WP_CLI::success( 'Cycle terminé.' );
	}
}

\WP_CLI::add_command( 'rescue18', __NAMESPACE__ . '\CLI_Command' );
