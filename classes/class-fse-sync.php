<?php
/**
 * Synchronisation FSE — Templates et Template Parts
 *
 * Gère la re-synchronisation des templates FSE entre le filesystem
 * et la base de données WordPress afin d'éviter les erreurs
 * "Template part has been deleted or is unavailable".
 *
 * @package G2RD
 * @since   1.5.0
 * @license EUPL-1.2
 * @copyright (c) 2025 Sebastien GERARD
 */

namespace G2RD;

/**
 * Synchronisation des templates FSE depuis le filesystem.
 */
class FseSync {

	/**
	 * Clé d'option (persistante, résiste aux flush transients) stockant
	 * la version du thème au dernier passage de sync_fse_once.
	 */
	private const SYNC_VERSION_OPTION = 'g2rd_sync_version';

	/**
	 * Clé d'option (persistante) stockant la version du thème au dernier
	 * passage de recreate_fse_templates. Remplace l'ancien transient.
	 */
	private const RECREATE_VERSION_OPTION = 'g2rd_recreate_version';

	/** Clé de l'option stockant la version au dernier flush des permaliens. */
	private const REWRITE_VERSION_OPTION = 'g2rd_rewrite_flushed_version';

	/**
	 * Enregistre les hooks WordPress liés à la synchronisation FSE.
	 *
	 * ⚠️ Ce hook gère uniquement admin_init.
	 * Le hook after_switch_theme est enregistré via register_switch_hook()
	 * directement dans functions.php (avant after_setup_theme).
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'admin_init', [ $this, 'sync_fse_once' ] );
		\add_action( 'admin_init', [ $this, 'recreate_fse_templates' ], 20 );
		\add_action( 'admin_init', [ $this, 'maybe_flush_rewrite_rules' ], 30 );
		\add_filter( 'render_block_data', [ $this, 'normalize_template_part_theme' ] );
	}

	/**
	 * Neutralise l'attribut `theme` des blocs core/template-part au rendu.
	 *
	 * L'éditeur de site ré-injecte « "theme":"<slug>" » dans les références de
	 * template-part à chaque sauvegarde. Si le nom du dossier du thème diffère
	 * de ce slug (ZIP renommé, install différente…), la résolution échoue avec
	 * « Le template part a été supprimé ou n'est pas disponible ». En retirant
	 * l'attribut au rendu, WordPress résout toujours le part depuis le thème
	 * ACTIF — quel que soit le nom du dossier et même après une re-sauvegarde.
	 * S'applique aux templates du filesystem ET aux versions personnalisées en BDD.
	 *
	 * @param array $parsed_block Bloc analysé (avant rendu).
	 * @return array
	 */
	public function normalize_template_part_theme( $parsed_block ) {
		if ( is_array( $parsed_block )
			&& isset( $parsed_block['blockName'], $parsed_block['attrs']['theme'] )
			&& 'core/template-part' === $parsed_block['blockName'] ) {
			unset( $parsed_block['attrs']['theme'] );
		}
		return $parsed_block;
	}

	/**
	 * Enregistre le hook after_switch_theme.
	 * Doit être appelé AVANT after_setup_theme pour être actif lors de l'activation.
	 *
	 * @return void
	 */
	public function register_switch_hook(): void {
		\add_action( 'after_switch_theme', [ $this, 'sync_fse_templates' ] );
	}

	/**
	 * Supprime toutes les entrées DB des templates/template-parts du thème
	 * afin de forcer WordPress à les recharger depuis le filesystem.
	 *
	 * @return void
	 */
	public function sync_fse_templates(): void {
		$slugs = [ \get_stylesheet(), 'G2RD-theme', 'g2rd-theme' ];

		$stale_posts = \get_posts(
			[
				'post_type'      => [ 'wp_template_part', 'wp_template' ],
				'posts_per_page' => -1,
				'post_status'    => [ 'trash', 'auto-draft', 'publish' ],
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => $slugs,
						'operator' => 'IN',
					],
				],
			]
		);

		foreach ( $stale_posts as $post ) {
			\wp_delete_post( $post->ID, true );
		}

		\wp_clean_themes_cache();

		if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}

	/**
	 * Nettoyage conservateur une seule fois par version de thème.
	 *
	 * Utilise wp_option (non effacée par les flush de transients ou les mises
	 * à jour) et supprime uniquement les entrées auto-draft / trash — jamais
	 * les templates publiés que l'utilisateur a personnalisés dans l'éditeur.
	 *
	 * @return void
	 */
	public function sync_fse_once(): void {
		$current_version = \wp_get_theme()->get( 'Version' );

		if ( \get_option( self::SYNC_VERSION_OPTION ) === $current_version ) {
			return;
		}

		// Nettoyage des anciens transients hérités des versions précédentes.
		\delete_transient( 'g2rd_sync_done' );
		\delete_transient( 'g2rd_sync_v2' );
		\delete_transient( 'g2rd_sync_v3' );
		\delete_transient( 'g2rd_sync_v4' );

		$this->cleanup_stale_templates();

		\update_option( self::SYNC_VERSION_OPTION, $current_version, false );
	}

	/**
	 * Supprime uniquement les entrées auto-draft et trash du thème.
	 *
	 * Préserve les templates publiés (personnalisations utilisateur).
	 * Appelé par sync_fse_once() — jamais lors d'un changement de thème.
	 *
	 * @return void
	 */
	private function cleanup_stale_templates(): void {
		$slugs = [ \get_stylesheet(), 'G2RD-theme', 'g2rd-theme' ];

		$stale_posts = \get_posts(
			[
				'post_type'      => [ 'wp_template_part', 'wp_template' ],
				'posts_per_page' => -1,
				'post_status'    => [ 'trash', 'auto-draft' ], // publish intentionnellement exclu
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => $slugs,
						'operator' => 'IN',
					],
				],
			]
		);

		foreach ( $stale_posts as $post ) {
			\wp_delete_post( $post->ID, true );
		}

		\wp_clean_themes_cache();

		if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}

	/**
	 * Recrée en DB les template parts et templates FSE manquants depuis le filesystem.
	 *
	 * S'exécute une seule fois par version de thème (wp_option, résistante aux
	 * flush de transients). Ne touche jamais aux templates déjà présents en DB.
	 *
	 * @return void
	 */
	public function recreate_fse_templates(): void {
		$current_version = \wp_get_theme()->get( 'Version' );

		if ( \get_option( self::RECREATE_VERSION_OPTION ) === $current_version ) {
			return;
		}

		// Nettoyage des anciens transients hérités des versions précédentes.
		\delete_transient( 'g2rd_tpl_recreated_v1' );
		\delete_transient( 'g2rd_tpl_recreated_v2' );
		\delete_transient( 'g2rd_tpl_recreated_v3' );

		$theme_slug = \get_stylesheet();
		$theme_dir  = \get_template_directory();

		// ── Template parts (parts/*.html) ──────────────────────────────────────
		$parts_config = [
			'header'       => [ 'title' => 'Header',         'area' => 'header'        ],
			'header-color' => [ 'title' => 'Header Couleur', 'area' => 'header'        ],
			'footer'       => [ 'title' => 'Footer',         'area' => 'footer'        ],
			'sidebar'      => [ 'title' => 'Sidebar',        'area' => 'uncategorized' ],
		];

		foreach ( $parts_config as $slug => $meta ) {
			$file = $theme_dir . '/parts/' . $slug . '.html';
			if ( ! \file_exists( $file ) ) {
				continue;
			}

			$existing = \get_posts(
				[
					'post_type'      => 'wp_template_part',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'post_name__in'  => [ $slug ],
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => 'wp_theme',
							'field'    => 'name',
							'terms'    => [ $theme_slug ],
						],
					],
				]
			);

			if ( ! empty( $existing ) ) {
				continue;
			}

			$content = (string) \file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$post_id = \wp_insert_post(
				[
					'post_title'   => $meta['title'],
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'wp_template_part',
				]
			);

			if ( $post_id && ! \is_wp_error( $post_id ) ) {
				\wp_set_object_terms( $post_id, $theme_slug, 'wp_theme' );

				if ( ! empty( $meta['area'] ) ) {
					\wp_set_object_terms( $post_id, $meta['area'], 'wp_template_part_area' );
				}
			}
		}

		// ── Templates (templates/*.html) ───────────────────────────────────────
		$template_files = \glob( $theme_dir . '/templates/*.html' ) ?: [];

		foreach ( $template_files as $file ) {
			$slug = basename( $file, '.html' );

			$existing = \get_posts(
				[
					'post_type'      => 'wp_template',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'post_name__in'  => [ $slug ],
					'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						[
							'taxonomy' => 'wp_theme',
							'field'    => 'name',
							'terms'    => [ $theme_slug ],
						],
					],
				]
			);

			if ( ! empty( $existing ) ) {
				continue;
			}

			$content = (string) \file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$post_id = \wp_insert_post(
				[
					'post_title'   => ucfirst( str_replace( '-', ' ', $slug ) ),
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'wp_template',
				]
			);

			if ( $post_id && ! \is_wp_error( $post_id ) ) {
				\wp_set_object_terms( $post_id, $theme_slug, 'wp_theme' );
			}
		}

		\wp_clean_themes_cache();

		if ( \class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}

		\update_option( self::RECREATE_VERSION_OPTION, $current_version, false );
	}

	/**
	 * Flush les règles de réécriture automatiquement si la version du thème a changé.
	 * Évite les 404 sur les archives CPT après un upload manuel (FileZilla, FTP).
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules(): void {
		$current_version = \wp_get_theme()->get( 'Version' );
		$flushed_version = \get_option( self::REWRITE_VERSION_OPTION, '' );

		if ( $flushed_version === $current_version ) {
			return;
		}

		\flush_rewrite_rules( false );
		\update_option( self::REWRITE_VERSION_OPTION, $current_version, false );
	}
}
