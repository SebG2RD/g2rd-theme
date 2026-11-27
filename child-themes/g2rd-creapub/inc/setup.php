<?php
/**
 * Installation des contenus en 1 clic : pages, prestations, catégories,
 * navigation, réglages de lecture.
 *
 * Deux portes d'entrée, qui font la même chose :
 *   - Apparence › Installation Créa Pub (bouton « Créer les contenus manquants ») ;
 *   - WP-CLI : `wp creapub install` (inc/class-cli-command.php).
 *
 * L'installation est idempotente : un contenu dont le chemin existe déjà est
 * laissé tel quel (sauf « forcer »), la navigation n'est créée qu'une fois,
 * les réglages ne sont posés que s'ils sont encore à leur valeur par défaut.
 * Rien n'est jamais supprimé ; deux exceptions prudentes : le brouillon
 * « Politique de confidentialité » créé par WordPress est complété et publié,
 * et l'article d'exemple « Bonjour tout le monde ! », s'il n'a pas été
 * modifié, est mis à la corbeille (restaurable).
 *
 * Les contenus viennent des compositions patterns/*.php (markup Gutenberg,
 * PHP évalué) ; les références <!-- wp:pattern --> y sont développées pour
 * que le client édite de vrais blocs, pas des renvois.
 *
 * @package G2RD_CreaPub
 */

declare( strict_types = 1 );

namespace G2RD_CreaPub;

\defined( 'ABSPATH' ) || exit;

/**
 * Plan des pages.
 *
 * Clé = slug. `patterns` = compositions assemblées (références développées).
 * `role` : front (page d'accueil), posts (page des articles), privacy.
 *
 * @return array<string,array<string,mixed>>
 */
function content_map(): array {
	return array(
		'accueil'                      => array(
			'title'     => 'Accueil',
			'patterns'  => array( 'hero-accueil', 'bande-prestations', 'chiffres-cles', 'grille-prestations', 'process-etapes', 'logos-clients', 'bande-brochure', 'partenaires', 'temoignages', 'faq', 'bande-cta' ),
			'role'      => 'front',
			'seo_title' => 'Créa Pub Communication — Agence de communication visuelle à Vernon (27)',
			'seo_desc'  => "Agence de communication à Vernon depuis 12 ans : print, signalétique et enseignes, marquage véhicule, textile et objets publicitaires. Conception et fabrication dans notre atelier. Devis gratuit sous 48 h.",
		),
		'l-agence'                     => array(
			'title'     => "L'agence",
			'patterns'  => array( 'page-agence' ),
			'seo_title' => "L'agence Créa Pub Communication à Vernon | Créativité, proximité, expertise",
			'seo_desc'  => "Depuis 12 ans à Vernon, Créa Pub Communication accompagne artisans, commerces, collectivités et associations : plus de 700 clients, 8000 projets conçus et fabriqués à l'atelier.",
		),
		'la-team'                      => array(
			'title'     => 'La Team',
			'patterns'  => array( 'page-la-team' ),
			'seo_title' => 'La Team — les visages derrière vos projets | Créa Pub Communication',
			'seo_desc'  => "Graphistes, poseurs, brodeurs : l'équipe de Créa Pub Communication à Vernon suit votre projet de A à Z, de la maquette à la pose.",
		),
		'prestations'                  => array(
			'title'     => 'Prestations',
			'patterns'  => array( 'page-prestations' ),
			'seo_title' => 'Nos prestations : print, enseignes, véhicules, textile, goodies | Créa Pub Vernon',
			'seo_desc'  => 'Print, signalétique et enseignes, marquage véhicule, textile personnalisé et objets publicitaires : toutes les prestations de Créa Pub Communication, conçues et fabriquées à Vernon.',
		),
		'actualites'                   => array(
			'title'     => 'Actualités',
			'content'   => '',
			'role'      => 'posts',
			'seo_title' => 'Actualités de votre agence de communication à Vernon | Créa Pub',
			'seo_desc'  => "Chantiers récents, conseils print, signalétique, marquage véhicule et textile, vie de l'atelier : toute l'actualité de Créa Pub Communication à Vernon.",
		),
		'contact'                      => array(
			'title'     => 'Contact',
			'patterns'  => array( 'page-contact' ),
			'seo_title' => 'Contact et devis gratuit sous 48 h | Créa Pub Communication Vernon',
			'seo_desc'  => 'Un projet de communication ? Contactez Créa Pub à Vernon : 56 avenue de Rouen, 02 32 21 31 16. Devis clair sous 48 h, sans engagement.',
		),
		'mentions-legales'             => array(
			'title'     => 'Mentions légales',
			'patterns'  => array( 'page-mentions-legales' ),
			'noindex'   => true,
		),
		'politique-de-confidentialite' => array(
			'title'     => 'Politique de confidentialité',
			'patterns'  => array( 'page-politique-confidentialite' ),
			'role'      => 'privacy',
			'noindex'   => true,
		),
	);
}

/**
 * Plan des prestations (CPT `prestations`).
 *
 * Clé = slug de la prestation ; `pattern` = composition du contenu rédigé,
 * dont la structure suit le gabarit verrouillé contentOnly.
 *
 * @return array<string,array<string,string>>
 */
function prestations_map(): array {
	return array(
		'print'             => array(
			'title'     => 'Print',
			'pattern'   => 'prestation-print',
			'seo_title' => 'Impression print à Vernon : cartes, flyers, affiches | Créa Pub',
			'seo_desc'  => 'Cartes de visite, flyers, brochures, affiches, bâches : conception et impression à Vernon, BAT validé avant chaque tirage. Devis sous 48 h.',
		),
		'signaletique'      => array(
			'title'     => 'Signalétique & enseignes',
			'pattern'   => 'prestation-signaletique',
			'seo_title' => 'Enseignes et signalétique à Vernon (27) | Créa Pub Communication',
			'seo_desc'  => "Enseignes lumineuses, panneaux, vitrophanie : conception, fabrication et pose par Créa Pub à Vernon et dans toute l'Eure. Devis sous 48 h.",
		),
		'marquage-vehicule' => array(
			'title'     => 'Marquage véhicule',
			'pattern'   => 'prestation-marquage-vehicule',
			'seo_title' => 'Marquage véhicule et covering à Vernon (27) | Créa Pub',
			'seo_desc'  => 'Lettrage, semi-covering ou covering complet : films Hexis, Orafol, Avery posés en atelier à Vernon. Votre véhicule devient votre meilleure publicité.',
		),
		'textile'           => array(
			'title'     => 'Textile',
			'pattern'   => 'prestation-textile',
			'seo_title' => 'Textile personnalisé à Vernon : flocage, broderie, DTF | Créa Pub',
			'seo_desc'  => "T-shirts, polos, vêtements de travail : flocage, broderie et impression dès l'unité à Vernon. Gammes Sol's, Clique, Projob, Craft.",
		),
		'goodies'           => array(
			'title'     => 'Goodies & objets pub',
			'pattern'   => 'prestation-goodies',
			'seo_title' => 'Objets publicitaires et goodies à Vernon (27) | Créa Pub',
			'seo_desc'  => "Stylos, mugs, tote-bags, cadeaux d'affaires personnalisés à votre image : sélection, marquage et conseil par Créa Pub Communication à Vernon.",
		),
	);
}

/**
 * Catégories du blog, alignées sur les prestations + vie de l'atelier.
 *
 * @return array<string,string> slug → nom.
 */
function categories_map(): array {
	return array(
		'print'             => 'Print',
		'signaletique'      => 'Signalétique & enseignes',
		'marquage-vehicule' => 'Marquage véhicule',
		'textile'           => 'Textile',
		'goodies'           => 'Goodies & objets pub',
		'vie-de-l-atelier'  => "Vie de l'atelier",
	);
}

/**
 * Markup d'une composition, PHP évalué, en-tête ignoré, références
 * <!-- wp:pattern --> développées récursivement : le contenu installé est
 * fait de vrais blocs que le client édite librement.
 *
 * @param string $slug  Nom du fichier sans extension dans patterns/.
 * @param int    $depth Garde-fou de récursivité.
 * @return string
 */
function pattern_markup( string $slug, int $depth = 0 ): string {
	$file = \get_stylesheet_directory() . '/patterns/' . \basename( $slug ) . '.php';
	if ( $depth > 3 || ! \is_readable( $file ) ) {
		return '';
	}
	\ob_start();
	include $file; // phpcs:ignore PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt -- fichier patterns/<basename>.php du thème, jamais une entrée utilisateur.
	$markup = \trim( (string) \ob_get_clean() );

	return (string) \preg_replace_callback(
		'#<!--\s*wp:pattern\s*\{"slug":"g2rd-creapub/([a-z0-9-]+)"\}\s*/-->#',
		static fn( array $m ): string => pattern_markup( $m[1], $depth + 1 ),
		$markup
	);
}

/**
 * Contenu Gutenberg d'une entrée du plan.
 *
 * @param array<string,mixed> $entry Entrée.
 * @return string
 */
function content_markup( array $entry ): string {
	if ( isset( $entry['content'] ) ) {
		return (string) $entry['content'];
	}
	if ( ! empty( $entry['patterns'] ) ) {
		return \implode( "\n\n", \array_filter( \array_map( __NAMESPACE__ . '\pattern_markup', (array) $entry['patterns'] ) ) );
	}
	return '';
}

/**
 * Trouve une page par chemin.
 *
 * @param string $slug Slug.
 * @return \WP_Post|null
 */
function find_page( string $slug ) {
	$post = \get_page_by_path( $slug, OBJECT, 'page' );
	return $post instanceof \WP_Post ? $post : null;
}

/**
 * Trouve un contenu de CPT par slug.
 *
 * @param string $slug      Slug.
 * @param string $post_type Type de contenu.
 * @return \WP_Post|null
 */
function find_cpt( string $slug, string $post_type ) {
	$posts = \get_posts(
		array(
			'post_type'   => $post_type,
			'name'        => $slug,
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => 1,
		)
	);
	return $posts[0] ?? null;
}

/**
 * Métadonnées : SEOPress (posées seulement si vides), robots.
 *
 * @param int                 $id    Identifiant.
 * @param array<string,mixed> $entry Entrée du plan.
 */
function apply_meta( int $id, array $entry ): void {
	if ( ! empty( $entry['seo_title'] ) && '' === (string) \get_post_meta( $id, '_seopress_titles_title', true ) ) {
		\update_post_meta( $id, '_seopress_titles_title', (string) $entry['seo_title'] );
	}
	if ( ! empty( $entry['seo_desc'] ) && '' === (string) \get_post_meta( $id, '_seopress_titles_desc', true ) ) {
		\update_post_meta( $id, '_seopress_titles_desc', (string) $entry['seo_desc'] );
	}
	if ( ! empty( $entry['noindex'] ) ) {
		\update_post_meta( $id, '_seopress_robots_index', 'yes' );
	}
}

/**
 * Empreinte du contenu posé par l'installateur.
 *
 * @param int $id Identifiant.
 */
function stamp( int $id ): void {
	$post = \get_post( $id );
	if ( $post ) {
		\update_post_meta( $id, '_creapub_hash', \md5( \trim( (string) $post->post_content ) ) );
	}
}

/**
 * Un contenu posé par l'installateur et jamais retouché depuis peut être
 * remplacé par la version courante des compositions, sans case « Forcer ».
 * Les pages créées par la v1.0 (sans empreinte) sont considérées comme non
 * retouchées : elles ont été installées sur un site neuf.
 *
 * @param \WP_Post $post Contenu.
 * @return bool
 */
function is_untouched( \WP_Post $post ): bool {
	$hash = (string) \get_post_meta( $post->ID, '_creapub_hash', true );
	if ( '' === $hash ) {
		// Contenus posés par une version antérieure sans empreinte : remplacés une fois (révision 3).
		return (int) \get_option( 'g2rd_creapub_content_rev', 0 ) < 3;
	}
	return \md5( \trim( (string) $post->post_content ) ) === $hash;
}

/**
 * Crée les pages manquantes.
 *
 * @param bool $force Réécrire le contenu des pages existantes (jamais leur titre ni leur statut).
 * @return array<string,string> slug → « créée », « existante », « mise à jour » ou « erreur : … ».
 */
function install_contents( bool $force = false ): array {
	$report = array();
	$ids    = array();

	foreach ( content_map() as $slug => $entry ) {
		$existing = find_page( $slug );

		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			$contenu      = \trim( (string) $existing->post_content );
			$content      = content_markup( $entry );
			$untouched    = '' === $contenu
				|| $contenu === $content
				|| $existing->post_modified_gmt === $existing->post_date_gmt
				|| ( \class_exists( '\WP_Privacy_Policy_Content' ) && \trim( (string) \WP_Privacy_Policy_Content::get_default_content() ) === $contenu );
			if ( 'draft' === $existing->post_status && $untouched && '' !== $content ) {
				\wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_status'  => 'publish',
						'post_content' => $content,
					)
				);
				apply_meta( $existing->ID, $entry );
				$report[ $slug ] = 'brouillon complété et publié';
				continue;
			}
			if ( ( $force || is_untouched( $existing ) ) && '' !== $content && $contenu !== $content ) {
				\wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
					)
				);
				stamp( $existing->ID );
				apply_meta( $existing->ID, $entry );
				$report[ $slug ] = 'mise à jour';
				continue;
			}
			$report[ $slug ] = 'existante';
			continue;
		}

		$id = \wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => (string) $entry['title'],
				'post_name'      => $slug,
				'post_content'   => content_markup( $entry ),
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$report[ $slug ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		$ids[ $slug ] = (int) $id;
		stamp( (int) $id );
		apply_meta( (int) $id, $entry );
		$report[ $slug ] = 'créée';
	}

	install_settings( $ids );
	return $report;
}

/**
 * Crée les cinq prestations manquantes (CPT verrouillé contentOnly : le
 * contenu est posé ici en base, le client ne fait ensuite que remplir).
 *
 * @param bool $force Réécrire le contenu des prestations existantes.
 * @return array<string,string>
 */
function install_prestations( bool $force = false ): array {
	$report = array();

	if ( ! \post_type_exists( 'prestations' ) ) {
		return array( 'prestations' => 'erreur : le CPT prestations du thème parent est introuvable' );
	}

	$order  = 0;
	$photos = array( 'print' => 'print-1', 'signaletique' => 'signa-1', 'marquage-vehicule' => 'vehicule-1', 'textile' => 'textile-1', 'goodies' => 'goodies-1' );
	foreach ( prestations_map() as $slug => $entry ) {
		++$order;
		$existing = find_cpt( $slug, 'prestations' );
		$content  = pattern_markup( $entry['pattern'] );
		$after    = static function ( int $id ) use ( $slug, $order, $photos ): void {
			// Ordre d'affichage et photo à la une (posés seulement s'ils manquent).
			if ( ! (int) \get_post_field( 'menu_order', $id ) ) {
				\wp_update_post( array( 'ID' => $id, 'menu_order' => $order ) );
			}
			if ( ! \has_post_thumbnail( $id ) && isset( $photos[ $slug ] ) && media_id( $photos[ $slug ] ) ) {
				\set_post_thumbnail( $id, media_id( $photos[ $slug ] ) );
			}
		};

		if ( $existing instanceof \WP_Post ) {
			if ( ( $force || is_untouched( $existing ) ) && '' !== $content && \trim( (string) $existing->post_content ) !== $content ) {
				\wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $content,
					)
				);
				stamp( $existing->ID );
				apply_meta( $existing->ID, $entry );
				$report[ $slug ] = 'mise à jour';
			} else {
				$report[ $slug ] = 'existante';
			}
			$after( $existing->ID );
			continue;
		}

		$id = \wp_insert_post(
			array(
				'post_type'      => 'prestations',
				'post_status'    => 'publish',
				'post_title'     => $entry['title'],
				'post_name'      => $slug,
				'post_content'   => $content,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$report[ $slug ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		stamp( (int) $id );
		apply_meta( (int) $id, $entry );
		$after( (int) $id );
		$report[ $slug ] = 'créée';
	}

	return $report;
}

/**
 * Portfolio : une catégorie de projet par prestation (même identifiant), puis
 * une réalisation par photo du site actuel (titre, photo à la une, catégorie).
 *
 * @return array<string,string>
 */
function install_portfolio(): array {
	if ( ! \post_type_exists( 'portfolio' ) ) {
		return array( 'portfolio' => 'erreur : le CPT portfolio du thème parent est introuvable' );
	}
	$tax    = portfolio_taxonomy();
	$report = array();
	$names  = array( 'print' => 'Print', 'signaletique' => 'Signalétique', 'marquage-vehicule' => 'Véhicule', 'textile' => 'Textile', 'goodies' => 'Goodies' );
	$prefix = array( 'print' => 'print', 'signaletique' => 'signa', 'marquage-vehicule' => 'vehicule', 'textile' => 'textile', 'goodies' => 'goodies' );
	$titles = array( 'print' => 'Réalisation print', 'signaletique' => 'Signalétique', 'marquage-vehicule' => 'Marquage véhicule', 'textile' => 'Textile personnalisé', 'goodies' => 'Objets publicitaires' );
	$files  = gallery_files();

	foreach ( $names as $slug => $name ) {
		$term = \term_exists( $slug, $tax );
		if ( ! $term ) {
			$term = \wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
		}
		if ( \is_wp_error( $term ) ) {
			$report[ $slug ] = 'erreur : ' . $term->get_error_message();
			continue;
		}
		$term_id = (int) ( \is_array( $term ) ? $term['term_id'] : $term );
		$created = 0;
		$count   = \count( $files[ $prefix[ $slug ] ] ?? array() );
		// Les plus anciennes d'abord : la première photo du site actuel reste en tête (tri par date décroissante).
		for ( $i = $count; $i >= 1; $i-- ) {
			$key = $prefix[ $slug ] . '-' . $i;
			if ( ! media_id( $key ) ) {
				continue;
			}
			$exists = \get_posts(
				array(
					'post_type'   => 'portfolio',
					'post_status' => 'any',
					'numberposts' => 1,
					'fields'      => 'ids',
					'meta_key'    => '_creapub_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( $exists ) {
				continue;
			}
			$id = \wp_insert_post(
				array(
					'post_type'      => 'portfolio',
					'post_status'    => 'publish',
					'post_title'     => \sprintf( '%s %02d', $titles[ $slug ], $i ),
					'post_content'   => '',
					'post_date'      => \gmdate( 'Y-m-d H:i:s', \time() - ( $count - $i ) * 60 - \array_search( $slug, \array_keys( $names ), true ) * 3600 ),
					'comment_status' => 'closed',
				),
				true
			);
			if ( \is_wp_error( $id ) ) {
				continue;
			}
			\set_post_thumbnail( (int) $id, media_id( $key ) );
			\wp_set_object_terms( (int) $id, array( $term_id ), $tax );
			\update_post_meta( (int) $id, '_creapub_source', $key );
			++$created;
		}
		$report[ $slug ] = $created ? $created . ' réalisation(s) créée(s)' : 'à jour';
	}
	return $report;
}

/**
 * La Team : une fiche par membre (prénom, photo à la une, mini-bio, ordre).
 *
 * @return array<string,string>
 */
function install_team(): array {
	if ( ! \post_type_exists( 'qui-sommes-nous' ) ) {
		return array( 'team' => 'erreur : le CPT qui-sommes-nous du thème parent est introuvable' );
	}
	$team   = array(
		'cathie'   => array( 'Cathie', 'team-cathie', 'À la tête de CréaPub, je suis la matière grise derrière chaque idée depuis plus de 20 ans. Toujours une solution, souvent un sourire, et une vraie passion pour ce métier qui ne m’a jamais quittée. Un brin blagueuse, mais surtout très investie, je cultive depuis plus de 10 ans des relations de confiance, de proximité et de complicité avec mes clients. Parce qu’au fond, une bonne communication commence toujours par l’humain.' ),
		'baptiste' => array( 'Baptiste', 'team-baptiste', 'Commercial à l’agence, je suis toujours là pour mes clients, avec le sourire… et une coiffure au top. Rien ne m’échappe : je trouve la bonne solution, au bon budget, avec une bonne dose d’humour. Disponible, réactif et à l’écoute, mon objectif est simple : que chaque projet roule parfaitement, du premier contact jusqu’au résultat final.' ),
		'leslie'   => array( 'Leslie', 'team-leslie', 'Graphiste depuis plusieurs années, je suis un peu la signature artistique de l’agence… et souvent la sauveuse de projet à la dernière minute. Menus, pubs de restos : c’est clairement mon dada. Toujours lunettes sur le nez et sourire aux lèvres, je suis aussi celle qui vous répond au téléphone… et qui vous relance (un peu) pour le suivi des dossiers.' ),
		'mathieu'  => array( 'Mathieu', 'team-mathieu', 'Graphiste à l’agence depuis près de 10 ans, je suis polyvalent et j’interviens à toutes les étapes : de la création à la technique, jusqu’à la pose. Je conçois des visuels efficaces qui prennent vie sur tous types de supports, y compris les camions que vous croisez au quotidien. L’atelier d’impression est mon univers : j’y veille à la qualité et au rendu final de chaque projet.' ),
		'adrien'   => array( 'Adrien', 'team-adrien', 'Graphisme, tatouage, création… c’est mon quotidien depuis 17 ans. Toujours avec une tablette graphique à portée de main, j’ai les idées qui fusent sous le bonnet, prêt à créer à tout moment. Inspiré, précis et passionné, je donne vie aux concepts avec un style bien à moi, entre créativité brute et sens du détail. Bref, quand il s’agit de créer, je ne suis jamais à court d’encre… ni d’idées.' ),
	);
	$report = array();
	$order  = 0;
	foreach ( $team as $slug => $member ) {
		++$order;
		$existing = find_cpt( $slug, 'qui-sommes-nous' );
		if ( $existing instanceof \WP_Post ) {
			if ( ! \has_post_thumbnail( $existing->ID ) && media_id( $member[1] ) ) {
				\set_post_thumbnail( $existing->ID, media_id( $member[1] ) );
			}
			$report[ $slug ] = 'existant';
			continue;
		}
		$id = \wp_insert_post(
			array(
				'post_type'      => 'qui-sommes-nous',
				'post_status'    => 'publish',
				'post_title'     => $member[0],
				'post_name'      => $slug,
				'menu_order'     => $order,
				'post_content'   => "<!-- wp:paragraph -->\n<p>" . \esc_html( $member[2] ) . "</p>\n<!-- /wp:paragraph -->",
				'comment_status' => 'closed',
			),
			true
		);
		if ( \is_wp_error( $id ) ) {
			$report[ $slug ] = 'erreur : ' . $id->get_error_message();
			continue;
		}
		if ( media_id( $member[1] ) ) {
			\set_post_thumbnail( (int) $id, media_id( $member[1] ) );
		}
		$report[ $slug ] = 'créé';
	}
	return $report;
}

/**
 * Crée les catégories du blog et met l'article d'exemple de WordPress à la
 * corbeille s'il n'a jamais été modifié.
 *
 * @return array<string,string>
 */
function install_categories(): array {
	$report = array();

	foreach ( categories_map() as $slug => $name ) {
		if ( \term_exists( $slug, 'category' ) ) {
			$report[ $slug ] = 'existante';
			continue;
		}
		$result          = \wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		$report[ $slug ] = \is_wp_error( $result ) ? 'erreur : ' . $result->get_error_message() : 'créée';
	}

	// Article d'exemple « Bonjour tout le monde ! », jamais retouché → corbeille.
	$hello = \get_posts(
		array(
			'post_type'   => 'post',
			'name'        => 'bonjour-tout-le-monde',
			'post_status' => 'publish',
			'numberposts' => 1,
		)
	);
	if ( empty( $hello ) ) {
		$hello = \get_posts(
			array(
				'post_type'   => 'post',
				'name'        => 'hello-world',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);
	}
	if ( ! empty( $hello ) && $hello[0]->post_modified_gmt === $hello[0]->post_date_gmt ) {
		\wp_trash_post( $hello[0]->ID );
		$report['article-exemple'] = 'mis à la corbeille';
	}

	return $report;
}

/**
 * Réglages de lecture et permaliens, posés seulement s'ils sont encore par défaut.
 *
 * @param array<string,int> $ids Identifiants des pages par slug.
 */
function install_settings( array $ids ): void {
	// Le déploiement 1 clic doit afficher le site Créa Pub : la page
	// d'accueil et la page des articles sont assignées même si le site
	// affichait auparavant un contenu de démonstration.
	if ( 'page' !== \get_option( 'show_on_front' ) ) {
		\update_option( 'show_on_front', 'page' );
	}
	if ( isset( $ids['accueil'] ) && (int) \get_option( 'page_on_front' ) !== $ids['accueil'] ) {
		\update_option( 'page_on_front', $ids['accueil'] );
	}
	if ( isset( $ids['actualites'] ) && (int) \get_option( 'page_for_posts' ) !== $ids['actualites'] ) {
		\update_option( 'page_for_posts', $ids['actualites'] );
	}
	if ( isset( $ids['politique-de-confidentialite'] ) && ! (int) \get_option( 'wp_page_for_privacy_policy' ) ) {
		\update_option( 'wp_page_for_privacy_policy', $ids['politique-de-confidentialite'] );
	}
	// Articles sous /actualites/, pages et CPT à la racine de leur slug.
	if ( \in_array( (string) \get_option( 'permalink_structure' ), array( '', '/%postname%/' ), true ) ) {
		\update_option( 'permalink_structure', '/actualites/%postname%/' );
		\delete_option( 'rewrite_rules' );
		\flush_rewrite_rules( false );
	}
	if ( '' === (string) \get_option( 'timezone_string' ) ) {
		\update_option( 'timezone_string', 'Europe/Paris' );
	}
	if ( \in_array( (string) \get_option( 'blogdescription' ), array( 'Un site utilisant WordPress', 'Just another WordPress site' ), true ) ) {
		\update_option( 'blogdescription', 'Votre agence de communication à Vernon' );
	}
	\update_option( 'default_comment_status', 'closed' );
	\update_option( 'g2rd_creapub_installed', VERSION );
}

/**
 * Navigation principale (bloc Navigation du modèle d'en-tête).
 *
 * Sans `ref`, le bloc Navigation utilise la navigation la plus récente :
 * on n'en crée qu'une. Si la nôtre existe déjà, rien n'est touché.
 *
 * @return string « créée », « existante » ou « erreur : … ».
 */
function install_navigation(): string {
	$existing = \get_posts(
		array(
			'post_type'   => 'wp_navigation',
			'name'        => 'navigation-principale',
			'post_status' => 'publish',
			'numberposts' => 1,
		)
	);

	$page_link = static function ( string $label, string $slug ): string {
		$page = find_page( $slug );
		$url  = $page ? \get_permalink( $page ) : \home_url( '/' . $slug . '/' );
		$id   = $page ? ',"id":' . $page->ID . ',"kind":"post-type","type":"page"' : '';
		return \sprintf( '<!-- wp:navigation-link {"label":"%s","url":"%s"%s} /-->', \esc_attr( $label ), \esc_url( (string) $url ), $id );
	};

	$cpt_link = static function ( string $label, string $slug ): string {
		$post = find_cpt( $slug, 'prestations' );
		$url  = $post ? \get_permalink( $post ) : \home_url( '/prestations/' . $slug . '/' );
		$id   = $post ? ',"id":' . $post->ID . ',"kind":"post-type","type":"prestations"' : '';
		return \sprintf( '<!-- wp:navigation-link {"label":"%s","url":"%s"%s,"className":"creapub-menu-%s"} /-->', \esc_attr( $label ), \esc_url( (string) $url ), $id, $slug );
	};

	// Menu du site actuel : Agence, Nos prestations (page + méga-menu à icônes), Réalisations, La Team, Contact.
	$prest_page   = find_page( 'prestations' );
	$submenu_open = \sprintf(
		'<!-- wp:navigation-submenu {"label":"Nos prestations","url":"%1$s"%2$s} -->',
		\esc_url( $prest_page ? (string) \get_permalink( $prest_page ) : \home_url( '/prestations/' ) ),
		$prest_page ? ',"id":' . $prest_page->ID . ',"kind":"post-type","type":"page"' : ''
	);
	$content = \implode(
		"\n",
		array(
			$page_link( 'Agence', 'l-agence' ),
			$submenu_open,
			$cpt_link( 'Print', 'print' ),
			$cpt_link( 'Signalétique', 'signaletique' ),
			$cpt_link( 'Véhicule', 'marquage-vehicule' ),
			$cpt_link( 'Textile', 'textile' ),
			$cpt_link( 'Goodies', 'goodies' ),
			'<!-- /wp:navigation-submenu -->',
			'<!-- wp:navigation-link {"label":"Réalisations","url":"' . \esc_url( (string) ( \get_post_type_archive_link( 'portfolio' ) ?: \home_url( '/realisations/' ) ) ) . '","kind":"post-type-archive","type":"portfolio"} /-->',
			$page_link( 'La Team', 'la-team' ),
			$page_link( 'Contact', 'contact' ),
		)
	);

	if ( ! empty( $existing ) ) {
		$nav = $existing[0];
		// Navigation posée par une version précédente de l'installateur et jamais retouchée : remplacée.
		if ( '3' !== (string) \get_option( 'g2rd_creapub_nav' ) && ( is_untouched( $nav ) || '' === (string) \get_post_meta( $nav->ID, '_creapub_hash', true ) ) ) {
			\wp_update_post(
				array(
					'ID'           => $nav->ID,
					'post_content' => $content,
				)
			);
			stamp( $nav->ID );
			\update_option( 'g2rd_creapub_nav', '3', false );
			return 'mise à jour';
		}
		return 'existante';
	}

	$id = \wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'Navigation principale',
			'post_name'    => 'navigation-principale',
			'post_content' => $content,
		),
		true
	);
	if ( \is_wp_error( $id ) ) {
		return 'erreur : ' . $id->get_error_message();
	}
	stamp( (int) $id );
	\update_option( 'g2rd_creapub_nav', '3', false );
	return 'créée';
}

/**
 * Le thème parent recopie en base ses propres modèles et éléments (header,
 * footer…) sous le nom du thème actif. Avec un thème enfant, ces copies
 * masquent les fichiers de l'enfant. On retire les copies restées identiques
 * au fichier du PARENT ; une version modifiée dans l'éditeur de site est
 * conservée.
 *
 * @return int Nombre de copies retirées.
 */
function purge_parent_template_copies(): int {
	$removed = 0;
	$clean   = static function ( string $html ): string {
		return \trim( (string) \preg_replace( '/\s+/', ' ', $html ) );
	};

	foreach ( array( 'wp_template', 'wp_template_part' ) as $type ) {
		$posts = \get_posts(
			array(
				'post_type'   => $type,
				'post_status' => array( 'publish', 'auto-draft' ),
				'numberposts' => -1,
				'tax_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => \get_stylesheet(),
					),
				),
			)
		);
		$dir   = 'wp_template' === $type ? 'templates' : 'parts';
		foreach ( $posts as $post ) {
			$parent_file = \get_template_directory() . '/' . $dir . '/' . $post->post_name . '.html';
			$child_file  = \get_stylesheet_directory() . '/' . $dir . '/' . $post->post_name . '.html';
			$same_parent = \is_readable( $parent_file )
				&& $clean( (string) \file_get_contents( $parent_file ) ) === $clean( (string) $post->post_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			// Copie jamais éditée d'un modèle que l'enfant fournit lui-même.
			$never_edited = \is_readable( $child_file ) && $post->post_modified_gmt === $post->post_date_gmt;
			if ( $same_parent || $never_edited ) {
				\wp_delete_post( $post->ID, true );
				++$removed;
			}
		}
	}

	return $removed;
}

/**
 * Tout installer : pages, prestations, catégories, navigation, purge.
 *
 * @param bool $force Réécrire les contenus existants.
 * @return array<string,mixed>
 */
function install_all( bool $force = false ): array {
	$report           = array();
	$report['medias'] = install_media();
	$pending          = isset( $report['medias']['_restant'] ) ? (int) $report['medias']['_restant'] : 0;
	if ( $pending ) {
		// Import pas fini : les contenus sont posés quand même (les photos manquantes
		// s'affichent depuis le site actuel) puis remis à jour à chaque lot, avec les
		// fichiers de la médiathèque, tant qu'ils n'ont pas été retouchés à la main.
		$total           = \count( media_map() );
		$report['suite'] = \sprintf( 'import des visuels : %1$d / %2$d', $total - $pending, $total )
			. ( isset( $report['medias']['_pause'] ) ? ' (le site actuel limite les téléchargements, pause de 20 s)' : '' );
		$report['pause'] = isset( $report['medias']['_pause'] ) ? 20000 : 800;
	}
	$report['purge']       = purge_parent_template_copies() . ' copie(s) de modèle du parent retirée(s)';
	$report['portfolio']   = install_portfolio();
	$report['team']        = install_team();
	$report['pages']       = install_contents( $force );
	$report['prestations'] = install_prestations( $force );
	$report['categories']  = install_categories();
	$report['navigation']  = install_navigation();
	\update_option( 'g2rd_creapub_content_rev', 3, false );
	return $report;
}

/**
 * Page Apparence › Installation Créa Pub.
 */
\add_action(
	'admin_menu',
	function (): void {
		\add_theme_page(
			\__( 'Installation Créa Pub', 'g2rd-creapub' ),
			\__( 'Installation Créa Pub', 'g2rd-creapub' ),
			'manage_options',
			'creapub-install',
			__NAMESPACE__ . '\render_install_page'
		);
	}
);

/**
 * Rendu de la page d'installation.
 */
function render_install_page(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$report = null;
	$force  = false;
	if ( isset( $_POST['creapub_install'] ) && \check_admin_referer( 'creapub_install', 'creapub_nonce' ) ) {
		$force  = ! empty( $_POST['creapub_force'] );
		$report = install_all( $force );
	}
	$continue = \is_array( $report ) && isset( $report['suite'] );

	echo '<div class="wrap"><h1>' . \esc_html__( 'Installation Créa Pub', 'g2rd-creapub' ) . '</h1>';
	echo '<p>' . \esc_html__( 'Importe les visuels du site actuel dans la médiathèque, crée les réalisations (Portfolio), les membres (La Team), les 8 pages, les 5 prestations, les 6 catégories du blog, la navigation et les réglages de lecture. Les contenus déjà installés et jamais retouchés sont mis à jour ; un contenu modifié à la main n’est réécrit qu’avec « Forcer ».', 'g2rd-creapub' ) . '</p>';

	if ( \is_array( $report ) ) {
		echo '<h2>' . \esc_html__( 'Rapport', 'g2rd-creapub' ) . '</h2><ul style="list-style:disc;padding-left:20px">';
		foreach ( $report as $section => $lines ) {
			if ( \is_array( $lines ) ) {
				foreach ( $lines as $slug => $state ) {
					echo '<li><strong>' . \esc_html( $section . ' / ' . $slug ) . '</strong> : ' . \esc_html( (string) $state ) . '</li>';
				}
			} else {
				echo '<li><strong>' . \esc_html( $section ) . '</strong> : ' . \esc_html( (string) $lines ) . '</li>';
			}
		}
		echo '</ul>';
	}

	if ( $continue ) {
		echo '<div class="notice notice-info"><p><strong>' . \esc_html( (string) $report['suite'] ) . '</strong> : la suite démarre automatiquement…</p></div>';
	}
	echo '<form method="post" id="creapub-install-form">';
	\wp_nonce_field( 'creapub_install', 'creapub_nonce' );
	echo '<p><label><input type="checkbox" name="creapub_force" value="1"> ' . \esc_html__( 'Forcer : réécrire le contenu des pages et prestations déjà en place (titres et statuts conservés)', 'g2rd-creapub' ) . '</label></p>';
	\submit_button( \__( 'Créer les contenus manquants', 'g2rd-creapub' ), 'primary', 'creapub_install' );
	echo '</form></div>';
	if ( $continue ) {
		// Lot suivant : renvoie le formulaire (même nonce, même option « Forcer »).
		\printf(
			'<script>(function(){var f=document.getElementById("creapub-install-form");if(!f)return;var c=f.querySelector("[name=creapub_force]");if(c)c.checked=%s;var b=f.querySelector("[name=creapub_install]");setTimeout(function(){b.click();},%d);})();</script>',
			$force ? 'true' : 'false',
			(int) ( $report['pause'] ?? 800 )
		);
	}
}

/**
 * À l'activation du thème : purge des copies de modèles du parent, pour que
 * les gabarits et parts de l'enfant prennent la main immédiatement.
 */
\add_action(
	'after_switch_theme',
	function (): void {
		purge_parent_template_copies();
	}
);
