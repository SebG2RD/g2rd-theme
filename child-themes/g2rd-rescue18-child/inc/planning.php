<?php
/**
 * Planification centrale des tâches de fond (WP-Cron).
 *
 * Une seule source de vérité pour les horaires : chaque collecte a son
 * créneau, décalé des autres, pour ne jamais charger le serveur avec deux
 * collectes lourdes en même temps.
 *
 * - Horaires exprimés dans le fuseau du site (Réglages › Général).
 * - Minutes décalées (:07, :17, :23, :37, :43, :47) : rien ne tombe à l'heure
 *   pile, où l'hébergeur lance ses propres tâches et sauvegardes.
 * - Une collecte qui démarre pendant un cycle encore en cours est reportée de
 *   20 minutes (3 fois au plus), au lieu de s'empiler.
 * - Les gestionnaires rendent la main tout de suite : le travail tourne en
 *   arrière-plan, et WP-Cron passe à la tâche suivante sans attendre.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const PLAN_VERSION = '2';
const OPT_PLAN     = 'r18_plan_cron';

/**
 * Créneaux quotidiens, du plus lourd (au creux de la nuit) au plus léger.
 *
 * @return array<string,array{hook:string,args:array<int,string>,heure:string,rythme:string,lib:string,detail:string}>
 */
function plan(): array {
	return array(
		'casernes'  => array(
			'hook'   => Veille\CRON_HOOK,
			'args'   => array( 'casernes' ),
			'heure'  => '01:37',
			'rythme' => 'daily',
			'lib'    => 'Casernes (France et monde)',
			'detail' => 'OpenStreetMap, zones absentes ou de plus de 25 jours, 12 zones du monde par nuit',
		),
		'liens'     => array(
			'hook'   => 'r18_annales_liens',
			'args'   => array(),
			'heure'  => '02:47',
			'rythme' => 'daily',
			'lib'    => 'Contrôle des liens des sujets de concours',
			'detail' => '15 liens par nuit, chacun revérifié tous les 7 jours',
		),
		'documents' => array(
			'hook'   => Veille\CRON_HOOK,
			'args'   => array( 'documents' ),
			'heure'  => '03:17',
			'rythme' => 'daily',
			'lib'    => 'Veille documentaire',
			'detail' => 'DGSCGC, PNRS, ENSOSP, Journal officiel',
		),
		'annuaire'  => array(
			'hook'   => Veille\CRON_HOOK,
			'args'   => array( 'annuaire' ),
			'heure'  => '04:07',
			'rythme' => 'daily',
			'lib'    => 'Annuaire et recrutement',
			'detail' => 'Annuaire des SDIS, actualités des SDIS, offres d\'emploi',
		),
		'concours'  => array(
			'hook'   => 'r18_concours_sync',
			'args'   => array(),
			'heure'  => '05:43',
			'rythme' => 'daily',
			'lib'    => 'Concours et examens',
			'detail' => 'concours-territorial.fr, filière sapeurs-pompiers',
		),
	);
}

/**
 * Prochaine occurrence d'une heure locale du site.
 *
 * @param string $heure HH:MM.
 * @return int Horodatage UTC.
 */
function prochaine_heure( string $heure ): int {
	$t = new \DateTimeImmutable( 'today ' . $heure, \wp_timezone() );
	if ( $t->getTimestamp() <= \time() + 60 ) {
		$t = $t->modify( '+1 day' );
	}
	return $t->getTimestamp();
}

/**
 * Prochaine heure décalée à la minute donnée (collectes horaires).
 *
 * @param int $minute Minute (0-59).
 * @return int
 */
function prochaine_minute( int $minute ): int {
	$t = ( new \DateTimeImmutable( 'now', \wp_timezone() ) );
	$t = $t->setTime( (int) $t->format( 'G' ), $minute );
	if ( $t->getTimestamp() <= \time() + 60 ) {
		$t = $t->modify( '+1 hour' );
	}
	return $t->getTimestamp();
}

/**
 * (Re)planifie tout. Appelé à l'activation, au changement de version du
 * plan, et pour réparer un créneau disparu.
 *
 * @param bool $reset Effacer d'abord les créneaux existants.
 */
function planifier_tout( bool $reset = false ): void {
	if ( $reset ) {
		foreach ( \array_unique( \array_column( plan(), 'hook' ) ) as $hook ) {
			\wp_unschedule_hook( $hook );
		}
		\wp_clear_scheduled_hook( 'rescue18_cycle_quotidien' );
		if ( \function_exists( __NAMESPACE__ . '\Alertes\planifier' ) ) {
			Alertes\planifier();
		}
	}
	foreach ( plan() as $p ) {
		if ( ! \wp_next_scheduled( $p['hook'], $p['args'] ) ) {
			\wp_schedule_event( prochaine_heure( $p['heure'] ), $p['rythme'], $p['hook'], $p['args'] );
		}
	}
}

\add_action( 'after_switch_theme', static fn() => planifier_tout( true ) );
\add_action(
	'init',
	static function (): void {
		if ( PLAN_VERSION !== \get_option( OPT_PLAN ) ) {
			planifier_tout( true );
			\update_option( OPT_PLAN, PLAN_VERSION, true );
		}
	},
	20
);
\add_action( 'admin_init', static fn() => planifier_tout() );
\add_action(
	'switch_theme',
	static function (): void {
		foreach ( \array_unique( \array_column( plan(), 'hook' ) ) as $hook ) {
			\wp_unschedule_hook( $hook );
		}
		\delete_option( OPT_PLAN );
	}
);

/**
 * Une collecte lourde tourne-t-elle déjà ?
 *
 * @return bool
 */
function collecte_en_cours(): bool {
	\wp_cache_delete( Veille\OPT_CYCLE, 'options' );
	$c = Veille\cycle();
	return 'en_cours' === ( $c['statut'] ?? '' ) && \time() - (int) ( $c['maj'] ?? 0 ) < 20 * MINUTE_IN_SECONDS;
}

/**
 * Reporte une tâche de 20 minutes si une collecte tourne encore.
 * Trois reports au plus par jour et par tâche, puis la tâche passe.
 *
 * @param string            $hook Crochet.
 * @param array<int,string> $args Arguments.
 * @return bool Reportée.
 */
function reporter_si_occupe( string $hook, array $args = array() ): bool {
	if ( ! collecte_en_cours() ) {
		return false;
	}
	$cle = 'r18_report_' . \md5( $hook . \implode( ',', $args ) . \wp_date( 'Ymd' ) );
	$n   = (int) \get_transient( $cle );
	if ( $n >= 3 ) {
		return false;
	}
	\set_transient( $cle, $n + 1, DAY_IN_SECONDS );
	\wp_schedule_single_event( \time() + 20 * MINUTE_IN_SECONDS, $hook, $args );
	Veille\journal( 'cycle', 'info', 'Tâche « ' . $hook . ( $args ? ' · ' . \implode( ',', $args ) : '' ) . ' » reportée de 20 minutes : une collecte tourne encore.' );
	return true;
}

/**
 * Prochaine exécution d'une tâche du plan.
 *
 * @param string $id Clé du plan.
 * @return int
 */
function prochain_passage( string $id ): int {
	$p = plan()[ $id ] ?? null;
	return $p ? (int) \wp_next_scheduled( $p['hook'], $p['args'] ) : 0;
}

/**
 * Créneau du plan qui collecte une source de la veille.
 *
 * @param string $source Identifiant du connecteur.
 * @return string
 */
function groupe_de( string $source ): string {
	foreach ( Veille\GROUPES as $g => $sources ) {
		if ( \in_array( $source, $sources, true ) ) {
			return $g;
		}
	}
	return 'documents';
}

// Fuseau du site modifié : les créneaux sont recalculés sur la nouvelle heure locale.
foreach ( array( 'timezone_string', 'gmt_offset' ) as $r18_opt ) {
	\add_action( 'update_option_' . $r18_opt, static fn() => planifier_tout( true ) );
}
unset( $r18_opt );
