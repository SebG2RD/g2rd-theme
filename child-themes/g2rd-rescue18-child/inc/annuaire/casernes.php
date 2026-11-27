<?php
/**
 * Casernes de France et du monde : données OpenStreetMap, corrections
 * au back-office, fichiers des cartes.
 *
 * Source : OpenStreetMap (amenity=fire_station), seule base ouverte qui couvre
 * toute la France et le monde. Chaque caserne garde son identifiant OSM.
 * Une caserne corrigée au back-office (adresse, position, nom, masquage)
 * l'emporte sur OSM et n'est jamais écrasée par la synchronisation.
 *
 * Fichiers publics (uploads/rescue18/) :
 * - casernes.json        France, [lat, lng, nom, id, exploitant, fiche, adresse]
 * - monde/index.json     comptes par carreau
 * - monde/<carreau>.json casernes d'un carreau
 * Fichiers bruts OSM (uploads/rescue18/osm/) : jamais modifiés à la main.
 *
 * @package G2RD_Rescue18
 */

namespace G2RD_Rescue18\Annuaire;

use G2RD_Rescue18\Veille\Contexte;

\defined( 'ABSPATH' ) || exit;

const CASERNE = 'r18_caserne';

/**
 * Dossier des fichiers de cartes.
 *
 * @param string $sous Sous-dossier.
 * @return array{0:string,1:string} Chemin et URL.
 */
function dossier_cartes( string $sous = '' ): array {
	$up  = \wp_upload_dir();
	$rel = 'rescue18' . ( '' !== $sous ? '/' . $sous : '' );
	$dir = \trailingslashit( $up['basedir'] ) . $rel;
	\wp_mkdir_p( $dir );
	return array( $dir, \trailingslashit( $up['baseurl'] ) . $rel );
}

/**
 * Lit un fichier JSON de cartes.
 *
 * @param string $chemin Chemin.
 * @return array<int,mixed>
 */
function lire_json( string $chemin ): array {
	if ( ! \is_readable( $chemin ) ) {
		return array();
	}
	$d = \json_decode( (string) \file_get_contents( $chemin ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return \is_array( $d ) ? $d : array();
}

/**
 * Écrit un fichier JSON de cartes.
 *
 * @param string $chemin Chemin.
 * @param mixed  $data   Données.
 * @return bool
 */
function ecrire_json( string $chemin, $data ): bool {
	return false !== \file_put_contents( $chemin, (string) \wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/* Type de contenu : une correction ou un ajout, pas de fiche publique. */
\add_action(
	'init',
	static function (): void {
		\register_post_type(
			CASERNE,
			array(
				'labels'          => array(
					'name'          => 'Casernes corrigées',
					'singular_name' => 'Caserne',
					'add_new'       => 'Ajouter une caserne',
					'add_new_item'  => 'Ajouter ou corriger une caserne',
					'edit_item'     => 'Corriger la caserne',
					'all_items'     => 'Casernes corrigées',
					'menu_name'     => 'Casernes',
					'search_items'  => 'Rechercher une caserne',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'rescue18-annuaire',
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'page',
				'map_meta_cap'    => true,
			)
		);
	}
);

/**
 * Champs d'une caserne : clé => [ libellé, aide ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function champs_caserne(): array {
	return array(
		'r18_c_adresse'    => array( 'Adresse', 'Numéro et rue. Pour la France, la position se recalcule toute seule depuis l’adresse (Base adresse nationale).' ),
		'r18_c_cp'         => array( 'Code postal', '' ),
		'r18_c_commune'    => array( 'Commune', '' ),
		'r18_c_pays'       => array( 'Pays (code ISO)', 'FR pour la France. Autre code pour une caserne à l’étranger, par exemple BE, CH, CA.' ),
		'r18_c_exploitant' => array( 'Service', 'Par exemple « SDIS 27 », « BSPP », « BMPM ».' ),
		'r18_c_lat'        => array( 'Latitude', 'Rempli tout seul depuis l’adresse en France. Saisis-le pour l’étranger.' ),
		'r18_c_lng'        => array( 'Longitude', '' ),
		'r18_c_osm'        => array( 'Identifiant OpenStreetMap', 'Rempli quand tu corriges une caserne depuis la carte. Vide pour une caserne ajoutée.' ),
	);
}

/**
 * Cherche une caserne OSM par identifiant dans les fichiers bruts.
 *
 * @param string $osm Identifiant (n123, w456…).
 * @return array<int,mixed>|null
 */
function caserne_osm( string $osm ): ?array {
	[ $dir ] = dossier_cartes( 'osm' );
	foreach ( \array_merge( array( $dir . '/france.json' ), (array) \glob( $dir . '/monde-*.json' ) ) as $f ) {
		foreach ( lire_json( (string) $f ) as $p ) {
			if ( \is_array( $p ) && ( $p[3] ?? '' ) === $osm ) {
				return $p;
			}
		}
	}
	return null;
}

/**
 * Correction existante pour un identifiant OSM.
 *
 * @param string $osm Identifiant.
 * @return int
 */
function correction_osm( string $osm ): int {
	$ids = \get_posts(
		array(
			'post_type'      => CASERNE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'r18_c_osm', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $osm, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) ( $ids[0] ?? 0 );
}

/* Depuis la carte : « Corriger » ouvre la correction existante ou en prépare une. */
\add_action(
	'load-post-new.php',
	static function (): void {
		if ( CASERNE !== ( $_GET['post_type'] ?? '' ) || empty( $_GET['osm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$osm = \sanitize_key( \wp_unslash( $_GET['osm'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$id  = correction_osm( $osm );
		if ( $id ) {
			\wp_safe_redirect( (string) \get_edit_post_link( $id, 'raw' ) );
			exit;
		}
	}
);

\add_action(
	'add_meta_boxes_' . CASERNE,
	static function (): void {
		\add_meta_box( 'r18-caserne', 'Caserne', __NAMESPACE__ . '\\boite_caserne', CASERNE, 'normal', 'high' );
	}
);

/**
 * Boîte de saisie d'une caserne.
 *
 * @param \WP_Post $post Caserne.
 */
function boite_caserne( \WP_Post $post ): void {
	\wp_nonce_field( 'r18_caserne', 'r18_caserne_nonce' );
	$osm   = (string) \get_post_meta( $post->ID, 'r18_c_osm', true );
	$init  = array();
	if ( '' === $osm && ! empty( $_GET['osm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$osm = \sanitize_key( \wp_unslash( $_GET['osm'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$p   = caserne_osm( $osm );
		if ( $p ) {
			$init = array(
				'r18_c_lat'        => (string) $p[0],
				'r18_c_lng'        => (string) $p[1],
				'r18_c_exploitant' => (string) ( $p[4] ?? '' ),
				'titre'            => (string) $p[2],
			);
		}
		$init['r18_c_osm'] = $osm;
	}
	echo '<p>Cette fiche corrige ou ajoute une caserne sur les cartes. Elle l’emporte sur OpenStreetMap et la synchronisation ne la modifie jamais. Aucune page publique n’est créée.</p>';
	if ( isset( $init['titre'] ) && '' === $post->post_title ) {
		echo '<script>document.addEventListener("DOMContentLoaded",function(){var t=document.getElementById("title");if(t&&!t.value){t.value=' . \wp_json_encode( $init['titre'] ) . ';t.dispatchEvent(new Event("input"));var l=document.getElementById("title-prompt-text");if(l)l.classList.add("screen-reader-text");}});</script>';
	}
	echo '<table class="form-table" role="presentation">';
	foreach ( champs_caserne() as $cle => [ $label, $aide ] ) {
		$v = (string) \get_post_meta( $post->ID, $cle, true );
		$v = '' !== $v ? $v : (string) ( $init[ $cle ] ?? ( 'r18_c_pays' === $cle ? 'FR' : '' ) );
		echo '<tr><th scope="row"><label for="' . \esc_attr( $cle ) . '">' . \esc_html( $label ) . '</label></th><td><input type="text" class="regular-text" id="' . \esc_attr( $cle ) . '" name="' . \esc_attr( $cle ) . '" value="' . \esc_attr( $v ) . '"' . ( 'r18_c_osm' === $cle ? ' readonly' : '' ) . '>';
		if ( '' !== $aide ) {
			echo '<p class="description">' . \esc_html( $aide ) . '</p>';
		}
		echo '</td></tr>';
	}
	$masque = '1' === \get_post_meta( $post->ID, 'r18_c_masque', true );
	echo '<tr><th scope="row">Affichage</th><td><label><input type="checkbox" name="r18_c_masque" value="1"' . \checked( $masque, true, false ) . '> Retirer cette caserne des cartes (caserne fermée ou doublon)</label></td></tr></table>';
	$lat = (string) \get_post_meta( $post->ID, 'r18_c_lat', true );
	$lng = (string) \get_post_meta( $post->ID, 'r18_c_lng', true );
	if ( '' !== $lat && '' !== $lng ) {
		echo '<p><a href="' . \esc_url( 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=17/' . $lat . '/' . $lng ) . '" target="_blank" rel="noopener">Vérifier la position sur OpenStreetMap</a></p>';
	}
	$err = (string) \get_post_meta( $post->ID, 'r18_c_geo_erreur', true );
	if ( '' !== $err ) {
		echo '<div class="notice notice-warning inline"><p>' . \esc_html( $err ) . '</p></div>';
	}
}

/**
 * Position d'une adresse française (Base adresse nationale).
 *
 * @param string $adresse Adresse complète.
 * @return array{0:float,1:float}|null
 */
function geocoder( string $adresse ): ?array {
	$r = \wp_safe_remote_get(
		'https://api-adresse.data.gouv.fr/search/?limit=1&q=' . \rawurlencode( $adresse ),
		array(
			'timeout'    => 8,
			'user-agent' => 'Rescue18 (rescue18.com)',
		)
	);
	if ( \is_wp_error( $r ) || 200 !== \wp_remote_retrieve_response_code( $r ) ) {
		return null;
	}
	$d = \json_decode( (string) \wp_remote_retrieve_body( $r ), true );
	$f = $d['features'][0] ?? null;
	if ( ! \is_array( $f ) || (float) ( $f['properties']['score'] ?? 0 ) < 0.5 ) {
		return null;
	}
	return array( \round( (float) $f['geometry']['coordinates'][1], 6 ), \round( (float) $f['geometry']['coordinates'][0], 6 ) );
}

\add_action(
	'save_post_' . CASERNE,
	static function ( int $id ): void {
		if ( ! isset( $_POST['r18_caserne_nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['r18_caserne_nonce'] ), 'r18_caserne' ) ) {
			return;
		}
		if ( ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! \current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$avant = array();
		foreach ( \array_keys( champs_caserne() ) as $cle ) {
			$avant[ $cle ] = (string) \get_post_meta( $id, $cle, true );
			$v             = isset( $_POST[ $cle ] ) ? \sanitize_text_field( \wp_unslash( $_POST[ $cle ] ) ) : '';
			if ( \in_array( $cle, array( 'r18_c_lat', 'r18_c_lng' ), true ) ) {
				$v = \is_numeric( \str_replace( ',', '.', $v ) ) ? (string) \round( (float) \str_replace( ',', '.', $v ), 6 ) : '';
			}
			if ( 'r18_c_pays' === $cle ) {
				$v = \strtoupper( \substr( (string) \preg_replace( '/[^a-z]/i', '', $v ), 0, 2 ) );
				$v = '' !== $v ? $v : 'FR';
			}
			if ( 'r18_c_osm' === $cle ) {
				$v = (string) \preg_replace( '/[^nwr0-9]/', '', $v );
			}
			\update_post_meta( $id, $cle, $v );
		}
		\update_post_meta( $id, 'r18_c_masque', isset( $_POST['r18_c_masque'] ) ? '1' : '' );
		\delete_post_meta( $id, 'r18_c_geo_erreur' );

		// Adresse française modifiée, position non retouchée à la main : recalcul.
		$adr     = \trim( \implode( ' ', array( (string) \get_post_meta( $id, 'r18_c_adresse', true ), (string) \get_post_meta( $id, 'r18_c_cp', true ), (string) \get_post_meta( $id, 'r18_c_commune', true ) ) ) );
		$change  = $adr !== \trim( $avant['r18_c_adresse'] . ' ' . $avant['r18_c_cp'] . ' ' . $avant['r18_c_commune'] );
		$manuel  = (string) \get_post_meta( $id, 'r18_c_lat', true ) !== $avant['r18_c_lat'] || (string) \get_post_meta( $id, 'r18_c_lng', true ) !== $avant['r18_c_lng'];
		$sans    = '' === (string) \get_post_meta( $id, 'r18_c_lat', true );
		if ( 'FR' === \get_post_meta( $id, 'r18_c_pays', true ) && '' !== $adr && ( ( $change && ! $manuel ) || $sans ) ) {
			$pos = geocoder( $adr );
			if ( $pos ) {
				\update_post_meta( $id, 'r18_c_lat', (string) $pos[0] );
				\update_post_meta( $id, 'r18_c_lng', (string) $pos[1] );
			} else {
				\update_post_meta( $id, 'r18_c_geo_erreur', 'Adresse introuvable dans la Base adresse nationale : saisis la latitude et la longitude à la main.' );
			}
		}
		construire_cartes();
	},
	20
);
\add_action(
	'trashed_post',
	static function ( int $id ): void {
		if ( CASERNE === \get_post_type( $id ) ) {
			construire_cartes();
		}
	}
);

\add_filter(
	'manage_' . CASERNE . '_posts_columns',
	static function ( array $c ): array {
		return array(
			'cb'        => $c['cb'] ?? '',
			'title'     => 'Caserne',
			'r18_lieu'  => 'Commune',
			'r18_svc'   => 'Service',
			'r18_type'  => 'Correction',
			'date'      => $c['date'] ?? 'Date',
		);
	}
);
\add_action(
	'manage_' . CASERNE . '_posts_custom_column',
	static function ( string $col, int $id ): void {
		if ( 'r18_lieu' === $col ) {
			echo \esc_html( \trim( \get_post_meta( $id, 'r18_c_commune', true ) . ' (' . \get_post_meta( $id, 'r18_c_pays', true ) . ')' ) );
		} elseif ( 'r18_svc' === $col ) {
			echo \esc_html( (string) \get_post_meta( $id, 'r18_c_exploitant', true ) );
		} elseif ( 'r18_type' === $col ) {
			$m = '1' === \get_post_meta( $id, 'r18_c_masque', true );
			echo \esc_html( $m ? 'Retirée des cartes' : ( '' !== \get_post_meta( $id, 'r18_c_osm', true ) ? 'Corrige OpenStreetMap' : 'Ajoutée' ) );
		}
	},
	10,
	2
);

/**
 * Corrections publiées, indexées par identifiant OSM ou par fiche.
 *
 * @return array{0:array<string,array<int,mixed>|null>,1:array<int,array<int,mixed>>} Corrections OSM (null : masquée), ajouts.
 */
function corrections(): array {
	$par_osm = array();
	$ajouts  = array();
	foreach ( \get_posts( array( 'post_type' => CASERNE, 'post_status' => 'publish', 'posts_per_page' => 5000, 'fields' => 'ids' ) ) as $id ) {
		$osm  = (string) \get_post_meta( $id, 'r18_c_osm', true );
		$lat  = (string) \get_post_meta( $id, 'r18_c_lat', true );
		$lng  = (string) \get_post_meta( $id, 'r18_c_lng', true );
		$cach = '1' === \get_post_meta( $id, 'r18_c_masque', true );
		$row  = ( '' === $lat || '' === $lng || $cach ) ? null : array(
			(float) $lat,
			(float) $lng,
			\get_the_title( $id ),
			'' !== $osm ? $osm : 'c' . $id,
			(string) \get_post_meta( $id, 'r18_c_exploitant', true ),
			$id,
			\implode( ', ', \array_filter( array( (string) \get_post_meta( $id, 'r18_c_adresse', true ), \trim( \get_post_meta( $id, 'r18_c_cp', true ) . ' ' . \get_post_meta( $id, 'r18_c_commune', true ) ) ) ) ),
			(string) \get_post_meta( $id, 'r18_c_pays', true ),
		);
		if ( '' !== $osm ) {
			$par_osm[ $osm ] = $row;
		} elseif ( $row ) {
			$ajouts[] = $row;
		}
	}
	return array( $par_osm, $ajouts );
}

/**
 * Applique les corrections à une liste OSM.
 *
 * @param array<int,array<int,mixed>>          $pts     Points OSM.
 * @param array<string,array<int,mixed>|null>  $par_osm Corrections.
 * @return array<int,array<int,mixed>>
 */
function appliquer( array $pts, array $par_osm ): array {
	$out = array();
	foreach ( $pts as $p ) {
		$id = (string) ( $p[3] ?? '' );
		if ( '' !== $id && \array_key_exists( $id, $par_osm ) ) {
			if ( null !== $par_osm[ $id ] ) {
				$out[] = \array_slice( $par_osm[ $id ], 0, 7 );
			}
			continue;
		}
		$out[] = array( $p[0], $p[1], (string) ( $p[2] ?? '' ), $id, (string) ( $p[4] ?? '' ), 0, (string) ( $p[6] ?? '' ) );
	}
	return $out;
}

/**
 * Carreau du monde qui contient un point.
 *
 * @param float $lat Latitude.
 * @param float $lng Longitude.
 * @return string
 */
function carreau( float $lat, float $lng ): string {
	$la = (int) ( \floor( ( \max( -60, \min( 89.99, $lat ) ) + 60 ) / 15 ) * 15 );
	$lo = (int) ( \floor( ( \max( -180, \min( 179.99, $lng ) ) + 180 ) / 30 ) * 30 );
	return 'g' . $la . '_' . $lo;
}

/**
 * Reconstruit les fichiers des cartes depuis les données OSM et les corrections.
 */
function construire_cartes(): void {
	[ $par_osm, $ajouts ] = corrections();
	[ $osm ]              = dossier_cartes( 'osm' );
	[ $dir ]              = dossier_cartes();

	// France.
	$fr = lire_json( $osm . '/france.json' );
	if ( $fr ) {
		$pts = appliquer( $fr, $par_osm );
		foreach ( $ajouts as $a ) {
			if ( 'FR' === $a[7] ) {
				$pts[] = \array_slice( $a, 0, 7 );
			}
		}
		if ( ecrire_json( $dir . '/casernes.json', $pts ) ) {
			\update_option( 'r18_casernes', array( 'n' => \count( $pts ), 't' => \time() ), false );
		}
	}

	// Monde, carreau par carreau.
	$fichiers = (array) \glob( $osm . '/monde-*.json' );
	if ( ! $fichiers ) {
		return;
	}
	[ $mdir ] = dossier_cartes( 'monde' );
	$plus     = array();
	foreach ( $ajouts as $a ) {
		$plus[ carreau( (float) $a[0], (float) $a[1] ) ][] = \array_slice( $a, 0, 7 );
	}
	// Un carreau découpé en quarts (monde-gX_Y__q0.json…) est regroupé sous son carreau.
	$groupes = array();
	foreach ( $fichiers as $f ) {
		$z               = \explode( '__', \substr( \basename( (string) $f, '.json' ), 6 ) )[0];
		$groupes[ $z ][] = (string) $f;
	}
	$index = array();
	$total = 0;
	foreach ( $groupes as $z => $liste ) {
		$brut = array();
		foreach ( $liste as $f ) {
			foreach ( lire_json( $f ) as $p ) {
				$brut[ (string) ( $p[3] ?? $p[0] . ',' . $p[1] ) ] = $p;
			}
		}
		$pts = \array_merge( appliquer( \array_values( $brut ), $par_osm ), $plus[ $z ] ?? array() );
		ecrire_json( $mdir . '/' . $z . '.json', $pts );
		if ( $pts ) {
			$la = 0.0;
			$lo = 0.0;
			foreach ( $pts as $p ) {
				$la += (float) $p[0];
				$lo += (float) $p[1];
			}
			$index[ $z ] = array( \count( $pts ), \round( $la / \count( $pts ), 3 ), \round( $lo / \count( $pts ), 3 ) );
			$total      += \count( $pts );
		}
	}
	$t = \time();
	ecrire_json(
		$mdir . '/index.json',
		array(
			't'     => $t,
			'total' => $total,
			'zones' => $index,
		)
	);
	\update_option( 'r18_casernes_monde', array( 'n' => $total, 't' => $t ), false );
}

/**
 * Gestionnaire : un carreau du monde reçu.
 *
 * @param array<string,string> $r Élément.
 * @param Contexte             $c Contexte.
 * @return array{action:string,id:int,note:string}
 */
function g_casernes_monde( array $r, Contexte $c ): array {
	if ( $c->simulation ) {
		return res( 'identique', 0, 'Simulation : fichier non écrit.' );
	}
	[ $osm ] = dossier_cartes( 'osm' );
	$pts     = (array) \json_decode( $r['donnees'], true );
	if ( ecrire_json( $osm . '/monde-' . \preg_replace( '/[^a-z0-9_]/', '', (string) $r['zone'] ) . '.json', $pts ) ) {
		$etat               = (array) \get_option( 'r18_monde_zones', array() );
		$etat[ $r['zone'] ] = array(
			't' => \time(),
			'e' => 0,
		);
		\update_option( 'r18_monde_zones', $etat, false );
	}
	\update_option( 'r18_monde_a_construire', 1, false );
	// Carte reconstruite tous les 20 carreaux et en fin de cycle (voir plus bas).
	if ( (int) $r['rang'] % 20 !== 19 && (int) $r['rang'] < (int) $r['total'] - 1 ) {
		return res( 'identique', 0, '' );
	}
	construire_cartes();
	\delete_option( 'r18_monde_a_construire' );
	$n = (int) ( ( (array) \get_option( 'r18_casernes_monde', array() ) )['n'] ?? 0 );
	return res( 'version', 0, $n . ' casernes sur la carte du monde.' );
}

\add_filter(
	'r18_veille_gestionnaires',
	static function ( array $g ): array {
		return $g + array( 'casernes_monde' => __NAMESPACE__ . '\\g_casernes_monde' );
	}
);
\add_filter(
	'r18_veille_connecteurs',
	static function ( array $c ): array {
		return $c + array( 'casernes_monde' => new Connecteur_Casernes_Monde() );
	},
	20
);

/* Fin de cycle : un dernier carreau en échec ne bloque pas la carte du monde. */
\add_action(
	'r18_veille_cycle_fini',
	static function (): void {
		if ( \get_option( 'r18_monde_a_construire' ) ) {
			construire_cartes();
			\delete_option( 'r18_monde_a_construire' );
		}
	}
);
