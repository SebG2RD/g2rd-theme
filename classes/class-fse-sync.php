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

	/** Clé du transient de synchronisation (v4). */
	private const SYNC_TRANSIENT = 'g2rd_sync_v4';

	/** Clé du transient de recréation (v3). */
	private const RECREATE_TRANSIENT = 'g2rd_tpl_recreated_v3';

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
	 * Resynchronisation forcée une seule fois — se déclenche au prochain admin_init
	 * après restauration manuelle des fichiers, puis ne s'exécute plus.
	 *
	 * @return void
	 */
	public function sync_fse_once(): void {
		if ( \get_transient( self::SYNC_TRANSIENT ) ) {
			return;
		}

		\delete_transient( 'g2rd_sync_done' );
		\delete_transient( 'g2rd_sync_v2' );
		\delete_transient( 'g2rd_sync_v3' );

		$this->sync_fse_templates();

		\set_transient( self::SYNC_TRANSIENT, true, DAY_IN_SECONDS * 30 );
	}

	/**
	 * Recrée en DB les template parts et templates FSE manquants depuis le filesystem.
	 *
	 * @return void
	 */
	public function recreate_fse_templates(): void {
		if ( \get_transient( self::RECREATE_TRANSIENT ) ) {
			return;
		}

		\delete_transient( 'g2rd_tpl_recreated_v1' );
		\delete_transient( 'g2rd_tpl_recreated_v2' );

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

		\set_transient( self::RECREATE_TRANSIENT, true, DAY_IN_SECONDS * 30 );
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
