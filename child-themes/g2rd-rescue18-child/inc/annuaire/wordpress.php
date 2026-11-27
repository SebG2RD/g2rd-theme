<?php
/**
 * Annuaire, actualités, offres et concours : intégration WordPress.
 *
 * - Types de contenu : r18_service (fiche SDIS, /sdis/…), r18_offre (/offre/…),
 *   r18_actu_sdis (actualités agrégées, non publiques), r18_concours (calendrier).
 * - Gestionnaires branchés sur le moteur de veille (filtre r18_veille_gestionnaires).
 * - Toute donnée modifiée à la main dans l'admin est verrouillée : la
 *   synchronisation ne l'écrase plus (case « Ne plus actualiser »).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annuaire;

use G2RD_Rescue18\Veille\Contexte;

\defined( 'ABSPATH' ) || exit;

const OPT_REG = 'r18_annuaire_reglages';

/**
 * Réglages de l'annuaire et du recrutement.
 *
 * @return array<string,mixed>
 */
function reglages(): array {
	$def = array(
		'actus_jours'       => 45,
		'actus_conserve'    => 120,
		'actus_par_service' => 6,
		'actus_publication' => 'auto',
		'actus_exclus'      => '',
		'offres_publication' => 'auto',
		'offres_exclus'     => '',
		'images'            => true,
		'carte_fond'        => 'plan',
		'casernes'          => true,
		'detect_concours'   => true,
		'lien_spv'          => 'https://www.pompiers.fr/',
		'lien_bspp'         => 'https://pompiersparis.fr/',
		'lien_bmpm'         => 'https://www.lamarinerecrute.fr/',
		'lien_csp'          => 'https://choisirleservicepublic.gouv.fr/',
	);
	return \array_merge( $def, (array) \get_option( OPT_REG, array() ) );
}

/**
 * Champs par type : clé => [ libellé, type de champ, aide ].
 *
 * @param string $type Type de contenu.
 * @return array<string,array{0:string,1:string,2?:string}>
 */
function champs( string $type ): array {
	$c = array(
		'r18_service'   => array(
			'r18_nom'             => array( 'Nom officiel', 'text' ),
			'r18_genre'           => array( 'Type de service', 'select:sdis=SDIS|sis=SIS|sdmis=SDMIS|bspp=BSPP|bmpm=BMPM' ),
			'r18_deps'            => array( 'Départements couverts', 'text', 'Codes séparés par des virgules : 27 ou 75,92,93,94.' ),
			'r18_region'          => array( 'Région', 'text' ),
			'r18_site'            => array( 'Site internet', 'url' ),
			'r18_recrutement_url' => array( 'Page « recrutement » du service', 'url', 'Affichée en bouton sur la fiche et dans le parcours.' ),
			'r18_tel'             => array( 'Téléphone (standard, pas le 18)', 'text' ),
			'r18_email'           => array( 'Courriel', 'email' ),
			'r18_contact'         => array( 'Formulaire de contact', 'url' ),
			'r18_adresse'         => array( 'Adresse', 'text' ),
			'r18_cp'              => array( 'Code postal', 'text' ),
			'r18_ville'           => array( 'Ville', 'text' ),
			'r18_lat'             => array( 'Latitude', 'text' ),
			'r18_lng'             => array( 'Longitude', 'text' ),
			'r18_reseaux'         => array( 'Réseaux sociaux', 'reseaux', 'Un par ligne : Réseau | URL.' ),
			'r18_flux'            => array( 'Flux d\'actualités (RSS ou wp-json)', 'url', 'Trouvé tout seul. Saisis-le si la détection échoue.' ),
			'r18_flux_type'       => array( 'Type de flux', 'select:rss=RSS ou Atom|wpjson=API WordPress|=Aucun' ),
			'r18_image'           => array( 'Visuel (URL externe)', 'url', 'Image de partage du site officiel, affichée sans copie.' ),
			'r18_logo'            => array( 'Logo (URL externe)', 'url' ),
			'r18_fiche'           => array( 'Fiche Service-Public.fr', 'url' ),
			'r18_actus_off'       => array( 'Ne pas afficher les actualités de ce service', 'checkbox' ),
			'r18_a_verifier'      => array( 'Fiche à vérifier', 'checkbox', 'Signale une fiche saisie à la main (BMPM).' ),
		),
		'r18_offre'     => array(
			'r18_offre_ref'  => array( 'Référence Choisir le Service Public', 'text' ),
			'r18_employeur'  => array( 'Employeur', 'text' ),
			'r18_service_id' => array( 'Fiche SDIS liée (ID)', 'number' ),
			'r18_dep'        => array( 'Département', 'text' ),
			'r18_lieu'       => array( 'Lieu d\'affectation', 'text' ),
			'r18_metier'     => array( 'Métier', 'text' ),
			'r18_categorie'  => array( 'Catégorie', 'text' ),
			'r18_nature'     => array( 'Nature de l\'emploi', 'text' ),
			'r18_statut'     => array( 'Statut du poste', 'text' ),
			'r18_contrat'    => array( 'Durée du contrat', 'text' ),
			'r18_temps'      => array( 'Temps plein', 'text' ),
			'r18_teletravail' => array( 'Télétravail', 'text' ),
			'r18_etudes'     => array( 'Niveau d\'études', 'text' ),
			'r18_debut'      => array( 'Début de publication', 'date' ),
			'r18_fin'        => array( 'Date limite de candidature', 'date' ),
			'r18_vacance'    => array( 'Date de vacance', 'date' ),
			'r18_competences' => array( 'Compétences attendues', 'textarea' ),
			'r18_url'        => array( 'Lien pour postuler', 'url' ),
		),
		'r18_actu_sdis' => array(
			'r18_url'         => array( 'Article d\'origine', 'url' ),
			'r18_image'       => array( 'Image (URL externe)', 'url' ),
			'r18_service_id'  => array( 'Fiche SDIS (ID)', 'number' ),
			'r18_dep'         => array( 'Département', 'text' ),
			'r18_recrutement' => array( 'Annonce de recrutement', 'checkbox' ),
		),
		'r18_concours'  => array(
			'r18_cat'          => array( 'Catégorie', 'select:spp=Concours sapeur-pompier professionnel|sante=Service de santé (SSSM)|spv=Recrutement de volontaires|jsp=Jeunes sapeurs-pompiers|bspp=BSPP|bmpm=BMPM|examen=Examen professionnel|autre=Autre' ),
			'r18_type'         => array( 'Type (Concours, Examen professionnel)', 'text' ),
			'r18_categorie_fp' => array( 'Catégorie de la fonction publique', 'text' ),
			'r18_session'      => array( 'Session', 'text' ),
			'r18_organisateur' => array( 'Organisateur', 'text' ),
			'r18_service_id'   => array( 'Fiche SDIS liée (ID)', 'number' ),
			'r18_deps'         => array( 'Départements', 'text' ),
			'r18_insc_debut'   => array( 'Ouverture des inscriptions', 'date' ),
			'r18_insc_fin'     => array( 'Clôture des inscriptions', 'date' ),
			'r18_dossier'      => array( 'Dépôt du dossier avant le', 'date' ),
			'r18_epreuves'     => array( 'Date des épreuves', 'date' ),
			'r18_url'          => array( 'Lien officiel', 'url' ),
			'r18_origine'      => array( 'Origine', 'select:manuel=Saisie|auto=Repéré dans une actualité|officiel=Concours-Territorial (mis à jour chaque matin)' ),
		),
	);
	return $c[ $type ] ?? array();
}

/**
 * Types de contenu et méta.
 */
\add_action(
	'init',
	static function (): void {
		$lab = static fn( string $p, string $s, bool $f = false ): array => array(
			'name'               => $p,
			'singular_name'      => $s,
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter ' . ( $f ? 'une ' : 'un ' ) . \mb_strtolower( $s ),
			'edit_item'          => 'Modifier : ' . \mb_strtolower( $s ),
			'search_items'       => 'Rechercher',
			'not_found'          => 'Aucun résultat',
			'all_items'          => $p,
			'menu_name'          => $p,
		);
		$commun = array(
			'show_in_rest' => true,
			'show_ui'      => true,
			'show_in_menu' => 'rescue18-annuaire',
			'map_meta_cap' => true,
		);
		\register_post_type(
			'r18_service',
			$commun + array(
				'labels'      => $lab( 'Services (SDIS)', 'Service' ),
				'public'      => true,
				'has_archive' => 'sdis',
				'rewrite'     => array( 'slug' => 'sdis', 'with_front' => false ),
				'supports'    => array( 'title', 'editor', 'excerpt', 'custom-fields', 'revisions' ),
				'menu_icon'   => 'dashicons-location-alt',
			)
		);
		\register_post_type(
			'r18_offre',
			$commun + array(
				'labels'      => $lab( 'Offres d\'emploi', 'Offre', true ),
				'public'      => true,
				'has_archive' => false,
				'rewrite'     => array( 'slug' => 'offre', 'with_front' => false ),
				'supports'    => array( 'title', 'editor', 'custom-fields' ),
			)
		);
		\register_post_type(
			'r18_actu_sdis',
			$commun + array(
				'labels'              => $lab( 'Actualités des SDIS', 'Actualité', true ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title', 'excerpt', 'custom-fields' ),
			)
		);
		\register_post_type(
			'r18_concours',
			$commun + array(
				'labels'              => $lab( 'Concours et recrutements', 'Date de concours', true ),
				'public'              => true,
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'concours', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'excerpt', 'custom-fields', 'revisions' ),
			)
		);
		foreach ( array( 'r18_service', 'r18_offre', 'r18_actu_sdis', 'r18_concours' ) as $t ) {
			foreach ( champs( $t ) as $k => $def ) {
				\register_post_meta(
					$t,
					$k,
					array(
						'type'          => 'number' === $def[1] ? 'integer' : 'string',
						'single'        => true,
						'show_in_rest'  => true,
						'auth_callback' => static fn(): bool => \current_user_can( 'edit_posts' ),
					)
				);
			}
		}
	}
);

/* -------------------------------------------------------------------------
 * Accès aux fiches
 * ---------------------------------------------------------------------- */

/**
 * Recherche une fiche par méta, quel que soit son statut (corbeille comprise :
 * une fiche mise à la corbeille n'est jamais recréée).
 *
 * @param string $type Type.
 * @param string $cle  Méta.
 * @param string $val  Valeur.
 * @return int
 */
function par_meta( string $type, string $cle, string $val ): int {
	if ( '' === $val ) {
		return 0;
	}
	$ids = \get_posts(
		array(
			'post_type'        => $type,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_key'         => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $val, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( empty( $ids ) ) {
		$ids = \get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'trash',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $val, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}
	return (int) ( $ids[0] ?? 0 );
}

/**
 * Champs verrouillés d'une fiche.
 *
 * @param int $id Fiche.
 * @return string[]
 */
function verrous( int $id ): array {
	return \array_filter( \explode( ',', (string) \get_post_meta( $id, 'r18_verrous', true ) ) );
}

/**
 * Écrit les méta non verrouillées. Renvoie le nombre de valeurs changées.
 *
 * @param int                  $id   Fiche.
 * @param array<string,string> $vals Méta => valeur.
 * @return int
 */
function ecrire( int $id, array $vals ): int {
	$v = verrous( $id );
	$n = 0;
	foreach ( $vals as $k => $val ) {
		if ( \in_array( $k, $v, true ) ) {
			continue;
		}
		if ( (string) \get_post_meta( $id, $k, true ) !== (string) $val ) {
			\update_post_meta( $id, $k, $val );
			++$n;
		}
	}
	return $n;
}

/**
 * Services publiés, mis en cache 10 minutes.
 *
 * @return array<int,array<string,mixed>>
 */
function services(): array {
	$c = \wp_cache_get( 'services', 'r18_annuaire' );
	if ( \is_array( $c ) ) {
		return $c;
	}
	$out = array();
	foreach ( \get_posts(
		array(
			'post_type'      => 'r18_service',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	) as $p ) {
		$m     = static fn( string $k ): string => (string) \get_post_meta( $p->ID, $k, true );
		$deps  = \array_values( \array_filter( \array_map( 'trim', \explode( ',', $m( 'r18_deps' ) ) ) ) );
		$out[] = array(
			'id'          => $p->ID,
			'titre'       => \get_the_title( $p ),
			'nom'         => $m( 'r18_nom' ),
			'genre'       => $m( 'r18_genre' ),
			'deps'        => $deps,
			'dep'         => $deps[0] ?? '',
			'region'      => $m( 'r18_region' ),
			'site'        => $m( 'r18_site' ),
			'recrutement' => $m( 'r18_recrutement_url' ),
			'tel'         => $m( 'r18_tel' ),
			'ville'       => $m( 'r18_ville' ),
			'lat'         => (float) $m( 'r18_lat' ),
			'lng'         => (float) $m( 'r18_lng' ),
			'image'       => $m( 'r18_image' ),
			'logo'        => $m( 'r18_logo' ),
			'flux'        => $m( 'r18_flux' ),
			'flux_type'   => $m( 'r18_flux_type' ),
			'flux_verif'  => (int) $m( 'r18_flux_verif' ),
			'actus_off'   => '' !== $m( 'r18_actus_off' ),
			'url'         => (string) \get_permalink( $p ),
		);
	}
	\wp_cache_set( 'services', $out, 'r18_annuaire', 600 );
	return $out;
}

/**
 * Service couvrant un département.
 *
 * @param string $dep Code.
 * @return array<string,mixed>|null
 */
function service_du_dep( string $dep ): ?array {
	foreach ( services() as $s ) {
		if ( 'bmpm' !== $s['genre'] && \in_array( $dep, $s['deps'], true ) ) {
			return $s;
		}
	}
	return null;
}

/**
 * Un service par identifiant.
 *
 * @param int $id ID.
 * @return array<string,mixed>|null
 */
function service( int $id ): ?array {
	foreach ( services() as $s ) {
		if ( $s['id'] === $id ) {
			return $s;
		}
	}
	return null;
}

\add_action(
	'save_post',
	static function ( int $id, \WP_Post $p ): void {
		if ( \in_array( $p->post_type, array( 'r18_service', 'r18_offre', 'r18_actu_sdis', 'r18_concours' ), true ) ) {
			\wp_cache_delete( 'services', 'r18_annuaire' );
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Gestionnaires (moteur de veille)
 * ---------------------------------------------------------------------- */

/**
 * Résultat standard.
 *
 * @param string $a  Action.
 * @param int    $id Fiche.
 * @param string $n  Note.
 * @return array{action:string,id:int,note:string}
 */
function res( string $a, int $id, string $n ): array {
	return array( 'action' => $a, 'id' => $id, 'note' => $n );
}

/**
 * Fiche de service depuis l'Annuaire de l'administration.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_service( array $r, Contexte $c ): array {
	$id   = par_meta( 'r18_service', 'r18_annuaire_id', $r['ref'] );
	$vals = array(
		'r18_annuaire_id' => $r['ref'],
		'r18_nom'         => $r['nom'],
		'r18_genre'       => $r['genre'],
		'r18_deps'        => $r['deps'],
		'r18_region'      => $r['region'],
		'r18_site'        => $r['site'],
		'r18_tel'         => $r['tel'],
		'r18_email'       => $r['email'],
		'r18_contact'     => $r['contact'],
		'r18_adresse'     => $r['adresse'],
		'r18_cp'          => $r['cp'],
		'r18_ville'       => $r['ville'],
		'r18_lat'         => $r['lat'],
		'r18_lng'         => $r['lng'],
		'r18_reseaux'     => $r['reseaux'],
		'r18_fiche'       => $r['fiche'],
	);
	if ( $c->simulation ) {
		return res( $id ? 'identique' : 'nouveau', $id, $id ? 'Fiche existante.' : 'Serait créée.' );
	}
	if ( 0 === $id ) {
		$id = (int) \wp_insert_post(
			array(
				'post_type'    => 'r18_service',
				'post_status'  => 'publish',
				'post_title'   => $r['titre'],
				'post_name'    => $r['slug'],
				'post_excerpt' => $r['nom'],
			),
			true
		);
		if ( $id <= 0 ) {
			return res( 'erreur', 0, 'Création impossible.' );
		}
		ecrire( $id, $vals );
		\update_post_meta( $id, 'r18_signal', $r['signal'] );
		if ( 'bmpm' === $r['genre'] ) {
			\update_post_meta( $id, 'r18_a_verifier', '1' );
			\update_post_meta( $id, 'r18_verrous', 'r18_site,r18_tel,r18_email,r18_adresse,r18_cp,r18_ville,r18_lat,r18_lng' );
		}
		return res( 'nouveau', $id, 'Fiche créée.' );
	}
	if ( 'trash' === \get_post_status( $id ) ) {
		return res( 'ecarte', $id, 'Fiche à la corbeille : laissée telle quelle.' );
	}
	if ( (string) \get_post_meta( $id, 'r18_signal', true ) === $r['signal'] && 'bmpm' !== $r['genre'] ) {
		return res( 'identique', $id, '' );
	}
	$n = ecrire( $id, 'bmpm' === $r['genre'] ? array() : $vals );
	\update_post_meta( $id, 'r18_signal', $r['signal'] );
	return res( $n > 0 ? 'version' : 'identique', $id, $n . ' information(s) actualisée(s).' );
}

/**
 * Carte des casernes : fichier JSON dans uploads/rescue18/.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_casernes( array $r, Contexte $c ): array {
	if ( $c->simulation ) {
		return res( 'identique', 0, 'Simulation : fichier non écrit.' );
	}
	// Chaque zone est gardée à part : une zone en échec garde sa version précédente.
	[ $osm ] = dossier_cartes( 'osm' );
	// Reprise de l'ancien stockage en option (zones reçues avant la mise à jour).
	foreach ( (array) \get_option( 'r18_casernes_tmp', array() ) as $z => $pts ) {
		ecrire_json( $osm . '/france-' . \sanitize_key( (string) $z ) . '.json', (array) $pts );
	}
	\delete_option( 'r18_casernes_tmp' );
	if ( ecrire_json( $osm . '/france-' . \sanitize_key( $r['zone'] ) . '.json', (array) \json_decode( $r['donnees'], true ) ) ) {
		$etat               = (array) \get_option( 'r18_france_zones', array() );
		$etat[ $r['zone'] ] = array(
			't' => \time(),
			'v' => 3,
		);
		\update_option( 'r18_france_zones', $etat, false );
	}
	\update_option( 'r18_france_a_construire', 1, false );
	if ( (int) $r['rang'] < (int) $r['total'] - 1 ) {
		return res( 'identique', 0, '' );
	}
	return assembler_france();
}

/**
 * Assemble les zones de France en un fichier brut, puis reconstruit la carte.
 *
 * @return array{action:string,id:int,note:string}
 */
function assembler_france(): array {
	\delete_option( 'r18_france_a_construire' );
	[ $osm ] = dossier_cartes( 'osm' );
	$zones   = (array) \glob( $osm . '/france-*.json' );
	$tout    = array();
	foreach ( $zones as $f ) {
		foreach ( lire_json( (string) $f ) as $p ) {
			// Doublons aux bords des zones : même identifiant OSM ou même position.
			$tout[ (string) ( $p[3] ?? $p[0] . ',' . $p[1] ) ] = $p;
		}
	}
	$tout     = \array_values( $tout );
	$manquent = \count( Connecteur_Casernes::zones() ) - \count( $zones );
	if ( \count( $tout ) < 3000 ) {
		return res( 'a_valider', 0, \count( $tout ) . ' casernes seulement : carte précédente conservée.' );
	}
	if ( ! ecrire_json( $osm . '/france.json', $tout ) ) {
		return res( 'erreur', 0, 'Écriture de uploads/rescue18/osm/france.json impossible.' );
	}
	construire_cartes();
	$n = (int) ( ( (array) \get_option( 'r18_casernes', array() ) )['n'] ?? 0 );
	return res( 'version', 0, $n . ' casernes sur la carte (' . \count( $tout ) . ' dans OpenStreetMap' . ( $manquent > 0 ? ', ' . $manquent . ' zone(s) jamais reçue(s), nouvel essai au prochain cycle' : '' ) . ').' );
}

\add_action(
	'r18_veille_cycle_fini',
	static function (): void {
		if ( \get_option( 'r18_france_a_construire' ) ) {
			assembler_france();
		}
	},
	5
);

/**
 * Flux et visuels découverts pour un service.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_flux( array $r, Contexte $c ): array {
	$id = (int) $r['service'];
	if ( $c->simulation || $id <= 0 ) {
		return res( 'identique', $id, '' );
	}
	$vals = array(
		'r18_flux'       => $r['flux'],
		'r18_flux_type'  => $r['flux_type'],
		'r18_flux_verif' => (string) \time(),
	);
	foreach ( array( 'image', 'logo' ) as $k ) {
		if ( '' !== $r[ $k ] ) {
			$vals[ 'r18_' . $k ] = $r[ $k ];
		}
	}
	$n = ecrire( $id, $vals );
	return res( $n > 1 ? 'version' : 'identique', $id, '' !== $r['flux'] ? 'Flux : ' . $r['flux'] : 'Aucun flux public : à saisir dans la fiche si le service en publie un.' );
}

/**
 * Article d'un SDIS.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_actu( array $r, Contexte $c ): array {
	$reg = reglages();
	$id  = par_meta( 'r18_actu_sdis', 'r18_ref', $r['ref'] );
	if ( $id > 0 ) {
		if ( ! $c->simulation && '' !== $r['image'] && '' === (string) \get_post_meta( $id, 'r18_image', true ) ) {
			ecrire( $id, array( 'r18_image' => $r['image'] ) );
		}
		return res( 'identique', $id, '' );
	}
	foreach ( \array_filter( \array_map( 'trim', \explode( ',', (string) $reg['actus_exclus'] ) ) ) as $mot ) {
		if ( \str_contains( \G2RD_Rescue18\Veille\plat( $r['titre'] ), \G2RD_Rescue18\Veille\plat( $mot ) ) ) {
			return res( 'ecarte', 0, 'Mot exclu : ' . $mot . '.' );
		}
	}
	if ( $c->simulation ) {
		return res( 'nouveau', 0, 'Serait ajoutée.' );
	}
	$gmt = '' !== $r['date'] ? $r['date'] : \gmdate( 'Y-m-d H:i:s' );
	$id  = (int) \wp_insert_post(
		array(
			'post_type'     => 'r18_actu_sdis',
			'post_status'   => 'validation' === $reg['actus_publication'] ? 'pending' : 'publish',
			'post_title'    => \wp_strip_all_tags( $r['titre'] ),
			'post_excerpt'  => $r['resume'],
			'post_date_gmt' => $gmt,
			'post_date'     => \get_date_from_gmt( $gmt ),
		),
		true
	);
	if ( $id <= 0 ) {
		return res( 'erreur', 0, 'Création impossible.' );
	}
	foreach ( array(
		'r18_ref'         => $r['ref'],
		'r18_url'         => \esc_url_raw( $r['url'] ),
		'r18_image'       => \esc_url_raw( $r['image'] ),
		'r18_service_id'  => $r['service'],
		'r18_dep'         => $r['dep'],
		'r18_recrutement' => $r['recrutement'],
	) as $k => $v ) {
		\update_post_meta( $id, $k, $v );
	}
	if ( ! empty( $reg['detect_concours'] ) && \preg_match( '/\bconcours\b|recrutement (de |des )?(sapeurs?-pompiers )?(volontaires|professionnels|spv|spp)|campagne de recrutement/i', \G2RD_Rescue18\Veille\plat( $r['titre'] ) ) && 0 === par_meta( 'r18_concours', 'r18_url', $r['url'] ) ) {
		$s   = service( (int) $r['service'] );
		$cid = (int) \wp_insert_post(
			array(
				'post_type'    => 'r18_concours',
				'post_status'  => 'draft',
				'post_title'   => \wp_strip_all_tags( $r['titre'] ),
				'post_excerpt' => $r['resume'],
			)
		);
		if ( $cid > 0 ) {
			foreach ( array(
				'r18_cat'          => \str_contains( \G2RD_Rescue18\Veille\plat( $r['titre'] ), 'concours' ) ? 'spp' : 'spv',
				'r18_organisateur' => $s ? (string) $s['titre'] : '',
				'r18_service_id'   => $r['service'],
				'r18_deps'         => $r['dep'],
				'r18_url'          => \esc_url_raw( $r['url'] ),
				'r18_origine'      => 'auto',
			) as $k => $v ) {
				\update_post_meta( $cid, $k, $v );
			}
		}
	}
	return res( 'nouveau', $id, '' !== $r['recrutement'] ? 'Recrutement repéré.' : '' );
}

/**
 * Offre d'emploi.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_offre( array $r, Contexte $c ): array {
	$id = par_meta( 'r18_offre', 'r18_offre_ref', $r['ref'] );
	if ( '' !== $r['fin'] && $r['fin'] < \wp_date( 'Y-m-d' ) ) {
		return res( 'ecarte', $id, 'Candidatures closes.' );
	}
	$reg = reglages();
	foreach ( \array_filter( \array_map( 'trim', \explode( ',', (string) $reg['offres_exclus'] ) ) ) as $mot ) {
		if ( \str_contains( \G2RD_Rescue18\Veille\plat( $r['titre'] . ' ' . $r['metier'] ), \G2RD_Rescue18\Veille\plat( $mot ) ) ) {
			return res( 'ecarte', $id, 'Mot exclu : ' . $mot . '.' );
		}
	}
	if ( $c->simulation ) {
		return res( $id ? 'identique' : 'nouveau', $id, '' );
	}
	$s    = '' !== $r['dep'] && ! empty( $r['sdis'] ) ? service_du_dep( $r['dep'] ) : null;
	$vals = array(
		'r18_offre_ref'   => $r['ref'],
		'r18_employeur'   => $s ? (string) $s['titre'] : $r['employeur'],
		'r18_service_id'  => $s ? (string) $s['id'] : '',
		'r18_dep'         => $r['dep'],
		'r18_lieu'        => '' !== $r['ville'] ? $r['ville'] : $r['lieu'],
		'r18_metier'      => $r['metier'],
		'r18_categorie'   => $r['categorie'],
		'r18_nature'      => $r['nature'],
		'r18_statut'      => $r['statut'],
		'r18_contrat'     => $r['contrat'],
		'r18_temps'       => $r['temps'],
		'r18_teletravail' => $r['teletravail'],
		'r18_etudes'      => $r['etudes'],
		'r18_debut'       => $r['debut'],
		'r18_fin'         => $r['fin'],
		'r18_vacance'     => $r['vacance'],
		'r18_competences' => $r['competences'],
		'r18_url'         => $r['url'],
	);
	if ( 0 === $id ) {
		$id = (int) \wp_insert_post(
			array(
				'post_type'   => 'r18_offre',
				'post_status' => 'validation' === $reg['offres_publication'] ? 'pending' : 'publish',
				'post_title'  => \wp_strip_all_tags( $r['titre'] ),
				'post_name'   => \sanitize_title( $r['titre'] . '-' . $r['ref'] ),
			),
			true
		);
		if ( $id <= 0 ) {
			return res( 'erreur', 0, 'Création impossible.' );
		}
		ecrire( $id, $vals );
		\update_post_meta( $id, 'r18_import', $r['import'] );
		// Recrutement collectif (concours, sélection de caporaux…) : proposé au calendrier.
		if ( ! empty( $reg['detect_concours'] ) && \preg_match( '/\bconcours\b|\b\d+\s+(caporaux|sapeurs|lieutenants|sergents)\b|selection|recrutement de (sapeurs|caporaux)/', \G2RD_Rescue18\Veille\plat( $r['titre'] ) ) && 0 === par_meta( 'r18_concours', 'r18_url', $r['url'] ) ) {
			$cid = (int) \wp_insert_post(
				array(
					'post_type'   => 'r18_concours',
					'post_status' => 'draft',
					'post_title'  => \wp_strip_all_tags( $r['titre'] ),
				)
			);
			foreach ( array(
				'r18_cat'          => 'spp',
				'r18_organisateur' => (string) $vals['r18_employeur'],
				'r18_service_id'   => (string) $vals['r18_service_id'],
				'r18_deps'         => $r['dep'],
				'r18_insc_debut'   => $r['debut'],
				'r18_insc_fin'     => $r['fin'],
				'r18_url'          => $r['url'],
				'r18_origine'      => 'auto',
			) as $k => $v ) {
				if ( $cid > 0 ) {
					\update_post_meta( $cid, $k, $v );
				}
			}
		}
		return res( 'nouveau', $id, (string) $vals['r18_employeur'] );
	}
	\update_post_meta( $id, 'r18_import', $r['import'] );
	$st = (string) \get_post_status( $id );
	if ( 'trash' === $st ) {
		return res( 'ecarte', $id, 'Offre à la corbeille : laissée telle quelle.' );
	}
	if ( 'draft' === $st && '' !== (string) \get_post_meta( $id, 'r18_etat', true ) ) {
		\wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		\delete_post_meta( $id, 'r18_etat' );
	}
	$n = ecrire( $id, $vals );
	return res( $n > 0 ? 'version' : 'identique', $id, '' );
}

/**
 * Fin d'un import complet : retrait des offres absentes du fichier.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_offres_fin( array $r, Contexte $c ): array {
	if ( $c->simulation ) {
		return res( 'identique', 0, '' );
	}
	$ids    = \get_posts(
		array(
			'post_type'      => 'r18_offre',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$absent = array();
	foreach ( $ids as $id ) {
		if ( (string) \get_post_meta( (int) $id, 'r18_import', true ) !== $r['import'] ) {
			$absent[] = (int) $id;
		}
	}
	$total = \count( $ids );
	if ( $total > 10 && \count( $absent ) > $total * 0.6 ) {
		return res( 'a_valider', 0, \count( $absent ) . ' offres absentes sur ' . $total . ' : retrait suspendu par prudence. Vérifie le fichier avant le prochain import.' );
	}
	foreach ( $absent as $id ) {
		\wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		\update_post_meta( $id, 'r18_etat', 'retiree' );
	}
	return res( $absent ? 'version' : 'identique', 0, \count( $absent ) . ' offre(s) retirée(s) : absentes de Choisir le Service Public.' );
}

\add_filter(
	'r18_veille_gestionnaires',
	static function ( array $g ): array {
		return $g + array(
			'service'    => __NAMESPACE__ . '\g_service',
			'casernes'   => __NAMESPACE__ . '\g_casernes',
			'flux_sdis'  => __NAMESPACE__ . '\g_flux',
			'actu_sdis'  => __NAMESPACE__ . '\g_actu',
			'offre'      => __NAMESPACE__ . '\g_offre',
			'offres_fin' => __NAMESPACE__ . '\g_offres_fin',
		);
	}
);

\add_filter(
	'r18_veille_connecteurs',
	static function ( array $c ): array {
		$actus           = new Connecteur_Actus();
		$actus->services = static function (): array {
			$reg = reglages();
			$out = array();
			foreach ( services() as $s ) {
				if ( $s['actus_off'] ) {
					continue;
				}
				$out[] = array(
					'id'         => $s['id'],
					'titre'      => $s['titre'],
					'site'       => $s['site'],
					'flux'       => $s['flux'],
					'flux_type'  => $s['flux_type'],
					'flux_verif' => $s['flux_verif'],
					'dep'        => $s['dep'],
					'jours'      => (int) $reg['actus_jours'],
					'max'        => (int) $reg['actus_par_service'],
				);
			}
			return $out;
		};
		return $c + array(
			'annuaire' => new Connecteur_Annuaire(),
			'casernes' => new Connecteur_Casernes(),
			'sdis'     => $actus,
			'offres'   => new Connecteur_Offres(),
		);
	}
);

/**
 * Entretien quotidien, après chaque cycle : purge des actualités anciennes,
 * retrait des offres dont la date limite est passée.
 */
\add_action(
	'r18_veille_cycle_fini',
	static function ( array $c ): void {
		if ( ! empty( $c['simulation'] ) ) {
			return;
		}
		$reg = reglages();
		foreach ( \get_posts(
			array(
				'post_type'      => 'r18_actu_sdis',
				'post_status'    => 'any',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'date_query'     => array( array( 'before' => ( (int) $reg['actus_conserve'] ) . ' days ago' ) ),
			)
		) as $id ) {
			\wp_delete_post( (int) $id, true );
		}
		foreach ( \get_posts(
			array(
				'post_type'      => 'r18_offre',
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'r18_fin',
						'value'   => \wp_date( 'Y-m-d' ),
						'compare' => '<',
					),
					array(
						'key'     => 'r18_fin',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		) as $id ) {
			\wp_update_post( array( 'ID' => (int) $id, 'post_status' => 'draft' ) );
			\update_post_meta( (int) $id, 'r18_etat', 'expiree' );
		}
		\wp_cache_delete( 'services', 'r18_annuaire' );
	}
);

/**
 * Mise en place, une fois par version du thème : pages du module (créées
 * si absentes, jamais réécrites) et règles d'URL (/sdis/, /offre/).
 */
\add_action(
	'wp_loaded',
	static function (): void {
		if ( \get_option( 'r18_annuaire_version' ) === \G2RD_Rescue18\VERSION || \wp_installing() ) {
			return;
		}
		\update_option( 'r18_annuaire_version', \G2RD_Rescue18\VERSION, false );
		// Toutes les pages gérées absentes (outils, recrutement…) : créées, jamais réécrites.
		$crees = \G2RD_Rescue18\install_missing_pages();
		// Pages importées de l'ancien site refondues une fois (révision conservée).
		if ( \G2RD_Rescue18\refondre_page( 'devenir-pompier', 'rescue18/page-devenir-pompier', 'r18-voies' ) ) {
			$crees[] = '/devenir-pompier/ (refonte)';
		}
		if ( \G2RD_Rescue18\refondre_page( 'partenaires', 'rescue18/page-partenaires', 'r18-pt-v3' ) ) {
			$crees[] = '/partenaires/ (refonte)';
		}
		if ( \G2RD_Rescue18\refondre_page( 'recrutement/concours', 'rescue18/page-concours', 'rescue18/concours-chiffres' ) ) {
			$crees[] = '/recrutement/concours/ (refonte)';
		}
		if ( \G2RD_Rescue18\refondre_page( 'contactez-nous', 'rescue18/page-contact', 'r18-ctc__form' ) ) {
			$crees[] = '/contactez-nous/ (refonte)';
		}
		foreach ( array( 'recrutement/concours' => 'Concours de sapeur-pompier', 'acronyme' => 'Lexique et acronymes', 'contactez-nous' => 'Contact' ) as $chemin => $titre ) {
			$pg = \get_page_by_path( $chemin );
			if ( $pg instanceof \WP_Post && ! \get_post_meta( $pg->ID, '_r18_titre_v2', true ) ) {
				\update_post_meta( $pg->ID, '_r18_titre_v2', 1 );
				\wp_update_post( array( 'ID' => $pg->ID, 'post_title' => $titre ) );
			}
		}
		if ( \G2RD_Rescue18\refondre_page( 'acronyme', 'rescue18/page-lexique', 'r18-lex-v4' ) ) {
			$crees[] = '/acronyme/ (refonte)';
		}
		// Premier passage : les concours officiels arrivent sans attendre le lendemain.
		if ( ! \get_option( 'r18_concours_amorce' ) ) {
			\update_option( 'r18_concours_amorce', 1, false );
			\wp_schedule_single_event( \time() + 30, 'r18_concours_sync' );
		}
		if ( $crees && \function_exists( 'G2RD_Rescue18\\Veille\\journal' ) ) {
			\G2RD_Rescue18\Veille\journal( 'cycle', 'info', 'Pages ajoutées : ' . \implode( ', ', $crees ) );
		}
		\flush_rewrite_rules( false );
		// Premier passage : l'annuaire est importé tout seul, sans attendre la nuit.
		if ( ! \get_option( 'r18_annuaire_amorce' ) ) {
			\update_option( 'r18_annuaire_amorce', 'annuaire', false );
			\wp_schedule_single_event( \time() + 15, 'r18_annuaire_amorce' );
		}
	},
	30
);

/**
 * Amorçage automatique : l'annuaire d'abord, puis un cycle complet
 * (actualités, offres, casernes) dès que les fiches existent.
 */
\add_action(
	'r18_annuaire_amorce',
	static function (): void {
		$etape = (string) \get_option( 'r18_annuaire_amorce' );
		$cible = 'annuaire' === $etape ? 'annuaire' : '';
		if ( \G2RD_Rescue18\Veille\demarrer( $cible, false, 'premier import' ) ) {
			\update_option( 'r18_annuaire_amorce', 'annuaire' === $etape ? 'complet' : 'fait', false );
			\G2RD_Rescue18\Veille\relancer();
		} else {
			\wp_schedule_single_event( \time() + 120, 'r18_annuaire_amorce' );
		}
	}
);
\add_action(
	'r18_veille_cycle_fini',
	static function ( array $c ): void {
		if ( 'annuaire' === ( $c['cible'] ?? '' ) && 'complet' === \get_option( 'r18_annuaire_amorce' ) && empty( $c['simulation'] ) ) {
			\wp_schedule_single_event( \time() + 60, 'r18_annuaire_amorce' );
		}
	},
	20
);

/**
 * Titres saisis en capitales par les sources : affichés en casse de phrase
 * (le titre enregistré reste celui de la source, modifiable dans l'admin).
 */
\add_filter(
	'the_title',
	static function ( string $t, $id = 0 ): string {
		return $id && \in_array( \get_post_type( (int) $id ), array( 'r18_offre', 'r18_actu_sdis', 'r18_concours' ), true ) && ! \is_admin() ? casse_titre( $t ) : $t;
	},
	10,
	2
);

\add_filter(
	'post_type_archive_title',
	static fn( string $t, string $type ): string => 'r18_service' === $type ? 'Annuaire des SDIS, de la BSPP et du BMPM' : $t,
	10,
	2
);

/**
 * Service cité dans un intitulé (« SDIS de l'Oise », « SDIS 13 », « BSPP »).
 *
 * @param string $texte Intitulé.
 * @return array<string,mixed>|null
 */
function service_cite( string $texte ): ?array {
	$t = \G2RD_Rescue18\Veille\plat( $texte );
	if ( \preg_match( '/\bbspp\b|brigade de sapeurs-pompiers de paris/', $t ) ) {
		foreach ( services() as $s ) {
			if ( 'bspp' === $s['genre'] ) {
				return $s;
			}
		}
	}
	if ( \preg_match( '/\bbmpm\b|marins-pompiers de marseille/', $t ) ) {
		foreach ( services() as $s ) {
			if ( 'bmpm' === $s['genre'] ) {
				return $s;
			}
		}
	}
	if ( \preg_match( '/\b(?:sdis|sis|sdmis|s\.d\.i\.s\.?)\s*(\d{2,3}|2a|2b)\b/', $t, $m ) ) {
		return service_du_dep( \strtoupper( $m[1] ) );
	}
	if ( ! \preg_match( '/\b(sdis|sis|sdmis|service departemental)\b/', $t ) ) {
		return null;
	}
	static $noms = null;
	if ( null === $noms ) {
		$noms = array();
		foreach ( departements() as $code => [ $nom ] ) {
			$noms[ \G2RD_Rescue18\Veille\plat( $nom ) ] = (string) $code;
		}
		\uksort( $noms, static fn( string $a, string $b ): int => \mb_strlen( $b ) <=> \mb_strlen( $a ) );
	}
	foreach ( $noms as $nom => $code ) {
		if ( \preg_match( '/(?<![\p{L}-])' . \preg_quote( $nom, '/' ) . '(?![\p{L}-])/u', $t ) ) {
			return service_du_dep( $code );
		}
	}
	return null;
}
