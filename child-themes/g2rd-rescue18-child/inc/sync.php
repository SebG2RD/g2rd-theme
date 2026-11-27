<?php
/**
 * Synchronisation documentaire : point d'entrée unique.
 *
 * Le moteur vit dans inc/veille/ : connecteurs DGSCGC, PNRS et ENSOSP,
 * orchestrateur quotidien (hook r18_daily_document_sync), traitement par
 * lots, verrou, journal et écran « Rescue 18 Automatisations ».
 *
 * upsert_fiche() reste disponible pour les extensions qui branchaient un
 * connecteur sur le filtre « rescue18_connecteurs » (ex. futur module
 * Légifrance / PISTE) : ces connecteurs tournent à la fin du cycle quotidien.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const SYNC_LOG  = 'rescue18_sync_log';

require_once __DIR__ . '/veille/normalisation.php';
require_once __DIR__ . '/veille/http.php';
require_once __DIR__ . '/veille/connecteurs.php';
require_once __DIR__ . '/veille/moteur.php';
require_once __DIR__ . '/veille/wordpress.php';
require_once __DIR__ . '/annuaire/territoires.php';
require_once __DIR__ . '/annuaire/connecteurs.php';
require_once __DIR__ . '/annuaire/wordpress.php';
require_once __DIR__ . '/annuaire/casernes.php';
require_once __DIR__ . '/alertes/wordpress.php';
require_once __DIR__ . '/concours.php';
require_once __DIR__ . '/lexique.php';
require_once __DIR__ . '/fiches.php';
require_once __DIR__ . '/annales.php';
require_once __DIR__ . '/planning.php';
require_once __DIR__ . '/seopress.php';
require_once __DIR__ . '/juridique/jorf.php';
require_once __DIR__ . '/juridique/piste.php';
if ( \is_admin() ) {
	require_once __DIR__ . '/veille/admin.php';
	require_once __DIR__ . '/annuaire/admin.php';
	require_once __DIR__ . '/admin-ui.php';
}

/**
 * Connecteurs tiers (filtre historique) : exécutés en fin de cycle.
 */
\add_action(
	'rescue18_cycle_termine',
	static function (): void {
		if ( \has_filter( 'rescue18_connecteurs' ) ) {
			run_cycle();
		}
	},
	5
);

/**
 * Horodatage de la dernière synchronisation terminée.
 *
 * @return int
 */
function last_sync(): int {
	$log = \get_option( SYNC_LOG, array() );
	return \is_array( $log ) && isset( $log[0]['fin'] ) ? (int) $log[0]['fin'] : 0;
}

/**
 * Exécute un cycle : chaque connecteur renvoie des fiches, insérées ou
 * mises à jour sans doublon. Verrou de 6 h contre les exécutions parallèles.
 *
 * @return array<string,mixed> Résumé du cycle.
 */
function run_cycle(): array {
	if ( \get_transient( 'rescue18_sync_lock' ) || ! \has_filter( 'rescue18_connecteurs' ) ) {
		return array( 'statut' => 'verrou' );
	}
	\set_transient( 'rescue18_sync_lock', 1, 6 * \HOUR_IN_SECONDS );

	$resume = array(
		'debut'      => \time(),
		'fin'        => 0,
		'connecteur' => array(),
		'crees'      => 0,
		'maj'        => 0,
		'identiques' => 0,
		'erreurs'    => array(),
	);

	/**
	 * Connecteurs de sources officielles : nom => callable(): array.
	 *
	 * @param array<string,callable> $connecteurs
	 */
	$connecteurs = (array) \apply_filters( 'rescue18_connecteurs', array() );
	foreach ( $connecteurs as $nom => $callback ) {
		if ( ! \is_callable( $callback ) ) {
			continue;
		}
		try {
			$fiches = (array) \call_user_func( $callback );
			$n      = 0;
			foreach ( $fiches as $fiche ) {
				$r = upsert_fiche( (array) $fiche );
				++$resume[ $r ];
				++$n;
			}
			$resume['connecteur'][ (string) $nom ] = $n;
		} catch ( \Throwable $e ) {
			$resume['erreurs'][] = \sanitize_text_field( $nom . ' : ' . $e->getMessage() );
		}
	}

	$resume['fin'] = \time();
	$log           = \get_option( SYNC_LOG, array() );
	$log           = \is_array( $log ) ? $log : array();
	\array_unshift( $log, $resume );
	\update_option( SYNC_LOG, \array_slice( $log, 0, 20 ), false );
	\delete_transient( 'rescue18_sync_lock' );
	return $resume;
}

/**
 * Insère ou met à jour une fiche issue d'un connecteur.
 *
 * Clés attendues : type (r18_document|r18_texte), source_id, titre,
 * extrait, meta (array), termes (array taxonomie => slugs), empreinte,
 * sensible (bool : changement à valider humainement).
 *
 * Règles : même identifiant + même empreinte = rien ; empreinte différente
 * = nouvelle version ajoutée à l'historique ; changement sensible
 * (abrogation, modification juridique) = statut « en validation » et
 * contenu laissé en brouillon jusqu'à relecture.
 *
 * @param array<string,mixed> $f Fiche normalisée.
 * @return string crees|maj|identiques
 */
function upsert_fiche( array $f ): string {
	$type = \in_array( $f['type'] ?? '', array( 'r18_document', 'r18_texte' ), true ) ? $f['type'] : 'r18_document';
	$sid  = \sanitize_text_field( (string) ( $f['source_id'] ?? '' ) );
	$hash = \sanitize_text_field( (string) ( $f['empreinte'] ?? '' ) );
	if ( '' === $sid ) {
		throw new \InvalidArgumentException( 'source_id manquant' );
	}

	$found = \get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- clé de dédoublonnage.
			'meta_value'     => $sid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- clé de dédoublonnage.
			'no_found_rows'  => true,
		)
	);
	$id    = (int) ( $found[0] ?? 0 );
	$today = \wp_date( 'Y-m-d' );

	if ( $id > 0 && '' !== $hash && meta( $id, 'r18_empreinte' ) === $hash ) {
		\update_post_meta( $id, 'r18_derniere_verif', $today );
		return 'identiques';
	}

	$sensible = ! empty( $f['sensible'] );
	$postarr  = array(
		'post_type'    => $type,
		'post_title'   => \sanitize_text_field( (string) ( $f['titre'] ?? $sid ) ),
		'post_excerpt' => \sanitize_textarea_field( (string) ( $f['extrait'] ?? '' ) ),
	);
	if ( $id > 0 ) {
		$postarr['ID'] = $id;
		\wp_update_post( \wp_slash( $postarr ) );
	} else {
		$postarr['post_status'] = $sensible ? 'draft' : 'publish';
		$id                     = (int) \wp_insert_post( \wp_slash( $postarr ) );
	}

	$defs = fields()[ $type ];
	foreach ( (array) ( $f['meta'] ?? array() ) as $key => $value ) {
		if ( isset( $defs[ $key ] ) ) {
			\update_post_meta( $id, $key, \wp_slash( sanitize_field( $value, $defs[ $key ][1], $defs[ $key ][2] ?? array() ) ) );
		}
	}
	\update_post_meta( $id, 'r18_source_id', $sid );
	\update_post_meta( $id, 'r18_derniere_verif', $today );
	if ( '' !== $hash ) {
		\update_post_meta( $id, 'r18_empreinte', $hash );
	}
	if ( 'r18_document' === $type ) {
		\update_post_meta( $id, 'r18_statut', $sensible ? 'en-validation' : ( empty( $found ) ? 'nouveau' : 'mis-a-jour' ) );
	} else {
		\update_post_meta( $id, 'r18_derniere_synchro', \wp_date( 'j F Y · H:i' ) );
		if ( $sensible ) {
			// Jamais d'état « Abrogé » ou « Modifié » sans validation humaine.
			\update_post_meta( $id, 'r18_etat', 'a-verifier' );
		}
	}
	foreach ( (array) ( $f['termes'] ?? array() ) as $tax => $slugs ) {
		if ( \taxonomy_exists( (string) $tax ) ) {
			\wp_set_object_terms( $id, \array_map( 'sanitize_title', (array) $slugs ), (string) $tax, true );
		}
	}
	return empty( $found ) ? 'crees' : 'maj';
}

/**
 * Ancien écran Outils › Veille Rescue 18 : redirigé vers les automatisations.
 */
\add_action(
	'admin_init',
	static function (): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- simple redirection de navigation.
		if ( isset( $_GET['page'] ) && 'rescue18-veille' === $_GET['page'] ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=rescue18-automatisations' ) );
			exit;
		}
	}
);
