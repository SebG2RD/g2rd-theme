<?php
/**
 * Mode Business — adapte les suggestions de blocs et CTAs selon le type de site.
 *
 * L'administrateur choisit un "type de site" (vitrine / leads / e-commerce).
 * Ce mode :
 *  - Injecte des données JS dans l'éditeur pour adapter les conseils de la sidebar
 *  - Ajoute un widget tableau de bord adapté
 *  - Affiche des conseils contextuels dans les notices admin
 *
 * @package G2RD Theme
 * @since   1.4.0
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Business_Mode {

	/** Types disponibles */
	const TYPES = [
		'vitrine'    => 'Site vitrine',
		'leads'      => 'Génération de leads',
		'ecommerce'  => 'E-commerce',
	];

	/** Sections/pages recommandées par type */
	const RECOMMENDATIONS = [
		'vitrine' => [
			'pages'    => [ 'Accueil', 'Services', 'À propos', 'Contact' ],
			'patterns' => [ 'section-hero', 'section-services', 'section-temoignages', 'section-cta' ],
			'cta'      => 'Contactez-nous',
			'tip'      => 'Un site vitrine performant mise sur la clarté : services clairs, témoignages clients, formulaire de contact accessible.',
		],
		'leads' => [
			'pages'    => [ 'Accueil', 'Offre', 'Témoignages', 'Contact / Devis' ],
			'patterns' => [ 'section-hero', 'section-cta-countdown', 'section-temoignages', 'section-faq' ],
			'cta'      => 'Obtenir un devis gratuit',
			'tip'      => 'Pour générer des leads : un seul objectif par page, CTA répété 3× minimum, formulaire court et rassurances visibles.',
		],
		'ecommerce' => [
			'pages'    => [ 'Boutique', 'Panier', 'Paiement', 'Mon compte' ],
			'patterns' => [ 'template-ecommerce', 'section-temoignages', 'section-cta' ],
			'cta'      => 'Acheter maintenant',
			'tip'      => 'E-commerce : mettez en avant les avis, les garanties (livraison, retours) et les offres limitées pour augmenter le taux de conversion.',
		],
	];

	/**
	 * Enregistre les hooks WordPress du mode business.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_data' ] );
		\add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		\add_action( 'admin_notices', [ $this, 'show_setup_notice' ] );
		\add_action( 'admin_head', [ $this, 'enqueue_widget_styles' ] );
	}

	/**
	 * Injecte le type de site dans l'éditeur Gutenberg.
	 */
	public function enqueue_editor_data(): void {
		$type = \get_option( 'g2rd_business_type', '' );
		if ( ! $type ) {
			return;
		}

		$rec = self::RECOMMENDATIONS[ $type ] ?? null;
		if ( ! $rec ) {
			return;
		}

		\wp_enqueue_script(
			'g2rd-business-mode',
			\get_template_directory_uri() . '/assets/js/business-mode.js',
			[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n' ],
			\wp_get_theme()->get( 'Version' ),
			true
		);

		\wp_localize_script( 'g2rd-business-mode', 'g2rdBusiness', [
			'type'            => $type,
			'typeLabel'       => self::TYPES[ $type ],
			'cta'             => $rec['cta'],
			'tip'             => $rec['tip'],
			'recommendedPages'    => $rec['pages'],
			'recommendedPatterns' => $rec['patterns'],
		] );
	}

	/**
	 * Widget tableau de bord : conseils selon le type de site.
	 */
	public function register_dashboard_widget(): void {
		\wp_add_dashboard_widget(
			'g2rd_business_mode_widget',
			\esc_html__( '🎯 Conseils G2RD pour votre site', 'g2rd' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Affiche le contenu du widget tableau de bord avec les conseils adaptés.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$type = \get_option( 'g2rd_business_type', '' );

		if ( ! $type || ! isset( self::RECOMMENDATIONS[ $type ] ) ) {
			printf(
				'<p>%s <a href="%s">%s</a></p>',
				\esc_html__( 'Configurez le type de votre site dans', 'g2rd' ),
				\esc_url( \admin_url( 'themes.php?page=g2rd-options' ) ),
				\esc_html__( 'Options G2RD', 'g2rd' )
			);
			return;
		}

		$rec   = self::RECOMMENDATIONS[ $type ];
		$label = self::TYPES[ $type ];

		echo '<div class="g2rd-bm-widget">';
		printf( '<p class="g2rd-bm-widget__type"><strong>%s</strong> %s</p>', \esc_html__( 'Type de site :', 'g2rd' ), \esc_html( $label ) );
		printf( '<p class="g2rd-bm-widget__tip">💡 %s</p>', \esc_html( $rec['tip'] ) );

		echo '<p><strong>' . \esc_html__( 'Pages recommandées :', 'g2rd' ) . '</strong></p>';
		echo '<ul class="g2rd-bm-widget__list">';
		foreach ( $rec['pages'] as $page ) {
			printf( '<li>• %s</li>', \esc_html( $page ) );
		}
		echo '</ul>';

		printf(
			'<p><strong>%s</strong> <code>%s</code></p>',
			\esc_html__( 'CTA suggéré :', 'g2rd' ),
			\esc_html( $rec['cta'] )
		);

		echo '</div>';
	}

	/**
	 * Injecte les styles du widget tableau de bord sur la page dashboard uniquement.
	 *
	 * @return void
	 */
	public function enqueue_widget_styles(): void {
		$screen = \get_current_screen();
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}
		?>
		<style id="g2rd-bm-widget-styles">
		.g2rd-bm-widget__type { color:#2F425D; font-size:13px; }
		.g2rd-bm-widget__tip  { background:#FFF8F0; border-left:3px solid #D4A373; padding:8px 12px; border-radius:0 4px 4px 0; font-size:12px; }
		.g2rd-bm-widget__list { margin:4px 0 8px 0; }
		.g2rd-bm-widget__list li { font-size:12px; margin:2px 0; }
		</style>
		<?php
	}

	/**
	 * Notice si le type de site n'est pas encore configuré.
	 */
	public function show_setup_notice(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$type   = \get_option( 'g2rd_business_type', '' );
		$screen = \get_current_screen();

		if ( $type || ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible" style="border-left-color:#2F425D;">
				<p>🚀 <strong>G2RD</strong> — %s <a href="%s">%s</a></p>
			</div>',
			\esc_html__( 'Configurez le type de votre site pour obtenir des conseils personnalisés.', 'g2rd' ),
			\esc_url( \admin_url( 'themes.php?page=g2rd-options' ) ),
			\esc_html__( 'Configurer maintenant →', 'g2rd' )
		);
	}

	/**
	 * Retourne le type de site actif.
	 */
	public static function get_type(): string {
		return (string) \get_option( 'g2rd_business_type', '' );
	}
}
