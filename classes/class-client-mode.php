<?php
/**
 * Mode client — simplifie l'interface WordPress pour les utilisateurs non techniques.
 *
 * Active/désactive via Apparence > Options G2RD > "Mode client".
 * En mode client : menus sensibles cachés, options dangereuses verrouillées,
 * barre d'admin épurée, message d'accueil personnalisé.
 *
 * @package G2RD Theme
 * @since   1.4.0
 */

namespace G2RD;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Client_Mode {

	private bool $enabled;

	public function __construct() {
		$this->enabled = (bool) \get_option( 'g2rd_client_mode', false );
	}

	public function register_hooks(): void {
		// Paramètre dans les options du thème.
		\add_action( 'admin_init', [ $this, 'register_setting' ] );

		if ( ! $this->enabled ) {
			return;
		}

		// Appliquer le mode client uniquement aux rôles non-admin.
		if ( \current_user_can( 'manage_options' ) ) {
			return;
		}

		\add_action( 'admin_menu', [ $this, 'remove_menus' ], 999 );
		\add_action( 'admin_bar_menu', [ $this, 'clean_admin_bar' ], 999 );
		\add_action( 'admin_head', [ $this, 'inject_client_styles' ] );
		\add_action( 'admin_notices', [ $this, 'welcome_notice' ] );
		\add_filter( 'show_admin_bar', [ $this, 'maybe_hide_admin_bar' ] );
		\add_action( 'admin_init', [ $this, 'block_restricted_pages' ] );
	}

	public function register_setting(): void {
		\register_setting( 'g2rd_options_group', 'g2rd_client_mode', [
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		] );

		\register_setting( 'g2rd_options_group', 'g2rd_client_mode_message', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		] );
	}

	/**
	 * Supprime les menus non nécessaires pour le client.
	 */
	public function remove_menus(): void {
		// Menus à masquer pour les éditeurs/auteurs.
		$menus_to_remove = [
			'plugins.php',
			'tools.php',
			'options-general.php',
			'themes.php',
			'edit-comments.php',
		];

		foreach ( $menus_to_remove as $menu ) {
			\remove_menu_page( $menu );
		}

		// Sous-menus Apparence : garder uniquement Menus et Éditeur de site.
		\remove_submenu_page( 'themes.php', 'widgets.php' );
		\remove_submenu_page( 'themes.php', 'customize.php' );
		\remove_submenu_page( 'themes.php', 'theme-editor.php' );
	}

	/**
	 * Nettoie la barre d'administration.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Instance de la barre admin.
	 */
	public function clean_admin_bar( \WP_Admin_Bar $wp_admin_bar ): void {
		$nodes_to_remove = [
			'wp-logo',
			'updates',
			'comments',
			'new-content',
			'customize',
		];

		foreach ( $nodes_to_remove as $node ) {
			$wp_admin_bar->remove_node( $node );
		}
	}

	/**
	 * Styles CSS pour épurer l'interface admin client.
	 */
	public function inject_client_styles(): void {
		?>
		<style id="g2rd-client-mode-styles">
			/* Mode client G2RD — interface épurée */
			#adminmenu .wp-menu-separator { display: none; }
			#screen-meta-links,
			#screen-options-link-wrap { display: none !important; }
			.welcome-panel { display: none; }
			#footer-thankyou { display: none; }

			/* Mise en valeur des menus principaux */
			#adminmenu li.menu-top a.menu-top {
				border-radius: 6px;
				transition: background 0.2s;
			}

			/* Badge version discret */
			#footer-upgrade { opacity: 0.5; font-size: 11px; }
		</style>
		<?php
	}

	/**
	 * Affiche un message d'accueil personnalisé en haut de l'admin.
	 */
	public function welcome_notice(): void {
		$screen = \get_current_screen();
		if ( ! $screen || 'dashboard' !== $screen->id ) {
			return;
		}

		$message = \get_option( 'g2rd_client_mode_message', '' );
		if ( empty( $message ) ) {
			$message = \esc_html__( 'Bienvenue dans l\'espace de gestion de votre site. En cas de question, contactez votre agence.', 'g2rd' );
		}

		printf(
			'<div class="notice notice-info is-dismissible" style="border-left-color:#D4A373;padding:1rem 1.25rem;">
				<p style="font-size:1rem;margin:0;">👋 <strong>%s</strong> — %s</p>
			</div>',
			\esc_html__( 'Bonjour', 'g2rd' ),
			\esc_html( $message )
		);
	}

	/**
	 * Masque la barre d'admin en frontend pour les abonnés.
	 *
	 * @param bool $show Valeur actuelle.
	 * @return bool
	 */
	public function maybe_hide_admin_bar( bool $show ): bool {
		if ( ! \is_admin() && ! \current_user_can( 'edit_posts' ) ) {
			return false;
		}

		return $show;
	}

	/**
	 * Redirige vers le tableau de bord si accès à une page restreinte.
	 */
	public function block_restricted_pages(): void {
		$screen = \get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$restricted = [
			'plugins',
			'plugin-install',
			'plugin-editor',
			'tools',
			'export',
			'import',
			'options-general',
			'options-writing',
			'options-reading',
			'options-discussion',
			'options-media',
			'options-permalink',
			'theme-editor',
		];

		if ( in_array( $screen->id, $restricted, true ) ) {
			\wp_safe_redirect( \admin_url( 'index.php' ) );
			exit;
		}
	}

	/**
	 * Retourne true si le mode client est actif.
	 */
	public static function is_enabled(): bool {
		return (bool) \get_option( 'g2rd_client_mode', false );
	}
}
