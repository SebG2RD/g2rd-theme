<?php
/**
 * Module IA G2RD — Bootstrap et orchestration
 *
 * Point d'entrée du module IA. Enregistre les hooks WordPress, charge les assets
 * éditeur et expose la configuration au frontend via wp_localize_script.
 *
 * Chargé uniquement si ThemeOptions::isFeatureEnabled('enable_ai') === true.
 *
 * @package    G2RD\AI
 * @since      1.14.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD\AI;

/**
 * Classe AiModule
 */
class AiModule {

	/**
	 * Namespace REST partagé.
	 *
	 * @var string
	 */
	public const REST_NAMESPACE = 'g2rd/v1';

	/**
	 * Handle du script éditeur.
	 *
	 * @var string
	 */
	public const SCRIPT_HANDLE = 'g2rd-ai-editor';

	/**
	 * Option WordPress pour les réglages IA.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'g2rd_ai_settings';

	/**
	 * Valeurs par défaut des réglages IA.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		'ai_blocks_enabled'        => false,
		'ai_editor_enabled'        => false,
		'ai_woo_enabled'           => false,
		'ai_allowed_roles'         => [ 'administrator', 'editor' ],
		'ai_logs_enabled'          => true,
		'ai_daily_limit'           => 50,
		'ai_default_tone'          => 'professionnel',
		'ai_default_length'        => 'moyen',
		'ai_custom_instructions'   => '',
	];

	/**
	 * Clé d'option du profil du site (refonte AI Studio).
	 * Capturé une fois, réinjecté côté serveur dans tous les prompts.
	 *
	 * @var string
	 */
	public const PROFILE_KEY = 'g2rd_ai_profile';

	/**
	 * Valeurs par défaut du profil du site.
	 *
	 * @var array<string, string>
	 */
	private const PROFILE_DEFAULTS = [
		'activity' => '',
		'city'     => '',
		'target'   => '',
		'tone'     => 'professionnel',
	];

	/**
	 * Retourne le profil du site (fusionné avec les valeurs par défaut).
	 *
	 * @return array<string, string>
	 */
	public static function get_profile(): array {
		$saved = \get_option( self::PROFILE_KEY, [] );
		if ( ! \is_array( $saved ) ) {
			$saved = [];
		}
		return \array_merge( self::PROFILE_DEFAULTS, $saved );
	}

	/**
	 * Enregistre les hooks WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		\add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Charge le script IA dans l'éditeur Gutenberg.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$settings = self::get_settings();

		// Ne rien charger si ni les blocs ni l'éditeur IA ne sont activés.
		if ( ! $settings['ai_blocks_enabled'] && ! $settings['ai_editor_enabled'] ) {
			return;
		}

		// Vérification du rôle utilisateur courant.
		if ( ! self::current_user_has_ai_access( $settings['ai_allowed_roles'] ) ) {
			return;
		}

		$dir_path = \get_template_directory();
		$dir_uri  = \get_template_directory_uri();
		$js_path  = $dir_path . '/blocks/g2rd-ai-editor/build/index.js';
		$css_path = $dir_path . '/blocks/g2rd-ai-editor/build/style-index.css';

		if ( ! \file_exists( $js_path ) ) {
			return;
		}

		\wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$dir_uri . '/blocks/g2rd-ai-editor/build/index.js',
			[
				'wp-plugins',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-compose',
				'wp-edit-post',
				'wp-editor',
				'wp-block-editor',
				'wp-i18n',
				'wp-api-fetch',
				'wp-hooks',
			],
			(string) \filemtime( $js_path ),
			true
		);

		\wp_set_script_translations( self::SCRIPT_HANDLE, 'g2rd' );

		// Passer la configuration au JS.
		\wp_localize_script(
			self::SCRIPT_HANDLE,
			'g2rdAiConfig',
			[
				'restPath'       => '/' . self::REST_NAMESPACE . '/ai/',
				'nonce'          => \wp_create_nonce( 'wp_rest' ),
				'enabled'        => true,
				'blocksEnabled'  => (bool) $settings['ai_blocks_enabled'],
				'editorEnabled'  => (bool) $settings['ai_editor_enabled'],
				'wooEnabled'     => (bool) $settings['ai_woo_enabled'] && \class_exists( 'WooCommerce' ),
				'dailyLimit'     => (int) $settings['ai_daily_limit'],
				'tone'           => \sanitize_text_field( (string) $settings['ai_default_tone'] ),
				'language'       => \get_locale() === 'fr_FR' ? 'fr' : 'en',
				'userCan'        => \current_user_can( 'edit_posts' ),
				'connectorReady' => AiClient::is_available(),
				'settingsUrl'    => \esc_url( \admin_url( 'themes.php?page=g2rd-theme-options#ia' ) ),
				'i18n'           => [
					'generating'    => \esc_html__( 'Génération en cours…', 'g2rd' ),
					'generated'     => \esc_html__( 'Proposition prête', 'g2rd' ),
					'error'         => \esc_html__( 'Une erreur est survenue', 'g2rd' ),
					'insert'        => \esc_html__( 'Insérer', 'g2rd' ),
					'copy'          => \esc_html__( 'Copier', 'g2rd' ),
					'regenerate'    => \esc_html__( 'Régénérer', 'g2rd' ),
					'cancel'        => \esc_html__( 'Annuler', 'g2rd' ),
					'limitReached'  => \esc_html__( 'Limite journalière atteinte', 'g2rd' ),
					'noConnector'   => \esc_html__( 'Connecteur IA non disponible', 'g2rd' ),
				],
			]
		);

		if ( \file_exists( $css_path ) ) {
			\wp_enqueue_style(
				self::SCRIPT_HANDLE,
				$dir_uri . '/blocks/g2rd-ai-editor/build/style-index.css',
				[ 'wp-components' ],
				(string) \filemtime( $css_path )
			);
		}
	}

	/**
	 * Enregistre les endpoints REST du module IA.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new AiRest() )->register_routes();
	}

	/**
	 * Retourne les réglages IA fusionnés avec les valeurs par défaut.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$saved = \get_option( self::OPTION_KEY, [] );
		return \wp_parse_args( \is_array( $saved ) ? $saved : [], self::DEFAULTS );
	}

	/**
	 * Vérifie si l'utilisateur courant a accès au module IA.
	 *
	 * @param array<string> $allowed_roles Rôles autorisés.
	 * @return bool
	 */
	public static function current_user_has_ai_access( array $allowed_roles ): bool {
		if ( ! \is_user_logged_in() ) {
			return false;
		}

		$user = \wp_get_current_user();
		if ( ! ( $user instanceof \WP_User ) ) {
			return false;
		}

		foreach ( $allowed_roles as $role ) {
			if ( \in_array( \sanitize_key( $role ), (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Vérifie et incrémente la limite journalière de l'utilisateur.
	 *
	 * Utilise un transient WordPress avec TTL à minuit pour un compteur par jour.
	 *
	 * @param int $user_id    Identifiant de l'utilisateur.
	 * @param int $max_calls  Limite journalière configurée.
	 * @return bool True si la limite est atteinte.
	 */
	public static function is_daily_limit_reached( int $user_id, int $max_calls ): bool {
		if ( $max_calls <= 0 ) {
			return false;
		}

		$transient_key = 'g2rd_ai_limit_' . $user_id . '_' . \gmdate( 'Ymd' );
		$count         = (int) \get_transient( $transient_key );

		if ( $count >= $max_calls ) {
			return true;
		}

		// Expiration au prochain minuit UTC.
		$seconds_until_midnight = \strtotime( 'tomorrow midnight UTC' ) - \time();
		\set_transient( $transient_key, $count + 1, max( 1, $seconds_until_midnight ) );

		return false;
	}
}
