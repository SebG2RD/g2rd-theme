<?php
/**
 * Animation Globe géodésique filaire
 *
 * Gère l'animation de globe filaire (fond signature wp-manager) : contrôle
 * dans la sidebar de l'éditeur (activation + position par bloc groupe),
 * aperçu live dans le canvas du BO et rendu sur le front.
 *
 * @package G2RD
 * @since 1.25.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace G2RD;

/**
 * Gestionnaire de l'animation Globe.
 *
 * Activation et position pilotées par la classe du bloc (« g2rd-globe-bg »,
 * « is-globe-{position} ») posée depuis l'inspecteur — source unique de vérité,
 * rendue nativement par core/group (front et canvas éditeur). Seul le réglage
 * fin (décalage / taille) passe par des attributs injectés en variables CSS au
 * rendu (render_block) et en aperçu live dans le canvas.
 *
 * @package G2RD
 * @since 1.25.0
 */
class GlobeEffect {

	/**
	 * Version du thème pour le cache-busting.
	 *
	 * @var string
	 */
	private string $theme_version;

	/**
	 * Constructeur.
	 */
	public function __construct() {
		$this->theme_version = \wp_get_theme()->get( 'Version' );
	}

	/**
	 * Enregistre les hooks nécessaires à l'animation du globe.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'enqueue_block_assets', array( $this, 'enqueueGlobeStyle' ) );
		\add_action( 'enqueue_block_editor_assets', array( $this, 'registerEditorControls' ), 5 );
		\add_filter( 'render_block', array( $this, 'addGlobeStyle' ), 10, 2 );
	}

	/**
	 * Charge la feuille de style du globe (front + canvas de l'éditeur).
	 *
	 * Hook enqueue_block_assets → chargée sur le front ET dans l'iframe de
	 * l'éditeur (aperçu live dans le BO). Chargement inconditionnel justifié :
	 * le globe est un élément de charte présent dans la quasi-totalité des
	 * templates (et pas seulement le post_content) ; la détection par
	 * hiérarchie de templates s'est révélée fragile sur ce projet. La feuille
	 * est minime (~1,5 Ko) et le masque SVG n'est requêté que si un élément
	 * `.g2rd-globe-bg` est effectivement rendu. L'activation se fait désormais
	 * par section (classe du bloc), il n'y a plus de drapeau global.
	 *
	 * @return void
	 */
	public function enqueueGlobeStyle(): void {
		$style_path = \get_template_directory() . '/assets/css/globe.css';
		$version    = \file_exists( $style_path ) ? \filemtime( $style_path ) : $this->theme_version;

		\wp_enqueue_style(
			'g2rd-globe',
			\get_template_directory_uri() . '/assets/css/globe.css',
			array(),
			$version
		);
	}

	/**
	 * Enregistre le contrôle de l'inspecteur dans l'éditeur.
	 *
	 * @return void
	 */
	public function registerEditorControls(): void {
		$script_path = \get_template_directory() . '/assets/js/g2rd-globe-sidebar.js';
		$version     = \file_exists( $script_path ) ? \filemtime( $script_path ) : $this->theme_version;

		\wp_enqueue_script(
			'g2rd-globe-sidebar',
			\get_template_directory_uri() . '/assets/js/g2rd-globe-sidebar.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-block-editor',
				'wp-compose',
				'wp-i18n',
				'wp-hooks',
			),
			$version,
			true
		);
	}

	/**
	 * Injecte les variables CSS de réglage fin (décalage / taille) sur les
	 * sections portant un globe — parité front avec l'aperçu éditeur.
	 *
	 * L'activation et la position sont des classes du bloc (déjà dans le markup
	 * sauvegardé, rendues nativement) : aucune injection de classe ici.
	 *
	 * @param string               $block_content Le HTML rendu du bloc.
	 * @param array<string, mixed> $block         Les données du bloc (nom, attributs).
	 * @return string
	 */
	public function addGlobeStyle( string $block_content, array $block ): string {
		if ( ( $block['blockName'] ?? '' ) !== 'core/group' ) {
			return $block_content;
		}

		// Réglage fin : décalage et taille en pixels, bornés et entiers.
		$dx   = isset( $block['attrs']['globeOffsetX'] ) ? (int) $block['attrs']['globeOffsetX'] : 0;
		$dy   = isset( $block['attrs']['globeOffsetY'] ) ? (int) $block['attrs']['globeOffsetY'] : 0;
		$size = isset( $block['attrs']['globeSize'] ) ? (int) $block['attrs']['globeSize'] : 0;

		if ( 0 === $dx && 0 === $dy && 0 === $size ) {
			return $block_content;
		}

		// Ne s'applique qu'aux sections effectivement dotées d'un globe.
		if ( false === \strpos( $block_content, 'g2rd-globe-bg' ) ) {
			return $block_content;
		}

		$style = '';
		if ( 0 !== $dx ) {
			$style .= '--g2rd-globe-dx:' . \max( -500, \min( 500, $dx ) ) . 'px;';
		}
		if ( 0 !== $dy ) {
			$style .= '--g2rd-globe-dy:' . \max( -500, \min( 500, $dy ) ) . 'px;';
		}
		if ( 0 !== $size ) {
			$style .= '--g2rd-globe-size:' . \max( 0, \min( 1000, $size ) ) . 'px;';
		}

		// Injecte les variables dans le premier attribut style="…" (le wrapper
		// d'une section sombre porte toujours un style de padding).
		return (string) \preg_replace(
			'/style="/',
			'style="' . \esc_attr( $style ),
			$block_content,
			1
		);
	}
}
