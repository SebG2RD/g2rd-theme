<?php
/**
 * Commandes WP-CLI du thème enfant GlassAuto.
 *
 * Wp glassauto install [--force]   Pages, articles, navigation, réglages (idempotent)
 *   wp glassauto pages [--force]     Pages seulement
 *   wp glassauto articles [--force]  Articles Conseils seulement
 *   wp glassauto navigation          Navigation principale seulement
 *   wp glassauto seo                 Préremplit SEOPress / SEOPress PRO
 *   wp glassauto centre              Affiche les informations du centre
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

if ( ! \class_exists( '\WP_CLI' ) ) {
	return;
}

/**
 * Installation et vérification du site GlassAuto Vernon.
 */
class Cli_Command {

	/**
	 * Crée les contenus manquants, la navigation et les réglages de lecture.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Réécrit aussi le contenu des pages existantes (titre et statut conservés).
	 *
	 * @param array<int,string>    $args       Arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function install( array $args, array $assoc_args ): void {
		$this->rapport( install_all( ! empty( $assoc_args['force'] ) ) );
		\WP_CLI::success( 'Installation terminée.' );
	}

	/**
	 * Crée les pages manquantes.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Réécrit aussi le contenu des pages existantes.
	 *
	 * @param array<int,string>    $args       Arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function pages( array $args, array $assoc_args ): void {
		$this->rapport( install_contents( ! empty( $assoc_args['force'] ) ) );
		\WP_CLI::success( 'Pages vérifiées.' );
	}

	/**
	 * Crée les articles manquants (rubrique Conseils) avec leur image à la une.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Réécrit aussi le contenu des articles existants.
	 *
	 * @param array<int,string>    $args       Arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function articles( array $args, array $assoc_args ): void {
		$this->rapport( install_posts( ! empty( $assoc_args['force'] ) ) );
		\WP_CLI::success( 'Articles vérifiés.' );
	}

	/**
	 * Préremplit SEOPress (modules, titres, sitemaps, Knowledge Graph) et
	 * SEOPress PRO (Local Business) avec les informations du centre.
	 * Une valeur déjà saisie dans SEOPress n'est jamais écrasée.
	 */
	public function seo(): void {
		$this->rapport( seopress_prefill() );
		\WP_CLI::success( 'SEOPress vérifié.' );
	}

	/**
	 * Crée la navigation principale si elle n'existe pas.
	 */
	public function navigation(): void {
		\WP_CLI::log( 'navigation : ' . install_navigation() );
	}

	/**
	 * Affiche les informations du centre (option fusionnée avec les défauts).
	 */
	public function centre(): void {
		foreach ( centre() as $k => $v ) {
			\WP_CLI::log( \str_pad( (string) $k, 18 ) . ' ' . ( \is_array( $v ) ? \wp_json_encode( $v, JSON_UNESCAPED_UNICODE ) : (string) $v ) );
		}
	}

	/**
	 * Affiche un rapport slug → état.
	 *
	 * @param array<string,string> $report Rapport.
	 */
	private function rapport( array $report ): void {
		foreach ( $report as $slug => $state ) {
			$line = \str_pad( $slug, 32 ) . ' ' . $state;
			if ( 0 === \strpos( $state, 'erreur' ) ) {
				\WP_CLI::warning( $line );
			} else {
				\WP_CLI::log( $line );
			}
		}
	}
}

\WP_CLI::add_command( 'glassauto', __NAMESPACE__ . '\Cli_Command' );
