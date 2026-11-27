<?php
/**
 * Annuaire des services d'incendie et de secours : connecteurs automatiques.
 *
 * Sources vérifiées le 30 octobre 2026 :
 * - Annuaire de l'administration (DILA, licence ouverte), API Explore :
 *   98 fiches « sdis » (96 SDIS, SIS du Bas-Rhin, SDMIS, BSPP), site,
 *   réseaux sociaux, téléphone, adresse et coordonnées GPS du siège.
 *   Le BMPM n'y figure pas : fiche ajoutée par le thème.
 * - OpenStreetMap (ODbL) par l'API Overpass : casernes (amenity=fire_station).
 *   Une requête par mois.
 * - Sites des SDIS : flux RSS déclarés, /feed/ WordPress ou API wp-json,
 *   dans le respect de leur robots.txt.
 * - Choisir le Service Public, jeu de données hebdomadaire sur data.gouv.fr
 *   (licence ouverte) : CSV « ; » de 130 Mo lu par tranches HTTP Range.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annuaire;

use G2RD_Rescue18\Veille\Connecteur;
use G2RD_Rescue18\Veille\Erreur_Acces;
use G2RD_Rescue18\Veille\Http;
use function G2RD_Rescue18\Veille\absolue;
use function G2RD_Rescue18\Veille\dom;
use function G2RD_Rescue18\Veille\plat;
use function G2RD_Rescue18\Veille\texte;

const API_ANNUAIRE = 'https://api-lannuaire.service-public.fr/api/explore/v2.1/catalog/datasets/api-lannuaire-administration/records';
const DATASET_CSP  = '6322e99e12175f7eb26ff465';

/* -------------------------------------------------------------------------
 * Petit état persistant (options WordPress, mémoire en test)
 * ---------------------------------------------------------------------- */

/**
 * Lecture d'un état.
 *
 * @param string $k Clé.
 * @return array<string,mixed>
 */
function etat_get( string $k ): array {
	if ( \function_exists( 'get_option' ) ) {
		$v = \get_option( $k, array() );
		return \is_array( $v ) ? $v : array();
	}
	return (array) ( $GLOBALS['r18_etat_test'][ $k ] ?? array() );
}

/**
 * Écriture d'un état.
 *
 * @param string              $k Clé.
 * @param array<string,mixed> $v Valeur.
 */
function etat_set( string $k, array $v ): void {
	if ( \function_exists( 'update_option' ) ) {
		\update_option( $k, $v, false );
		return;
	}
	$GLOBALS['r18_etat_test'][ $k ] = $v;
}

/**
 * La dernière exécution réussie date-t-elle de moins de $jours ?
 *
 * @param string $id    Connecteur.
 * @param int    $jours Fréquence.
 * @return bool
 */
function recent( string $id, int $jours ): bool {
	$e = etat_get( 'r18_veille_etat' );
	$v = (int) ( $e[ $id ]['verif'] ?? 0 );
	$r = (string) ( $e[ $id ]['resultat'] ?? '' );
	return $v > 0 && \in_array( $r, array( 'ok', 'partiel' ), true ) && ( \time() - $v ) < $jours * 86400 - 3600;
}

/* -------------------------------------------------------------------------
 * Annuaire DILA
 * ---------------------------------------------------------------------- */

/**
 * Décode un champ JSON de l'API (liste encodée en chaîne).
 *
 * @param mixed $v Valeur.
 * @return array<int,array<string,mixed>>
 */
function liste( $v ): array {
	if ( \is_array( $v ) ) {
		return $v;
	}
	$d = \json_decode( (string) $v, true );
	return \is_array( $d ) ? $d : array();
}

/**
 * Fiches de service depuis la réponse de l'API Annuaire.
 *
 * @param string $json Réponse.
 * @return array<int,array<string,string>>
 */
function analyser_annuaire( string $json ): array {
	$d = \json_decode( $json, true );
	if ( ! \is_array( $d ) || ! isset( $d['results'] ) ) {
		throw new Erreur_Acces( 'structure', 'Réponse de l\'API Annuaire inattendue.' );
	}
	$out = array();
	foreach ( (array) $d['results'] as $r ) {
		$nom   = (string) ( $r['nom'] ?? '' );
		$insee = (string) ( $r['code_insee_commune'] ?? '' );
		if ( '' === $insee ) {
			$piv   = liste( $r['pivot'] ?? '' );
			$insee = (string) ( $piv[0]['code_insee_commune'][0] ?? '' );
		}
		$bspp = \str_contains( plat( $nom ), 'brigade des sapeurs-pompiers de paris' );
		$dep  = $bspp ? '75' : dep_depuis_insee( $insee );
		$deps = $bspp ? '75,92,93,94' : $dep;
		$adr  = array();
		foreach ( liste( $r['adresse'] ?? '' ) as $a ) {
			if ( 'Adresse' === ( $a['type_adresse'] ?? '' ) || empty( $adr ) ) {
				$adr = $a;
			}
		}
		$tel = '';
		foreach ( liste( $r['telephone'] ?? '' ) as $t ) {
			$v = \trim( (string) ( $t['valeur'] ?? '' ) );
			if ( '' !== $v && '18' !== $v && '112' !== $v ) {
				$tel = $v;
				break;
			}
		}
		$reseaux = array();
		foreach ( liste( $r['reseau_social'] ?? '' ) as $s ) {
			$u = (string) ( $s['valeur'] ?? '' );
			if ( \preg_match( '#^https://#', $u ) && ! \str_contains( $u, '/accounts/login' ) ) {
				$reseaux[] = array( 'reseau' => (string) ( $s['custom_dico2'] ?? 'Réseau' ), 'url' => $u );
			}
		}
		$site  = (string) ( liste( $r['site_internet'] ?? '' )[0]['valeur'] ?? '' );
		$court = \trim( (string) \preg_replace( '/^.*?\)\s*-\s*/u', '', $nom ) );
		if ( $bspp ) {
			$type  = 'bspp';
			$titre = 'BSPP · Brigade de sapeurs-pompiers de Paris';
			$slug  = 'bspp';
		} else {
			$type  = \str_contains( plat( $nom ), 'metropole de lyon' ) ? 'sdmis' : 'sdis';
			$sigle = 'sdmis' === $type ? 'SDMIS' : ( \str_starts_with( plat( $nom ), 'service d\'incendie et de secours (sis)' ) ? 'SIS' : 'SDIS' );
			$titre = $sigle . ' ' . $dep . ' · ' . ( 'sdmis' === $type ? 'Rhône et Métropole de Lyon' : nom_dep( $dep ) );
			$slug  = \strtolower( (string) \preg_replace( '/[^a-z0-9]+/i', '-', plat( nom_dep( $dep ) ) ) ) . '-' . \strtolower( $dep );
		}
		if ( '' === $dep || ! isset( departements()[ $dep ] ) ) {
			continue;
		}
		$out[] = array(
			'source'    => 'annuaire',
			'ref'       => (string) ( $r['id'] ?? $insee ),
			'type'      => 'service',
			'titre'     => $titre,
			'slug'      => $slug,
			'genre'     => $type,
			'nom'       => $nom,
			'court'     => $court,
			'deps'      => $deps,
			'region'    => region_dep( $dep ),
			'site'      => $site,
			'tel'       => $tel,
			'email'     => (string) ( $r['adresse_courriel'] ?? '' ),
			'adresse'   => \trim( (string) ( $adr['numero_voie'] ?? '' ) ),
			'cp'        => (string) ( $adr['code_postal'] ?? '' ),
			'ville'     => (string) ( $adr['nom_commune'] ?? '' ),
			'lat'       => (string) ( $adr['latitude'] ?? '' ),
			'lng'       => (string) ( $adr['longitude'] ?? '' ),
			'reseaux'   => (string) \json_encode( $reseaux, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'fiche'     => (string) ( $r['url_service_public'] ?? '' ),
			'contact'   => (string) ( $r['formulaire_contact'] ?? '' ),
			'signal'    => (string) ( $r['date_modification'] ?? '' ),
			'url'       => (string) ( $r['url_service_public'] ?? '' ),
			'date'      => '',
			'resume'    => '',
		);
	}
	return $out;
}

/**
 * Fiche du Bataillon de marins-pompiers de Marseille, absent de l'annuaire
 * de l'administration (unité de la Marine nationale). Modifiable dans l'admin.
 *
 * @return array<string,string>
 */
function fiche_bmpm(): array {
	return array(
		'source'  => 'annuaire',
		'ref'     => 'bmpm',
		'type'    => 'service',
		'titre'   => 'BMPM · Bataillon de marins-pompiers de Marseille',
		'slug'    => 'bmpm',
		'genre'   => 'bmpm',
		'nom'     => 'Bataillon de marins-pompiers de Marseille (BMPM)',
		'court'   => 'Marseille',
		'deps'    => '13',
		'region'  => region_dep( '13' ),
		'site'    => 'https://www.defense.gouv.fr/marine',
		'tel'     => '',
		'email'   => '',
		'adresse' => '9 boulevard de Strasbourg',
		'cp'      => '13003',
		'ville'   => 'Marseille',
		'lat'     => '43.3113',
		'lng'     => '5.3796',
		'reseaux' => '[]',
		'fiche'   => '',
		'contact' => '',
		'signal'  => 'bmpm-1',
		'url'     => 'https://www.lamarinerecrute.fr/',
		'date'    => '',
		'resume'  => '',
	);
}

/**
 * Connecteur Annuaire (hebdomadaire).
 */
class Connecteur_Annuaire implements Connecteur {

	public function id(): string {
		return 'annuaire';
	}

	public function nom(): string {
		return 'Annuaire · SDIS, BSPP, BMPM';
	}

	public function organisme(): string {
		return '';
	}

	public function url(): string {
		return 'https://lannuaire.service-public.gouv.fr/';
	}

	public function methode(): string {
		return 'API ouverte de l\'Annuaire de l\'administration (DILA), chaque semaine';
	}

	public function domaines(): array {
		return array( 'api-lannuaire.service-public.fr' );
	}

	public function pause(): float {
		return 1.0;
	}

	public function plafond(): int {
		return 3;
	}

	public function unites( array $reglages ): array {
		if ( empty( $reglages['force'] ) && recent( 'annuaire', 7 ) ) {
			return array();
		}
		return array(
			array(
				'url'  => API_ANNUAIRE . '?limit=100&where=' . \rawurlencode( 'pivot like "sdis"' ),
				'type' => 'service',
			),
		);
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$r     = $http->get( $unite['url'], array( 'application/json' ), false, 4194304 );
		$items = analyser_annuaire( $r->corps );
		if ( \count( $items ) < 90 ) {
			throw new Erreur_Acces( 'structure', 'Seulement ' . \count( $items ) . ' services lus (98 attendus) : import suspendu, aucune fiche modifiée.' );
		}
		$items[] = fiche_bmpm();
		return array(
			'items'   => $items,
			'notes'   => array( \count( $items ) . ' services lus (dont le BMPM ajouté par le thème).' ),
			'methode' => 'api',
		);
	}
}

/* -------------------------------------------------------------------------
 * Casernes OpenStreetMap
 * ---------------------------------------------------------------------- */

/**
 * Casernes depuis une réponse Overpass : [ lat, lng, nom ].
 *
 * @param string $json Réponse.
 * @return array<int,array{0:float,1:float,2:string}>
 */
function analyser_casernes( string $json ): array {
	$d = \json_decode( $json, true );
	if ( ! \is_array( $d ) || ! isset( $d['elements'] ) ) {
		throw new Erreur_Acces( 'structure', 'Réponse Overpass inattendue.' );
	}
	$out = array();
	foreach ( (array) $d['elements'] as $e ) {
		$lat = $e['lat'] ?? ( $e['center']['lat'] ?? null );
		$lng = $e['lon'] ?? ( $e['center']['lon'] ?? null );
		if ( null === $lat || null === $lng ) {
			continue;
		}
		$t     = (array) ( $e['tags'] ?? array() );
		$nom   = (string) ( $t['name'] ?? $t['official_name'] ?? '' );
		$out[] = array( \round( (float) $lat, 5 ), \round( (float) $lng, 5 ), \mb_substr( $nom, 0, 80 ) );
	}
	return $out;
}

/**
 * Lit une réponse Overpass au format CSV (tabulations) : type, id, lat, lon,
 * name, operator, puis l'adresse (numéro, rue, code postal, ville).
 * Les chemins et relations arrivent avec leur centre.
 *
 * @param string $csv Corps de la réponse.
 * @return array<int,array<int,mixed>> lat, lng, nom, id OSM, exploitant, 0, adresse.
 */
function analyser_casernes_csv( string $csv ): array {
	$out = array();
	foreach ( \preg_split( '/\R/', $csv ) as $ligne ) {
		if ( '' === \trim( $ligne ) ) {
			continue;
		}
		$c = \explode( "\t", $ligne );
		if ( \count( $c ) < 4 || ! \is_numeric( $c[2] ) || ! \is_numeric( $c[3] ) ) {
			continue;
		}
		$type  = array( 'node' => 'n', 'way' => 'w', 'relation' => 'r' )[ $c[0] ] ?? '';
		if ( '' === $type || ! \ctype_digit( $c[1] ) ) {
			continue;
		}
		$out[] = array(
			\round( (float) $c[2], 5 ),
			\round( (float) $c[3], 5 ),
			\mb_substr( \trim( (string) ( $c[4] ?? '' ) ), 0, 70, 'UTF-8' ),
			$type . $c[1],
			\mb_substr( \trim( (string) ( $c[5] ?? '' ) ), 0, 70, 'UTF-8' ),
			0,
			\mb_substr( adresse_osm( $c ), 0, 120, 'UTF-8' ),
		);
	}
	if ( ! $out && '' !== \trim( $csv ) && \str_contains( $csv, 'runtime error' ) ) {
		throw new Erreur_Acces( 'structure', 'Overpass a interrompu la requête (délai ou mémoire).' );
	}
	return $out;
}

/**
 * Adresse lisible depuis les colonnes CSV : « 12 rue X, 27600 Gaillon ».
 *
 * @param string[] $c Colonnes.
 * @return string
 */
function adresse_osm( array $c ): string {
	$rue   = \trim( \trim( (string) ( $c[6] ?? '' ) ) . ' ' . \trim( (string) ( $c[7] ?? '' ) ) );
	$ville = \trim( \trim( (string) ( $c[8] ?? '' ) ) . ' ' . \trim( (string) ( $c[9] ?? '' ) ) );
	return \implode( ', ', \array_filter( array( $rue, $ville ) ) );
}

/**
 * Colonnes CSV demandées à Overpass.
 */
const CHAMPS_CASERNES = '::type,::id,::lat,::lon,name,operator,"addr:housenumber","addr:street","addr:postcode","addr:city"';

/**
 * Requête Overpass CSV des casernes dans un rectangle.
 *
 * @param float $s Sud.
 * @param float $w Ouest.
 * @param float $n Nord.
 * @param float $e Est.
 * @return string URL.
 */
function requete_casernes( float $s, float $w, float $n, float $e, bool $france = false ): string {
	// Carte de France : seulement les casernes situées dans le territoire français
	// (métropole et outre-mer), pas celles des pays voisins pris dans le carreau.
	$zone = $france ? 'area["ISO3166-1"="FR"]["admin_level"="2"]->.fr;' : '';
	$q    = '[out:csv(' . CHAMPS_CASERNES . ';false;"\t")][timeout:120];' . $zone . 'nwr["amenity"="fire_station"](' . $s . ',' . $w . ',' . $n . ',' . $e . ')' . ( $france ? '(area.fr)' : '' ) . ';out center;out count;';
	return 'https://overpass-api.de/api/interpreter?data=' . \rawurlencode( $q );
}

/**
 * Connecteur Casernes (mensuel).
 */
class Connecteur_Casernes implements Connecteur {

	public function id(): string {
		return 'casernes';
	}

	public function nom(): string {
		return 'Casernes · OpenStreetMap';
	}

	public function organisme(): string {
		return '';
	}

	public function url(): string {
		return 'https://www.openstreetmap.org/';
	}

	public function methode(): string {
		return 'API Overpass (ODbL), 21 zones, une fois par mois';
	}

	public function domaines(): array {
		return array( 'overpass-api.de', 'overpass.kumi.systems', 'overpass.private.coffee', 'maps.mail.ru' );
	}

	public function pause(): float {
		return 5.0;
	}

	public function plafond(): int {
		return 25;
	}

	/**
	 * Zones interrogées une à une (requêtes courtes, serveur public ménagé) :
	 * la métropole en 16 carreaux, puis chaque département d'outre-mer.
	 *
	 * @return array<string,array{0:float,1:float,2:float,3:float}> sud, ouest, nord, est.
	 */
	public static function zones(): array {
		$z = array();
		$lat = array( 41.3, 43.75, 46.2, 48.65, 51.1 );
		$lon = array( -5.2, -1.5, 2.2, 5.9, 9.6 );
		for ( $i = 0; $i < 4; $i++ ) {
			for ( $j = 0; $j < 4; $j++ ) {
				$z[ 'm' . $i . $j ] = array( $lat[ $i ], $lon[ $j ], $lat[ $i + 1 ], $lon[ $j + 1 ] );
			}
		}
		return $z + array(
			'971' => array( 15.8, -61.85, 16.55, -61.0 ),
			'972' => array( 14.38, -61.25, 14.9, -60.8 ),
			'973' => array( 2.1, -54.6, 5.8, -51.6 ),
			'974' => array( -21.4, 55.2, -20.85, 55.85 ),
			'976' => array( -13.05, 45.0, -12.6, 45.3 ),
		);
	}

	public function unites( array $reglages ): array {
		// Seules les zones absentes, anciennes ou d'un ancien format sont relues.
		$etat  = \function_exists( 'get_option' ) ? (array) \get_option( 'r18_france_zones', array() ) : array();
		$zones = \array_filter(
			self::zones(),
			static fn( string $k ): bool => ( (int) ( $etat[ $k ]['v'] ?? 0 ) < 3 ) || ( \time() - (int) ( $etat[ $k ]['t'] ?? 0 ) ) > 25 * 86400,
			ARRAY_FILTER_USE_KEY
		);
		if ( ! $zones || ( empty( $reglages['force'] ) && recent( 'casernes', 1 ) ) ) {
			return array();
		}
		$out   = array();
		$n     = \count( $zones );
		$i     = 0;
		foreach ( $zones as $k => [ $s, $w, $no, $e ] ) {
			$out[] = array(
				'url'   => requete_casernes( $s, $w, $no, $e, true ),
				'type'  => 'casernes',
				'zone'  => (string) $k,
				'rang'  => (string) $i++,
				'total' => (string) $n,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		// Un essai long (requêtes par territoire). Le miroir ne sert qu'après un refus rapide
		// du serveur principal (502, 503, 504, 429), jamais après un délai dépassé.
		$http->delai = 100;
		// Unités mises en file avant l'ajout des adresses : colonnes complétées.
		$unite['url'] = \str_replace( \rawurlencode( '::type,::id,::lat,::lon,name,operator;' ), \rawurlencode( CHAMPS_CASERNES . ';' ), $unite['url'] );
		// Serveur principal surchargé (502, 504) : même requête sur un miroir public d'Overpass.
		$r = null;
		// Miroirs publics d'Overpass, essayés à tour de rôle après un refus rapide.
		$miroirs = array( '', 'overpass.kumi.systems', 'overpass.private.coffee', 'maps.mail.ru/osm/tools/overpass' );
		$dernier = \end( $miroirs );
		foreach ( $miroirs as $miroir ) {
			$url = '' === $miroir ? $unite['url'] : \str_replace( 'overpass-api.de', $miroir, $unite['url'] );
			if ( '' !== $miroir ) {
				$http->delai = 60; // Overpass saturé en journée : 30 s coupait des réponses valides.
			}
			try {
				$r = $http->get( $url, array( 'text/csv', 'application/json', 'text/plain' ), false, 41943040 );
				break;
			} catch ( Erreur_Acces $e ) {
				if ( $dernier === $miroir || ! \preg_match( '/HTTP (502|503|504|429)|limitation/i', $e->getMessage() ) ) {
					throw $e;
				}
			}
		}
		// Témoin de fin : Overpass coupé par son délai répond 200 sans la ligne « count ».
		// Sans ce contrôle, une zone dense passait pour vide et n'était plus relue.
		if ( \str_contains( $unite['url'], \rawurlencode( 'out count;' ) ) && ! \preg_match( '/^count\t/m', $r->corps ) ) {
			throw new Erreur_Acces( 'structure', 'Zone ' . $unite['zone'] . ' : réponse coupée avant la fin (délai du serveur), nouvel essai en zones plus petites.' );
		}
		$c = \str_starts_with( \ltrim( $r->corps ), '{' ) ? analyser_casernes( $r->corps ) : analyser_casernes_csv( $r->corps );
		// Une zone terrestre de France sans aucune caserne signale une réponse tronquée.
		if ( ! $c && 'casernes' === $unite['type'] ) {
			throw new Erreur_Acces( 'structure', 'Zone ' . $unite['zone'] . ' vide : réponse incomplète du serveur, version précédente conservée.' );
		}
		// Noms en cyrillique, arabe, chinois : un octet invalide ne doit pas vider tout le carreau.
		$json = \json_encode( $c, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
		if ( false === $json || ( $c && '[]' === $json ) ) {
			throw new Erreur_Acces( 'structure', 'Zone ' . $unite['zone'] . ' : données illisibles, version précédente conservée.' );
		}
		return array(
			'items'   => array(
				array(
					'source'  => 'casernes',
					'ref'     => 'osm-' . $unite['zone'],
					'type'    => 'casernes',
					'titre'   => 'Zone ' . $unite['zone'] . ' : ' . \count( $c ) . ' casernes',
					'zone'    => (string) $unite['zone'],
					'rang'    => (string) $unite['rang'],
					'total'   => (string) $unite['total'],
					'donnees' => $json,
				),
			),
			'notes'   => array( 'Zone ' . $unite['zone'] . ' : ' . \count( $c ) . ' casernes.' ),
			'methode' => 'api',
		);
	}
}

/**
 * Connecteur Casernes du monde (mensuel) : la planète en carreaux de
 * 15° de latitude sur 30° de longitude, interrogés un à un. Chaque carreau
 * est conservé à part : un carreau en échec garde sa version précédente.
 */
class Connecteur_Casernes_Monde extends Connecteur_Casernes {

	public function id(): string {
		return 'casernes_monde';
	}

	public function nom(): string {
		return 'Casernes du monde · OpenStreetMap';
	}

	public function methode(): string {
		return 'API Overpass (ODbL), 120 carreaux, une fois par mois';
	}

	public function plafond(): int {
		return 260;
	}

	/**
	 * Carreaux du monde, de -60° à 90° de latitude.
	 *
	 * @return array<string,array{0:float,1:float,2:float,3:float}>
	 */
	public static function zones(): array {
		$z = array();
		for ( $lat = -60; $lat < 90; $lat += 15 ) {
			for ( $lon = -180; $lon < 180; $lon += 30 ) {
				$z[ 'g' . ( $lat + 60 ) . '_' . ( $lon + 180 ) ] = array( (float) $lat, (float) $lon, (float) ( $lat + 15 ), (float) ( $lon + 30 ) );
			}
		}
		return $z;
	}

	/**
	 * Carreaux à relire : jamais reçus ou reçus il y a plus de 25 jours.
	 * Un carreau déjà en échec (zone dense, serveur saturé) est découpé en
	 * quatre quarts, interrogés un par un.
	 *
	 * @param array<string,mixed> $reglages Réglages.
	 * @return array<int,array<string,string>>
	 */
	public function unites( array $reglages ): array {
		$etat  = \function_exists( 'get_option' ) ? (array) \get_option( 'r18_monde_zones', array() ) : array();
		$dir   = \function_exists( 'wp_upload_dir' ) ? \trailingslashit( \wp_upload_dir()['basedir'] ) . 'rescue18/osm/monde-' : '';
		$frais = static function ( string $k ) use ( $etat, $dir ): bool {
			$t = (int) ( $etat[ $k ]['t'] ?? 0 );
			if ( ! $t && '' !== $dir && \is_file( $dir . $k . '.json' ) ) {
				$t = (int) \filemtime( $dir . $k . '.json' );
			}
			return ( \time() - $t ) < 25 * 86400;
		};
		$liste = array();
		// Découpage récursif : un carreau en échec est relu en quarts, un quart en échec en seizièmes…
		$tuiler = static function ( string $k, array $b, int $prof ) use ( &$tuiler, &$liste, $etat, $frais ): bool {
			if ( $frais( $k ) ) {
				return true;
			}
			if ( (int) ( $etat[ $k ]['e'] ?? 0 ) > 0 && $prof < 4 ) {
				[ $s, $w, $no, $e ] = $b;
				$ms   = ( $s + $no ) / 2;
				$mw   = ( $w + $e ) / 2;
				$base = \str_contains( $k, '__q' ) ? $k : $k . '__q';
				$ok   = true;
				for ( $q = 0; $q < 4; $q++ ) {
					$ok = $tuiler( $base . $q, array( $q < 2 ? $s : $ms, 0 === $q % 2 ? $w : $mw, $q < 2 ? $ms : $no, 0 === $q % 2 ? $mw : $e ), $prof + 1 ) && $ok;
				}
				return $ok;
			}
			// Carreau jamais tenté : ses sous-carreaux déjà frais suffisent.
			if ( 0 === $prof || ! isset( $etat[ $k ] ) ) {
				$base = \str_contains( $k, '__q' ) ? $k : $k . '__q';
				$tous = true;
				for ( $q = 0; $q < 4; $q++ ) {
					$tous = $tous && $frais( $base . $q );
				}
				if ( $tous ) {
					return true;
				}
			}
			$liste[ $k ] = $b;
			return false;
		};
		foreach ( self::zones() as $k => $b ) {
			$tuiler( $k, $b, 0 );
		}
		if ( ! $liste ) {
			return array();
		}
		// Zones jamais reçues d'abord (trous de la carte), puis les latitudes
		// les plus peuplées (autour de 40° N), les océans du sud en dernier.
		$recu = static fn( string $k ): bool => '' !== $dir && \is_file( $dir . $k . '.json' );
		\uksort(
			$liste,
			static function ( string $a, string $b ) use ( $liste, $recu ): int {
				$pa = \abs( ( $liste[ $a ][0] + $liste[ $a ][2] ) / 2 - 40 );
				$pb = \abs( ( $liste[ $b ][0] + $liste[ $b ][2] ) / 2 - 40 );
				return array( $recu( $a ), $pa ) <=> array( $recu( $b ), $pb );
			}
		);
		$out = array();
		$n   = \count( $liste );
		$i   = 0;
		foreach ( $liste as $k => [ $s, $w, $no, $e ] ) {
			$out[] = array(
				'url'   => requete_casernes( $s, $w, $no, $e ),
				'type'  => 'casernes_monde',
				'zone'  => (string) $k,
				'rang'  => (string) $i++,
				'total' => (string) $n,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		try {
			$r = parent::traiter( $unite, $http, $reglages, $connu );
		} catch ( Erreur_Acces $e ) {
			// Échec mémorisé : au prochain passage, le carreau est découpé en quarts.
			if ( \function_exists( 'update_option' ) ) {
				$etat = (array) \get_option( 'r18_monde_zones', array() );
				$etat[ $unite['zone'] ]['e'] = (int) ( $etat[ $unite['zone'] ]['e'] ?? 0 ) + 1;
				\update_option( 'r18_monde_zones', $etat, false );
			}
			throw $e;
		}
		$r['items'][0]['source'] = 'casernes_monde';
		$r['items'][0]['type']   = 'casernes_monde';
		return $r;
	}
}

/* -------------------------------------------------------------------------
 * Actualités des SDIS
 * ---------------------------------------------------------------------- */

/**
 * Le chemin est-il autorisé par ce robots.txt pour notre robot ?
 *
 * @param string $robots Contenu du robots.txt.
 * @param string $chemin Chemin demandé.
 * @return bool
 */
function robots_autorise( string $robots, string $chemin ): bool {
	$groupes = array();
	$agents  = array();
	$regles  = array();
	$dernier = '';
	foreach ( \preg_split( '/\R/', $robots ) as $ligne ) {
		$ligne = \trim( (string) \preg_replace( '/#.*$/', '', $ligne ) );
		if ( ! \preg_match( '/^([a-z-]+)\s*:\s*(.*)$/i', $ligne, $m ) ) {
			continue;
		}
		$cle = \strtolower( $m[1] );
		$val = \trim( $m[2] );
		if ( 'user-agent' === $cle ) {
			if ( 'agent' !== $dernier && ! empty( $agents ) ) {
				$groupes[] = array( $agents, $regles );
				$agents    = array();
				$regles    = array();
			}
			$agents[] = \strtolower( $val );
			$dernier  = 'agent';
		} elseif ( 'allow' === $cle || 'disallow' === $cle ) {
			$regles[] = array( $cle, $val );
			$dernier  = 'regle';
		}
	}
	if ( ! empty( $agents ) ) {
		$groupes[] = array( $agents, $regles );
	}
	$choisi = null;
	foreach ( $groupes as [ $ag, $rg ] ) {
		foreach ( $ag as $a ) {
			if ( \str_contains( 'rescue18-veille', $a ) && '*' !== $a ) {
				$choisi = $rg;
				break 2;
			}
		}
	}
	if ( null === $choisi ) {
		$choisi = array();
		foreach ( $groupes as [ $ag, $rg ] ) {
			if ( \in_array( '*', $ag, true ) ) {
				$choisi = \array_merge( $choisi, $rg );
			}
		}
	}
	$meilleur = -1;
	$permis   = true;
	foreach ( $choisi as [ $type, $motif ] ) {
		if ( '' === $motif ) {
			continue;
		}
		$re = '#^' . \str_replace( array( '\*', '\$' ), array( '.*', '$' ), \preg_quote( $motif, '#' ) ) . '#';
		if ( \preg_match( $re, $chemin ) && \strlen( $motif ) > $meilleur ) {
			$meilleur = \strlen( $motif );
			$permis   = 'allow' === $type;
		}
	}
	return $permis;
}

/**
 * Flux déclaré dans le HTML d'une page d'accueil (hors flux des commentaires).
 *
 * @param string $html HTML.
 * @param string $base URL de la page.
 * @return string
 */
function flux_declare( string $html, string $base ): string {
	$x = dom( $html );
	foreach ( $x->query( "//link[@rel='alternate'][contains(@type,'rss') or contains(@type,'atom')]" ) as $l ) {
		/** @var \DOMElement $l */
		$h = absolue( $l->getAttribute( 'href' ), $base );
		if ( '' !== $h && ! \preg_match( '#comments/feed|/comments/|commentaires#i', $h ) ) {
			return $h;
		}
	}
	return '';
}

/**
 * URL d'image publique exploitable (HTTPS, extension ou chemin plausible).
 *
 * @param string $u    URL brute.
 * @param string $base Page de référence.
 * @return string
 */
function image_valide( string $u, string $base = '' ): string {
	$u = \trim( \html_entity_decode( $u, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $u || \str_starts_with( $u, 'data:' ) ) {
		return '';
	}
	if ( '' !== $base ) {
		$u = absolue( $u, $base );
	}
	$u = (string) \preg_replace( '#^http://#i', 'https://', $u );
	if ( ! \preg_match( '#^https://[^\s"<>]+$#', $u ) || \preg_match( '#gravatar|pixel|spacer|1x1|feeds\.feedburner|/emoji/|\.svg(\?|$)#i', $u ) ) {
		return '';
	}
	return \substr( $u, 0, 600 );
}

/**
 * Visuels d'une page d'accueil : image de partage (og:image) et logo.
 *
 * @param string $html HTML.
 * @param string $base URL de la page.
 * @return array{image:string,logo:string}
 */
function visuels_page( string $html, string $base ): array {
	$x     = dom( $html );
	$image = '';
	foreach ( array( "//meta[@property='og:image']/@content", "//meta[@name='twitter:image']/@content", "//meta[@property='og:image:url']/@content" ) as $q ) {
		$n = $x->query( $q )->item( 0 );
		if ( $n && '' !== ( $image = image_valide( (string) $n->nodeValue, $base ) ) ) {
			break;
		}
	}
	$logo = '';
	foreach ( array(
		"//*[contains(concat(' ',normalize-space(@class),' '),' custom-logo ')]/@src",
		"//header//img[contains(translate(@class,'LOGO','logo'),'logo') or contains(translate(@alt,'LOGO','logo'),'logo') or contains(translate(@src,'LOGO','logo'),'logo')]/@src",
		"//img[contains(translate(@class,'LOGO','logo'),'logo')]/@src",
		"//link[@rel='apple-touch-icon']/@href",
		"//link[contains(@rel,'icon')][@sizes='192x192' or @sizes='180x180']/@href",
	) as $q ) {
		$n = $x->query( $q )->item( 0 );
		if ( $n && '' !== ( $logo = image_valide( (string) $n->nodeValue, $base ) ) ) {
			break;
		}
	}
	return array( 'image' => $image, 'logo' => $logo );
}

/**
 * Première image d'un fragment HTML.
 *
 * @param string $html Fragment.
 * @param string $base Base.
 * @return string
 */
function premiere_image( string $html, string $base = '' ): string {
	if ( \preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\']#i', $html, $m ) ) {
		foreach ( $m[1] as $u ) {
			if ( '' !== ( $v = image_valide( $u, $base ) ) ) {
				return $v;
			}
		}
	}
	return '';
}

/**
 * Titre en capitales ramené en casse de phrase (lisibilité, RGAA 10.x).
 *
 * @param string $t Titre.
 * @return string
 */
function casse_titre( string $t ): string {
	$lettres = (string) \preg_replace( '/[^\p{L}]/u', '', $t );
	if ( \mb_strlen( $lettres ) < 8 || \mb_strtoupper( $lettres ) !== $lettres ) {
		return $t;
	}
	$b = \mb_strtolower( $t );
	$b = (string) \preg_replace_callback( '/\b(sdis|sdmis|bspp|bmpm|spv|spp|jsp|ssuap|nrbc|cta|codis|smur|samu|dps|fdf)\b/u', static fn( array $m ): string => \mb_strtoupper( $m[1] ), $b );
	return \mb_strtoupper( \mb_substr( $b, 0, 1 ) ) . \mb_substr( $b, 1 );
}

/**
 * Résumé débarrassé des mentions automatiques des flux WordPress.
 *
 * @param string $t Résumé.
 * @return string
 */
function nettoyer_resume( string $t ): string {
	$t = (string) \preg_replace( '/\s*(The post|L’article|L\'article|Cet article)\s.*?(appeared first on|est apparu en premier sur)\s.*$/su', '', $t );
	$t = (string) \preg_replace( '/\s*\[(…|&#8230;|\.\.\.)\]\s*$/u', '…', $t );
	return \trim( $t );
}

/**
 * Mots signalant un recrutement.
 *
 * @param string $texte Titre et résumé.
 * @return bool
 */
function est_recrutement( string $texte ): bool {
	return (bool) \preg_match( '/recrut|devenez|deviens|devenir (sapeur|pompier)|\bspv\b|volontaire|concours|candidat|jeunes? sapeurs?-pompiers|\bjsp\b|engagez-vous|rejoignez/', plat( $texte ) );
}

/**
 * Articles d'un flux RSS ou Atom.
 *
 * @param string $xml   Flux.
 * @param int    $max   Nombre maximal.
 * @param int    $jours Ancienneté maximale.
 * @return array<int,array{titre:string,url:string,date:string,resume:string}>
 */
function analyser_flux( string $xml, int $max = 6, int $jours = 45 ): array {
	$doc = new \DOMDocument();
	\libxml_use_internal_errors( true );
	$ok = '' !== \trim( $xml ) && $doc->loadXML( \trim( $xml ), LIBXML_NONET | LIBXML_NOCDATA );
	\libxml_clear_errors();
	if ( ! $ok ) {
		throw new Erreur_Acces( 'flux', 'Flux illisible.' );
	}
	$racine = $doc->documentElement?->localName;
	$noeuds = 'feed' === $racine ? $doc->getElementsByTagName( 'entry' ) : $doc->getElementsByTagName( 'item' );
	if ( ! \in_array( $racine, array( 'rss', 'feed', 'RDF' ), true ) ) {
		throw new Erreur_Acces( 'flux', 'Le document n\'est pas un flux RSS ou Atom.' );
	}
	$limite = \time() - $jours * 86400;
	$out    = array();
	foreach ( $noeuds as $it ) {
		/** @var \DOMElement $it */
		$get   = static fn( string $t ): string => \trim( (string) ( $it->getElementsByTagName( $t )->item( 0 )?->textContent ?? '' ) );
		$titre = \html_entity_decode( $get( 'title' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$lien  = $get( 'link' );
		if ( '' === $lien ) {
			$l    = $it->getElementsByTagName( 'link' )->item( 0 );
			$lien = $l instanceof \DOMElement ? $l->getAttribute( 'href' ) : '';
		}
		$quand = $get( 'pubDate' ) ?: ( $get( 'published' ) ?: ( $get( 'updated' ) ?: $get( 'date' ) ) );
		$ts    = '' !== $quand ? \strtotime( $quand ) : false;
		if ( '' === $titre || ! \preg_match( '#^https?://#', $lien ) || false === $ts || $ts < $limite ) {
			continue;
		}
		$desc    = $get( 'description' ) ?: $get( 'summary' );
		$contenu = '';
		$image   = '';
		foreach ( $it->childNodes as $ch ) {
			if ( ! $ch instanceof \DOMElement ) {
				continue;
			}
			$nom = $ch->localName;
			if ( '' === $image && 'enclosure' === $nom && \str_starts_with( $ch->getAttribute( 'type' ), 'image' ) ) {
				$image = image_valide( $ch->getAttribute( 'url' ) );
			} elseif ( '' === $image && \in_array( $nom, array( 'content', 'thumbnail' ), true ) && '' !== $ch->getAttribute( 'url' ) && ! \str_starts_with( $ch->getAttribute( 'medium' ) ?: 'image', 'video' ) ) {
				$image = image_valide( $ch->getAttribute( 'url' ) );
			} elseif ( 'encoded' === $nom || ( 'content' === $nom && '' === $ch->getAttribute( 'url' ) ) ) {
				$contenu = (string) $ch->textContent;
			}
		}
		if ( '' === $image ) {
			$image = premiere_image( $contenu . ' ' . $desc, $lien );
		}
		$out[] = array(
			'titre'  => $titre,
			'url'    => $lien,
			'date'   => \gmdate( 'Y-m-d H:i:s', $ts ),
			'resume' => resume_court( nettoyer_resume( wp_strip_texte( $desc ) ) ),
			'image'  => $image,
		);
		if ( \count( $out ) >= $max ) {
			break;
		}
	}
	return $out;
}

/**
 * Articles depuis l'API wp-json d'un site WordPress.
 *
 * @param string $json  Réponse.
 * @param int    $jours Ancienneté maximale.
 * @return array<int,array{titre:string,url:string,date:string,resume:string}>
 */
function analyser_wpjson( string $json, int $jours = 45 ): array {
	$d = \json_decode( $json, true );
	if ( ! \is_array( $d ) || ( $d && ! isset( $d[0]['link'] ) ) ) {
		throw new Erreur_Acces( 'flux', 'API wp-json inattendue.' );
	}
	$limite = \time() - $jours * 86400;
	$out    = array();
	foreach ( $d as $p ) {
		$ts = \strtotime( (string) ( $p['date_gmt'] ?? $p['date'] ?? '' ) );
		if ( false === $ts || $ts < $limite ) {
			continue;
		}
		$out[] = array(
			'titre'  => \html_entity_decode( wp_strip_texte( (string) ( $p['title']['rendered'] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'url'    => (string) $p['link'],
			'date'   => \gmdate( 'Y-m-d H:i:s', $ts ),
			'resume' => resume_court( wp_strip_texte( (string) ( $p['excerpt']['rendered'] ?? '' ) ) ),
			'image'  => image_valide(
				(string) ( $p['_embedded']['wp:featuredmedia'][0]['media_details']['sizes']['medium_large']['source_url']
					?? $p['_embedded']['wp:featuredmedia'][0]['source_url']
					?? $p['jetpack_featured_media_url']
					?? $p['yoast_head_json']['og_image'][0]['url']
					?? premiere_image( (string) ( $p['content']['rendered'] ?? '' ), (string) $p['link'] ) )
			),
		);
	}
	return $out;
}

/**
 * Connecteur Actualités des SDIS (quotidien, par lots de services).
 */
class Connecteur_Actus implements Connecteur {

	/**
	 * Services suivis : fournis par WordPress (id, site, flux, flux_type, flux_verif, dep, titre).
	 *
	 * @var callable|null
	 */
	public $services = null;

	public function id(): string {
		return 'sdis';
	}

	public function nom(): string {
		return 'Actualités des SDIS, BSPP, BMPM';
	}

	public function organisme(): string {
		return '';
	}

	public function url(): string {
		return 'https://lannuaire.service-public.gouv.fr/';
	}

	public function methode(): string {
		return 'Flux RSS déclarés, /feed/ WordPress ou API wp-json, robots.txt respecté';
	}

	/**
	 * Les sites des SDIS viennent de l'annuaire officiel et redirigent
	 * parfois vers un autre domaine : tout hôte HTTPS public est admis
	 * (protection SSRF de WordPress conservée).
	 */
	public function domaines(): array {
		return array( '*' );
	}

	public function pause(): float {
		return 2.0;
	}

	public function plafond(): int {
		return 400;
	}

	public function unites( array $reglages ): array {
		$liste = \is_callable( $this->services ) ? (array) \call_user_func( $this->services ) : array();
		$liste = \array_values( \array_filter( $liste, static fn( array $s ): bool => '' !== (string) ( $s['site'] ?? '' ) || '' !== (string) ( $s['flux'] ?? '' ) ) );
		$out   = array();
		foreach ( \array_chunk( $liste, 3 ) as $i => $lot ) {
			$out[] = array(
				'url'      => 'lot ' . ( $i + 1 ),
				'type'     => 'actu_sdis',
				'services' => (string) \json_encode( $lot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			);
		}
		return $out;
	}

	/**
	 * Autorisation robots.txt (mise en cache 30 jours par hôte).
	 *
	 * @param Http   $http Client.
	 * @param string $url  URL visée.
	 * @return bool
	 */
	private function autorise( Http $http, string $url ): bool {
		$p     = \parse_url( $url );
		$hote  = (string) ( $p['host'] ?? '' );
		$cache = etat_get( 'r18_robots' );
		if ( ! isset( $cache[ $hote ] ) || \time() - (int) $cache[ $hote ]['t'] > 30 * 86400 ) {
			$txt = '';
			try {
				$txt = $http->get( 'https://' . $hote . '/robots.txt', array( 'text/plain' ), false, 262144 )->corps;
			} catch ( Erreur_Acces $e ) {
				if ( \in_array( $e->categorie, array( 'bloque', 'limite' ), true ) ) {
					throw $e;
				}
			}
			$cache[ $hote ] = array( 't' => \time(), 'r' => \substr( $txt, 0, 20000 ) );
			etat_set( 'r18_robots', \array_slice( $cache, -300, null, true ) );
		}
		$chemin = (string) ( $p['path'] ?? '/' ) . ( isset( $p['query'] ) ? '?' . $p['query'] : '' );
		return robots_autorise( (string) $cache[ $hote ]['r'], $chemin );
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$items = array();
		$notes = array();
		foreach ( (array) \json_decode( $unite['services'], true ) as $s ) {
			$flux = (string) ( $s['flux'] ?? '' );
			$ft   = (string) ( $s['flux_type'] ?? '' );
			$sid  = (int) ( $s['id'] ?? 0 );
			try {
				if ( \time() - (int) ( $s['flux_verif'] ?? 0 ) > ( '' === $flux ? 7 : 30 ) * 86400 ) {
					[ $flux, $ft, $vis ] = $this->decouvrir( $http, (string) $s['site'], (string) ( $s['flux'] ?? '' ), (string) ( $s['flux_type'] ?? '' ) );
					$items[]             = array(
						'source'    => 'sdis',
						'ref'       => 'flux-' . $sid,
						'type'      => 'flux_sdis',
						'titre'     => (string) $s['titre'],
						'service'   => (string) $sid,
						'flux'      => $flux,
						'flux_type' => $ft,
						'image'     => $vis['image'],
						'logo'      => $vis['logo'],
					);
				}
				if ( '' === $flux ) {
					$notes[] = $s['titre'] . ' : aucun flux public trouvé (nouvel essai dans 7 jours).';
					continue;
				}
				if ( ! $this->autorise( $http, $flux ) ) {
					$notes[] = $s['titre'] . ' : lecture du flux interdite par robots.txt, respectée.';
					continue;
				}
				$r     = $http->get( $flux, 'wpjson' === $ft ? array( 'application/json' ) : array( 'application/rss+xml', 'application/atom+xml', 'application/xml', 'text/xml', 'text/html', 'text/plain' ), true, 2097152 );
				$jours = (int) ( $s['jours'] ?? 45 );
				$max   = (int) ( $s['max'] ?? 6 );
				$liste = 304 === $r->statut ? array() : ( 'wpjson' === $ft ? \array_slice( analyser_wpjson( $r->corps, $jours ), 0, $max ) : analyser_flux( $r->corps, $max, $jours ) );
				foreach ( $liste as $a ) {
					$items[] = array(
						'source'      => 'sdis',
						'ref'         => \substr( \hash( 'sha1', $a['url'] ), 0, 20 ),
						'type'        => 'actu_sdis',
						'titre'       => casse_titre( $a['titre'] ),
						'url'         => $a['url'],
						'date'        => $a['date'],
						'resume'      => $a['resume'],
						'image'       => (string) ( $a['image'] ?? '' ),
						'service'     => (string) $sid,
						'dep'         => (string) ( $s['dep'] ?? '' ),
						'recrutement' => est_recrutement( $a['titre'] . ' ' . $a['resume'] ) ? '1' : '',
					);
				}
			} catch ( Erreur_Acces $e ) {
				if ( 'limite' === $e->categorie && ! \str_contains( $e->getMessage(), 'Plafond' ) ) {
					$notes[] = $s['titre'] . ' : limitation de requêtes, site laissé pour le prochain cycle.';
					continue;
				}
				if ( 'limite' === $e->categorie ) {
					throw $e;
				}
				$notes[] = $s['titre'] . ' : ' . $e->getMessage();
			}
		}
		return array(
			'items'   => $items,
			'notes'   => $notes,
			'methode' => 'rss',
		);
	}

	/**
	 * Découverte du flux d'un site : flux déclaré, /feed/, puis wp-json.
	 *
	 * @param Http   $http Client.
	 * @param string $site Site officiel.
	 * @param string $flux Flux saisi à la main (conservé s'il existe).
	 * @param string $ft   Type du flux saisi.
	 * @return array{0:string,1:string,2:array{image:string,logo:string}} URL, type (rss|wpjson|''), visuels.
	 */
	private function decouvrir( Http $http, string $site, string $flux = '', string $ft = '' ): array {
		$vis = array( 'image' => '', 'logo' => '' );
		if ( '' === $site ) {
			return array( $flux, $ft, $vis );
		}
		$site = \preg_replace( '#^http://#i', 'https://', $site );
		$home = $http->get( (string) $site, array( 'text/html' ) );
		$base = $home->url;
		$vis  = visuels_page( $home->corps, $base );
		$f    = flux_declare( $home->corps, $base );
		if ( '' !== $f ) {
			return array( (string) \preg_replace( '#^http://#i', 'https://', $f ), 'rss', $vis );
		}
		$racine = \preg_replace( '#^(https://[^/]+).*$#', '$1', $base );
		$wp     = \str_contains( $home->corps, '/wp-json/' ) || \str_contains( $home->corps, 'wp-content' );
		$essais = $wp
			? array( array( $racine . '/feed/', 'rss' ), array( $racine . '/wp-json/wp/v2/posts?per_page=6&_embed=wp:featuredmedia&_fields=title,link,date,date_gmt,excerpt,jetpack_featured_media_url,_links,_embedded', 'wpjson' ) )
			: array( array( $racine . '/feed/', 'rss' ), array( $racine . '/rss.xml', 'rss' ), array( $racine . '/rss', 'rss' ) );
		foreach ( $essais as [ $u, $t ] ) {
			try {
				if ( ! $this->autorise( $http, $u ) ) {
					continue;
				}
				$r = $http->get( $u, 'wpjson' === $t ? array( 'application/json' ) : array( 'application/rss+xml', 'application/atom+xml', 'application/xml', 'text/xml', 'text/html', 'text/plain' ), false, 2097152 );
				'wpjson' === $t ? analyser_wpjson( $r->corps, 3650 ) : analyser_flux( $r->corps, 1, 3650 );
				return array( $u, $t, $vis );
			} catch ( Erreur_Acces $e ) {
				if ( \in_array( $e->categorie, array( 'bloque', 'limite' ), true ) ) {
					throw $e;
				}
			}
		}
		return array( '', '', $vis );
	}
}

/* -------------------------------------------------------------------------
 * Offres d'emploi (Choisir le Service Public, data.gouv.fr)
 * ---------------------------------------------------------------------- */

const TRANCHE = 8388608;

/**
 * Ligne CSV vers offre, si elle concerne un service d'incendie et de secours.
 *
 * @param array<string,string> $l Ligne (en-tête => valeur).
 * @return array<string,string>|null
 */
function offre_depuis_ligne( array $l ): ?array {
	$org   = (string) ( $l['Organisme de rattachement'] ?? '' );
	$titre = \trim( (string) ( $l['Intitulé du poste'] ?? '' ) );
	$p     = plat( $org . ' ' . $titre );
	if ( '' === $titre || ! \preg_match( '/incendie et de secours|\bsdis\b|\bsdmis\b|sapeurs?-pompiers|\bsis \d/', $p ) ) {
		return null;
	}
	$ref = \trim( (string) ( $l['Référence'] ?? '' ) );
	if ( '' === $ref ) {
		return null;
	}
	$dep  = dep_depuis_libelle( (string) ( $l['Localisation du poste'] ?? '' ) );
	$emp  = '';
	if ( \preg_match( '/\s-\s*([^-]{2,80})$/u', $titre, $m ) ) {
		$emp = \trim( $m[1] );
	}
	// Rattaché à un service d'incendie et de secours (et non à un ministère qui recrute sur ce thème).
	$sdis = \str_contains( plat( $org ), 'incendie et de secours' ) || (bool) \preg_match( '/\b(sdis|sdmis|sis|stis|s\.d\.i\.s\.?)\b/', plat( $emp ) );
	if ( \str_contains( plat( $emp . ' ' . $titre ), 'dgscgc' ) ) {
		$sdis = false; // Postes du ministère (DGSCGC), pas d'un service départemental.
	}
	$d = static function ( string $v ): string {
		return \preg_match( '#^(\d{2})/(\d{2})/(\d{4})$#', \trim( $v ), $m ) ? $m[3] . '-' . $m[2] . '-' . $m[1] : '';
	};
	return array(
		'source'     => 'offres',
		'ref'        => $ref,
		'type'       => 'offre',
		'titre'      => casse_titre( $titre ),
		'metier'     => (string) ( $l['Métier'] ?? '' ),
		'employeur'  => '' !== $emp ? $emp : ( '' !== \trim( $org ) ? \trim( $org ) : 'Service d\'incendie et de secours' ),
		'sdis'       => $sdis ? '1' : '',
		'dep'        => $dep,
		'lieu'       => (string) ( $l['Lieu d\'affectation'] ?? '' ),
		'ville'      => (string) ( $l['Lieu d\'affectation (sans géolocalisation)'] ?? '' ),
		'categorie'  => (string) ( $l['Catégorie'] ?? '' ),
		'nature'     => (string) ( $l['Nature de l\'emploi'] ?? '' ),
		'contrat'    => (string) ( $l['Durée du contrat'] ?? '' ),
		'statut'     => (string) ( $l['Statut du poste'] ?? '' ),
		'temps'      => \trim( (string) ( $l['Temps Plein'] ?? '' ) ),
		'teletravail' => \trim( (string) ( $l['Télétravail'] ?? '' ) ),
		'etudes'     => (string) ( $l['Niveau d\'études'] ?? '' ),
		'debut'      => $d( (string) ( $l['Date de début de publication par défaut'] ?? '' ) ),
		'fin'        => $d( (string) ( $l['Date de fin de publication par défaut'] ?? '' ) ),
		'vacance'    => $d( (string) ( $l['Date de vacance de l\'emploi'] ?? '' ) ),
		'premiere'   => $d( (string) ( $l['Date de première publication'] ?? '' ) ),
		'competences' => \mb_substr( \trim( (string) ( $l['Compétences attendues'] ?? '' ) ), 0, 1500 ),
		'management' => \trim( (string) ( $l['Management'] ?? '' ) ),
		'url'        => 'https://choisirleservicepublic.gouv.fr/offre-emploi/reference-' . \rawurlencode( $ref ) . '/',
		'date'       => '',
		'resume'     => '',
	);
}

/**
 * Analyse une tranche du CSV. La dernière ligne, peut-être coupée, est
 * rendue comme reste à recoller au début de la tranche suivante.
 *
 * @param string   $texte  Reste précédent + tranche.
 * @param string[] $entete En-tête (vide pour la première tranche).
 * @param bool     $finale Dernière tranche du fichier.
 * @return array{offres:array<int,array<string,string>>,entete:string[],reste:string,lignes:int}
 */
function analyser_tranche( string $texte, array $entete, bool $finale ): array {
	$f = \fopen( 'php://temp/maxmemory:1048576', 'w+' );
	\fwrite( $f, $texte );
	\rewind( $f );
	$offres  = array();
	$lignes  = 0;
	$attente = null;
	$pos_att = 0;
	$traiter = static function ( array $row ) use ( &$entete, &$offres, &$lignes ): void {
		if ( empty( $entete ) ) {
			$row[0] = (string) \preg_replace( '/^\xEF\xBB\xBF/', '', (string) $row[0] );
			$entete = \array_map( 'trim', $row );
			return;
		}
		if ( \count( $row ) < 5 ) {
			return;
		}
		++$lignes;
		$assoc = array();
		foreach ( $entete as $i => $k ) {
			$assoc[ $k ] = (string) ( $row[ $i ] ?? '' );
		}
		$o = offre_depuis_ligne( $assoc );
		if ( null !== $o ) {
			$offres[] = $o;
		}
	};
	// Lecture en flux : chaque ligne est traitée quand la suivante est lue,
	// la dernière (peut-être coupée) devient le reste de la tranche suivante.
	while ( true ) {
		$pos = (int) \ftell( $f );
		$row = \fgetcsv( $f, 0, ';', '"', '' );
		if ( false === $row ) {
			break;
		}
		if ( null !== $attente ) {
			$traiter( $attente );
		}
		$attente = $row;
		$pos_att = $pos;
	}
	\fclose( $f );
	$reste = '';
	if ( null !== $attente ) {
		if ( $finale ) {
			$traiter( $attente );
		} else {
			$reste = \substr( $texte, $pos_att );
		}
	}
	return array(
		'offres' => $offres,
		'entete' => $entete,
		'reste'  => $reste,
		'lignes' => $lignes,
	);
}

/**
 * Connecteur Offres d'emploi (hebdomadaire, lecture par tranches).
 */
class Connecteur_Offres implements Connecteur {

	public function id(): string {
		return 'offres';
	}

	public function nom(): string {
		return 'Offres d\'emploi des SDIS';
	}

	public function organisme(): string {
		return '';
	}

	public function url(): string {
		return 'https://www.data.gouv.fr/datasets/' . DATASET_CSP;
	}

	public function methode(): string {
		return 'Données ouvertes Choisir le Service Public (data.gouv.fr), lecture par tranches de 8 Mo';
	}

	public function domaines(): array {
		return array( 'www.data.gouv.fr', 'static.data.gouv.fr' );
	}

	public function pause(): float {
		return 1.0;
	}

	public function plafond(): int {
		return 40;
	}

	public function unites( array $reglages ): array {
		if ( empty( $reglages['force'] ) && recent( 'offres', 6 ) ) {
			return array();
		}
		$taille = (int) ( etat_get( 'r18_offres_import' )['taille'] ?? 140000000 );
		$n      = (int) \ceil( $taille * 1.15 / TRANCHE ) + 1;
		$out    = array(
			array(
				'url'   => 'https://www.data.gouv.fr/api/1/datasets/' . DATASET_CSP . '/',
				'type'  => 'meta',
				'force' => empty( $reglages['force'] ) ? '' : '1',
			),
		);
		for ( $i = 0; $i < $n; $i++ ) {
			$out[] = array(
				'url'     => 'tranche ' . ( $i + 1 ),
				'type'    => 'tranche',
				'tranche' => (string) $i,
			);
		}
		return $out;
	}

	public function traiter( array $unite, Http $http, array $reglages, callable $connu ): array {
		$st = etat_get( 'r18_offres_import' );
		if ( 'meta' === $unite['type'] ) {
			$d   = \json_decode( $http->get( $unite['url'], array( 'application/json' ), false, 4194304 )->corps, true );
			$res = array();
			foreach ( (array) ( $d['resources'] ?? array() ) as $r ) {
				if ( 'main' === ( $r['type'] ?? '' ) && 'csv' === \strtolower( (string) ( $r['format'] ?? '' ) ) ) {
					$res[] = $r;
				}
			}
			\usort( $res, static fn( array $a, array $b ): int => \strcmp( (string) $b['last_modified'], (string) $a['last_modified'] ) );
			if ( empty( $res[0]['url'] ) ) {
				throw new Erreur_Acces( 'structure', 'Aucun fichier CSV dans le jeu de données Choisir le Service Public.' );
			}
			$url     = (string) $res[0]['url'];
			$version = (string) $res[0]['last_modified'];
			$inch    = empty( $unite['force'] ) && ( $st['version'] ?? '' ) === $version && ! empty( $st['termine'] ) && \in_array( 'Référence', (array) ( $st['entete'] ?? array() ), true );
			etat_set(
				'r18_offres_import',
				array(
					'url'      => $url,
					'version'  => $version,
					'taille'   => (int) ( $res[0]['filesize'] ?? ( $st['taille'] ?? 140000000 ) ),
					'entete'   => array(),
					'reste'    => '',
					'import'   => $inch ? (string) ( $st['import'] ?? '' ) : \gmdate( 'YmdHis' ),
					'termine'  => $inch,
					'inchange' => $inch,
					'lignes'   => $inch ? (int) ( $st['lignes'] ?? 0 ) : 0,
					'offres'   => $inch ? (int) ( $st['offres'] ?? 0 ) : 0,
				)
			);
			return array(
				'items'   => array(),
				'notes'   => array( $inch ? 'Fichier inchangé depuis le dernier import : lecture évitée.' : 'Nouveau fichier : ' . \basename( $url ) . ' (' . \round( ( (int) ( $res[0]['filesize'] ?? 0 ) ) / 1048576 ) . ' Mo).' ),
				'methode' => 'csv',
			);
		}
		if ( ! empty( $st['inchange'] ) || ! empty( $st['termine'] ) || empty( $st['url'] ) ) {
			return array( 'items' => array(), 'notes' => array(), 'methode' => 'csv' );
		}
		$k      = (int) $unite['tranche'];
		if ( $k > 0 && ! \in_array( 'Référence', (array) $st['entete'], true ) ) {
			$st['termine'] = true;
			$st['inchange'] = false;
			etat_set( 'r18_offres_import', $st );
			throw new Erreur_Acces( 'structure', 'En-tête du fichier non lu (tranche 1 manquante) : import arrêté sans rien retirer. Il reprendra au prochain passage.' );
		}
		$debut  = $k * TRANCHE;
		$finale = false;
		try {
			$r      = $http->get( (string) $st['url'], array( 'text/csv', 'application/csv', 'application/octet-stream', 'binary/octet-stream', 'application/vnd.ms-excel', 'text/plain' ), false, TRANCHE + 1048576, array( 'Range' => 'bytes=' . $debut . '-' . ( $debut + TRANCHE - 1 ) ) );
			$corps  = $r->corps;
			$total  = \preg_match( '#/(\d+)$#', (string) ( $r->entetes['content-range'] ?? '' ), $m ) ? (int) $m[1] : 0;
			$finale = 200 === $r->statut || \strlen( $corps ) < TRANCHE || ( $total > 0 && $debut + \strlen( $corps ) >= $total );
		} catch ( Erreur_Acces $e ) {
			if ( 'fin' !== $e->categorie ) {
				throw $e;
			}
			$corps  = '';
			$finale = true;
		}
		// Le reste peut finir au milieu d'un caractère UTF-8 : stocké en base64 (la base refuse l'UTF-8 invalide).
		$a = analyser_tranche( (string) \base64_decode( (string) $st['reste'] ) . $corps, (array) $st['entete'], $finale );
		$st['entete']  = $a['entete'];
		$st['reste']   = \base64_encode( $a['reste'] );
		$st['lignes']  = (int) $st['lignes'] + $a['lignes'];
		$st['offres']  = (int) $st['offres'] + \count( $a['offres'] );
		$st['termine'] = $finale;
		etat_set( 'r18_offres_import', $st );
		$items = array();
		foreach ( $a['offres'] as $o ) {
			$o['import'] = (string) $st['import'];
			$items[]     = $o;
		}
		if ( $finale ) {
			$items[] = array(
				'source' => 'offres',
				'ref'    => 'fin-' . $st['import'],
				'type'   => 'offres_fin',
				'titre'  => 'Fin de l\'import',
				'import' => (string) $st['import'],
			);
		}
		return array(
			'items'   => $items,
			'notes'   => array( 'Tranche ' . ( $k + 1 ) . ' : ' . $a['lignes'] . ' lignes lues, ' . \count( $a['offres'] ) . ' offre(s) SDIS.' . ( $finale ? ' Fichier entièrement lu : ' . $st['lignes'] . ' offres publiques, ' . $st['offres'] . ' dans les SDIS.' : '' ) ),
			'methode' => 'csv',
		);
	}
}

/**
 * Résumé court (240 caractères au plus, coupé sur un mot).
 *
 * @param string $t Texte.
 * @return string
 */
function resume_court( string $t ): string {
	$t = \trim( $t );
	if ( \mb_strlen( $t ) <= 240 ) {
		return $t;
	}
	$c = \mb_substr( $t, 0, 240 );
	return \rtrim( (string) \preg_replace( '/\s+\S*$/u', '', $c ), " ,;:." ) . '…';
}

/**
 * Retire les balises HTML (sans WordPress en test).
 *
 * @param string $html HTML.
 * @return string
 */
function wp_strip_texte( string $html ): string {
	return \trim( (string) \preg_replace( '/\s+/u', ' ', \html_entity_decode( \strip_tags( (string) \preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}
