<?php
/**
 * Alertes d'interventions : collecte périodique, bandeau, écran d'administration.
 *
 * Aucune donnée durable : les alertes vivent dans un cache temporaire
 * (transient) et s'effacent seules. Rien n'est publié en article.
 *
 * Sources :
 * - flux des SDIS, BSPP et BMPM déjà repérés par l'annuaire (robots.txt respecté) ;
 * - flux RSS de presse saisis au back-office (aucun par défaut) ;
 * - satellites NASA FIRMS si une clé gratuite est saisie ;
 * - API Découpage administratif (geo.api.gouv.fr) pour situer les communes.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Alertes;

use G2RD_Rescue18\Veille\Erreur_Acces;
use G2RD_Rescue18\Veille\Http;
use G2RD_Rescue18\Veille\Transport_WP;
use function G2RD_Rescue18\Annuaire\analyser_flux;
use function G2RD_Rescue18\Annuaire\analyser_wpjson;
use function G2RD_Rescue18\Annuaire\departements;
use function G2RD_Rescue18\Annuaire\etat_get;
use function G2RD_Rescue18\Annuaire\etat_set;
use function G2RD_Rescue18\Annuaire\nom_dep;
use function G2RD_Rescue18\Annuaire\robots_autorise;
use function G2RD_Rescue18\Annuaire\services;
use function G2RD_Rescue18\Veille\plat;

\defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/analyse.php';

const OPT_REG  = 'r18_alertes_reglages';
const T_STORE  = 'r18_alertes';
const T_RUN    = 'r18_alertes_run';
const T_VUS    = 'r18_alertes_vus';
const T_GEO    = 'r18_alertes_geo';
const T_CHAUDS = 'r18_alertes_chauds';
const T_VALID  = 'r18_alertes_valid';
const T_BILAN  = 'r18_alertes_bilan';
const VERROU   = 'r18_alertes_verrou';
const HOOK     = 'r18_alertes_collecte';
const HOOK_LOT = 'r18_alertes_lot';
const PAGE     = 'rescue18-alertes';
const ZONE     = '-5.3,41.3,9.7,51.2';

/**
 * Réglages.
 *
 * @return array{actif:bool,frequence:int,firms:string,presse:string}
 */
function reglages(): array {
	$r = (array) \get_option( OPT_REG, array() );
	return array(
		'actif'     => ! isset( $r['actif'] ) || ! empty( $r['actif'] ),
		'frequence' => 2 === (int) ( $r['frequence'] ?? 1 ) ? 2 : 1,
		'firms'     => (string) ( $r['firms'] ?? '' ),
		'presse'    => (string) ( $r['presse'] ?? '' ),
	);
}

/**
 * Flux de presse saisis : « URL | Nom affiché | département ».
 *
 * @return array<int,array{url:string,nom:string,dep:string}>
 */
function flux_presse(): array {
	$out = array();
	foreach ( \preg_split( '/\R/', reglages()['presse'] ) ?: array() as $l ) {
		$c   = \array_map( 'trim', \explode( '|', $l ) );
		$url = \esc_url_raw( (string) ( $c[0] ?? '' ), array( 'https' ) );
		if ( '' === $url ) {
			continue;
		}
		$out[] = array(
			'url' => $url,
			'nom' => '' !== ( $c[1] ?? '' ) ? \sanitize_text_field( $c[1] ) : (string) \preg_replace( '/^www\./', '', (string) \wp_parse_url( $url, PHP_URL_HOST ) ),
			'dep' => \strtoupper( \sanitize_key( (string) ( $c[2] ?? '' ) ) ),
		);
	}
	return $out;
}

/**
 * Alertes en mémoire.
 *
 * @return array<string,array<string,mixed>>
 */
function alertes(): array {
	$v = \get_transient( T_STORE );
	return \is_array( $v ) ? $v : array();
}

/**
 * Enregistre les alertes (cache de 3 jours au plus).
 *
 * @param array<string,array<string,mixed>> $a Alertes.
 */
function garder( array $a ): void {
	\set_transient( T_STORE, $a, 3 * DAY_IN_SECONDS );
}

/**
 * Alertes à afficher, la plus récente en premier.
 *
 * @return array<int,array<string,mixed>>
 */
function en_cours(): array {
	$now = \time();
	$out = \array_values( \array_filter( alertes(), static fn( array $a ): bool => visible( $a, $now ) ) );
	\usort( $out, static fn( array $x, array $y ): int => (int) $y['maj'] <=> (int) $x['maj'] );
	return $out;
}

/* -------------------------------------------------------------------------
 * Planification
 * ---------------------------------------------------------------------- */

\add_filter(
	'cron_schedules',
	static function ( array $s ): array {
		$s['r18_2h'] = array(
			'interval' => 2 * HOUR_IN_SECONDS,
			'display'  => 'Toutes les 2 heures',
		);
		return $s;
	}
);

/**
 * (Re)planifie la collecte selon les réglages.
 */
function planifier(): void {
	\wp_clear_scheduled_hook( HOOK );
	$r = reglages();
	if ( $r['actif'] ) {
		// Minute 23 : à l'écart des créneaux de nuit (:07, :17, :37, :43, :47) et des heures pleines.
		\wp_schedule_event( \G2RD_Rescue18\prochaine_minute( 23 ), 2 === $r['frequence'] ? 'r18_2h' : 'hourly', HOOK );
	}
}

\add_action(
	'admin_init',
	static function (): void {
		if ( reglages()['actif'] && ! \wp_next_scheduled( HOOK ) ) {
			planifier();
		}
	}
);
\add_action(
	'switch_theme',
	static function (): void {
		\wp_clear_scheduled_hook( HOOK );
		\wp_clear_scheduled_hook( HOOK_LOT );
	}
);
\add_action( HOOK, __NAMESPACE__ . '\demarrer' );
\add_action( HOOK_LOT, __NAMESPACE__ . '\lot' );

/**
 * Prépare la liste des flux à lire, puis lance le premier lot.
 */
function demarrer(): void {
	$run = \get_transient( T_RUN );
	if ( \is_array( $run ) && \time() - (int) $run['maj'] < 15 * MINUTE_IN_SECONDS ) {
		return;
	}
	$file = array();
	foreach ( services() as $s ) {
		if ( '' !== (string) $s['flux'] && empty( $s['actus_off'] ) ) {
			$file[] = array( 'sdis', (string) $s['flux'], (string) $s['flux_type'], (string) $s['titre'], (string) $s['dep'] );
		}
	}
	foreach ( flux_presse() as $f ) {
		$file[] = array( 'presse', $f['url'], 'rss', $f['nom'], $f['dep'] );
	}
	if ( '' !== reglages()['firms'] ) {
		$file[] = array( 'firms', '', '', '', '' );
	}
	\set_transient(
		T_RUN,
		array(
			'file'    => $file,
			'pos'     => 0,
			'debut'   => \time(),
			'maj'     => \time(),
			'lus'     => 0,
			'erreurs' => array(),
		),
		2 * HOUR_IN_SECONDS
	);
	lot();
}

/**
 * Lit des flux pendant 25 secondes, intègre les signaux, replanifie la suite.
 */
function lot(): void {
	if ( \get_transient( VERROU ) ) {
		return;
	}
	$run = \get_transient( T_RUN );
	if ( ! \is_array( $run ) || $run['pos'] >= \count( $run['file'] ) ) {
		return;
	}
	\set_transient( VERROU, 1, 5 * MINUTE_IN_SECONDS );
	$t0      = \microtime( true );
	$http    = new Http( new Transport_WP(), array( '*' ), 1.0, 400 );
	$http->delai       = 10;
	$valid             = \get_transient( T_VALID );
	$http->validateurs = \is_array( $valid ) ? $valid : array();
	$vus     = \get_transient( T_VUS );
	$vus     = \is_array( $vus ) ? $vus : array();
	$now     = \time();
	$signaux = array();
	while ( $run['pos'] < \count( $run['file'] ) && \microtime( true ) - $t0 < 25 ) {
		[ $type, $url, $ft, $nom, $dep ] = $run['file'][ $run['pos'] ];
		++$run['pos'];
		try {
			if ( 'firms' === $type ) {
				$signaux = \array_merge( $signaux, lire_firms() );
				continue;
			}
			if ( ! autorise( $http, $url ) ) {
				continue;
			}
			$r = $http->get( $url, 'wpjson' === $ft ? array( 'application/json' ) : array( 'application/rss+xml', 'application/atom+xml', 'application/xml', 'text/xml', 'text/html', 'text/plain' ), true, 2097152 );
			++$run['lus'];
			if ( 304 === $r->statut ) {
				continue;
			}
			$liste = 'wpjson' === $ft ? analyser_wpjson( $r->corps, 2 ) : analyser_flux( $r->corps, 15, 2 );
			foreach ( $liste as $a ) {
				$h = \substr( \md5( $a['url'] ), 0, 12 );
				if ( isset( $vus[ $h ] ) ) {
					continue;
				}
				$vus[ $h ] = $now;
				$s         = signal( $a, $type, $nom, $dep, $now, departements() );
				if ( null !== $s ) {
					$signaux[] = $s;
				}
			}
		} catch ( Erreur_Acces $e ) {
			$run['erreurs'][] = $nom . ' : ' . masquer_cle( $e->getMessage() );
		} catch ( \Throwable $e ) {
			$run['erreurs'][] = $nom . ' : lecture impossible.';
		}
	}
	$run['erreurs'] = \array_slice( $run['erreurs'], -40 );
	$run['maj']     = \time();
	$nouvelles      = integrer( $signaux );
	$run['signaux'] = (int) ( $run['signaux'] ?? 0 ) + \count( $signaux );
	$run['alertes'] = (int) ( $run['alertes'] ?? 0 ) + $nouvelles;
	\set_transient( T_VUS, \array_filter( $vus, static fn( int $t ): bool => $now - $t < 2 * DAY_IN_SECONDS ), 2 * DAY_IN_SECONDS );
	\set_transient( T_VALID, \array_slice( $http->validateurs, -400, null, true ), 2 * DAY_IN_SECONDS );
	if ( $run['pos'] < \count( $run['file'] ) ) {
		\set_transient( T_RUN, $run, 2 * HOUR_IN_SECONDS );
		\wp_schedule_single_event( \time() + 15, HOOK_LOT );
	} else {
		\delete_transient( T_RUN );
		\set_transient(
			T_BILAN,
			array(
				'fin'     => \time(),
				'duree'   => \time() - (int) $run['debut'],
				'flux'    => \count( $run['file'] ),
				'lus'     => (int) $run['lus'],
				'signaux' => (int) $run['signaux'],
				'alertes' => (int) $run['alertes'],
				'erreurs' => $run['erreurs'],
			),
			7 * DAY_IN_SECONDS
		);
	}
	\delete_transient( VERROU );
}

/**
 * Retire la clé FIRMS d'un message.
 *
 * @param string $m Message.
 * @return string
 */
function masquer_cle( string $m ): string {
	$k = reglages()['firms'];
	return '' !== $k ? \str_replace( $k, '•••', $m ) : $m;
}

/**
 * robots.txt du site autorise-t-il la lecture du flux ? (cache partagé avec l'annuaire)
 *
 * @param Http   $http Client.
 * @param string $url  Flux.
 * @return bool
 */
function autorise( Http $http, string $url ): bool {
	$p     = \wp_parse_url( $url );
	$hote  = (string) ( $p['host'] ?? '' );
	$cache = etat_get( 'r18_robots' );
	if ( ! isset( $cache[ $hote ] ) || \time() - (int) $cache[ $hote ]['t'] > 30 * DAY_IN_SECONDS ) {
		$txt = '';
		try {
			$txt = $http->get( 'https://' . $hote . '/robots.txt', array( 'text/plain' ), false, 262144 )->corps;
		} catch ( Erreur_Acces $e ) {
			if ( \in_array( $e->categorie, array( 'bloque', 'limite' ), true ) ) {
				throw $e;
			}
		}
		$cache[ $hote ] = array(
			't' => \time(),
			'r' => \substr( $txt, 0, 20000 ),
		);
		etat_set( 'r18_robots', \array_slice( $cache, -300, null, true ) );
	}
	$chemin = (string) ( $p['path'] ?? '/' ) . ( isset( $p['query'] ) ? '?' . $p['query'] : '' );
	return robots_autorise( (string) $cache[ $hote ]['r'], $chemin );
}

/**
 * Feux repérés par les satellites VIIRS (NASA FIRMS) sur la métropole et la Corse.
 *
 * @return array<int,array<string,mixed>>
 */
function lire_firms(): array {
	$cle  = reglages()['firms'];
	$http = new Http( new Transport_WP(), array( 'firms.modaps.eosdis.nasa.gov' ), 1.0, 6 );
	$http->delai = 30;
	$pts  = array();
	foreach ( array( 'VIIRS_SNPP_NRT', 'VIIRS_NOAA20_NRT', 'VIIRS_NOAA21_NRT' ) as $src ) {
		$r   = $http->get( 'https://firms.modaps.eosdis.nasa.gov/api/area/csv/' . \rawurlencode( $cle ) . '/' . $src . '/' . ZONE . '/1', array( 'text/csv', 'text/plain', 'application/octet-stream' ), false, 4194304 );
		$pts = \array_merge( $pts, points_firms( $r->corps ) );
	}
	$jour = \gmdate( 'Y-m-d' );
	$hist = \get_transient( T_CHAUDS );
	$hist = \is_array( $hist ) ? $hist : array();
	$out  = array();
	foreach ( amas( \array_slice( $pts, 0, 3000 ) ) as $o ) {
		$case = case_geo( $o['lat'], $o['lng'] );
		if ( $o['n'] >= 8 && ! permanent( $hist, $case, $jour ) && \time() - $o['t'] < ttl( 'satellite' ) ) {
			$out[] = signal_firms( $o );
		}
		$hist[ $case ] = \array_slice( \array_values( \array_unique( \array_merge( (array) ( $hist[ $case ] ?? array() ), array( $jour ) ) ) ), -10 );
	}
	$limite = \gmdate( 'Y-m-d', \time() - 11 * DAY_IN_SECONDS );
	foreach ( $hist as $c => $dates ) {
		$dates = \array_values( \array_filter( (array) $dates, static fn( string $x ): bool => $x >= $limite ) );
		if ( $dates ) {
			$hist[ $c ] = $dates;
		} else {
			unset( $hist[ $c ] );
		}
	}
	\set_transient( T_CHAUDS, $hist, 12 * DAY_IN_SECONDS );
	return $out;
}

/* -------------------------------------------------------------------------
 * Géolocalisation (API Découpage administratif, geo.api.gouv.fr)
 * ---------------------------------------------------------------------- */

/**
 * Appel de geo.api.gouv.fr mis en cache 30 jours.
 *
 * @param array<string,string> $q Paramètres.
 * @return array<int,array<string,mixed>>
 */
function geo( array $q ): array {
	$cle   = \md5( (string) \wp_json_encode( $q ) );
	$cache = \get_transient( T_GEO );
	$cache = \is_array( $cache ) ? $cache : array();
	if ( isset( $cache[ $cle ] ) ) {
		return $cache[ $cle ];
	}
	$http        = new Http( new Transport_WP(), array( 'geo.api.gouv.fr' ), 0.1, 3 );
	$http->delai = 8;
	try {
		$r   = $http->get( 'https://geo.api.gouv.fr/communes?' . \http_build_query( $q + array( 'fields' => 'nom,code,codeDepartement,centre' ) ), array( 'application/json' ), false, 262144 );
		$res = \json_decode( $r->corps, true );
		$res = \is_array( $res ) ? \array_slice( $res, 0, 5 ) : array();
	} catch ( Erreur_Acces $e ) {
		return array();
	}
	$cache[ $cle ] = $res;
	\set_transient( T_GEO, \array_slice( $cache, -2000, null, true ), 30 * DAY_IN_SECONDS );
	return $res;
}

/**
 * Commune, code INSEE, département et coordonnées d'un signal.
 *
 * @param array<string,mixed> $s Signal.
 * @return array<string,mixed>
 */
function situer( array $s ): array {
	$s += array( 'commune' => '', 'insee' => '', 'lat' => 0.0, 'lng' => 0.0 );
	$retenir = static function ( array $c ) use ( &$s ): void {
		$s['commune'] = (string) $c['nom'];
		$s['insee']   = (string) $c['code'];
		$s['dep']     = (string) $c['codeDepartement'];
		$s['lat']     = (float) ( $c['centre']['coordinates'][1] ?? 0 );
		$s['lng']     = (float) ( $c['centre']['coordinates'][0] ?? 0 );
	};
	if ( ! empty( $s['lat'] ) ) {
		$c = geo(
			array(
				'lat' => (string) $s['lat'],
				'lon' => (string) $s['lng'],
			)
		);
		if ( $c ) {
			$lat = $s['lat'];
			$lng = $s['lng'];
			$retenir( $c[0] );
			$s['lat'] = $lat;
			$s['lng'] = $lng;
		}
		return $s;
	}
	foreach ( (array) $s['lieux'] as $nom ) {
		$q = array(
			'nom'   => (string) $nom,
			'boost' => 'population',
			'limit' => '5',
		);
		if ( '' !== (string) $s['dep'] ) {
			$q['codeDepartement'] = (string) $s['dep'];
		}
		foreach ( geo( $q ) as $c ) {
			if ( plat( (string) $c['nom'] ) === plat( (string) $nom ) ) {
				$retenir( $c );
				return $s;
			}
		}
	}
	return $s;
}

/**
 * Ajoute les signaux aux alertes en mémoire.
 *
 * @param array<int,array<string,mixed>> $signaux Signaux.
 * @return int Alertes ouvertes.
 */
function integrer( array $signaux ): int {
	$al  = alertes();
	$n   = 0;
	$now = \time();
	\usort( $signaux, static fn( array $a, array $b ): int => (int) $a['date'] <=> (int) $b['date'] );
	foreach ( $signaux as $s ) {
		$s  = situer( $s );
		$id = rattacher( $al, $s );
		if ( '' === $id ) {
			$x = nouvelle( $s );
			while ( isset( $al[ $x['id'] ] ) ) {
				$x['id'] .= 'x';
			}
			$al[ $x['id'] ] = $x;
			$n             += empty( $x['fin'] ) ? 1 : 0;
		} else {
			$al[ $id ] = fusionner( $al[ $id ], $s );
		}
	}
	garder( purger( $al, $now ) );
	return $n;
}

/* -------------------------------------------------------------------------
 * Bandeau public
 * ---------------------------------------------------------------------- */

/**
 * Lieu lisible d'une alerte.
 *
 * @param array<string,mixed> $a Alerte.
 * @return string
 */
function lieu( array $a ): string {
	$dep = (string) $a['dep'];
	if ( '' !== (string) $a['commune'] ) {
		return $a['commune'] . ( '' !== $dep ? ' (' . $dep . ')' : '' );
	}
	return '' !== $dep ? nom_dep( $dep ) : '';
}

/**
 * Moyens lisibles.
 *
 * @param array<string,int|float> $m Moyens.
 * @return string[]
 */
function moyens_lisibles( array $m ): array {
	$f   = static fn( $n ): string => \number_format_i18n( (float) $n, \floor( (float) $n ) == $n ? 0 : 1 ); // phpcs:ignore Universal.Operators.StrictComparisons
	$out = array();
	if ( ! empty( $m['pompiers'] ) ) {
		$out[] = $f( $m['pompiers'] ) . ' pompiers';
	}
	if ( ! empty( $m['engins'] ) ) {
		$out[] = $f( $m['engins'] ) . ' engins';
	}
	if ( ! empty( $m['aeriens'] ) ) {
		$out[] = $m['aeriens'] > 1 ? $f( $m['aeriens'] ) . ' moyens aériens' : 'moyens aériens';
	}
	if ( ! empty( $m['hectares'] ) ) {
		$out[] = $f( $m['hectares'] ) . ' ha parcourus';
	}
	if ( ! empty( $m['evacues'] ) ) {
		$out[] = $f( $m['evacues'] ) . ' personnes évacuées';
	}
	return $out;
}

/**
 * Heure lisible : « 14 h 10 » aujourd'hui, « 30/10 à 14 h 10 » sinon.
 *
 * @param int $t Horodatage.
 * @return string
 */
function heure( int $t ): string {
	return ( \wp_date( 'Y-m-d', $t ) === \wp_date( 'Y-m-d' ) ? '' : \wp_date( 'd/m', $t ) . ' à ' ) . \wp_date( 'G \h i', $t );
}

/**
 * Bloc rescue18/alertes : bandeau des interventions en cours (rien sinon).
 *
 * @return string
 */
function render_bandeau(): string {
	$al = en_cours();
	if ( ! $al ) {
		return '';
	}
	$premiere = $al[0];
	$resume   = ( natures()[ $premiere['nature'] ][0] ?? 'Intervention' ) . ( '' !== lieu( $premiere ) ? ' · ' . lieu( $premiere ) : '' );
	$items    = '';
	foreach ( $al as $a ) {
		$sources = array();
		foreach ( $a['sources'] as $s ) {
			$sources[] = '<a href="' . \esc_url( $s['url'] ) . '" rel="noopener">' . \esc_html( $s['nom'] ) . '</a>';
		}
		$m      = moyens_lisibles( (array) $a['moyens'] );
		$items .= '<li class="r18-al__item r18-al__item--' . \esc_attr( famille( (string) $a['nature'] ) ) . '">'
			. '<p class="r18-al__tete"><span class="r18-al__nat">' . \esc_html( natures()[ $a['nature'] ][0] ?? 'Intervention' ) . '</span>'
			. ( '' !== lieu( $a ) ? ' <strong>' . \esc_html( lieu( $a ) ) . '</strong>' : '' ) . '</p>'
			. '<p class="r18-al__titre">' . \esc_html( (string) $a['titre'] ) . '</p>'
			. ( $m ? '<p class="r18-al__moyens">' . \esc_html( \implode( ' · ', $m ) ) . '</p>' : '' )
			. '<p class="r18-al__meta">Depuis ' . \esc_html( heure( (int) $a['debut'] ) ) . ', dernière information à ' . \esc_html( heure( (int) $a['maj'] ) ) . '. '
			. ( \count( $sources ) > 1 ? 'Sources' : 'Source' ) . ' : ' . \implode( ', ', $sources ) . '.</p></li>';
	}
	$autres = \count( $al ) - 1;
	return '<section class="r18-al" aria-label="Interventions en cours"><details class="r18-al__d"><summary class="r18-al__s">'
		. '<span class="r18-al__pulse" aria-hidden="true"></span>'
		. '<strong class="r18-al__lbl">' . ( \count( $al ) > 1 ? 'Interventions en cours' : 'Intervention en cours' ) . '</strong>'
		. '<span class="r18-al__res">' . \esc_html( $resume ) . ( $autres > 0 ? ' <span class="r18-al__n">et ' . $autres . ' autre' . ( $autres > 1 ? 's' : '' ) . '</span>' : '' ) . '</span>'
		. '<span class="r18-al__voir">Détails</span></summary>'
		. '<div class="r18-al__panel"><ul class="r18-al__list" role="list">' . $items . '</ul>'
		. '<p class="r18-al__src">Relevé automatique des publications des SDIS et des médias, mis à jour ' . ( 2 === reglages()['frequence'] ? 'toutes les 2 heures' : 'toutes les heures' ) . '. L’alerte disparaît à la fin de l’intervention.</p></div>'
		. '</details></section>';
}

/**
 * Définition du bloc.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	return array(
		'alertes' => array(
			'title'  => 'Alertes interventions en cours',
			'icon'   => 'megaphone',
			'attrs'  => array(),
			'render' => 'Alertes\render_bandeau',
		),
	);
}

/* -------------------------------------------------------------------------
 * Administration
 * ---------------------------------------------------------------------- */

\add_action(
	'admin_menu',
	static function (): void {
		$n = \count( en_cours() );
		\add_submenu_page( 'index.php', 'Alertes interventions', 'Alertes interventions' . ( $n ? ' <span class="awaiting-mod">' . $n . '</span>' : '' ), 'edit_pages', PAGE, __NAMESPACE__ . '\ecran' );
	}
);

/**
 * Retour vers l'écran avec un message.
 *
 * @param string $msg Code du message.
 */
function retour( string $msg ): void {
	\wp_safe_redirect( \add_query_arg( array( 'page' => PAGE, 'msg' => $msg ), \admin_url( 'index.php' ) ) );
	exit;
}

\add_action(
	'admin_post_r18_alerte_masquer',
	static function (): void {
		if ( ! \current_user_can( 'edit_pages' ) ) {
			\wp_die( 'Accès refusé.', 403 );
		}
		\check_admin_referer( 'r18_alerte_masquer' );
		$id = \sanitize_key( (string) \wp_unslash( $_POST['id'] ?? '' ) );
		$al = alertes();
		if ( isset( $al[ $id ] ) ) {
			$al[ $id ]['masque'] = empty( $al[ $id ]['masque'] );
			garder( $al );
		}
		retour( 'masque' );
	}
);

\add_action(
	'admin_post_r18_alertes_collecte',
	static function (): void {
		if ( ! \current_user_can( 'edit_pages' ) ) {
			\wp_die( 'Accès refusé.', 403 );
		}
		\check_admin_referer( 'r18_alertes_collecte' );
		\wp_schedule_single_event( \time(), HOOK );
		\spawn_cron();
		retour( 'collecte' );
	}
);

\add_action(
	'admin_post_r18_alertes_reglages',
	static function (): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( 'Accès refusé.', 403 );
		}
		\check_admin_referer( 'r18_alertes_reglages' );
		$ancien = reglages();
		$cle    = \sanitize_text_field( (string) \wp_unslash( $_POST['firms'] ?? '' ) );
		if ( ! empty( $_POST['firms_effacer'] ) ) {
			$cle = '';
		} elseif ( '' === $cle ) {
			$cle = $ancien['firms'];
		}
		\update_option(
			OPT_REG,
			array(
				'actif'     => empty( $_POST['actif'] ) ? 0 : 1,
				'frequence' => 2 === (int) ( $_POST['frequence'] ?? 1 ) ? 2 : 1,
				'firms'     => \preg_replace( '/[^A-Za-z0-9]/', '', $cle ),
				'presse'    => \sanitize_textarea_field( (string) \wp_unslash( $_POST['presse'] ?? '' ) ),
			),
			false
		);
		planifier();
		retour( 'reglages' );
	}
);

/**
 * Écran « Tableau de bord > Alertes interventions ».
 */
function ecran(): void {
	$r     = reglages();
	$bilan = \get_transient( T_BILAN );
	$run   = \get_transient( T_RUN );
	$now   = \time();
	$al    = alertes();
	\uasort( $al, static fn( array $x, array $y ): int => (int) $y['maj'] <=> (int) $x['maj'] );
	$msgs = array(
		'masque'   => 'Affichage de l’alerte modifié.',
		'collecte' => 'Collecte lancée. Recharge la page dans une minute.',
		'reglages' => 'Réglages enregistrés.',
	);
	$msg  = \sanitize_key( (string) \wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap"><h1>Alertes interventions</h1>';
	if ( isset( $msgs[ $msg ] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html( $msgs[ $msg ] ) . '</p></div>';
	}
	echo '<p>Le site lit les flux des SDIS' . ( flux_presse() ? ', de la presse' : '' ) . ( '' !== $r['firms'] ? ' et les satellites NASA FIRMS' : '' ) . ' ' . ( 2 === $r['frequence'] ? 'toutes les 2 heures' : 'toutes les heures' ) . '. Les grosses interventions s’affichent dans un bandeau en haut du site, puis disparaissent seules. Rien n’est enregistré en article.</p>';

	echo '<h2>État</h2><p>';
	if ( ! $r['actif'] ) {
		echo '<strong>Collecte arrêtée.</strong> ';
	} elseif ( \is_array( $run ) ) {
		echo 'Collecte en cours : ' . (int) $run['pos'] . ' flux lus sur ' . \count( $run['file'] ) . '. ';
	}
	if ( \is_array( $bilan ) ) {
		echo 'Dernière collecte terminée à ' . \esc_html( \wp_date( 'G \h i', (int) $bilan['fin'] ) ) . ' en ' . (int) $bilan['duree'] . ' s : ' . (int) $bilan['flux'] . ' sources, ' . (int) $bilan['signaux'] . ' article(s) retenu(s), ' . (int) $bilan['alertes'] . ' nouvelle(s) alerte(s).';
	} else {
		echo 'Aucune collecte terminée pour l’instant.';
	}
	$next = \wp_next_scheduled( HOOK );
	if ( $next ) {
		echo ' Prochaine collecte à ' . \esc_html( \wp_date( 'G \h i', $next ) ) . '.';
	}
	echo '</p><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
	\wp_nonce_field( 'r18_alertes_collecte' );
	echo '<input type="hidden" name="action" value="r18_alertes_collecte"><button class="button">Lancer une collecte maintenant</button></form>';
	if ( \is_array( $bilan ) && ! empty( $bilan['erreurs'] ) ) {
		echo '<details style="margin-top:1em"><summary>' . \count( (array) $bilan['erreurs'] ) . ' source(s) illisible(s) lors de la dernière collecte</summary><ul style="list-style:disc;margin-left:2em">';
		foreach ( (array) $bilan['erreurs'] as $e ) {
			echo '<li>' . \esc_html( (string) $e ) . '</li>';
		}
		echo '</ul></details>';
	}

	echo '<h2>Alertes en mémoire</h2>';
	if ( ! $al ) {
		echo '<p>Aucune alerte pour l’instant.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Intervention</th><th>Moyens</th><th>Sources</th><th>État</th><th></th></tr></thead><tbody>';
		foreach ( $al as $id => $a ) {
			$etat = ! empty( $a['masque'] ) ? 'Masquée' : ( ! empty( $a['fin'] ) ? 'Terminée' : ( visible( $a, $now ) ? 'Affichée' : 'Expirée' ) );
			$src  = array();
			foreach ( $a['sources'] as $s ) {
				$src[] = '<a href="' . \esc_url( $s['url'] ) . '" rel="noopener">' . \esc_html( $s['nom'] ) . '</a>';
			}
			echo '<tr><td><strong>' . \esc_html( ( natures()[ $a['nature'] ][0] ?? 'Intervention' ) . ( '' !== lieu( $a ) ? ' · ' . lieu( $a ) : '' ) ) . '</strong><br>' . \esc_html( (string) $a['titre'] ) . '<br><small>Depuis ' . \esc_html( heure( (int) $a['debut'] ) ) . ', mise à jour ' . \esc_html( heure( (int) $a['maj'] ) ) . '</small></td>'
				. '<td>' . \esc_html( \implode( ', ', moyens_lisibles( (array) $a['moyens'] ) ) ) . '</td>'
				. '<td>' . \implode( '<br>', $src ) . '</td><td>' . \esc_html( $etat ) . '</td><td>';
			echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
			\wp_nonce_field( 'r18_alerte_masquer' );
			echo '<input type="hidden" name="action" value="r18_alerte_masquer"><input type="hidden" name="id" value="' . \esc_attr( (string) $id ) . '"><button class="button">' . ( empty( $a['masque'] ) ? 'Masquer cette alerte' : 'Réafficher' ) . '</button></form></td></tr>';
		}
		echo '</tbody></table>';
	}

	if ( \current_user_can( 'manage_options' ) ) {
		echo '<h2>Réglages</h2><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '">';
		\wp_nonce_field( 'r18_alertes_reglages' );
		echo '<input type="hidden" name="action" value="r18_alertes_reglages"><table class="form-table" role="presentation">'
			. '<tr><th scope="row">Collecte</th><td><label><input type="checkbox" name="actif" value="1"' . \checked( $r['actif'], true, false ) . '> Afficher les grosses interventions en cours</label></td></tr>'
			. '<tr><th scope="row"><label for="r18-al-f">Fréquence</label></th><td><select id="r18-al-f" name="frequence"><option value="1"' . \selected( $r['frequence'], 1, false ) . '>Toutes les heures</option><option value="2"' . \selected( $r['frequence'], 2, false ) . '>Toutes les 2 heures</option></select></td></tr>'
			. '<tr><th scope="row"><label for="r18-al-k">Clé NASA FIRMS</label></th><td><input id="r18-al-k" type="password" name="firms" class="regular-text" autocomplete="off" placeholder="' . ( '' !== $r['firms'] ? 'Clé enregistrée, laisse vide pour la garder' : 'Aucune clé' ) . '">'
			. ( '' !== $r['firms'] ? ' <label><input type="checkbox" name="firms_effacer" value="1"> Retirer la clé</label>' : '' )
			. '<p class="description">Clé gratuite (MAP_KEY) à demander sur <a href="https://firms.modaps.eosdis.nasa.gov/api/map_key/" rel="noopener">firms.modaps.eosdis.nasa.gov</a>. Sans clé, les satellites ne sont pas lus.</p></td></tr>'
			. '<tr><th scope="row"><label for="r18-al-p">Flux de presse</label></th><td><textarea id="r18-al-p" name="presse" rows="6" class="large-text code" placeholder="https://exemple.fr/rss/faits-divers.xml | Nom du journal | 13">' . \esc_textarea( $r['presse'] ) . '</textarea>'
			. '<p class="description">Un flux RSS par ligne : adresse, nom affiché, département (facultatif), séparés par « | ». Ajoute les flux dont le journal autorise la reprise du titre et du lien. Le robots.txt de chaque site est respecté.</p></td></tr>'
			. '</table>';
		\submit_button( 'Enregistrer les réglages' );
		echo '</form>';
	}
	echo '</div>';
}
