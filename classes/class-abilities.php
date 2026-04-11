<?php
/**
 * Intégration WordPress Abilities API (WP 6.9+)
 *
 * Expose les CPTs et la configuration du thème G2RD via l'API WordPress Abilities,
 * permettant aux outils IA compatibles MCP d'interagir avec les données du thème.
 *
 * Activation : page d'options G2RD → "Intégration IA / MCP", ou via filtre :
 *   add_filter( 'g2rd_settings_enable_ai', '__return_true' );
 *
 * @package G2RD
 * @since   1.1.0
 * @requires WordPress 6.9+
 */

namespace G2RD;

/**
 * Classe Abilities
 *
 * Enregistre les catégories et capacités G2RD dans le registre WordPress Abilities.
 */
class Abilities {

	/**
	 * Enregistre les hooks si l'API est disponible et activée.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// L'API Abilities n'existe que depuis WP 6.9
		if ( ! \function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		// Opt-in explicite requis — désactivé par défaut pour ne pas exposer
		// accidentellement des données à des outils IA non autorisés.
		if ( ! \apply_filters( 'g2rd_settings_enable_ai', ThemeOptions::isFeatureEnabled( 'enable_ai' ) ) ) {
			return;
		}

		\add_action( 'wp_abilities_api_categories_init', [ $this, 'registerCategories' ] );
		\add_action( 'wp_abilities_api_init',            [ $this, 'registerAbilities'  ] );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Catégories
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre les catégories de capacités G2RD.
	 *
	 * @return void
	 */
	public function registerCategories(): void {
		\wp_register_ability_category(
			'g2rd-content-management',
			[
				'label'       => \__( 'G2RD — Gestion du contenu', 'g2rd' ),
				'description' => \__( 'Lecture, création, modification et suppression du contenu G2RD (Portfolio, Prestations, Membres).', 'g2rd' ),
			]
		);

		\wp_register_ability_category(
			'g2rd-theme-configuration',
			[
				'label'       => \__( 'G2RD — Configuration du thème', 'g2rd' ),
				'description' => \__( 'Consultation de la configuration et des réglages du thème G2RD.', 'g2rd' ),
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Capacités
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre toutes les capacités G2RD.
	 *
	 * @return void
	 */
	public function registerAbilities(): void {
		if ( ThemeOptions::isCPTEnabled( 'portfolio' ) ) {
			$this->registerPortfolioAbilities();
		}
		if ( ThemeOptions::isCPTEnabled( 'prestations' ) ) {
			$this->registerPrestationsAbilities();
		}
		if ( ThemeOptions::isCPTEnabled( 'qui-sommes-nous' ) ) {
			$this->registerMembresAbilities();
		}

		$this->registerThemeAbilities();
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Portfolio
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre les capacités CRUD pour le CPT Portfolio.
	 *
	 * @return void
	 */
	private function registerPortfolioAbilities(): void {

		// ── Lister ────────────────────────────────────────────────────────────
		\wp_register_ability(
			'g2rd/portfolios',
			[
				'label'               => \__( 'Lister les projets portfolio', 'g2rd' ),
				'description'         => \__( 'Retourne la liste paginée des projets portfolio publiés avec leurs scores et URL. Les champs sensibles (login, mot de passe) ne sont jamais exposés.', 'g2rd' ),
				'category'            => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [ 'type' => 'integer', 'default' => 10, 'description' => \__( 'Nombre de projets (max 50)', 'g2rd' ) ],
						'page'     => [ 'type' => 'integer', 'default' => 1  ],
						'search'   => [ 'type' => 'string'  ],
					],
				],
				'output_schema'      => $this->portfolioListSchema(),
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePortfolioList' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'readonly' => true, 'idempotent' => true ],
				],
			]
		);

		// ── Voir ──────────────────────────────────────────────────────────────
		\wp_register_ability(
			'g2rd/view-portfolio',
			[
				'label'       => \__( 'Voir un projet portfolio', 'g2rd' ),
				'description' => \__( 'Retourne toutes les données publiques d\'un projet portfolio par son ID.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'required'   => [ 'post_id' ],
					'properties' => [
						'post_id' => [ 'type' => 'integer', 'description' => \__( 'ID du projet', 'g2rd' ) ],
					],
				],
				'output_schema'      => $this->portfolioItemSchema(),
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePortfolioView' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'readonly' => true, 'idempotent' => true ],
				],
			]
		);

		// ── Créer ─────────────────────────────────────────────────────────────
		\wp_register_ability(
			'g2rd/create-portfolio',
			[
				'label'       => \__( 'Créer un projet portfolio', 'g2rd' ),
				'description' => \__( 'Crée un nouveau projet portfolio (brouillon par défaut).', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'required'   => [ 'title' ],
					'properties' => [
						'title'          => [ 'type' => 'string'  ],
						'content'        => [ 'type' => 'string'  ],
						'status'         => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ], 'default' => 'draft' ],
						'portfolio_link' => [ 'type' => 'string'  ],
						'portfolio_perf' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_a11y' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_bp'   => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_seo'  => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
					],
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'status'  => [ 'type' => 'string'  ],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePortfolioCreate' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'idempotent' => false ],
				],
			]
		);

		// ── Modifier ──────────────────────────────────────────────────────────
		\wp_register_ability(
			'g2rd/update-portfolio',
			[
				'label'       => \__( 'Modifier un projet portfolio', 'g2rd' ),
				'description' => \__( 'Met à jour les données d\'un projet portfolio existant.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'required'   => [ 'post_id' ],
					'properties' => [
						'post_id'        => [ 'type' => 'integer' ],
						'title'          => [ 'type' => 'string'  ],
						'content'        => [ 'type' => 'string'  ],
						'status'         => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ] ],
						'portfolio_link' => [ 'type' => 'string'  ],
						'portfolio_perf' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_a11y' => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_bp'   => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
						'portfolio_seo'  => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100 ],
					],
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'updated' => [ 'type' => 'boolean' ],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePortfolioUpdate' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'idempotent' => true ],
				],
			]
		);

		// ── Supprimer ─────────────────────────────────────────────────────────
		\wp_register_ability(
			'g2rd/delete-portfolio',
			[
				'label'       => \__( 'Supprimer un projet portfolio', 'g2rd' ),
				'description' => \__( 'Supprime définitivement un projet portfolio. Action irréversible — confirmation explicite requise.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'required'   => [ 'post_id', 'confirm' ],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'confirm' => [ 'type' => 'boolean', 'description' => \__( 'Doit être true pour confirmer la suppression', 'g2rd' ) ],
					],
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'deleted' => [ 'type' => 'boolean' ],
						'post_id' => [ 'type' => 'integer' ],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'manage_options' ),
				'execute_callback'    => [ $this, 'executePortfolioDelete' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'destructive' => true, 'idempotent' => false ],
				],
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Prestations
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre les capacités CRUD pour le CPT Prestations.
	 *
	 * @return void
	 */
	private function registerPrestationsAbilities(): void {

		\wp_register_ability(
			'g2rd/prestations',
			[
				'label'       => \__( 'Lister les prestations', 'g2rd' ),
				'description' => \__( 'Retourne la liste des prestations publiées.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [ 'type' => 'integer', 'default' => 10 ],
						'page'     => [ 'type' => 'integer', 'default' => 1  ],
						'search'   => [ 'type' => 'string'  ],
					],
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'items' => [ 'type' => 'array', 'items' => $this->prestationItemSchema() ],
						'total' => [ 'type' => 'integer' ],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePrestationList' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'readonly' => true, 'idempotent' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/view-prestation',
			[
				'label'       => \__( 'Voir une prestation', 'g2rd' ),
				'description' => \__( 'Retourne les données complètes d\'une prestation par son ID.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id' ],
					'properties' => [ 'post_id' => [ 'type' => 'integer' ] ],
				],
				'output_schema'      => $this->prestationItemSchema(),
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePrestationView' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'readonly' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/create-prestation',
			[
				'label'       => \__( 'Créer une prestation', 'g2rd' ),
				'description' => \__( 'Crée une nouvelle prestation avec son contenu Gutenberg.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'title' ],
					'properties' => [
						'title'   => [ 'type' => 'string' ],
						'content' => [ 'type' => 'string', 'description' => \__( 'Contenu Gutenberg sérialisé ou HTML', 'g2rd' ) ],
						'status'  => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ], 'default' => 'draft' ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'post_id' => [ 'type' => 'integer' ], 'status' => [ 'type' => 'string' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePrestationCreate' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'idempotent' => false ] ],
			]
		);

		\wp_register_ability(
			'g2rd/update-prestation',
			[
				'label'       => \__( 'Modifier une prestation', 'g2rd' ),
				'description' => \__( 'Met à jour le titre ou le contenu Gutenberg d\'une prestation.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id' ],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'title'   => [ 'type' => 'string'  ],
						'content' => [ 'type' => 'string'  ],
						'status'  => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ] ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'post_id' => [ 'type' => 'integer' ], 'updated' => [ 'type' => 'boolean' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executePrestationUpdate' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'idempotent' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/delete-prestation',
			[
				'label'       => \__( 'Supprimer une prestation', 'g2rd' ),
				'description' => \__( 'Supprime définitivement une prestation. Confirmation requise.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id', 'confirm' ],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'confirm' => [ 'type' => 'boolean' ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'deleted' => [ 'type' => 'boolean' ], 'post_id' => [ 'type' => 'integer' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'manage_options' ),
				'execute_callback'    => [ $this, 'executePrestationDelete' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'destructive' => true, 'idempotent' => false ] ],
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Qui sommes-nous (membres)
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre les capacités CRUD pour le CPT Qui sommes-nous.
	 *
	 * @return void
	 */
	private function registerMembresAbilities(): void {

		\wp_register_ability(
			'g2rd/membres',
			[
				'label'       => \__( 'Lister les membres', 'g2rd' ),
				'description' => \__( 'Retourne la liste des membres (Qui sommes-nous) avec leurs compétences et objectifs.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object',
					'properties' => [
						'per_page' => [ 'type' => 'integer', 'default' => 10 ],
						'page'     => [ 'type' => 'integer', 'default' => 1  ],
					],
				],
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'items' => [ 'type' => 'array', 'items' => $this->membreItemSchema() ],
						'total' => [ 'type' => 'integer' ],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executeMembreList' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'readonly' => true, 'idempotent' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/view-membre',
			[
				'label'       => \__( 'Voir un membre', 'g2rd' ),
				'description' => \__( 'Retourne les données complètes d\'un membre par son ID.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id' ],
					'properties' => [ 'post_id' => [ 'type' => 'integer' ] ],
				],
				'output_schema'      => $this->membreItemSchema(),
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executeMembreView' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'readonly' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/create-membre',
			[
				'label'       => \__( 'Créer un membre', 'g2rd' ),
				'description' => \__( 'Crée un nouveau profil de membre.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'title' ],
					'properties' => [
						'title'           => [ 'type' => 'string' ],
						'status'          => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ], 'default' => 'draft' ],
						'experience_dev'  => [ 'type' => 'string' ],
						'soft_skills'     => [ 'type' => 'string' ],
						'methodologie'    => [ 'type' => 'string' ],
						'objectif'        => [ 'type' => 'string' ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'post_id' => [ 'type' => 'integer' ], 'status' => [ 'type' => 'string' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executeMembreCreate' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'idempotent' => false ] ],
			]
		);

		\wp_register_ability(
			'g2rd/update-membre',
			[
				'label'       => \__( 'Modifier un membre', 'g2rd' ),
				'description' => \__( 'Met à jour les données d\'un membre existant.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id' ],
					'properties' => [
						'post_id'        => [ 'type' => 'integer' ],
						'title'          => [ 'type' => 'string' ],
						'status'         => [ 'type' => 'string', 'enum' => [ 'draft', 'publish' ] ],
						'experience_dev' => [ 'type' => 'string' ],
						'soft_skills'    => [ 'type' => 'string' ],
						'methodologie'   => [ 'type' => 'string' ],
						'objectif'       => [ 'type' => 'string' ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'post_id' => [ 'type' => 'integer' ], 'updated' => [ 'type' => 'boolean' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'edit_posts' ),
				'execute_callback'    => [ $this, 'executeMembreUpdate' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'idempotent' => true ] ],
			]
		);

		\wp_register_ability(
			'g2rd/delete-membre',
			[
				'label'       => \__( 'Supprimer un membre', 'g2rd' ),
				'description' => \__( 'Supprime définitivement un profil de membre. Confirmation requise.', 'g2rd' ),
				'category'    => 'g2rd-content-management',
				'input_schema' => [
					'type' => 'object', 'required' => [ 'post_id', 'confirm' ],
					'properties' => [
						'post_id' => [ 'type' => 'integer' ],
						'confirm' => [ 'type' => 'boolean' ],
					],
				],
				'output_schema' => [
					'type' => 'object',
					'properties' => [ 'deleted' => [ 'type' => 'boolean' ], 'post_id' => [ 'type' => 'integer' ] ],
				],
				'permission_callback' => fn() => \current_user_can( 'manage_options' ),
				'execute_callback'    => [ $this, 'executeMembreDelete' ],
				'meta' => [ 'show_in_rest' => true, 'annotations' => [ 'destructive' => true, 'idempotent' => false ] ],
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Thème
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enregistre les capacités de configuration du thème.
	 *
	 * @return void
	 */
	private function registerThemeAbilities(): void {

		\wp_register_ability(
			'g2rd/theme-settings',
			[
				'label'       => \__( 'Lire la configuration du thème G2RD', 'g2rd' ),
				'description' => \__( 'Retourne la version du thème, les CPTs actifs et les fonctionnalités activées.', 'g2rd' ),
				'category'    => 'g2rd-theme-configuration',
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'version'  => [ 'type' => 'string' ],
						'cpts'     => [
							'type'       => 'object',
							'properties' => [
								'portfolio'       => [ 'type' => 'boolean' ],
								'prestations'     => [ 'type' => 'boolean' ],
								'qui_sommes_nous' => [ 'type' => 'boolean' ],
							],
						],
						'features' => [
							'type'       => 'object',
							'properties' => [
								'gsap_animations' => [ 'type' => 'boolean' ],
								'particles_effect' => [ 'type' => 'boolean' ],
								'glass_effect'    => [ 'type' => 'boolean' ],
								'dark_mode'       => [ 'type' => 'boolean' ],
							],
						],
					],
				],
				'permission_callback' => fn() => \current_user_can( 'manage_options' ),
				'execute_callback'    => [ $this, 'executeThemeSettings' ],
				'meta' => [
					'show_in_rest' => true,
					'annotations'  => [ 'readonly' => true, 'idempotent' => true ],
				],
			]
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Callbacks d'exécution — Portfolio
	// ─────────────────────────────────────────────────────────────────────────

	/** @param array $input Paramètres d'entrée validés. */
	public function executePortfolioList( array $input ): array {
		$query = new \WP_Query( [
			'post_type'      => 'portfolio',
			'post_status'    => 'publish',
			'posts_per_page' => \min( \absint( $input['per_page'] ?? 10 ), 50 ),
			'paged'          => \absint( $input['page'] ?? 1 ),
			's'              => \sanitize_text_field( $input['search'] ?? '' ),
			'fields'         => 'all',
		] );

		return [
			'items' => \array_map( [ $this, 'formatPortfolio' ], $query->posts ),
			'total' => (int) $query->found_posts,
		];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePortfolioView( array $input ): array {
		$post = \get_post( \absint( $input['post_id'] ?? 0 ) );
		if ( ! $post || 'portfolio' !== $post->post_type ) {
			return [ 'error' => \__( 'Projet introuvable.', 'g2rd' ) ];
		}
		return $this->formatPortfolio( $post );
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePortfolioCreate( array $input ): array {
		$post_id = \wp_insert_post( [
			'post_title'   => \sanitize_text_field( $input['title'] ),
			'post_content' => \wp_kses_post( $input['content'] ?? '' ),
			'post_status'  => \in_array( $input['status'] ?? 'draft', [ 'draft', 'publish' ], true ) ? $input['status'] : 'draft',
			'post_type'    => 'portfolio',
		] );

		if ( \is_wp_error( $post_id ) ) {
			return [ 'error' => $post_id->get_error_message() ];
		}
		$this->savePortfolioMeta( (int) $post_id, $input );
		return [ 'post_id' => (int) $post_id, 'status' => (string) \get_post_status( $post_id ) ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePortfolioUpdate( array $input ): array {
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'portfolio' !== $post->post_type ) {
			return [ 'error' => \__( 'Projet introuvable.', 'g2rd' ) ];
		}
		$data = [ 'ID' => $post_id ];
		if ( isset( $input['title']   ) ) $data['post_title']   = \sanitize_text_field( $input['title'] );
		if ( isset( $input['content'] ) ) $data['post_content'] = \wp_kses_post( $input['content'] );
		if ( isset( $input['status']  ) && \in_array( $input['status'], [ 'draft', 'publish' ], true ) ) {
			$data['post_status'] = $input['status'];
		}
		$result = \wp_update_post( $data, true );
		if ( \is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}
		$this->savePortfolioMeta( $post_id, $input );
		return [ 'post_id' => $post_id, 'updated' => true ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePortfolioDelete( array $input ): array {
		if ( empty( $input['confirm'] ) ) {
			return [ 'error' => \__( 'La suppression doit être confirmée (confirm: true).', 'g2rd' ) ];
		}
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'portfolio' !== $post->post_type ) {
			return [ 'error' => \__( 'Projet introuvable.', 'g2rd' ) ];
		}
		$deleted = \wp_delete_post( $post_id, true );
		return [ 'deleted' => (bool) $deleted, 'post_id' => $post_id ];
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Callbacks d'exécution — Prestations
	// ─────────────────────────────────────────────────────────────────────────

	/** @param array $input Paramètres d'entrée validés. */
	public function executePrestationList( array $input ): array {
		$query = new \WP_Query( [
			'post_type'      => 'prestations',
			'post_status'    => 'publish',
			'posts_per_page' => \min( \absint( $input['per_page'] ?? 10 ), 50 ),
			'paged'          => \absint( $input['page'] ?? 1 ),
			's'              => \sanitize_text_field( $input['search'] ?? '' ),
			'fields'         => 'all',
		] );
		return [
			'items' => \array_map( [ $this, 'formatPrestation' ], $query->posts ),
			'total' => (int) $query->found_posts,
		];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePrestationView( array $input ): array {
		$post = \get_post( \absint( $input['post_id'] ?? 0 ) );
		if ( ! $post || 'prestations' !== $post->post_type ) {
			return [ 'error' => \__( 'Prestation introuvable.', 'g2rd' ) ];
		}
		return $this->formatPrestation( $post );
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePrestationCreate( array $input ): array {
		$post_id = \wp_insert_post( [
			'post_title'   => \sanitize_text_field( $input['title'] ),
			'post_content' => \wp_kses_post( $input['content'] ?? '' ),
			'post_status'  => \in_array( $input['status'] ?? 'draft', [ 'draft', 'publish' ], true ) ? $input['status'] : 'draft',
			'post_type'    => 'prestations',
		] );
		if ( \is_wp_error( $post_id ) ) {
			return [ 'error' => $post_id->get_error_message() ];
		}
		return [ 'post_id' => (int) $post_id, 'status' => (string) \get_post_status( $post_id ) ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePrestationUpdate( array $input ): array {
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'prestations' !== $post->post_type ) {
			return [ 'error' => \__( 'Prestation introuvable.', 'g2rd' ) ];
		}
		$data = [ 'ID' => $post_id ];
		if ( isset( $input['title']   ) ) $data['post_title']   = \sanitize_text_field( $input['title'] );
		if ( isset( $input['content'] ) ) $data['post_content'] = \wp_kses_post( $input['content'] );
		if ( isset( $input['status']  ) && \in_array( $input['status'], [ 'draft', 'publish' ], true ) ) {
			$data['post_status'] = $input['status'];
		}
		$result = \wp_update_post( $data, true );
		if ( \is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}
		return [ 'post_id' => $post_id, 'updated' => true ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executePrestationDelete( array $input ): array {
		if ( empty( $input['confirm'] ) ) {
			return [ 'error' => \__( 'La suppression doit être confirmée (confirm: true).', 'g2rd' ) ];
		}
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'prestations' !== $post->post_type ) {
			return [ 'error' => \__( 'Prestation introuvable.', 'g2rd' ) ];
		}
		return [ 'deleted' => (bool) \wp_delete_post( $post_id, true ), 'post_id' => $post_id ];
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Callbacks d'exécution — Membres
	// ─────────────────────────────────────────────────────────────────────────

	/** @param array $input Paramètres d'entrée validés. */
	public function executeMembreList( array $input ): array {
		$query = new \WP_Query( [
			'post_type'      => 'qui-sommes-nous',
			'post_status'    => 'publish',
			'posts_per_page' => \min( \absint( $input['per_page'] ?? 10 ), 50 ),
			'paged'          => \absint( $input['page'] ?? 1 ),
			'fields'         => 'all',
		] );
		return [
			'items' => \array_map( [ $this, 'formatMembre' ], $query->posts ),
			'total' => (int) $query->found_posts,
		];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executeMembreView( array $input ): array {
		$post = \get_post( \absint( $input['post_id'] ?? 0 ) );
		if ( ! $post || 'qui-sommes-nous' !== $post->post_type ) {
			return [ 'error' => \__( 'Membre introuvable.', 'g2rd' ) ];
		}
		return $this->formatMembre( $post );
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executeMembreCreate( array $input ): array {
		$post_id = \wp_insert_post( [
			'post_title'  => \sanitize_text_field( $input['title'] ),
			'post_status' => \in_array( $input['status'] ?? 'draft', [ 'draft', 'publish' ], true ) ? $input['status'] : 'draft',
			'post_type'   => 'qui-sommes-nous',
		] );
		if ( \is_wp_error( $post_id ) ) {
			return [ 'error' => $post_id->get_error_message() ];
		}
		$this->saveMembreMeta( (int) $post_id, $input );
		return [ 'post_id' => (int) $post_id, 'status' => (string) \get_post_status( $post_id ) ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executeMembreUpdate( array $input ): array {
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'qui-sommes-nous' !== $post->post_type ) {
			return [ 'error' => \__( 'Membre introuvable.', 'g2rd' ) ];
		}
		$data = [ 'ID' => $post_id ];
		if ( isset( $input['title']  ) ) $data['post_title'] = \sanitize_text_field( $input['title'] );
		if ( isset( $input['status'] ) && \in_array( $input['status'], [ 'draft', 'publish' ], true ) ) {
			$data['post_status'] = $input['status'];
		}
		$result = \wp_update_post( $data, true );
		if ( \is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}
		$this->saveMembreMeta( $post_id, $input );
		return [ 'post_id' => $post_id, 'updated' => true ];
	}

	/** @param array $input Paramètres d'entrée validés. */
	public function executeMembreDelete( array $input ): array {
		if ( empty( $input['confirm'] ) ) {
			return [ 'error' => \__( 'La suppression doit être confirmée (confirm: true).', 'g2rd' ) ];
		}
		$post_id = \absint( $input['post_id'] ?? 0 );
		$post    = \get_post( $post_id );
		if ( ! $post || 'qui-sommes-nous' !== $post->post_type ) {
			return [ 'error' => \__( 'Membre introuvable.', 'g2rd' ) ];
		}
		return [ 'deleted' => (bool) \wp_delete_post( $post_id, true ), 'post_id' => $post_id ];
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Callbacks d'exécution — Thème
	// ─────────────────────────────────────────────────────────────────────────

	/** @param array $_input Paramètres d'entrée (non utilisés — thème sans input). */
	public function executeThemeSettings( array $_input ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return [
			'version' => \wp_get_theme()->get( 'Version' ),
			'cpts'    => [
				'portfolio'       => ThemeOptions::isCPTEnabled( 'portfolio' ),
				'prestations'     => ThemeOptions::isCPTEnabled( 'prestations' ),
				'qui_sommes_nous' => ThemeOptions::isCPTEnabled( 'qui-sommes-nous' ),
			],
			'features' => [
				'gsap_animations'  => ThemeOptions::isFeatureEnabled( 'gsap_animations' ),
				'particles_effect' => ThemeOptions::isFeatureEnabled( 'particles_effect' ),
				'glass_effect'     => ThemeOptions::isFeatureEnabled( 'glass_effect' ),
				'dark_mode'        => ThemeOptions::isFeatureEnabled( 'dark_mode' ),
			],
		];
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Helpers — formatage
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Formate un post Portfolio en tableau de données publiques.
	 * Les champs sensibles (_portfolio_password, _portfolio_login) sont exclus.
	 *
	 * @param \WP_Post $post Instance du post.
	 * @return array Données formatées.
	 */
	private function formatPortfolio( \WP_Post $post ): array {
		return [
			'post_id'          => $post->ID,
			'title'            => \get_the_title( $post ),
			'content'          => \get_post_field( 'post_content', $post ),
			'status'           => $post->post_status,
			'url'              => \esc_url( \get_permalink( $post ) ),
			'thumbnail'        => \get_the_post_thumbnail_url( $post, 'full' ) ?: '',
			'portfolio_link'   => (string) \get_post_meta( $post->ID, '_portfolio_link', true ),
			'portfolio_perf'   => (int)    \get_post_meta( $post->ID, '_portfolio_perf', true ),
			'portfolio_a11y'   => (int)    \get_post_meta( $post->ID, '_portfolio_a11y', true ),
			'portfolio_bp'     => (int)    \get_post_meta( $post->ID, '_portfolio_bp',   true ),
			'portfolio_seo'    => (int)    \get_post_meta( $post->ID, '_portfolio_seo',  true ),
			'hebergement'      => (string) \get_post_meta( $post->ID, '_portfolio_hebergement', true ),
			'maintenance'      => (bool)   \get_post_meta( $post->ID, '_portfolio_maintenance', true ),
			'contrat'          => (string) \get_post_meta( $post->ID, '_portfolio_contrat',    true ),
			'date_anniv'       => (string) \get_post_meta( $post->ID, '_portfolio_date_anniv', true ),
			// _portfolio_password et _portfolio_login intentionnellement exclus
		];
	}

	/**
	 * Formate un post Prestation.
	 *
	 * @param \WP_Post $post Instance du post.
	 * @return array Données formatées.
	 */
	private function formatPrestation( \WP_Post $post ): array {
		return [
			'post_id'   => $post->ID,
			'title'     => \get_the_title( $post ),
			'content'   => \get_post_field( 'post_content', $post ),
			'excerpt'   => \get_the_excerpt( $post ),
			'status'    => $post->post_status,
			'url'       => \esc_url( \get_permalink( $post ) ),
			'thumbnail' => \get_the_post_thumbnail_url( $post, 'full' ) ?: '',
		];
	}

	/**
	 * Formate un post Membre (Qui sommes-nous).
	 *
	 * @param \WP_Post $post Instance du post.
	 * @return array Données formatées.
	 */
	private function formatMembre( \WP_Post $post ): array {
		return [
			'post_id'        => $post->ID,
			'title'          => \get_the_title( $post ),
			'status'         => $post->post_status,
			'thumbnail'      => \get_the_post_thumbnail_url( $post, 'full' ) ?: '',
			'experience_dev' => (string) \get_post_meta( $post->ID, '_experience_dev', true ),
			'soft_skills'    => (string) \get_post_meta( $post->ID, '_soft_skills',    true ),
			'methodologie'   => (string) \get_post_meta( $post->ID, '_methodologie',   true ),
			'objectif'       => (string) \get_post_meta( $post->ID, '_objectif',       true ),
		];
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Helpers — sauvegarde des méta
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Sauvegarde les méta-champs publics d'un Portfolio.
	 *
	 * @param int   $post_id ID du post.
	 * @param array $input   Données à sauvegarder.
	 * @return void
	 */
	private function savePortfolioMeta( int $post_id, array $input ): void {
		if ( isset( $input['portfolio_link'] ) ) {
			\update_post_meta( $post_id, '_portfolio_link', \esc_url_raw( $input['portfolio_link'] ) );
		}
		foreach ( [ 'portfolio_perf', 'portfolio_a11y', 'portfolio_bp', 'portfolio_seo' ] as $field ) {
			if ( isset( $input[ $field ] ) ) {
				\update_post_meta( $post_id, '_' . $field, \absint( $input[ $field ] ) );
			}
		}
	}

	/**
	 * Sauvegarde les méta-champs d'un Membre.
	 *
	 * @param int   $post_id ID du post.
	 * @param array $input   Données à sauvegarder.
	 * @return void
	 */
	private function saveMembreMeta( int $post_id, array $input ): void {
		$fields = [ 'experience_dev', 'soft_skills', 'methodologie', 'objectif' ];
		foreach ( $fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				\update_post_meta( $post_id, '_' . $field, \sanitize_textarea_field( $input[ $field ] ) );
			}
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Helpers — schémas JSON
	// ─────────────────────────────────────────────────────────────────────────

	/** @return array Schéma d'un item portfolio. */
	private function portfolioItemSchema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'        => [ 'type' => 'integer' ],
				'title'          => [ 'type' => 'string'  ],
				'content'        => [ 'type' => 'string'  ],
				'status'         => [ 'type' => 'string'  ],
				'url'            => [ 'type' => 'string'  ],
				'thumbnail'      => [ 'type' => 'string'  ],
				'portfolio_link' => [ 'type' => 'string',  'description' => \__( 'URL du projet', 'g2rd' ) ],
				'portfolio_perf' => [ 'type' => 'integer', 'description' => \__( 'Score performance (0-100)', 'g2rd' ) ],
				'portfolio_a11y' => [ 'type' => 'integer', 'description' => \__( 'Score accessibilité (0-100)', 'g2rd' ) ],
				'portfolio_bp'   => [ 'type' => 'integer', 'description' => \__( 'Score bonnes pratiques (0-100)', 'g2rd' ) ],
				'portfolio_seo'  => [ 'type' => 'integer', 'description' => \__( 'Score SEO (0-100)', 'g2rd' ) ],
				'hebergement'    => [ 'type' => 'string'  ],
				'maintenance'    => [ 'type' => 'boolean' ],
				'contrat'        => [ 'type' => 'string'  ],
				'date_anniv'     => [ 'type' => 'string'  ],
			],
		];
	}

	/** @return array Schéma liste portfolio. */
	private function portfolioListSchema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'items' => [ 'type' => 'array', 'items' => $this->portfolioItemSchema() ],
				'total' => [ 'type' => 'integer' ],
			],
		];
	}

	/** @return array Schéma d'un item prestation. */
	private function prestationItemSchema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'   => [ 'type' => 'integer' ],
				'title'     => [ 'type' => 'string'  ],
				'content'   => [ 'type' => 'string'  ],
				'excerpt'   => [ 'type' => 'string'  ],
				'status'    => [ 'type' => 'string'  ],
				'url'       => [ 'type' => 'string'  ],
				'thumbnail' => [ 'type' => 'string'  ],
			],
		];
	}

	/** @return array Schéma d'un item membre. */
	private function membreItemSchema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'        => [ 'type' => 'integer' ],
				'title'          => [ 'type' => 'string'  ],
				'status'         => [ 'type' => 'string'  ],
				'thumbnail'      => [ 'type' => 'string'  ],
				'experience_dev' => [ 'type' => 'string'  ],
				'soft_skills'    => [ 'type' => 'string'  ],
				'methodologie'   => [ 'type' => 'string'  ],
				'objectif'       => [ 'type' => 'string'  ],
			],
		];
	}
}
