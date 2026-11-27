<?php
/**
 * G2RD — Créa Pub Communication : thème enfant pour l'agence de Vernon (27).
 *
 * Rôles de ce fichier :
 *  1. Charger l'installateur de contenus (inc/setup.php) et la commande WP-CLI.
 *  2. Appliquer la charte Créa Pub par défaut (styles/creapub.json du parent).
 *  3. Forcer les réglages du parent utiles au projet (CPT actifs, slug
 *     /realisations/ pour le portfolio, mode sombre coupé).
 *  4. Verrouiller les CPT en « champs à remplir » (template_lock contentOnly).
 *  5. Charger la feuille de style et le script d'animation du hero.
 *
 * Le thème parent g2rd-theme n'est jamais modifié : il reçoit uniquement
 * la variation styles/creapub.json.
 *
 * @package G2RD_CreaPub
 * @license EUPL-1.2
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

const VERSION    = '1.4.1';
const TEXTDOMAIN = 'g2rd-creapub';

require_once __DIR__ . '/inc/media.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/form.php';
require_once __DIR__ . '/inc/admin.php';

if ( \defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once __DIR__ . '/inc/class-cli-command.php';
}

/**
 * Fonctionnalités du parent forcées depuis l'enfant, sans toucher au parent.
 *
 * - Mode sombre : la charte Créa Pub est noire par nature ; la bascule du
 *   parent réécrirait la palette et casserait les contrastes validés.
 * - GSAP activé : apparition des sections au défilement (classes gsap-*).
 * - Particules, glassmorphism : aucun usage ici.
 *
 * Désactivable : add_filter( 'creapub_force_parent_features', '__return_false' ).
 *
 * @param mixed $features Valeur de l'option g2rd_theme_features.
 * @return array<string,mixed>
 */
function force_parent_features( $features ): array {
	$features = \is_array( $features ) ? $features : array();
	if ( \apply_filters( 'creapub_force_parent_features', true ) ) {
		$features['dark_mode']        = false;
		// Animations d'apparition au défilement : moteur GSAP + ScrollTrigger du parent,
		// piloté par les classes gsap-* des blocs (panneau « Animation » dans l'éditeur).
		$features['gsap_animations']  = true;
		$features['particles_effect'] = false;
		$features['glass_effect']     = false;
	}
	return $features;
}
\add_filter( 'option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );
\add_filter( 'default_option_g2rd_theme_features', __NAMESPACE__ . '\force_parent_features' );

/**
 * Types de contenu du parent : les trois CPT servent au projet.
 *
 * - portfolio → /realisations/ (URLs du plan de refonte et de la table 301) ;
 * - prestations → /prestations/<slug>/ ;
 * - qui-sommes-nous : fiches de la page La Team, sans archive publique.
 *
 * @param mixed $cpts Valeur de l'option g2rd_cpt_settings.
 * @return array<string,array<string,mixed>>
 */
function force_parent_cpts( $cpts ): array {
	$cpts = \is_array( $cpts ) ? $cpts : array();
	foreach ( array( 'portfolio', 'prestations', 'qui-sommes-nous' ) as $key ) {
		$cpts[ $key ]            = \is_array( $cpts[ $key ] ?? null ) ? $cpts[ $key ] : array();
		$cpts[ $key ]['enabled'] = true;
	}
	$cpts['portfolio']['slug']            = 'realisations';
	$cpts['portfolio']['has_archive']     = true;
	$cpts['prestations']['slug']          = 'prestations';
	$cpts['prestations']['has_archive']   = false; // La grille vit sur la page d'accueil et dans le menu.
	$cpts['qui-sommes-nous']['has_archive'] = false; // La Team est une page.
	return $cpts;
}
\add_filter( 'option_g2rd_cpt_settings', __NAMESPACE__ . '\force_parent_cpts' );
\add_filter( 'default_option_g2rd_cpt_settings', __NAMESPACE__ . '\force_parent_cpts' );

/**
 * FseSync du parent : à chaque nouvelle version du thème ACTIF, il recopie en
 * base TOUS les modèles et éléments du parent (header, footer, page-accueil…)
 * sous le nom de l'enfant. Une copie en base passe devant les fichiers de
 * l'enfant : le header, le footer et les gabarits Créa Pub seraient masqués.
 *
 * On lui indique que la recréation est déjà faite pour cette version (option
 * lue par le parent), sans modifier le parent. Les modèles personnalisés dans
 * l'éditeur de site restent en base et gardent la main, comme prévu.
 *
 * @return string Version courante du thème enfant.
 */
\add_filter(
	'pre_option_g2rd_recreate_version',
	static function () {
		return (string) \wp_get_theme()->get( 'Version' );
	}
);

/**
 * Copies déjà faites par le parent avant l'activation de cette version :
 * retirées une fois par version de l'enfant (seulement celles restées
 * identiques aux fichiers du parent).
 */
\add_action(
	'admin_init',
	static function (): void {
		if ( \get_option( 'g2rd_creapub_purged' ) === VERSION ) {
			return;
		}
		purge_parent_template_copies();
		\update_option( 'g2rd_creapub_purged', VERSION, false );
	},
	25
);

/**
 * Supports du thème et styles de l'éditeur.
 */
\add_action(
	'after_setup_theme',
	function (): void {
		\load_child_theme_textdomain( TEXTDOMAIN, \get_stylesheet_directory() . '/languages' );
		// L'éditeur affiche les mêmes styles que le front (pinceau, tuiles, boutons).
		\add_editor_style( 'style.css' );
	}
);

/**
 * Applique la charte Créa Pub sans action manuelle.
 *
 * Le parent recompose theme.json (settings + styles + variations) en priorité 10.
 * Un theme.json d'enfant n'apporte de façon fiable que des `settings` : les
 * `styles` de la charte vivent dans styles/creapub.json, déposé dans le THÈME
 * PARENT (seul fichier qu'il reçoit). On les fusionne ici, après le parent,
 * pour que la charte soit active dès l'activation du thème enfant. La
 * variation reste disponible dans le sélecteur de styles.
 *
 * @param \WP_Theme_JSON_Data $theme_json Données theme.json du thème.
 * @return \WP_Theme_JSON_Data
 */
function apply_default_variation( $theme_json ) {
	static $variation = null;

	if ( null === $variation ) {
		$variation = array();
		// Variation dans l'enfant si présente (elle masque alors celle du parent), sinon dans le parent.
		foreach ( array( \get_stylesheet_directory(), \get_template_directory() ) as $dir ) {
			$path = $dir . '/styles/creapub.json';
			if ( \is_readable( $path ) ) {
				$data = \wp_json_file_decode( $path, array( 'associative' => true ) );
				if ( \is_array( $data ) && isset( $data['styles'] ) && \is_array( $data['styles'] ) ) {
					$variation = $data['styles'];
				}
				break;
			}
		}
	}

	if ( empty( $variation ) || ! \is_object( $theme_json ) || ! \method_exists( $theme_json, 'update_with' ) ) {
		return $theme_json;
	}

	return $theme_json->update_with(
		array(
			'version' => 3,
			'styles'  => $variation,
		)
	);
}
\add_filter( 'wp_theme_json_data_theme', __NAMESPACE__ . '\apply_default_variation', 20 );

/**
 * Catégories de compositions.
 */
\add_action(
	'init',
	function (): void {
		\register_block_pattern_category( 'creapub', array( 'label' => \__( 'Créa Pub — Sections', 'g2rd-creapub' ) ) );
		\register_block_pattern_category( 'creapub-pages', array( 'label' => \__( 'Créa Pub — Pages', 'g2rd-creapub' ) ) );
	}
);

/**
 * CPT en « champs à remplir » : gabarit pré-rempli + structure verrouillée.
 *
 * Le client édite les contenus (textes, photos, FAQ), ne peut ni déplacer
 * ni supprimer les sections. Les pages standards restent en édition libre.
 *
 * @param array<string,mixed> $args      Arguments du type de contenu.
 * @param string              $post_type Clé du type.
 * @return array<string,mixed>
 */
function lock_cpt_templates( array $args, string $post_type ): array {

	$locked = array( 'prestations', 'portfolio', 'qui-sommes-nous' );
	if ( ! \in_array( $post_type, $locked, true ) ) {
		return $args;
	}

	// Éditeur de blocs, boucles de requête et gabarits de saisie : l'API REST est indispensable.
	$args['show_in_rest']  = true;
	$args['template_lock'] = 'contentOnly';

	switch ( $post_type ) {
		case 'prestations':
			// Même structure que les prestations installées : intro, réalisations
			// (remplies seules depuis le portfolio de même catégorie), savoir-faire, FAQ.
			$args['template'] = array(
				array( 'core/paragraph', array( 'placeholder' => 'Intro : une phrase qui présente la prestation (mettez le mot-clé en italique)…', 'align' => 'center', 'className' => 'creapub-intro-text' ) ),
				array( 'core/heading', array( 'level' => 2, 'content' => 'Nos réalisations', 'className' => 'creapub-section-heading' ) ),
				array(
					'core/query',
					array(
						'queryId'   => 4242,
						'query'     => array( 'perPage' => 12, 'pages' => 0, 'offset' => 0, 'postType' => 'portfolio', 'order' => 'desc', 'orderBy' => 'date', 'inherit' => false ),
						'className' => 'creapub-realisations-auto',
					),
					array(
						array( 'core/post-template', array( 'layout' => array( 'type' => 'grid', 'columnCount' => 3 ) ), array( array( 'core/post-featured-image', array( 'aspectRatio' => '4/3', 'sizeSlug' => 'large' ) ) ) ),
					),
				),
				array( 'core/heading', array( 'level' => 2, 'content' => 'Notre savoir-faire', 'className' => 'creapub-savoir-faire' ) ),
				array( 'core/paragraph', array( 'placeholder' => 'Description : pour qui, pour quoi, comment…' ) ),
				array( 'core/heading', array( 'level' => 2, 'content' => 'Matériaux & techniques' ) ),
				array( 'core/list', array() ),
				array( 'core/heading', array( 'level' => 2, 'content' => 'Délais indicatifs' ) ),
				array( 'core/paragraph', array( 'placeholder' => 'Ex. : 1 à 2 semaines entre la validation du BAT et la pose…' ) ),
				array( 'core/heading', array( 'level' => 2, 'content' => 'Questions fréquentes' ) ),
				array( 'core/details', array( 'summary' => 'Question 1 ?' ) ),
				array( 'core/details', array( 'summary' => 'Question 2 ?' ) ),
				array( 'core/details', array( 'summary' => 'Question 3 ?' ) ),
			);
			break;

		case 'portfolio':
			// Une réalisation = titre + photo à la une + catégorie. Texte facultatif.
			$args['template'] = array(
				array( 'core/paragraph', array( 'placeholder' => 'Description facultative : client, lieu, année, ce qui a été réalisé…' ) ),
			);
			break;

		case 'qui-sommes-nous':
			// Un membre = prénom (titre) + photo à la une + mini-bio + ordre d'affichage.
			$args['template'] = array(
				array( 'core/paragraph', array( 'placeholder' => 'Mini-bio à la première personne, 4 à 6 lignes…' ) ),
			);
			break;
	}

	return $args;
}
\add_filter( 'register_post_type_args', __NAMESPACE__ . '\lock_cpt_templates', 10, 2 );

/**
 * Feuille de style et script d'animation du hero.
 *
 * Le script ne fait que révéler les caractères du H1 déjà présents dans le
 * DOM (machine à écrire SEO-safe) ; prefers-reduced-motion le coupe.
 */
\add_action(
	'wp_enqueue_scripts',
	function (): void {
		$dir = \get_stylesheet_directory();

		\wp_enqueue_style( 'g2rd-creapub', \get_stylesheet_uri(), array(), (string) \filemtime( $dir . '/style.css' ) );

		$js = $dir . '/assets/js/creapub-hero.js';
		if ( \is_readable( $js ) ) {
			\wp_enqueue_script(
				'g2rd-creapub-hero',
				\get_stylesheet_directory_uri() . '/assets/js/creapub-hero.js',
				array(),
				(string) \filemtime( $js ),
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	},
	20
);

/**
 * Classe de corps : sert de garde aux sélecteurs du thème.
 *
 * @param string[] $classes Classes du body.
 * @return string[]
 */
\add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'creapub';
		return $classes;
	}
);

/**
 * Réalisations d'une prestation : la boucle « queryId 4242 » est filtrée sur
 * la catégorie de projet qui porte le même identifiant (slug) que la
 * prestation affichée. Ajouter une réalisation = la classer dans la bonne
 * catégorie, rien d'autre ; une nouvelle prestation fonctionne d'office.
 *
 * @param array<string,mixed> $query Arguments de WP_Query.
 * @param \WP_Block           $block Bloc post-template.
 * @return array<string,mixed>
 */
\add_filter(
	'query_loop_block_query_vars',
	static function ( array $query, $block ): array {
		if ( 4242 !== (int) ( $block->context['queryId'] ?? 0 ) ) {
			return $query;
		}
		$post = \get_queried_object();
		if ( ! $post instanceof \WP_Post || 'prestations' !== $post->post_type ) {
			return $query;
		}
		$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => portfolio_taxonomy(),
				'field'    => 'slug',
				'terms'    => $post->post_name,
			),
		);
		return $query;
	},
	10,
	2
);

/**
 * Visionneuse des réalisations, chargée seulement là où il y en a.
 */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! \is_singular( 'prestations' ) && ! \is_post_type_archive( 'portfolio' ) && ! \is_tax( portfolio_taxonomy() ) ) {
			return;
		}
		$js = \get_stylesheet_directory() . '/assets/js/creapub-lightbox.js';
		\wp_enqueue_script( 'g2rd-creapub-lightbox', \get_stylesheet_directory_uri() . '/assets/js/creapub-lightbox.js', array(), (string) \filemtime( $js ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	},
	25
);
