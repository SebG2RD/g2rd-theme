<?php
/**
 * Veille documentaire : intégration WordPress.
 *
 * - Store_WP : fiches r18_document et articles « Actualités des organismes ».
 * - Orchestrateur quotidien unique (hook r18_daily_document_sync), traitement
 *   par lots (r18_document_sync_batch), verrou, reprise après interruption.
 * - Réglages, état par connecteur, journal, propositions de rapprochement.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

\defined( 'ABSPATH' ) || exit;

const CRON_HOOK  = 'r18_daily_document_sync';
const BATCH_HOOK = 'r18_document_sync_batch';
const OPT_REG    = 'r18_veille_reglages';
const OPT_ETAT   = 'r18_veille_etat';
const OPT_CYCLE  = 'r18_veille_cycle';
const OPT_JOUR   = 'r18_veille_journal';
const OPT_PROP   = 'r18_veille_propositions';
const OPT_DEC    = 'r18_veille_decisions';
const OPT_VALID  = 'r18_veille_validateurs';
const VERROU     = 'r18_veille_verrou';
const JETON      = 'r18_veille_jeton';
const OPT_EMP    = 'r18_veille_empreintes';

/**
 * Sources dont la page lue porte directement le contenu : inchangée, l'unité
 * est ignorée sans analyse ni lecture des pages de détail.
 */
const GARDE      = array( 'dgscgc', 'ensosp', 'annuaire' );

/**
 * Sources lourdes (une requête longue par zone) : en fin de file, et plafonnées
 * par cycle quand la synchronisation n'est pas ciblée. Le reste suit au cycle suivant.
 */
/**
 * Familles de sources, chacune collectée dans son propre créneau de nuit
 * (voir inc/planning.php). Une source absente de la liste rejoint « documents ».
 */
const GROUPES    = array(
	'casernes'  => array( 'casernes', 'casernes_monde' ),
	'documents' => array( 'dgscgc', 'pnrs', 'ensosp', 'jorf' ),
	'annuaire'  => array( 'annuaire', 'sdis', 'offres' ),
);

const LOURDES     = array( 'casernes_monde' => 12, 'casernes' => 20 );

/* -------------------------------------------------------------------------
 * Magasin WordPress
 * ---------------------------------------------------------------------- */

/**
 * Fiches WordPress.
 */
class Store_WP implements Store {

	/**
	 * Cache des clés par type.
	 *
	 * @var array<string,array<int,string>>
	 */
	private array $cache = array();

	public function par_ref( string $source, string $ref ): int {
		$ids = \get_posts(
			array(
				'post_type'      => array( 'r18_document', 'post' ),
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => 'r18_ref_' . \sanitize_key( $source ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- identifiant de source.
				'meta_value'     => $ref, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return (int) ( $ids[0] ?? 0 );
	}

	public function par_cle( string $cle ): int {
		$ids = \get_posts(
			array(
				'post_type'      => 'r18_document',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => 'r18_cle_doc', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- clé de rapprochement.
				'meta_value'     => $cle, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return (int) ( $ids[0] ?? 0 );
	}

	public function cles( string $type ): array {
		if ( ! isset( $this->cache[ $type ] ) ) {
			global $wpdb;
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- lecture groupée, mise en cache le temps du cycle.
				$wpdb->prepare(
					"SELECT m.post_id, m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
					 WHERE m.meta_key = 'r18_cle_doc' AND m.meta_value LIKE %s AND p.post_type = 'r18_document' AND p.post_status NOT IN ('trash','auto-draft')",
					$wpdb->esc_like( $type . '|' ) . '%'
				)
			);
			$this->cache[ $type ] = array();
			foreach ( (array) $rows as $r ) {
				$this->cache[ $type ][ (int) $r->post_id ] = (string) $r->meta_value;
			}
		}
		return $this->cache[ $type ];
	}

	public function lire( int $id ): array {
		$p    = \get_post( $id );
		$json = static function ( string $k ) use ( $id ): array {
			$v = \json_decode( (string) \get_post_meta( $id, $k, true ), true );
			return \is_array( $v ) ? $v : array();
		};
		return array(
			'titre'        => $p ? (string) $p->post_title : '',
			'extrait'      => $p ? (string) $p->post_excerpt : '',
			'sync_titre'   => (string) \get_post_meta( $id, 'r18_sync_titre', true ),
			'sync_extrait' => (string) \get_post_meta( $id, 'r18_sync_extrait', true ),
			'refs'         => $json( 'r18_refs' ),
			'versions'     => $json( 'r18_versions' ),
			'statut_post'  => $p ? (string) $p->post_status : '',
			'editeur'      => (string) \get_post_meta( $id, 'r18_editeur', true ),
		);
	}

	public function creer( array $doc ): int {
		$type    = 'actualite' === $doc['kind'] ? 'post' : 'r18_document';
		$postarr = array(
			'post_type'    => $type,
			'post_status'  => \in_array( $doc['statut'], array( 'publish', 'pending', 'draft' ), true ) ? $doc['statut'] : 'pending',
			'post_title'   => \sanitize_text_field( (string) $doc['titre'] ),
			'post_excerpt' => \sanitize_textarea_field( (string) $doc['extrait'] ),
			'post_content' => \wp_kses_post( (string) $doc['contenu'] ),
		);
		if ( ! empty( $doc['date'] ) && \preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $doc['date'] ) && $doc['date'] <= \wp_date( 'Y-m-d' ) ) {
			$postarr['post_date']     = $doc['date'] . ' 09:00:00';
			$postarr['post_date_gmt'] = \get_gmt_from_date( $postarr['post_date'] );
		}
		$id = \wp_insert_post( \wp_slash( $postarr ), true );
		if ( \is_wp_error( $id ) ) {
			throw new \RuntimeException( $id->get_error_message() );
		}
		$this->ecrire( (int) $id, array( 'meta' => $doc['meta'], 'termes' => $doc['termes'] ) );
		if ( isset( $doc['meta']['r18_cle_doc'] ) ) {
			[ $t ] = \explode( '|', (string) $doc['meta']['r18_cle_doc'] );
			$this->cache[ $t ][ (int) $id ] = (string) $doc['meta']['r18_cle_doc'];
		}
		return (int) $id;
	}

	public function ecrire( int $id, array $champs ): void {
		$up = array();
		if ( isset( $champs['titre'] ) ) {
			$up['post_title'] = \sanitize_text_field( (string) $champs['titre'] );
		}
		if ( isset( $champs['extrait'] ) ) {
			$up['post_excerpt'] = \sanitize_textarea_field( (string) $champs['extrait'] );
		}
		if ( $up ) {
			$up['ID'] = $id;
			\wp_update_post( \wp_slash( $up ) );
		}
		foreach ( (array) ( $champs['meta'] ?? array() ) as $k => $v ) {
			$v = \is_array( $v ) ? (string) \wp_json_encode( $v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : \sanitize_text_field( (string) $v );
			\update_post_meta( $id, \sanitize_key( (string) $k ), \wp_slash( $v ) );
		}
		foreach ( (array) ( $champs['termes'] ?? array() ) as $tax => $slugs ) {
			if ( ! \taxonomy_exists( (string) $tax ) || empty( $slugs ) ) {
				continue;
			}
			foreach ( (array) $slugs as $slug ) {
				assurer_terme( (string) $tax, (string) $slug );
			}
			\wp_set_object_terms( $id, \array_map( 'sanitize_title', (array) $slugs ), (string) $tax, true );
		}
	}

	public function decision( string $source, string $ref ) {
		$d = (array) \get_option( OPT_DEC, array() );
		return $d[ $source . ':' . $ref ] ?? null;
	}

	public function proposer( array $p ): void {
		$all = (array) \get_option( OPT_PROP, array() );
		$k   = $p['source'] . ':' . $p['ref'];
		if ( ! isset( $all[ $k ] ) ) {
			$all[ $k ] = $p;
			\update_option( OPT_PROP, $all, false );
		}
	}
}

/**
 * Crée un terme manquant avec un libellé lisible.
 *
 * @param string $tax  Taxonomie.
 * @param string $slug Slug.
 */
function assurer_terme( string $tax, string $slug ): void {
	if ( \term_exists( $slug, $tax ) ) {
		return;
	}
	$noms = array(
		'r18_type_doc'  => types(),
		'r18_organisme' => array(
			'dgscgc'      => 'DGSCGC',
			'ensosp-pnrs' => 'ENSOSP / PNRS',
		),
		'category'      => array( 'actualites-des-organismes' => 'Actualités des organismes' ),
	);
	$nom = $noms[ $tax ][ $slug ] ?? \ucfirst( \str_replace( '-', ' ', $slug ) );
	\wp_insert_term( $nom, $tax, array( 'slug' => $slug ) );
}

/**
 * Pose la clé de rapprochement sur les fiches existantes (import initial,
 * saisie manuelle) pour éviter tout doublon avec les sources.
 *
 * @param int $max Fiches traitées par appel.
 * @return int Nombre de fiches indexées.
 */
function indexer_cles( int $max = 300 ): int {
	$ids = \get_posts(
		array(
			'post_type'      => 'r18_document',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
			'posts_per_page' => $max,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexation ponctuelle.
				array(
					'key'     => 'r18_cle_doc',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		$terms = \wp_get_object_terms( (int) $id, 'r18_type_doc', array( 'fields' => 'slugs' ) );
		$titre = (string) \get_the_title( (int) $id );
		$type  = type_depuis_titre( $titre, \is_array( $terms ) && isset( $terms[0] ) ? (string) $terms[0] : 'autre' );
		\update_post_meta( (int) $id, 'r18_cle_doc', cle( $titre, $type ) );
	}
	return \count( $ids );
}

/* -------------------------------------------------------------------------
 * Réglages, état, journal
 * ---------------------------------------------------------------------- */

/**
 * Réglages d'un connecteur, valeurs par défaut comprises.
 *
 * @param string $id Connecteur.
 * @return array<string,mixed>
 */
function reglages( string $id ): array {
	$all    = (array) \get_option( OPT_REG, array() );
	$defaut = array(
		'actif'          => true,
		'publication'    => 'ensosp' === $id ? 'validation' : 'auto',
		'pages_txt'      => '',
		'mots'           => array(),
		'institutionnel' => false,
		'pages'          => 1,
	);
	return \array_merge( $defaut, (array) ( $all[ $id ] ?? array() ) );
}

/**
 * Ajoute une ligne au journal (300 dernières lignes).
 *
 * @param string $source  Source.
 * @param string $niveau  info|ok|alerte|erreur.
 * @param string $message Message.
 * @param int    $id      Fiche concernée.
 * @param bool   $simul   Simulation.
 */
function journal( string $source, string $niveau, string $message, int $id = 0, bool $simul = false ): void {
	$j = (array) \get_option( OPT_JOUR, array() );
	\array_unshift(
		$j,
		array(
			't'  => \time(),
			's'  => $source,
			'n'  => $niveau,
			'm'  => \sanitize_text_field( $message ),
			'id' => $id,
			'x'  => $simul,
		)
	);
	\update_option( OPT_JOUR, \array_slice( $j, 0, 300 ), false );
}

/**
 * État d'un connecteur.
 *
 * @param string $id Connecteur.
 * @return array<string,mixed>
 */
function etat( string $id ): array {
	$all = (array) \get_option( OPT_ETAT, array() );
	return (array) ( $all[ $id ] ?? array() );
}

/**
 * Nombre de références suivies par une source.
 *
 * @param string $id Connecteur.
 * @return int
 */
function references_suivies( string $id ): int {
	global $wpdb;
	$types = array(
		'annuaire' => 'r18_service',
		'sdis'     => 'r18_actu_sdis',
		'offres'   => 'r18_offre',
	);
	if ( isset( $types[ $id ] ) ) {
		return (int) ( \wp_count_posts( $types[ $id ] )->publish ?? 0 );
	}
	if ( 'casernes' === $id || 'casernes_monde' === $id ) {
		return (int) ( ( (array) \get_option( 'casernes' === $id ? 'r18_casernes' : 'r18_casernes_monde', array() ) )['n'] ?? 0 );
	}
	return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- compteur d'administration.
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_status NOT IN ('trash','auto-draft')",
			'r18_ref_' . $id
		)
	);
}

/* -------------------------------------------------------------------------
 * Orchestrateur
 * ---------------------------------------------------------------------- */

/**
 * Planification : un cycle par 24 h (nuit, 3 h 17), migration de l'ancien hook.
 */
function planifier(): void {
	\G2RD_Rescue18\planifier_tout();
}
\add_action(
	'switch_theme',
	static function (): void {
		\wp_clear_scheduled_hook( BATCH_HOOK );
	}
);
\add_action(
	CRON_HOOK,
	static function ( string $groupe = '' ): void {
		// Ancien créneau sans famille : remplacé par les créneaux du plan.
		if ( ! isset( GROUPES[ $groupe ] ) ) {
			return;
		}
		if ( \G2RD_Rescue18\reporter_si_occupe( CRON_HOOK, array( $groupe ) ) ) {
			return;
		}
		if ( demarrer( '', false, 'cron · ' . $groupe, GROUPES[ $groupe ] ) ) {
			relancer();
		}
	}
);
\add_action( BATCH_HOOK, __NAMESPACE__ . '\lot' );

/**
 * Relance le traitement en arrière-plan : requête interne non bloquante
 * vers admin-ajax, protégée par un jeton à usage unique. WP-Cron (BATCH_HOOK)
 * reste le filet de sécurité si la requête interne échoue.
 */
function relancer(): void {
	$jeton = \wp_generate_password( 32, false );
	\set_transient( JETON, $jeton, 10 * MINUTE_IN_SECONDS );
	\wp_remote_post(
		\admin_url( 'admin-ajax.php' ),
		array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => (bool) \apply_filters( 'https_local_ssl_verify', false ),
			'body'      => array(
				'action' => 'r18v_travail',
				'jeton'  => $jeton,
			),
		)
	);
}

/**
 * Travailleur d'arrière-plan : enchaîne les lots pendant 40 secondes,
 * puis se relance tant que le cycle n'est pas terminé.
 */
function travailler(): void {
	\ignore_user_abort( true );
	if ( \function_exists( 'set_time_limit' ) ) {
		@\set_time_limit( 400 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- travail borné.
	}
	$t0 = \microtime( true );
	do {
		if ( 'en_cours' !== ( cycle()['statut'] ?? '' ) || \get_transient( VERROU ) ) {
			return;
		}
		lot();
	} while ( \microtime( true ) - $t0 < 40 );
	if ( 'en_cours' === ( cycle()['statut'] ?? '' ) ) {
		relancer();
	}
}

$r18v_travail = static function (): void {
	$recu   = (string) \wp_unslash( $_POST['jeton'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- jeton à usage unique comparé en temps constant.
	$attend = (string) \get_transient( JETON );
	if ( '' === $recu || '' === $attend || ! \hash_equals( $attend, $recu ) ) {
		\wp_die( '', '', array( 'response' => 403 ) );
	}
	\delete_transient( JETON );
	travailler();
	\wp_die( '', '', array( 'response' => 200 ) );
};
\add_action( 'wp_ajax_nopriv_r18v_travail', $r18v_travail );
\add_action( 'wp_ajax_r18v_travail', $r18v_travail );

/**
 * Cycle en cours ou dernier cycle.
 *
 * @return array<string,mixed>
 */
function cycle(): array {
	return (array) \get_option( OPT_CYCLE, array() );
}

/**
 * Démarre un cycle (tous les connecteurs actifs, ou une source ciblée).
 *
 * @param string $cible      Source ciblée ou vide.
 * @param bool   $simulation Ne rien écrire.
 * @param string $origine    cron|manuel|cli.
 * @return bool Démarré (false si un cycle est déjà en cours).
 */
function demarrer( string $cible = '', bool $simulation = false, string $origine = 'manuel', array $sources = array() ): bool {
	$c = cycle();
	if ( 'en_cours' === ( $c['statut'] ?? '' ) ) {
		if ( \time() - (int) ( $c['maj'] ?? 0 ) < 20 * MINUTE_IN_SECONDS ) {
			journal( 'cycle', 'info', 'Cycle déjà en cours : aucune nouvelle collecte lancée (' . $origine . ').' );
			return false;
		}
		journal( 'cycle', 'alerte', 'Cycle interrompu détecté : reprise à l\'unité ' . ( (int) $c['pos'] + 1 ) . '.' );
		\delete_transient( VERROU );
		\wp_schedule_single_event( \time() + 5, BATCH_HOOK );
		relancer();
		return false;
	}
	$file = array();
	foreach ( connecteurs() as $id => $k ) {
		$reg = reglages( $id );
		if ( ( '' !== $cible && $cible !== $id ) || ( '' === $cible && empty( $reg['actif'] ) ) ) {
			continue;
		}
		$famille = 'documents';
		foreach ( GROUPES as $g => $ids ) {
			if ( \in_array( $id, $ids, true ) ) {
				$famille = $g;
			}
		}
		if ( $sources && ! \in_array( $id, $sources, true ) && ! ( 'documents' === $famille && \in_array( 'dgscgc', $sources, true ) ) ) {
			continue;
		}
		$reg['force'] = $cible === $id;
		$unites = $k->unites( $reg );
		if ( '' === $cible && isset( LOURDES[ $id ] ) ) {
			$unites = \array_slice( $unites, 0, LOURDES[ $id ], true );
		}
		foreach ( $unites as $i => $u ) {
			$file[] = array( $id, $i, $u );
		}
	}
	// Sources rapides d'abord, sources lourdes en dernier.
	$rang = \array_flip( \array_keys( LOURDES ) );
	\usort( $file, static fn( array $a, array $b ): int => ( isset( $rang[ $a[0] ] ) ? 1 : 0 ) <=> ( isset( $rang[ $b[0] ] ) ? 1 : 0 ) );
	$nouveau = array(
		'id'         => \wp_generate_uuid4(),
		'origine'    => $origine,
		'cible'      => $cible,
		'simulation' => $simulation,
		'debut'      => \time(),
		'maj'        => \time(),
		'statut'     => 'en_cours',
		'file'       => $file,
		'pos'        => 0,
		'stats'      => array(),
		'erreurs'    => array(),
		'arrets'     => array(),
		'requetes'   => array(),
		'methodes'   => array(),
	);
	// L'indexation des clés tourne au premier lot, en arrière-plan.
	$nouveau['a_indexer'] = ! $simulation;
	\update_option( OPT_CYCLE, $nouveau, false );
	journal( 'cycle', 'info', ( $simulation ? 'Simulation' : 'Cycle' ) . ' démarré (' . $origine . ( '' !== $cible ? ', source ' . $cible : '' ) . ') : ' . \count( $file ) . ' page(s) à lire.', 0, $simulation );
	return true;
}

/**
 * Traite des unités pendant 20 secondes au plus, puis replanifie la suite.
 */
function lot(): void {
	if ( \get_transient( VERROU ) ) {
		return;
	}
	\set_transient( VERROU, 1, 10 * MINUTE_IN_SECONDS );
	if ( \function_exists( 'set_time_limit' ) ) {
		@\set_time_limit( 360 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- une zone Overpass peut tenir 100 s + 3 miroirs.
	}
	$c = cycle();
	if ( 'en_cours' !== ( $c['statut'] ?? '' ) ) {
		\delete_transient( VERROU );
		return;
	}
	if ( ! empty( $c['a_indexer'] ) ) {
		indexer_cles();
		$c['a_indexer'] = false;
		$c['maj']       = \time();
		\update_option( OPT_CYCLE, $c, false );
	}
	// Filet de sécurité : si PHP coupe ce lot en plein milieu, WP-Cron le relance.
	$filet = \time() + 8 * MINUTE_IN_SECONDS;
	\wp_schedule_single_event( $filet, BATCH_HOOK );
	$t0     = \microtime( true );
	$store  = new Store_WP();
	$valid  = (array) \get_option( OPT_VALID, array() );
	$emps   = (array) \get_option( OPT_EMP, array() );
	$total  = \count( $c['file'] );
	while ( $c['pos'] < $total && \microtime( true ) - $t0 < 20 ) {
		if ( arrete() ) {
			\wp_unschedule_event( $filet, BATCH_HOOK );
			\delete_transient( VERROU );
			return;
		}
		[ $src, $i ] = $c['file'][ $c['pos'] ];
		$unite       = (array) ( $c['file'][ $c['pos'] ][2] ?? array() );
		++$c['pos'];
		$c['maj'] = \time();
		if ( isset( $c['arrets'][ $src ] ) ) {
			\update_option( OPT_CYCLE, $c, false );
			continue;
		}
		$k     = connecteurs()[ $src ];
		$reg   = reglages( $src );
		if ( empty( $unite ) ) {
			continue;
		}
		$deja = (int) ( $c['requetes'][ $src ] ?? 0 );
		$http = new Http( new Transport_WP(), $k->domaines(), $k->pause(), \max( 1, $k->plafond() - $deja ) );
		$http->validateurs = $c['simulation'] ? array() : (array) ( $valid[ $src ] ?? array() );
		// Vérification préalable : empreintes du cycle précédent, invalidées si les réglages changent.
		$sig           = \md5( (string) \wp_json_encode( \array_diff_key( $reg, array( 'force' => 1 ) ) ) );
		$http->connues = ( $emps[ $src ]['sig'] ?? '' ) === $sig ? (array) ( $emps[ $src ]['h'] ?? array() ) : array();
		$http->garde   = \in_array( $src, GARDE, true ) ? (string) ( $unite['url'] ?? '' ) : '';
		$ctx  = new Contexte( $store, (string) $reg['publication'], (bool) $c['simulation'], \wp_date( 'Y-m-d' ) );
		$ctx->gestionnaires = (array) \apply_filters( 'r18_veille_gestionnaires', array() );
		try {
			$res = executer_unite( $k, $unite, $http, $reg, $ctx );
			foreach ( $res['stats'] as $a => $n ) {
				$c['stats'][ $src ][ $a ] = (int) ( $c['stats'][ $src ][ $a ] ?? 0 ) + $n;
			}
			$c['methodes'][ $src ][ $res['methode'] ] = true;
			foreach ( $res['lignes'] as $l ) {
				journal( $src, 'erreur' === $l['action'] ? 'erreur' : ( 'a_valider' === $l['action'] ? 'alerte' : 'ok' ), libelle_action( $l['action'] ) . ' · ' . $l['titre'] . ' · ' . $l['note'], (int) $l['id'], (bool) $c['simulation'] );
			}
			journal( $src, 'info', $unite['url'] . ' : ' . \implode( ' ', $res['notes'] ), 0, (bool) $c['simulation'] );
			// Empreinte enregistrée seulement si tout a été traité : une erreur sera retentée.
			if ( ! $c['simulation'] && empty( $res['reste'] ) && empty( $res['stats']['erreur'] ) && $http->vues ) {
				$emps[ $src ] = array(
					'sig' => $sig,
					'h'   => \array_slice( \array_merge( (array) ( $http->connues ), $http->vues ), -400, null, true ),
				);
				\update_option( OPT_EMP, $emps, false );
			}
		} catch ( Inchange $e ) {
			$c['inchanges']          = (int) ( $c['inchanges'] ?? 0 ) + 1;
			journal( $src, 'info', $unite['url'] . ' : ' . $e->getMessage() . ' Aucune nouveauté, page ignorée.', 0, (bool) $c['simulation'] );
		} catch ( Erreur_Acces $e ) {
			$c['erreurs'][ $src ][] = $e->getMessage();
			journal( $src, 'erreur', $e->getMessage(), 0, (bool) $c['simulation'] );
			if ( \in_array( $e->categorie, array( 'bloque', 'limite' ), true ) ) {
				$c['arrets'][ $src ] = $e->categorie;
				journal( $src, 'alerte', 'Source arrêtée pour ce cycle. Les fiches existantes restent en ligne.', 0, (bool) $c['simulation'] );
			}
		} catch ( \Throwable $e ) {
			$c['erreurs'][ $src ][] = $e->getMessage();
			journal( $src, 'erreur', 'Erreur inattendue : ' . $e->getMessage(), 0, (bool) $c['simulation'] );
		}
		$c['requetes'][ $src ] = $deja + $http->requetes;
		if ( ! $c['simulation'] ) {
			$valid[ $src ] = \array_slice( $http->validateurs, -60, null, true );
			\update_option( OPT_VALID, $valid, false );
		}
		if ( arrete() ) {
			\wp_unschedule_event( $filet, BATCH_HOOK );
			\delete_transient( VERROU );
			return;
		}
		\update_option( OPT_CYCLE, $c, false );
	}
	\wp_unschedule_event( $filet, BATCH_HOOK );
	if ( $c['pos'] >= $total ) {
		terminer( $c );
	} else {
		\wp_schedule_single_event( \time() + 30, BATCH_HOOK );
	}
	\delete_transient( VERROU );
}

/**
 * Le cycle a-t-il été arrêté par un autre processus ? Lecture hors cache.
 *
 * @return bool
 */
function arrete(): bool {
	\wp_cache_delete( OPT_CYCLE, 'options' );
	return 'en_cours' !== ( cycle()['statut'] ?? '' );
}

/**
 * Arrêt manuel : le cycle se termine sur ce qui est déjà traité.
 *
 * @return bool Un cycle était en cours.
 */
function arreter(): bool {
	\wp_cache_delete( OPT_CYCLE, 'options' );
	$c = cycle();
	if ( 'en_cours' !== ( $c['statut'] ?? '' ) ) {
		return false;
	}
	journal( 'cycle', 'alerte', 'Cycle arrêté à la main après ' . (int) $c['pos'] . ' page(s) sur ' . \count( (array) $c['file'] ) . '. La suite reprendra au prochain cycle.', 0, (bool) $c['simulation'] );
	terminer( $c );
	\delete_transient( VERROU );
	return true;
}

/**
 * Fin de cycle : état par connecteur, résumé, journal.
 *
 * @param array<string,mixed> $c Cycle.
 */
function terminer( array $c ): void {
	$c['statut'] = 'termine';
	$c['fin']    = \time();
	\update_option( OPT_CYCLE, $c, false );
	$etats = (array) \get_option( OPT_ETAT, array() );
	$srcs  = array();
	foreach ( $c['file'] as [ $s ] ) {
		$srcs[ $s ] = true;
	}
	foreach ( \array_keys( $srcs ) as $s ) {
		$st   = (array) ( $c['stats'][ $s ] ?? array() );
		$errs = (array) ( $c['erreurs'][ $s ] ?? array() );
		$arr  = (string) ( $c['arrets'][ $s ] ?? '' );
		$lus  = \array_sum( $st );
		if ( 'bloque' === $arr ) {
			$res = 'bloque';
		} elseif ( ! empty( $errs ) && 0 === $lus ) {
			$res = 'erreur';
		} elseif ( ! empty( $errs ) ) {
			$res = 'partiel';
		} else {
			$res = 'ok';
		}
		$ligne = array(
			'verif'      => \time(),
			'resultat'   => $res,
			'simulation' => (bool) $c['simulation'],
			'message'    => $errs ? (string) \end( $errs ) : '',
			'nouveaux'   => (int) ( $st['nouveau'] ?? 0 ),
			'actualises' => (int) ( $st['version'] ?? 0 ) + (int) ( $st['rapproche'] ?? 0 ),
			'identiques' => (int) ( $st['identique'] ?? 0 ),
			'a_valider'  => (int) ( $st['a_valider'] ?? 0 ),
			'ecartes'    => (int) ( $st['ecarte'] ?? 0 ),
			'erreurs'    => \count( $errs ) + (int) ( $st['erreur'] ?? 0 ),
			'requetes'   => (int) ( $c['requetes'][ $s ] ?? 0 ),
			'methode'    => \implode( ' + ', \array_keys( (array) ( $c['methodes'][ $s ] ?? array() ) ) ),
		);
		if ( $c['simulation'] ) {
			$etats[ $s ]['simu'] = $ligne;
		} else {
			$etats[ $s ] = \array_merge( (array) ( $etats[ $s ] ?? array() ), $ligne );
		}
	}
	\update_option( OPT_ETAT, $etats, false );
	\do_action( 'r18_veille_cycle_fini', $c );
	$inch = (int) ( $c['inchanges'] ?? 0 );
	journal( 'cycle', 'info', ( $c['simulation'] ? 'Simulation' : 'Cycle' ) . ' terminé en ' . \human_time_diff( (int) $c['debut'], (int) $c['fin'] ) . ( $inch ? ' : ' . $inch . ' page(s) sans nouveauté ignorée(s), ' . \max( 0, \count( $c['file'] ) - $inch ) . ' traitée(s).' : '.' ), 0, (bool) $c['simulation'] );
	if ( ! $c['simulation'] ) {
		\do_action( 'rescue18_cycle_termine', $c );
	}
}

/**
 * Libellé d'une action.
 *
 * @param string $a Action.
 * @return string
 */
function libelle_action( string $a ): string {
	return array(
		'nouveau'   => 'Nouveau',
		'version'   => 'Actualisé',
		'rapproche' => 'Source rattachée',
		'identique' => 'Identique',
		'a_valider' => 'À valider',
		'ecarte'    => 'Écarté',
		'erreur'    => 'Erreur',
	)[ $a ] ?? $a;
}

/**
 * Test de connexion d'un connecteur : lecture réelle de la première page,
 * en simulation (aucune écriture).
 *
 * @param string $id Connecteur.
 * @return array{ok:bool,lignes:string[]}
 */
function tester( string $id ): array {
	$k   = connecteurs()[ $id ] ?? null;
	if ( null === $k ) {
		return array( 'ok' => false, 'lignes' => array( 'Connecteur inconnu.' ) );
	}
	$reg          = reglages( $id );
	$reg['force'] = true;
	$unite        = $k->unites( $reg )[0] ?? null;
	if ( null === $unite ) {
		return array( 'ok' => false, 'lignes' => array( 'Aucune page à lire pour cette source.' ) );
	}
	$http  = new Http( new Transport_WP(), $k->domaines(), $k->pause(), 8 );
	$t0    = \microtime( true );
	try {
		$ctx                = new Contexte( new Store_WP(), 'auto', true, \wp_date( 'Y-m-d' ) );
		$ctx->gestionnaires = (array) \apply_filters( 'r18_veille_gestionnaires', array() );
		$res                = executer_unite( $k, $unite, $http, $reg, $ctx );
		$lignes = array(
			'Connexion réussie : ' . $unite['url'],
			'Méthode : ' . $res['methode'] . ' · ' . $http->requetes . ' requête(s) · ' . \round( \microtime( true ) - $t0, 1 ) . ' s',
		);
		$lignes = \array_merge( $lignes, $res['notes'] );
		$lignes[] = 'Références lues : ' . \array_sum( $res['stats'] ) . ' (nouvelles ' . (int) ( $res['stats']['nouveau'] ?? 0 ) . ', déjà suivies ' . ( (int) ( $res['stats']['identique'] ?? 0 ) + (int) ( $res['stats']['version'] ?? 0 ) + (int) ( $res['stats']['rapproche'] ?? 0 ) ) . ', écartées ' . (int) ( $res['stats']['ecarte'] ?? 0 ) . ').';
		journal( $id, 'ok', 'Test de connexion réussi (' . $res['methode'] . ').' );
		return array( 'ok' => true, 'lignes' => $lignes );
	} catch ( \Throwable $e ) {
		journal( $id, 'erreur', 'Test de connexion échoué : ' . $e->getMessage() );
		return array( 'ok' => false, 'lignes' => array( 'Échec : ' . $e->getMessage(), $http->requetes . ' requête(s) émise(s).' ) );
	}
}

/**
 * Applique une décision sur une proposition de rapprochement.
 *
 * @param string $cle      source:ref.
 * @param string $decision rattacher|distinct|ignore.
 * @return string Message.
 */
function decider( string $cle, string $decision ): string {
	$props = (array) \get_option( OPT_PROP, array() );
	$p     = $props[ $cle ] ?? null;
	if ( null === $p ) {
		return 'Proposition introuvable.';
	}
	$dec         = (array) \get_option( OPT_DEC, array() );
	$dec[ $cle ] = 'rattacher' === $decision ? (int) $p['candidat'] : ( 'distinct' === $decision ? 'distinct' : 'ignore' );
	\update_option( OPT_DEC, $dec, false );
	unset( $props[ $cle ] );
	\update_option( OPT_PROP, $props, false );
	if ( 'ignore' === $decision ) {
		journal( (string) $p['source'], 'info', 'Proposition ignorée : ' . $p['item']['titre'] );
		return 'Référence ignorée.';
	}
	$reg = reglages( (string) $p['source'] );
	$res = traiter( (array) $p['item'], new Contexte( new Store_WP(), (string) $reg['publication'], false, \wp_date( 'Y-m-d' ) ) );
	journal( (string) $p['source'], 'ok', libelle_action( $res['action'] ) . ' · ' . $p['item']['titre'] . ' · décision éditoriale.', $res['id'] );
	return libelle_action( $res['action'] ) . ' : ' . $p['item']['titre'];
}

/**
 * Import assisté DGSCGC : HTML d'une page ouverte dans le navigateur.
 *
 * @param string $html       Code source collé.
 * @param string $url        Page DGSCGC d'origine.
 * @param bool   $simulation Ne rien écrire.
 * @return array<string,int>
 */
function import_assiste( string $html, string $url, bool $simulation ): array {
	$pages = pages_reglees( reglages( 'dgscgc' ), pages_dgscgc_defaut() );
	$type  = $pages[ $url ] ?? 'auto';
	$items = analyser_dgscgc( \substr( $html, 0, 3145728 ), $url, $type );
	$reg   = reglages( 'dgscgc' );
	$ctx   = new Contexte( new Store_WP(), (string) $reg['publication'], $simulation, \wp_date( 'Y-m-d' ) );
	$stats = array( 'lus' => \count( $items ) );
	if ( ! $simulation ) {
		indexer_cles();
	}
	foreach ( $items as $r ) {
		$r['methode'] = 'import assisté';
		$res          = traiter( $r, $ctx );
		$stats[ $res['action'] ] = (int) ( $stats[ $res['action'] ] ?? 0 ) + 1;
		if ( 'identique' !== $res['action'] ) {
			journal( 'dgscgc', 'ok', libelle_action( $res['action'] ) . ' · ' . $r['titre'] . ' · import assisté.', $res['id'], $simulation );
		}
	}
	journal( 'dgscgc', empty( $items ) ? 'erreur' : 'info', 'Import assisté de ' . $url . ' : ' . \count( $items ) . ' document(s) lus.', 0, $simulation );
	return $stats;
}
