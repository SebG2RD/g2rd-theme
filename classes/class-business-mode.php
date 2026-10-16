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
		// La notice « type de site non défini » est remplacée par le hub G2RD du
		// tableau de bord (AdminConsole::renderDashboardHub), qui affiche l'état
		// du type de site parmi ses informations. show_setup_notice() reste
		// disponible mais n'est plus accroché.
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
			\esc_html__( 'Conseils G2RD pour votre site', 'g2rd' ),
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
				\esc_url( \admin_url( 'admin.php?page=g2rd' ) ),
				\esc_html__( 'Options G2RD', 'g2rd' )
			);
			return;
		}

		$rec   = self::RECOMMENDATIONS[ $type ];
		$label = self::TYPES[ $type ];

		echo '<div class="g2rd-bm-widget">';
		printf( '<p class="g2rd-bm-widget__type"><strong>%s</strong> %s</p>', \esc_html__( 'Type de site :', 'g2rd' ), \esc_html( $label ) );
		printf( '<p class="g2rd-bm-widget__tip"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><span>%s</span></p>', \esc_html( $rec['tip'] ) );

		echo '<p><strong>' . \esc_html__( 'Pages recommandées :', 'g2rd' ) . '</strong></p>';
		echo '<ul class="g2rd-bm-widget__list">';
		foreach ( $rec['pages'] as $page ) {
			printf( '<li>%s</li>', \esc_html( $page ) );
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
	 * Notice si le type de site n'est pas encore configuré.
	 *
	 * Les styles du widget (`.g2rd-bm-widget__*`) vivent dans
	 * assets/css/admin.css, chargé sur toutes les pages d'administration.
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
			'<div class="notice notice-info is-dismissible g2rd-admin-banner">
				<span class="g2rd-admin-banner__icon" aria-hidden="true"><span class="dashicons dashicons-admin-settings"></span></span>
				<div class="g2rd-admin-banner__body">
					<p><strong class="g2rd-admin-banner__title">%s</strong><span class="g2rd-admin-banner__text">%s</span></p>
				</div>
				<a href="%s" class="button g2rd-admin-banner__cta">%s</a>
			</div>',
			\esc_html__( 'Type de site non défini', 'g2rd' ),
			\esc_html__( 'Configurez le type de votre site pour obtenir des conseils personnalisés.', 'g2rd' ),
			\esc_url( \admin_url( 'admin.php?page=g2rd' ) ),
			\esc_html__( 'Configurer maintenant', 'g2rd' )
		);
	}

	/**
	 * Retourne le type de site actif.
	 */
	public static function get_type(): string {
		return (string) \get_option( 'g2rd_business_type', '' );
	}
}
