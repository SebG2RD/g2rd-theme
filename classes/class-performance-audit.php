<?php
/**
 * Performance Audit — journal de performance en mode WP_DEBUG
 *
 * Activé uniquement quand WP_DEBUG est true.
 * Logue dans error_log() à la fin de chaque requête frontend :
 * nombre de requêtes SQL, styles et scripts enqueués, taille HTML estimée.
 *
 * Format : [G2RD PERF] Page "Titre" : X queries SQL, Y styles, Z scripts, Wko HTML
 *
 * @package G2RD
 * @since   1.9.4
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Journal de performance frontend — actif uniquement en mode debug.
 */
class PerformanceAudit {

	/**
	 * Enregistre les hooks WordPress uniquement quand WP_DEBUG est actif.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! \defined( 'WP_DEBUG' ) || ! \WP_DEBUG ) {
			return;
		}

		\add_action( 'shutdown', [ $this, 'log_performance' ] );
	}

	/**
	 * Logue les métriques de performance de la page courante.
	 *
	 * @global \wpdb $wpdb
	 * @return void
	 */
	public function log_performance(): void {
		if ( \is_admin() ) {
			return;
		}

		global $wpdb;

		$title   = \get_the_title( \get_queried_object_id() );
		$title   = $title ?: \home_url( \add_query_arg( [] ) );
		$queries = (int) $wpdb->num_queries;
		$styles  = \count( \wp_styles()->queue );
		$scripts = \count( \wp_scripts()->queue );
		$html_kb = $this->estimate_html_size();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentionnel en mode debug
		\error_log(
			\sprintf(
				'[G2RD PERF] Page "%s" : %d queries SQL, %d styles, %d scripts, %sKo HTML',
				$title,
				$queries,
				$styles,
				$scripts,
				$html_kb
			)
		);
	}

	/**
	 * Estime la taille du HTML généré via ob_get_length().
	 *
	 * @return string Taille en Ko, ou '?' si indisponible.
	 */
	private function estimate_html_size(): string {
		$length = \ob_get_length();

		if ( false === $length ) {
			return '?';
		}

		return \number_format( $length / 1024, 1 );
	}
}
