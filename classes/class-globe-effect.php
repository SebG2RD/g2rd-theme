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
 * Modelé sur ParticlesEffect : attribut de bloc piloté depuis l'inspecteur,
 * CSS conditionnel sur le front, CSS chargé dans le canvas éditeur pour
 * l'aperçu, et classe injectée au rendu (render_block) pour la parité front.
 *
 * @package G2RD
 * @since 1.25.0
 */
class GlobeEffect {

	/**
	 * Positions autorisées pour le globe.
	 *
	 * @var string[]
	 */
	private const POSITIONS = array( 'center', 'right', 'left', 'top', 'bottom' );

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
		\add_filter( 'render_block', array( $this, 'addGlobeClass' ), 10, 2 );
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
	 * `.g2rd-globe-bg` est effectivement rendu. L'activation/désactivation
	 * globale reste pilotée par le drapeau de fonctionnalité « globe_effect »
	 * (la classe n'est instanciée que si la feature est active).
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
	 * Injecte les classes du globe sur le bloc groupe au rendu (parité front).
	 *
	 * @param string               $block_content Le HTML rendu du bloc.
	 * @param array<string, mixed> $block         Les données du bloc (nom, attributs).
	 * @return string
	 */
	public function addGlobeClass( string $block_content, array $block ): string {
		if ( ( $block['blockName'] ?? '' ) !== 'core/group' ) {
			return $block_content;
		}

		$enabled = isset( $block['attrs']['globeEffect'] ) && true === $block['attrs']['globeEffect'];
		if ( ! $enabled ) {
			return $block_content;
		}

		$position = isset( $block['attrs']['globePosition'] ) ? (string) $block['attrs']['globePosition'] : 'center';
		if ( ! \in_array( $position, self::POSITIONS, true ) ) {
			$position = 'center';
		}

		$classes = 'g2rd-globe-bg is-globe-' . $position;

		// Injecte dans le premier attribut class="…" (le wrapper du groupe).
		return (string) \preg_replace(
			'/class="/',
			'class="' . \esc_attr( $classes ) . ' ',
			$block_content,
			1
		);
	}
}
