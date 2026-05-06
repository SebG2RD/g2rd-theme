<?php
/**
 * Performance CSS — CSS critique inline dans &lt;head&gt;
 *
 * @package G2RD
 * @since   1.9.4
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline le CSS critique dans <head>.
 *
 * Note : le chargement différé des styles non critiques (media="print" onload)
 * est intentionnellement absent — LiteSpeed Cache gère son propre CSS async
 * via css_async.min.js. Une double-déférence provoque des styles manquants
 * en navigation privée (cache froid).
 */
class PerformanceCSS {

	/** @var string Version du thème, utilisée comme clé de cache. */
	private string $theme_version;

	/**
	 * Constructeur — récupère la version du thème.
	 */
	public function __construct() {
		$this->theme_version = \wp_get_theme()->get( 'Version' );
	}

	/**
	 * Enregistre les hooks WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'wp_head', [ $this, 'output_critical_css' ], 1 );
		\add_action( 'switch_theme', [ $this, 'invalidate_cache' ] );
	}

	/**
	 * Injecte le CSS critique dans <head> via un tag <style> inline.
	 *
	 * @return void
	 */
	public function output_critical_css(): void {
		if ( \is_admin() ) {
			return;
		}

		$css = $this->get_critical_css();
		if ( '' === $css ) {
			return;
		}

		echo '<style id="g2rd-critical-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS interne validé, pas de données utilisateur
	}

	/**
	 * Retourne le CSS critique (depuis le transient ou le fichier source).
	 *
	 * @return string CSS minifié, chaîne vide si le fichier est absent.
	 */
	private function get_critical_css(): string {
		$transient_key = 'g2rd_critical_css_' . \md5( $this->theme_version );
		$cached        = \get_transient( $transient_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$file = \get_template_directory() . '/assets/css/critical.css';

		if ( ! \file_exists( $file ) ) {
			return '';
		}

		$css = \file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier interne au thème, chemin contrôlé
		$css = $this->minify( $css );

		\set_transient( $transient_key, $css, \DAY_IN_SECONDS );

		return $css;
	}

	/**
	 * Minification CSS légère : supprime les commentaires et les espaces superflus.
	 *
	 * @param  string $css CSS source.
	 * @return string CSS minifié.
	 */
	private function minify( string $css ): string {
		// Supprime les commentaires CSS /* ... */
		$css = (string) \preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css ); // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceWeird -- CSS strip, pas de modificateur /e
		// Réduit les espaces multiples, tabulations et retours à la ligne
		$css = (string) \preg_replace( '/\s+/', ' ', $css );
		// Supprime les espaces autour des caractères spéciaux : ; { }
		$css = (string) \preg_replace( '/\s*([:;{},>~+])\s*/', '$1', $css );

		return \trim( $css );
	}

	/**
	 * Invalide le transient du CSS critique (appelé à la mise à jour du thème).
	 *
	 * @global \wpdb $wpdb
	 * @return void
	 */
	public function invalidate_cache(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- suppression ciblée des transients par préfixe
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_g2rd_critical_css_%',
				'_transient_timeout_g2rd_critical_css_%'
			)
		);
	}
}
