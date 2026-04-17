<?php
/**
 * Assistant de démarrage G2RD — wizard d'activation affiché une seule fois.
 *
 * Étapes :
 *   1. Choix du secteur d'activité (agence, artisan, vtc, ecommerce, autre)
 *   2. Configuration de la couleur principale
 *   3. Création des pages clés (Accueil, Services, Contact)
 *   4. Activation de la licence
 *
 * @package G2RD Theme
 * @since   1.4.0
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Onboarding {

	private const OPTION_DONE   = 'g2rd_onboarding_done';
	private const OPTION_STEP   = 'g2rd_onboarding_step';
	private const PAGE_SLUG     = 'g2rd-onboarding';
	private const NONCE_ACTION  = 'g2rd_onboarding_action';

	/**
	 * Enregistre les hooks WordPress de l'assistant de démarrage.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( \get_option( self::OPTION_DONE ) ) {
			return;
		}

		\add_action( 'admin_menu', [ $this, 'add_page' ] );
		\add_action( 'admin_init', [ $this, 'handle_actions' ] );
		\add_action( 'admin_notices', [ $this, 'show_banner' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Ajoute la page d'onboarding dans le menu admin (hidden).
	 *
	 * @return void
	 */
	public function add_page(): void {
		\add_submenu_page(
			null,
			\esc_html__( 'Assistant de démarrage G2RD', 'g2rd' ),
			\esc_html__( 'Démarrage', 'g2rd' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Charge les assets CSS de l'onboarding sur la page du wizard.
	 *
	 * @param string $hook Suffixe de la page admin courante.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		\wp_enqueue_style( 'g2rd-onboarding', \get_template_directory_uri() . '/assets/css/onboarding.css', [], \wp_get_theme()->get( 'Version' ) );
	}

	/**
	 * Affiche une bannière admin invitant à compléter l'onboarding.
	 *
	 * @return void
	 */
	public function show_banner(): void {
		$screen = \get_current_screen();
		if ( ! $screen || strpos( $screen->id, self::PAGE_SLUG ) !== false ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = \admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		printf(
			'<div class="notice notice-warning" style="border-left-color:#D4A373;padding:1rem 1.25rem;display:flex;align-items:center;gap:1.5rem;">
				<div style="flex:1">
					<strong>%s</strong><br>
					<span>%s</span>
				</div>
				<a href="%s" class="button button-primary" style="background:#2F425D;border-color:#2F425D;font-weight:600;flex-shrink:0;">%s →</a>
			</div>',
			\esc_html__( '🚀 Bienvenue sur le thème G2RD !', 'g2rd' ),
			\esc_html__( 'Complétez l\'assistant de démarrage pour configurer votre site en 2 minutes.', 'g2rd' ),
			\esc_url( $url ),
			\esc_html__( 'Démarrer l\'assistant', 'g2rd' )
		);
	}

	/**
	 * Traite les soumissions de formulaire de l'onboarding (POST).
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! isset( $_POST['g2rd_onboarding_nonce'] ) ) {
			return;
		}

		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['g2rd_onboarding_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_POST['onboarding_action'] ) ? \sanitize_key( $_POST['onboarding_action'] ) : '';

		switch ( $action ) {
			case 'step_sector':
				$sector = isset( $_POST['sector'] ) ? \sanitize_key( $_POST['sector'] ) : 'autre';
				\update_option( 'g2rd_business_sector', $sector );
				\update_option( self::OPTION_STEP, 2 );
				break;

			case 'step_colors':
				$color = isset( $_POST['primary_color'] ) ? \sanitize_hex_color( \wp_unslash( $_POST['primary_color'] ) ) : '#2F425D';
				if ( $color ) {
					\update_option( 'g2rd_custom_primary_color', $color );
				}
				\update_option( self::OPTION_STEP, 3 );
				break;

			case 'step_pages':
				$this->create_starter_pages();
				\update_option( self::OPTION_STEP, 4 );
				break;

			case 'step_finish':
				\update_option( self::OPTION_DONE, true );
				\update_option( self::OPTION_STEP, 0 );
				\wp_safe_redirect( \admin_url( 'index.php?g2rd_onboarding=done' ) );
				exit;

			case 'skip':
				\update_option( self::OPTION_DONE, true );
				\update_option( self::OPTION_STEP, 0 );
				\wp_safe_redirect( \admin_url() );
				exit;
		}

		\wp_safe_redirect( \admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Crée les pages de démarrage avec le bon template selon le secteur.
	 */
	private function create_starter_pages(): void {
		$sector = \get_option( 'g2rd_business_sector', 'autre' );

		$template_map = [
			'agence'     => 'page-agence',
			'artisan'    => 'page-artisan',
			'vtc'        => 'page-vtc',
			'ecommerce'  => 'page-ecommerce',
			'autre'      => 'page-accueil',
		];

		$template = $template_map[ $sector ] ?? 'page-accueil';

		$pages = [
			[
				'post_title'  => \__( 'Accueil', 'g2rd' ),
				'post_name'   => 'accueil',
				'meta_input'  => [ '_wp_page_template' => $template ],
			],
			[
				'post_title' => \__( 'Services', 'g2rd' ),
				'post_name'  => 'services',
				'meta_input' => [ '_wp_page_template' => 'page-services' ],
			],
			[
				'post_title' => \__( 'Contact', 'g2rd' ),
				'post_name'  => 'contact',
				'meta_input' => [ '_wp_page_template' => 'page-contact' ],
			],
		];

		$home_id = 0;

		foreach ( $pages as $page ) {
			$existing = \get_page_by_path( $page['post_name'] );
			if ( $existing ) {
				continue;
			}

			$id = \wp_insert_post( \array_merge( [
				'post_status' => 'publish',
				'post_type'   => 'page',
			], $page ) );

			if ( ! \is_wp_error( $id ) && 'accueil' === $page['post_name'] ) {
				$home_id = $id;
			}
		}

		// Définir la page d'accueil statique.
		if ( $home_id ) {
			\update_option( 'show_on_front', 'page' );
			\update_option( 'page_on_front', $home_id );
		}
	}

	/**
	 * Affiche la page HTML du wizard d'onboarding.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Accès refusé.', 'g2rd' ) );
		}

		$current_step = (int) \get_option( self::OPTION_STEP, 1 );
		if ( $current_step < 1 ) {
			$current_step = 1;
		}

		$steps = [
			1 => \__( 'Votre activité', 'g2rd' ),
			2 => \__( 'Couleurs', 'g2rd' ),
			3 => \__( 'Pages', 'g2rd' ),
			4 => \__( 'Terminé !', 'g2rd' ),
		];

		?>
		<div class="wrap g2rd-onboarding">
			<div class="g2rd-onboarding__header">
				<h1><?php \esc_html_e( '🚀 Configuration de votre thème G2RD', 'g2rd' ); ?></h1>
				<p class="g2rd-onboarding__subtitle"><?php \esc_html_e( 'Configurez votre site en 4 étapes rapides.', 'g2rd' ); ?></p>
			</div>

			<!-- Barre de progression -->
			<div class="g2rd-onboarding__progress">
				<?php foreach ( $steps as $num => $label ) : ?>
					<div class="g2rd-onboarding__step <?php echo $num < $current_step ? 'done' : ( $num === $current_step ? 'active' : '' ); ?>">
						<span class="g2rd-onboarding__step-num">
							<?php echo $num < $current_step ? '✓' : \esc_html( (string) $num ); ?>
						</span>
						<span class="g2rd-onboarding__step-label"><?php echo \esc_html( $label ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="g2rd-onboarding__content">
				<form method="post" action="">
					<?php \wp_nonce_field( self::NONCE_ACTION, 'g2rd_onboarding_nonce' ); ?>

					<?php if ( 1 === $current_step ) : ?>
						<h2><?php \esc_html_e( 'Quel est votre secteur d\'activité ?', 'g2rd' ); ?></h2>
						<p><?php \esc_html_e( 'Nous adapterons les templates et suggestions à votre métier.', 'g2rd' ); ?></p>

						<div class="g2rd-onboarding__sectors">
							<?php
							$sectors = [
								'agence'    => [ 'icon' => '🎨', 'label' => 'Agence / Studio', 'desc' => 'Web, créatif, conseil' ],
								'artisan'   => [ 'icon' => '🔧', 'label' => 'Artisan / Prestataire', 'desc' => 'Bâtiment, rénovation, services' ],
								'vtc'       => [ 'icon' => '🚗', 'label' => 'Transport / VTC', 'desc' => 'Taxi, chauffeur, livraison' ],
								'ecommerce' => [ 'icon' => '🛒', 'label' => 'E-commerce', 'desc' => 'Boutique en ligne, produits' ],
								'autre'     => [ 'icon' => '🌐', 'label' => 'Autre', 'desc' => 'Professionnel libéral, etc.' ],
							];
							foreach ( $sectors as $key => $s ) :
								?>
								<label class="g2rd-sector-card">
									<input type="radio" name="sector" value="<?php echo \esc_attr( $key ); ?>" <?php \checked( \get_option( 'g2rd_business_sector', 'autre' ), $key ); ?>>
									<span class="g2rd-sector-card__icon"><?php echo \esc_html( $s['icon'] ); ?></span>
									<span class="g2rd-sector-card__label"><?php echo \esc_html( $s['label'] ); ?></span>
									<span class="g2rd-sector-card__desc"><?php echo \esc_html( $s['desc'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<input type="hidden" name="onboarding_action" value="step_sector">
						<button type="submit" class="button button-primary button-hero"><?php \esc_html_e( 'Continuer →', 'g2rd' ); ?></button>

					<?php elseif ( 2 === $current_step ) : ?>
						<h2><?php \esc_html_e( 'Choisissez votre couleur principale', 'g2rd' ); ?></h2>
						<p><?php \esc_html_e( 'Cette couleur sera utilisée dans les boutons, titres et accents de votre site.', 'g2rd' ); ?></p>

						<div class="g2rd-onboarding__colors">
							<?php
							$presets = [
								'#2F425D' => 'Bleu profond (défaut)',
								'#1B4332' => 'Vert forêt',
								'#7B2D8B' => 'Violet prestige',
								'#C0392B' => 'Rouge passion',
								'#1A1A2E' => 'Nuit moderne',
								'#0077B6' => 'Bleu océan',
							];
							foreach ( $presets as $color => $name ) :
								?>
								<label class="g2rd-color-swatch" title="<?php echo \esc_attr( $name ); ?>">
									<input type="radio" name="primary_color" value="<?php echo \esc_attr( $color ); ?>" <?php \checked( \get_option( 'g2rd_custom_primary_color', '#2F425D' ), $color ); ?>>
									<span class="g2rd-color-swatch__preview" style="background:<?php echo \esc_attr( $color ); ?>"></span>
									<span class="g2rd-color-swatch__name"><?php echo \esc_html( $name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<input type="hidden" name="onboarding_action" value="step_colors">
						<button type="submit" class="button button-primary button-hero"><?php \esc_html_e( 'Continuer →', 'g2rd' ); ?></button>

					<?php elseif ( 3 === $current_step ) : ?>
						<h2><?php \esc_html_e( 'Créer vos pages de démarrage', 'g2rd' ); ?></h2>
						<p><?php \esc_html_e( 'Nous allons créer automatiquement vos pages Accueil, Services et Contact avec les bons templates, prêtes à personnaliser.', 'g2rd' ); ?></p>

						<div class="g2rd-onboarding__pages-preview">
							<?php
							$pages = [
								[ 'icon' => '🏠', 'name' => 'Accueil', 'desc' => 'Template adapté à votre secteur' ],
								[ 'icon' => '⚙️', 'name' => 'Services', 'desc' => 'Présentation de vos prestations' ],
								[ 'icon' => '📧', 'name' => 'Contact', 'desc' => 'Formulaire et coordonnées' ],
							];
							foreach ( $pages as $p ) :
								?>
								<div class="g2rd-page-preview">
									<span><?php echo \esc_html( $p['icon'] ); ?></span>
									<div>
										<strong><?php echo \esc_html( $p['name'] ); ?></strong>
										<small><?php echo \esc_html( $p['desc'] ); ?></small>
									</div>
									<span style="color:#46b450">✓</span>
								</div>
							<?php endforeach; ?>
						</div>

						<input type="hidden" name="onboarding_action" value="step_pages">
						<button type="submit" class="button button-primary button-hero"><?php \esc_html_e( 'Créer mes pages →', 'g2rd' ); ?></button>

					<?php elseif ( 4 <= $current_step ) : ?>
						<div class="g2rd-onboarding__success">
							<div class="g2rd-onboarding__success-icon">🎉</div>
							<h2><?php \esc_html_e( 'Votre site est prêt !', 'g2rd' ); ?></h2>
							<p><?php \esc_html_e( 'Vos pages ont été créées et votre site est configuré. Rendez-vous dans l\'Éditeur de site pour le personnaliser.', 'g2rd' ); ?></p>

							<div class="g2rd-onboarding__success-links">
								<a href="<?php echo \esc_url( \admin_url( 'site-editor.php' ) ); ?>" class="button button-primary button-hero"><?php \esc_html_e( '✏️ Ouvrir l\'éditeur de site', 'g2rd' ); ?></a>
								<a href="<?php echo \esc_url( \home_url( '/' ) ); ?>" class="button button-secondary" target="_blank"><?php \esc_html_e( '👁️ Voir mon site', 'g2rd' ); ?></a>
							</div>
						</div>

						<input type="hidden" name="onboarding_action" value="step_finish">
						<button type="submit" class="button"><?php \esc_html_e( 'Terminer l\'assistant', 'g2rd' ); ?></button>
					<?php endif; ?>

					<p class="g2rd-onboarding__skip">
						<button type="submit" name="onboarding_action" value="skip" class="button-link" style="color:#999">
							<?php \esc_html_e( 'Passer cette étape et configurer manuellement', 'g2rd' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Réinitialise l'assistant (pour retester).
	 */
	public static function reset(): void {
		\delete_option( self::OPTION_DONE );
		\delete_option( self::OPTION_STEP );
	}
}
