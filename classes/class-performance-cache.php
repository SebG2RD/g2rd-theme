<?php
/**
 * Performance Cache — gestion centralisée des transients de performance
 *
 * Fournit des accesseurs statiques pour mettre en cache les résultats
 * de calculs répétitifs (CSS critique, JSON-LD schema.org).
 * LiteSpeed Cache est actif sur la production : pas de headers HTTP ici.
 *
 * @package G2RD
 * @since   1.9.4
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralise la lecture/écriture des transients liés à la performance.
 *
 * Utilisé par : PerformanceCSS (CSS critique), et tout code générant du JSON-LD.
 */
class PerformanceCache {

	/** Durée de vie du cache CSS critique (24h). */
	const TTL_CRITICAL_CSS = \DAY_IN_SECONDS;

	/** Durée de vie du cache JSON-LD par post (12h). */
	const TTL_JSONLD = 12 * \HOUR_IN_SECONDS;

	/**
	 * Enregistre les hooks WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'switch_theme', [ __CLASS__, 'invalidate_critical_css' ] );
		\add_action( 'save_post', [ __CLASS__, 'invalidate_post_jsonld' ] );
	}

	// -------------------------------------------------------------------------
	// CSS critique
	// -------------------------------------------------------------------------

	/**
	 * Retourne le CSS critique mis en cache, ou exécute $generator pour le produire.
	 *
	 * @param  string   $version   Version du thème (clé de cache unique par version).
	 * @param  callable $generator Callable retournant le CSS minifié (string).
	 * @return string CSS minifié.
	 */
	public static function get_critical_css( string $version, callable $generator ): string {
		$key    = 'g2rd_critical_css_' . \md5( $version );
		$cached = \get_transient( $key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$css = (string) $generator();
		\set_transient( $key, $css, self::TTL_CRITICAL_CSS );

		return $css;
	}

	/**
	 * Invalide tous les transients de CSS critique (appelé sur switch_theme).
	 *
	 * @global \wpdb $wpdb
	 * @return void
	 */
	public static function invalidate_critical_css(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- suppression ciblée par préfixe
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_g2rd_critical_css_%',
				'_transient_timeout_g2rd_critical_css_%'
			)
		);
	}

	// -------------------------------------------------------------------------
	// JSON-LD schema.org
	// -------------------------------------------------------------------------

	/**
	 * Retourne le JSON-LD mis en cache pour un post, ou exécute $generator.
	 *
	 * @param  int      $post_id   ID du post.
	 * @param  callable $generator Callable retournant le JSON-LD encodé (string).
	 * @return string JSON-LD ou chaîne vide.
	 */
	public static function get_jsonld( int $post_id, callable $generator ): string {
		if ( $post_id <= 0 ) {
			return '';
		}

		$key    = 'g2rd_jsonld_' . $post_id;
		$cached = \get_transient( $key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$jsonld = (string) $generator();
		\set_transient( $key, $jsonld, self::TTL_JSONLD );

		return $jsonld;
	}

	/**
	 * Invalide le cache JSON-LD d'un post quand il est sauvegardé.
	 *
	 * @param  int $post_id ID du post sauvegardé.
	 * @return void
	 */
	public static function invalidate_post_jsonld( int $post_id ): void {
		\delete_transient( 'g2rd_jsonld_' . $post_id );
	}
}
