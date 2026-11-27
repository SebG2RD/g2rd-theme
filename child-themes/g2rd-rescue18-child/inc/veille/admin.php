<?php
/**
 * Veille documentaire : écran « Rescue 18 Automatisations ».
 *
 * Tableau de bord par source (état, compteurs, test, synchronisation
 * ciblée, simulation, réglages), progression du cycle en direct,
 * rapprochements à valider, import assisté DGSCGC, journal filtrable,
 * planification et cron de l'hébergeur.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Veille;

\defined( 'ABSPATH' ) || exit;

const PAGE = 'rescue18-automatisations';

\add_action(
	'admin_menu',
	static function (): void {
		$n    = \count( (array) \get_option( OPT_PROP, array() ) );
		$bulle = $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '';
		\add_menu_page( 'Rescue 18 · Automatisations', 'Automatisations' . $bulle, 'manage_options', PAGE, __NAMESPACE__ . '\rendu', 'dashicons-update-alt', 27 );
	}
);

\add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( 'toplevel_page_' . PAGE !== $hook ) {
			return;
		}
		$css = \G2RD_Rescue18\asset( 'assets/css/veille-admin.css' );
		if ( $css ) {
			\wp_enqueue_style( 'r18-veille', $css[0], array(), $css[1] );
		}
		charger_js();
	}
);

/**
 * Script de l'écran (synchronisation asynchrone, test, journal).
 * Réutilisé par le tableau de bord de l'annuaire.
 */
function charger_js(): void {
	$js = \G2RD_Rescue18\asset( 'assets/js/veille-admin.js' );
	if ( ! $js || \wp_script_is( 'r18-veille', 'enqueued' ) ) {
		return;
	}
	\wp_enqueue_script( 'r18-veille', $js[0], array(), $js[1], true );
	\wp_localize_script(
		'r18-veille',
		'r18Veille',
		array(
			'ajax'  => \admin_url( 'admin-ajax.php' ),
			'nonce' => \wp_create_nonce( 'r18_veille' ),
		)
	);
}

/**
 * URL de l'écran.
 *
 * @param array<string,string> $args Paramètres.
 * @return string
 */
function url_ecran( array $args = array() ): string {
	return \add_query_arg( \array_merge( array( 'page' => PAGE ), $args ), \admin_url( 'admin.php' ) );
}

/**
 * Contrôle commun des actions.
 *
 * @param string $action Nonce.
 */
function garde( string $action ): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		\wp_die( 'Action non autorisée.', '', array( 'response' => 403 ) );
	}
	\check_admin_referer( $action );
}

/* Actions ---------------------------------------------------------------- */

\add_action(
	'admin_post_r18v_sync',
	static function (): void {
		garde( 'r18v_sync' );
		$cible = \sanitize_key( \wp_unslash( $_POST['cible'] ?? '' ) );
		$cible = isset( connecteurs()[ $cible ] ) ? $cible : '';
		$simu  = ! empty( $_POST['simulation'] );
		$ok    = demarrer( $cible, $simu, 'manuel' );
		if ( $ok ) {
			relancer();
		}
		\wp_safe_redirect( url_ecran( array( 'r18msg' => $ok ? ( $simu ? 'simulation' : 'lance' ) : 'occupe' ) ) );
		exit;
	}
);

\add_action(
	'admin_post_r18v_relire',
	static function (): void {
		garde( 'r18v_relire' );
		\delete_option( OPT_EMP );
		\delete_option( OPT_VALID );
		journal( 'cycle', 'info', 'Empreintes effacées : la prochaine synchronisation relira tout.' );
		\set_transient( 'r18v_flash_' . \get_current_user_id(), 'Empreintes effacées : la prochaine synchronisation relira toutes les pages.', 60 );
		\wp_safe_redirect( url_ecran( array( 'r18msg' => 'flash' ) ) );
		exit;
	}
);

\add_action(
	'admin_post_r18v_reglages',
	static function (): void {
		garde( 'r18v_reglages' );
		$id = \sanitize_key( \wp_unslash( $_POST['connecteur'] ?? '' ) );
		if ( ! isset( connecteurs()[ $id ] ) ) {
			\wp_die( 'Connecteur inconnu.' );
		}
		$in            = \wp_unslash( $_POST );
		$all           = (array) \get_option( OPT_REG, array() );
		$pages         = array();
		foreach ( \preg_split( '/\R/u', (string) ( $in['pages_txt'] ?? '' ) ) as $line ) {
			$p = \array_map( 'trim', \explode( '|', $line, 2 ) );
			$u = \esc_url_raw( $p[0] );
			if ( '' !== $u && \in_array( (string) \wp_parse_url( $u, PHP_URL_HOST ), connecteurs()[ $id ]->domaines(), true ) ) {
				$pages[] = $u . ' | ' . \sanitize_key( $p[1] ?? 'auto' );
			}
		}
		$all[ $id ] = array(
			'actif'          => ! empty( $in['actif'] ),
			'publication'    => 'validation' === ( $in['publication'] ?? '' ) ? 'validation' : 'auto',
			'pages_txt'      => \implode( "\n", $pages ),
			'mots'           => \array_values( \array_filter( \array_map( 'sanitize_text_field', \preg_split( '/[\n,]+/u', (string) ( $in['mots'] ?? '' ) ) ) ) ),
			'institutionnel' => ! empty( $in['institutionnel'] ),
			'pages'          => \max( 1, \min( 5, (int) ( $in['pages'] ?? 1 ) ) ),
			'exclus'         => \array_values( \array_filter( \array_map( 'sanitize_text_field', \preg_split( '/[\n,]+/u', (string) ( $in['exclus'] ?? '' ) ) ) ) ),
			'depuis'         => \preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['depuis'] ?? '' ) ) ? (string) $in['depuis'] : '',
		);
		\update_option( OPT_REG, $all, false );
		if ( 'jorf' === $id ) {
			$n = \G2RD_Rescue18\Juridique\refiltrer();
			if ( $n > 0 ) {
				journal( 'jorf', 'info', $n . ' texte(s) ne passent plus le filtre : repassés en brouillon.' );
			}
		}
		journal( $id, 'info', 'Réglages enregistrés (' . ( $all[ $id ]['actif'] ? 'actif' : 'désactivé' ) . ', publication ' . ( 'auto' === $all[ $id ]['publication'] ? 'automatique' : 'soumise à validation' ) . ').' );
		\wp_safe_redirect( url_ecran( array( 'r18msg' => 'reglages' ) ) . '#src-' . $id );
		exit;
	}
);

\add_action(
	'admin_post_r18v_decider',
	static function (): void {
		garde( 'r18v_decider' );
		$cle = \sanitize_text_field( \wp_unslash( $_POST['cle'] ?? '' ) );
		$dec = \sanitize_key( \wp_unslash( $_POST['decision'] ?? '' ) );
		$msg = decider( $cle, \in_array( $dec, array( 'rattacher', 'distinct', 'ignore' ), true ) ? $dec : 'ignore' );
		\set_transient( 'r18v_flash_' . \get_current_user_id(), $msg, 60 );
		\wp_safe_redirect( url_ecran( array( 'r18msg' => 'flash' ) ) . '#a-valider' );
		exit;
	}
);

\add_action(
	'admin_post_r18v_import',
	static function (): void {
		garde( 'r18v_import' );
		$url  = \esc_url_raw( \wp_unslash( $_POST['page_url'] ?? '' ) );
		$html = (string) \wp_unslash( $_POST['html'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- HTML analysé par DOMDocument, jamais affiché.
		$simu = ! empty( $_POST['simulation'] );
		if ( ! isset( pages_reglees( reglages( 'dgscgc' ), pages_dgscgc_defaut() )[ $url ] ) || '' === \trim( $html ) ) {
			\set_transient( 'r18v_flash_' . \get_current_user_id(), 'Import assisté : choisis la page d\'origine et colle son code source.', 60 );
		} else {
			$s = import_assiste( $html, $url, $simu );
			\set_transient(
				'r18v_flash_' . \get_current_user_id(),
				( $simu ? 'Simulation de l\'import : ' : 'Import assisté terminé : ' ) . $s['lus'] . ' document(s) lus, ' . (int) ( $s['nouveau'] ?? 0 ) . ' nouveau(x), ' . ( (int) ( $s['version'] ?? 0 ) + (int) ( $s['rapproche'] ?? 0 ) ) . ' actualisé(s), ' . (int) ( $s['a_valider'] ?? 0 ) . ' à valider.',
				60
			);
		}
		\wp_safe_redirect( url_ecran( array( 'r18msg' => 'flash' ) ) . '#src-dgscgc' );
		exit;
	}
);

/* AJAX : test de connexion, progression, avancement du lot ------------------ */

\add_action(
	'wp_ajax_r18v_tester',
	static function (): void {
		\check_ajax_referer( 'r18_veille' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'lignes' => array( 'Action non autorisée.' ) ), 403 );
		}
		$r = tester( \sanitize_key( \wp_unslash( $_POST['connecteur'] ?? '' ) ) );
		$r['ok'] ? \wp_send_json_success( $r ) : \wp_send_json_error( $r );
	}
);

/**
 * État de progression du cycle, pour l'interface.
 *
 * @return array<string,mixed>
 */
function progression(): array {
	$c     = cycle();
	$total = \count( (array) ( $c['file'] ?? array() ) );
	$pos   = (int) ( $c['pos'] ?? 0 );
	$src   = $pos < $total ? (string) ( $c['file'][ $pos ][0] ?? '' ) : '';
	$k     = connecteurs();
	return array(
		'statut'     => (string) ( $c['statut'] ?? '' ),
		'pos'        => $pos,
		'total'      => $total,
		'simulation' => ! empty( $c['simulation'] ),
		'inchanges'  => (int) ( $c['inchanges'] ?? 0 ),
		'source'     => isset( $k[ $src ] ) ? $k[ $src ]->nom() : '',
	);
}

\add_action(
	'wp_ajax_r18v_lancer',
	static function (): void {
		\check_ajax_referer( 'r18_veille' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => 'Action non autorisée.' ), 403 );
		}
		$cible = \sanitize_key( \wp_unslash( $_POST['cible'] ?? '' ) );
		$cible = isset( connecteurs()[ $cible ] ) ? $cible : '';
		$simu  = ! empty( $_POST['simulation'] );
		$ok    = demarrer( $cible, $simu, 'manuel' );
		if ( $ok ) {
			relancer();
		}
		$d = progression();
		if ( $ok ) {
			$d['message'] = $simu ? 'Simulation lancée en arrière-plan : rien n\'est écrit.' : 'Synchronisation lancée en arrière-plan. Tu peux continuer à travailler.';
		} else {
			$d['message'] = 'en_cours' === $d['statut'] ? 'Un cycle est déjà en cours : la progression s\'affiche ci-dessous.' : 'Aucune collecte lancée.';
		}
		\wp_send_json_success( $d );
	}
);

\add_action(
	'wp_ajax_r18v_arreter',
	static function (): void {
		\check_ajax_referer( 'r18_veille' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => 'Action non autorisée.' ), 403 );
		}
		$ok = arreter();
		\wp_send_json_success( array( 'message' => $ok ? 'Cycle arrêté. Le travail déjà fait est conservé.' : 'Aucun cycle en cours.' ) );
	}
);

\add_action(
	'wp_ajax_r18v_progres',
	static function (): void {
		\check_ajax_referer( 'r18_veille' );
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( null, 403 );
		}
		// Ne traite jamais de lot ici : relance seulement un travailleur à l'arrêt.
		$c = cycle();
		if ( 'en_cours' === ( $c['statut'] ?? '' ) ) {
			$age    = \time() - (int) ( $c['maj'] ?? 0 );
			$verrou = (bool) \get_transient( VERROU );
			if ( $verrou && $age > 420 ) {
				\delete_transient( VERROU );
				$verrou = false;
			}
			if ( ! $verrou && $age > 15 && ! \get_transient( 'r18v_relance' ) ) {
				\set_transient( 'r18v_relance', 1, 30 );
				relancer();
			}
		}
		\wp_send_json_success( progression() );
	}
);

/* Rendu ------------------------------------------------------------------ */

/**
 * Badge d'état.
 *
 * @param array<string,mixed> $e     État.
 * @param bool                $actif Connecteur actif.
 * @return string
 */
function badge( array $e, bool $actif ): string {
	if ( ! $actif ) {
		return '<span class="r18v-badge is-off">Désactivé</span>';
	}
	$r   = (string) ( $e['resultat'] ?? '' );
	$map = array(
		'ok'      => array( 'is-ok', 'Opérationnel' ),
		'partiel' => array( 'is-warn', 'Partiel' ),
		'bloque'  => array( 'is-block', 'Bloqué par la source' ),
		'erreur'  => array( 'is-err', 'En erreur' ),
	);
	[ $cls, $txt ] = $map[ $r ] ?? array( 'is-new', 'Jamais lancé' );
	return '<span class="r18v-badge ' . $cls . '">' . \esc_html( $txt ) . '</span>';
}

/**
 * Date relative lisible.
 *
 * @param int $t Horodatage.
 * @return string
 */
function quand( int $t ): string {
	if ( $t <= 0 ) {
		return 'jamais';
	}
	$d = \time() - $t;
	return $d >= 0 ? 'il y a ' . \human_time_diff( $t ) : 'dans ' . \human_time_diff( \time(), $t );
}

/**
 * Bouton de formulaire d'action.
 *
 * @param string               $action  Action admin-post.
 * @param string               $label   Libellé.
 * @param array<string,string> $champs  Champs cachés.
 * @param string               $classe  Classe.
 * @param string               $confirm Message de confirmation.
 * @return string
 */
function bouton( string $action, string $label, array $champs = array(), string $classe = 'r18v-btn', string $confirm = '' ): string {
	$h = '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18v-inline"' . ( 'r18v_sync' === $action ? ' data-r18v-sync' : '' ) . ( '' !== $confirm ? ' data-confirm="' . \esc_attr( $confirm ) . '"' : '' ) . '>'
		. \wp_nonce_field( $action, '_wpnonce', true, false ) . '<input type="hidden" name="action" value="' . \esc_attr( $action ) . '">';
	foreach ( $champs as $k => $v ) {
		$h .= '<input type="hidden" name="' . \esc_attr( $k ) . '" value="' . \esc_attr( $v ) . '">';
	}
	return $h . '<button type="submit" class="' . \esc_attr( $classe ) . '">' . $label . '</button></form>';
}

/**
 * Écran complet.
 */
function rendu(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}
	$c     = cycle();
	$next  = (int) \min( \array_filter( \array_map( 'G2RD_Rescue18\prochain_passage', \array_keys( GROUPES ) ) ) ?: array( 0 ) );
	$props = (array) \get_option( OPT_PROP, array() );
	$jour  = (array) \get_option( OPT_JOUR, array() );
	$msg   = \sanitize_key( \wp_unslash( $_GET['r18msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage.
	$flash = array(
		'lance'      => 'Synchronisation lancée. La progression s\'affiche ci-dessous, la page se met à jour seule.',
		'simulation' => 'Simulation lancée : rien n\'est écrit, le journal décrit les actions prévues.',
		'occupe'     => 'Un cycle est déjà en cours : aucune nouvelle collecte lancée.',
		'reglages'   => 'Réglages enregistrés.',
		'flash'      => (string) \get_transient( 'r18v_flash_' . \get_current_user_id() ),
	)[ $msg ] ?? '';
	if ( 'flash' === $msg ) {
		\delete_transient( 'r18v_flash_' . \get_current_user_id() );
	}

	echo '<div class="wrap r18v">';
	echo '<header class="r18v-hero"><div><p class="r18v-kicker">Rescue 18 · Automatisations</p><h1>Veille documentaire</h1>';
	echo '<p class="r18v-sub">Une collecte par 24 heures sur les sources officielles. La synchronisation tourne en arrière-plan. Aucune fiche n\'est supprimée.</p>';
	echo '<div class="r18v-chips"><span class="r18v-chip"><span class="dashicons dashicons-clock" aria-hidden="true"></span>Prochaine collecte : <strong>' . \esc_html( $next ? \wp_date( 'j M · H:i', $next ) . ' (' . quand( $next ) . ')' : 'non planifiée' ) . '</strong></span>';
	if ( ! empty( $c['fin'] ) ) {
		echo '<span class="r18v-chip"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Dernier cycle : <strong>' . \esc_html( quand( (int) $c['fin'] ) ) . ( ! empty( $c['simulation'] ) ? ' (simulation)' : '' ) . '</strong></span>';
	}
	echo '</div></div><div class="r18v-hero__act">';
	echo bouton( 'r18v_sync', '<span class="dashicons dashicons-update" aria-hidden="true"></span>Synchroniser maintenant', array(), 'r18v-btn is-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé.
	echo bouton( 'r18v_sync', '<span class="dashicons dashicons-visibility" aria-hidden="true"></span>Simuler', array( 'simulation' => '1' ), 'r18v-btn is-ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div></header>';

	if ( '' !== $flash ) {
		echo '<div class="r18v-toast" role="status"><span class="dashicons dashicons-info" aria-hidden="true"></span>' . \esc_html( $flash ) . '</div>';
	}

	// Progression.
	$running = 'en_cours' === ( $c['statut'] ?? '' );
	$total   = \count( (array) ( $c['file'] ?? array() ) );
	$pct     = $total ? (int) \round( 100 * (int) $c['pos'] / $total ) : 0;
	echo '<section class="r18v-progress" data-r18v-progress' . ( $running ? '' : ' hidden' ) . ' aria-live="polite"><div class="r18v-progress__head"><strong data-r18v-progress-title>' . ( ! empty( $c['simulation'] ) ? 'Simulation en cours' : 'Synchronisation en cours' ) . '</strong><span class="r18v-progress__right"><span data-r18v-progress-label>' . (int) ( $c['pos'] ?? 0 ) . ' / ' . $total . ' pages</span><button type="button" class="r18v-btn is-stop" data-r18v-stop>Arrêter</button></span></div>';
	echo '<div class="r18v-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $pct . '"><span style="width:' . $pct . '%"></span></div><p class="r18v-progress__src" data-r18v-progress-src></p></section>';

	// Cartes des sources.
	echo '<h2 class="r18v-h2">Sources</h2><div class="r18v-grid">';
	foreach ( connecteurs() as $id => $k ) {
		carte( $id, $k );
	}
	echo '<article class="r18v-card is-muted"><header class="r18v-card__head"><div><h3>Légifrance · module juridique</h3><p class="r18v-url">api.piste.gouv.fr</p></div><span class="r18v-badge is-off">Non configuré</span></header>';
	echo '<p class="r18v-desc">Suivi juridique par l\'API Légifrance (PISTE). Il exige des identifiants OAuth gratuits délivrés par la DILA. Aucun connecteur n\'est activé sans ces identifiants : aucune donnée n\'est simulée.</p></article>';
	echo '</div>';

	// Rapprochements.
	echo '<h2 class="r18v-h2" id="a-valider">Rapprochements à valider <span class="r18v-count">' . \count( $props ) . '</span></h2>';
	if ( empty( $props ) ) {
		echo '<div class="r18v-empty"><span class="dashicons dashicons-yes" aria-hidden="true"></span>Rien à valider. Les documents identiques entre sources sont rapprochés automatiquement.</div>';
	} else {
		echo '<div class="r18v-props">';
		foreach ( $props as $cle => $p ) {
			$cand = \get_post( (int) $p['candidat'] );
			echo '<article class="r18v-prop"><div class="r18v-prop__pair"><div><span class="r18v-src is-' . \esc_attr( (string) $p['source'] ) . '">' . \esc_html( \strtoupper( (string) $p['source'] ) ) . '</span><p class="r18v-prop__t">' . \esc_html( (string) $p['item']['titre'] ) . '</p><a href="' . \esc_url( (string) $p['item']['url'] ) . '" target="_blank" rel="noopener">Voir à la source</a></div>';
			echo '<div class="r18v-prop__score"><span>' . \esc_html( (string) $p['score'] ) . ' %</span><small>ressemblance</small></div>';
			echo '<div><span class="r18v-src">Fiche Rescue 18</span><p class="r18v-prop__t">' . \esc_html( $cand ? $cand->post_title : '(fiche introuvable)' ) . '</p>' . ( $cand ? '<a href="' . \esc_url( (string) \get_edit_post_link( $cand->ID ) ) . '">Ouvrir la fiche</a>' : '' ) . '</div></div><div class="r18v-prop__act">';
			echo bouton( 'r18v_decider', 'Même document : rattacher', array( 'cle' => (string) $cle, 'decision' => 'rattacher' ), 'r18v-btn is-primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo bouton( 'r18v_decider', 'Documents distincts', array( 'cle' => (string) $cle, 'decision' => 'distinct' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo bouton( 'r18v_decider', 'Ignorer', array( 'cle' => (string) $cle, 'decision' => 'ignore' ), 'r18v-btn is-text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div></article>';
		}
		echo '</div>';
	}

	// Journal.
	echo '<h2 class="r18v-h2" id="journal">Journal des opérations</h2><section class="r18v-log" data-r18v-log>';
	echo '<div class="r18v-log__bar" role="group" aria-label="Filtrer le journal"><button type="button" aria-pressed="true" data-niv="">Tout</button><button type="button" aria-pressed="false" data-niv="ok">Changements</button><button type="button" aria-pressed="false" data-niv="alerte">Alertes</button><button type="button" aria-pressed="false" data-niv="erreur">Erreurs</button>';
	echo '<label class="r18v-log__src"><span class="screen-reader-text">Source</span><select data-src><option value="">Toutes les sources</option><option value="dgscgc">DGSCGC</option><option value="pnrs">PNRS</option><option value="ensosp">ENSOSP</option><option value="cycle">Cycles</option></select></label></div><ol class="r18v-log__list">';
	if ( empty( $jour ) ) {
		echo '<li class="r18v-log__empty">Le journal est vide. Lance un test de connexion ou une synchronisation.</li>';
	}
	foreach ( $jour as $l ) {
		$edit = ! empty( $l['id'] ) ? \get_edit_post_link( (int) $l['id'] ) : '';
		echo '<li class="is-' . \esc_attr( (string) $l['n'] ) . '" data-niv="' . \esc_attr( (string) $l['n'] ) . '" data-src="' . \esc_attr( (string) $l['s'] ) . '"><time datetime="' . \esc_attr( \gmdate( 'c', (int) $l['t'] ) ) . '">' . \esc_html( \wp_date( 'j M · H:i', (int) $l['t'] ) ) . '</time><span class="r18v-src is-' . \esc_attr( (string) $l['s'] ) . '">' . \esc_html( \strtoupper( (string) $l['s'] ) ) . '</span><span class="r18v-log__m">' . ( ! empty( $l['x'] ) ? '<em class="r18v-simu">Simulation</em> ' : '' ) . \esc_html( (string) $l['m'] ) . '</span>' . ( $edit ? '<a href="' . \esc_url( $edit ) . '">Fiche</a>' : '' ) . '</li>';
	}
	echo '</ol></section>';

	// Planification.
	echo '<h2 class="r18v-h2">Planification</h2><section class="r18v-plan"><div><h3>Créneaux de nuit</h3><p>Chaque famille de sources a son créneau, décalé des autres. Une tâche qui démarre pendant une collecte en cours attend 20 minutes, trois fois au plus. Un verrou empêche deux collectes simultanées. Un cycle interrompu reprend où il s\'est arrêté.</p>' . tableau_plan() . '<h3>Vérification avant synchronisation</h3><p>Chaque page et chaque flux reçoit une empreinte de son contenu utile. Au cycle suivant, une source sans nouveauté (réponse 304 ou empreinte identique) est ignorée : ni analyse, ni lecture des pages de détail. Seuls les éléments nouveaux ou modifiés sont traités. Les empreintes se réinitialisent quand tu changes les réglages d\'une source.</p>' . bouton( 'r18v_relire', '<span class="dashicons dashicons-backup" aria-hidden="true"></span>Forcer une relecture complète', array(), 'r18v-btn', 'Effacer les empreintes ? La prochaine synchronisation relira toutes les pages.' ) . '</div>';
	echo '<div><h3>Cron de l\'hébergeur, facultatif</h3><p>Pour ne plus dépendre des visites, ajoute <code>define( \'DISABLE_WP_CRON\', true );</code> dans wp-config.php, puis programme toutes les 5 minutes :</p><pre>wget -q -O - ' . \esc_html( \site_url( 'wp-cron.php?doing_wp_cron' ) ) . ' &gt;/dev/null 2&gt;&amp;1</pre><p>Ou, avec WP-CLI :</p><pre>wp cron event run --due-now</pre><p>Synchronisation manuelle en ligne de commande :</p><pre>wp rescue18 veille [--source=pnrs] [--simulation]</pre></div></section>';
	echo '</div>';
}

/**
 * Carte d'une source.
 *
 * @param string     $id Identifiant.
 * @param Connecteur $k  Connecteur.
 */
function carte( string $id, Connecteur $k ): void {
	$reg   = reglages( $id );
	$e     = etat( $id );
	$suivi = references_suivies( $id );
	// Résultat de la dernière simulation (clé « simu » ; « simulation » est un drapeau booléen).
	$sim   = \is_array( $e['simu'] ?? null ) ? $e['simu'] : ( \is_array( $e['simulation'] ?? null ) ? $e['simulation'] : array() );
	echo '<article class="r18v-card" id="src-' . \esc_attr( $id ) . '"><header class="r18v-card__head"><div><h3>' . \esc_html( $k->nom() ) . '</h3><a class="r18v-url" href="' . \esc_url( $k->url() ) . '" target="_blank" rel="noopener">' . \esc_html( (string) \wp_parse_url( $k->url(), PHP_URL_HOST ) ) . '</a></div>' . badge( $e, ! empty( $reg['actif'] ) ) . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- badge échappé.
	echo '<p class="r18v-desc"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span>' . \esc_html( $k->methode() ) . ( ! empty( $e['methode'] ) ? ' · dernier passage : ' . \esc_html( (string) $e['methode'] ) : '' ) . '</p>';
	echo '<dl class="r18v-kpi"><div><dt>Suivies</dt><dd>' . (int) $suivi . '</dd></div><div><dt>Nouveaux</dt><dd>' . (int) ( $e['nouveaux'] ?? 0 ) . '</dd></div><div><dt>Actualisés</dt><dd>' . (int) ( $e['actualises'] ?? 0 ) . '</dd></div><div class="' . ( ! empty( $e['erreurs'] ) ? 'is-err' : '' ) . '"><dt>Erreurs</dt><dd>' . (int) ( $e['erreurs'] ?? 0 ) . '</dd></div></dl>';
	echo '<ul class="r18v-meta"><li><span>Dernière vérification</span>' . \esc_html( quand( (int) ( $e['verif'] ?? 0 ) ) ) . '</li><li><span>Publication</span>' . ( 'auto' === $reg['publication'] ? 'automatique' : 'soumise à validation' ) . '</li><li><span>Prochaine synchronisation</span>' . \esc_html( ! empty( $reg['actif'] ) && \G2RD_Rescue18\prochain_passage( \G2RD_Rescue18\groupe_de( $id ) ) ? \wp_date( 'j M · H:i', \G2RD_Rescue18\prochain_passage( \G2RD_Rescue18\groupe_de( $id ) ) ) : '—' ) . '</li>';
	if ( ! empty( $e['requetes'] ) ) {
		echo '<li><span>Requêtes au dernier cycle</span>' . (int) $e['requetes'] . ' / ' . (int) $k->plafond() . '</li>';
	}
	echo '</ul>';
	if ( ! empty( $e['message'] ) ) {
		echo '<p class="r18v-alert is-' . \esc_attr( 'bloque' === ( $e['resultat'] ?? '' ) ? 'block' : 'err' ) . '"><span class="dashicons dashicons-warning" aria-hidden="true"></span>' . \esc_html( (string) $e['message'] ) . '</p>';
	}
	if ( ! empty( $sim['verif'] ) ) {
		echo '<p class="r18v-simline"><em class="r18v-simu">Simulation</em> ' . \esc_html( quand( (int) $sim['verif'] ) . ' : ' . (int) ( $sim['nouveaux'] ?? 0 ) . ' nouveau(x), ' . (int) ( $sim['actualises'] ?? 0 ) . ' actualisé(s), ' . (int) ( $sim['ecartes'] ?? 0 ) . ' écarté(s), ' . (int) ( $sim['erreurs'] ?? 0 ) . ' erreur(s).' ) . '</p>';
	}
	echo '<div class="r18v-card__act"><button type="button" class="r18v-btn" data-r18v-test="' . \esc_attr( $id ) . '"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>Tester la connexion</button>';
	echo bouton( 'r18v_sync', 'Synchroniser', array( 'cible' => $id ), 'r18v-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo bouton( 'r18v_sync', 'Simuler', array( 'cible' => $id, 'simulation' => '1' ), 'r18v-btn is-text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div><div class="r18v-test" data-r18v-test-out="' . \esc_attr( $id ) . '" hidden aria-live="polite"></div>';

	if ( 'dgscgc' === $id ) {
		$orig    = 'https://www.securite-civile.interieur.gouv.fr';
		$retour  = \admin_url( 'admin.php?page=' . PAGE . '&r18import=1#src-dgscgc' );
		$site    = (string) \preg_replace( '#^(https?://[^/]+).*$#', '$1', \admin_url() );
		$signet  = 'javascript:(function(){if(location.origin!==' . \wp_json_encode( $orig ) . '){alert(\'Ouvre d\\u2019abord une page documentaire de la DGSCGC.\');return;}var w=window.open(' . \wp_json_encode( $retour ) . ',\'r18import\');var d={t:\'r18-import\',html:document.documentElement.outerHTML,url:location.href.split(\'#\')[0]};var f=function(e){if(e.origin===' . \wp_json_encode( $site ) . '&&e.data===\'r18-pret\'){w.postMessage(d,' . \wp_json_encode( $site ) . ');removeEventListener(\'message\',f);}};addEventListener(\'message\',f);})();';
		$liens   = '';
		foreach ( pages_reglees( $reg, pages_dgscgc_defaut() ) as $u => $t ) {
			$liens .= '<li><a href="' . \esc_url( $u ) . '" target="_blank" rel="noopener">' . \esc_html( \strtoupper( $t ) . ' · ' . \str_replace( '-', ' ', \basename( (string) \wp_parse_url( $u, PHP_URL_PATH ), '.html' ) ) ) . '</a></li>';
		}
		echo '<details class="r18v-more"' . ( isset( $_GET['r18import'] ) ? ' open' : '' ) . '><summary>Import en un clic (le site bloque les robots)</summary><div class="r18v-form">'; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<p class="r18v-hint">Le site de la DGSCGC refuse les lectures automatiques. Tu lis la page dans ton navigateur, comme un visiteur, et Rescue 18 en extrait les documents.</p>';
		echo '<ol class="r18v-steps"><li>Une seule fois : glisse ce bouton dans ta barre de favoris. <a class="r18v-btn is-primary" href="' . \esc_attr( $signet ) . '" onclick="alert(\'Glisse ce bouton dans ta barre de favoris, puis utilise-le sur une page de la DGSCGC.\');return false;">Envoyer à Rescue 18</a></li><li>Ouvre une page suivie, puis clique sur le favori « Envoyer à Rescue 18 ». L\'import se lance dans un nouvel onglet.</li></ol>';
		echo '<ul class="r18v-pages">' . $liens . '</ul>';
		echo '<script>(function(){var o=' . \wp_json_encode( $orig ) . ';if(/[?&]r18import=1/.test(location.search)&&window.opener){window.opener.postMessage(\'r18-pret\',o);}window.addEventListener(\'message\',function(e){if(e.origin!==o||!e.data||e.data.t!==\'r18-import\')return;var f=document.getElementById(\'r18v-import-form\');if(!f)return;var s=f.querySelector(\'[name=page_url]\'),ok=false;for(var i=0;i<s.options.length;i++){if(s.options[i].value===e.data.url){s.selectedIndex=i;ok=true;}}if(!ok){alert(\'Cette page n\\u2019est pas suivie par Rescue 18 : ajoute-la dans les réglages de la source.\');return;}f.querySelector(\'[name=html]\').value=String(e.data.html).slice(0,3000000);f.submit();});})();</script></div></details>';
		echo '<details class="r18v-more"><summary>Import manuel (copier-coller du code source)</summary><form method="post" id="r18v-import-form" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18v-form">';
		\wp_nonce_field( 'r18v_import' );
		echo '<input type="hidden" name="action" value="r18v_import"><ol class="r18v-steps"><li>Ouvre la page DGSCGC dans ton navigateur.</li><li>Affiche le code source (Ctrl + U), copie tout (Ctrl + A, Ctrl + C).</li><li>Colle-le ici. Le même analyseur que le connecteur crée ou actualise les fiches.</li></ol>';
		echo '<label for="r18v-page">Page d\'origine</label><select id="r18v-page" name="page_url">';
		foreach ( pages_reglees( $reg, pages_dgscgc_defaut() ) as $u => $t ) {
			echo '<option value="' . \esc_attr( $u ) . '">' . \esc_html( \strtoupper( $t ) . ' · ' . \basename( (string) \wp_parse_url( $u, PHP_URL_PATH ), '.html' ) ) . '</option>';
		}
		echo '</select><label for="r18v-html">Code source de la page</label><textarea id="r18v-html" name="html" rows="5" spellcheck="false" placeholder="&lt;!DOCTYPE html&gt;…"></textarea>';
		echo '<label class="r18v-check"><input type="checkbox" name="simulation" value="1"> Simulation (ne rien écrire)</label><button type="submit" class="r18v-btn is-primary">Importer</button></form></details>';
	}

	if ( 'jorf' === $id && \function_exists( 'G2RD_Rescue18\\Juridique\\panneau_piste' ) ) {
		\G2RD_Rescue18\Juridique\panneau_piste();
	}
	echo '<details class="r18v-more"><summary>Réglages</summary><form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="r18v-form">';
	\wp_nonce_field( 'r18v_reglages' );
	echo '<input type="hidden" name="action" value="r18v_reglages"><input type="hidden" name="connecteur" value="' . \esc_attr( $id ) . '">';
	echo '<label class="r18v-switch"><input type="checkbox" name="actif" value="1"' . \checked( ! empty( $reg['actif'] ), true, false ) . '><span aria-hidden="true"></span>Source active dans le cycle quotidien</label>';
	if ( \in_array( $id, array( 'dgscgc', 'pnrs', 'ensosp', 'jorf' ), true ) ) echo '<fieldset class="r18v-seg"><legend>Publication des nouveautés</legend><label><input type="radio" name="publication" value="auto"' . \checked( $reg['publication'], 'auto', false ) . '><span>Automatique</span></label><label><input type="radio" name="publication" value="validation"' . \checked( $reg['publication'], 'validation', false ) . '><span>Soumise à validation</span></label></fieldset>';
	if ( 'jorf' === $id ) {
		echo '<p class="r18v-hint">Toujours retenus : sapeurs-pompiers, marins-pompiers, services d\'incendie et de secours, jeunes sapeurs-pompiers, secours à personne, feux de forêt.</p><label for="r18v-jmots">Autres mots-clés liés au secours (séparés par des virgules)</label><textarea id="r18v-jmots" name="mots" rows="4" placeholder="' . \esc_attr( \implode( ', ', \G2RD_Rescue18\Juridique\mots_defaut() ) ) . '">' . \esc_textarea( \implode( ', ', (array) $reg['mots'] ) ) . '</textarea><p class="r18v-hint">Vide : liste par défaut, affichée en grisé.</p>';
		echo '<label for="r18v-jexc">Écarter les titres contenant</label><textarea id="r18v-jexc" name="exclus" rows="3" placeholder="' . \esc_attr( \implode( ', ', \G2RD_Rescue18\Juridique\exclus_defaut() ) ) . '">' . \esc_textarea( \implode( ', ', (array) ( $reg['exclus'] ?? array() ) ) ) . '</textarea><p class="r18v-hint">Par défaut : nominations, médailles, habilitations et agréments d\'organismes, dérogations, accessibilité, actes sans lien avec le terrain. Enregistrer applique aussi le tri aux textes déjà importés (passage en brouillon, rien n\'est supprimé).</p>';
		echo '<label for="r18v-jdep">Rattraper les textes publiés depuis le</label><input id="r18v-jdep" type="date" name="depuis" value="' . \esc_attr( (string) ( $reg['depuis'] ?? '' ) ) . '" placeholder="2025-06-01"><p class="r18v-hint">Vide : 1er juin 2025. Les archives en ligne remontent à environ 15 mois. 60 archives lues par nuit au maximum, les plus récentes d\'abord.</p>';
	} elseif ( ! \in_array( $id, array( 'dgscgc', 'pnrs', 'ensosp' ), true ) ) {
		echo '<p class="r18v-hint">Réglages détaillés (publication, filtres, images, carte) : <a href="' . \esc_url( \admin_url( 'admin.php?page=rescue18-annuaire' ) ) . '">Annuaire SDIS</a>.</p>';
	} elseif ( 'ensosp' !== $id ) {
		$def = 'dgscgc' === $id ? pages_dgscgc_defaut() : pages_pnrs_defaut();
		$ph  = \implode( "\n", \array_map( static fn( string $u, string $t ): string => $u . ' | ' . $t, \array_keys( $def ), $def ) );
		echo '<label for="r18v-pages-' . \esc_attr( $id ) . '">Pages suivies, une par ligne : <code>URL | type</code> (auto, gdo, gto, retex…)</label><textarea id="r18v-pages-' . \esc_attr( $id ) . '" name="pages_txt" rows="4" spellcheck="false" placeholder="' . \esc_attr( $ph ) . '">' . \esc_textarea( (string) $reg['pages_txt'] ) . '</textarea><p class="r18v-hint">Vide : pages par défaut, affichées en grisé.</p>';
	} else {
		echo '<label for="r18v-mots">Mots-clés de la ligne éditoriale (séparés par des virgules)</label><textarea id="r18v-mots" name="mots" rows="3" placeholder="' . \esc_attr( \implode( ', ', mots_ensosp_defaut() ) ) . '">' . \esc_textarea( \implode( ', ', (array) $reg['mots'] ) ) . '</textarea>';
		echo '<label class="r18v-check"><input type="checkbox" name="institutionnel" value="1"' . \checked( ! empty( $reg['institutionnel'] ), true, false ) . '> Inclure aussi les actualités institutionnelles</label>';
		echo '<label for="r18v-np">Pages de la liste lues à chaque cycle</label><input id="r18v-np" type="number" name="pages" min="1" max="5" value="' . (int) $reg['pages'] . '">';
	}
	echo '<button type="submit" class="r18v-btn is-primary">Enregistrer</button></form></details></article>';
}

/**
 * Tableau des créneaux planifiés.
 *
 * @return string
 */
function tableau_plan(): string {
	$h = '<table class="r18v-table"><thead><tr><th scope="col">Heure</th><th scope="col">Tâche</th><th scope="col">Prochain passage</th></tr></thead><tbody>';
	foreach ( \G2RD_Rescue18\plan() as $id => $p ) {
		$n  = \G2RD_Rescue18\prochain_passage( $id );
		$h .= '<tr><td><strong>' . \esc_html( \str_replace( ':', ' h ', $p['heure'] ) ) . '</strong></td><td>' . \esc_html( $p['lib'] ) . '<small>' . \esc_html( $p['detail'] ) . '</small></td><td>' . \esc_html( $n ? \wp_date( 'j M · H:i', $n ) : 'non planifié' ) . '</td></tr>';
	}
	$al = \wp_next_scheduled( 'r18_alertes_collecte' );
	$h .= '<tr><td><strong>xx h 23</strong></td><td>Alertes interventions<small>Toutes les heures ou toutes les 2 heures, selon les réglages</small></td><td>' . \esc_html( $al ? \wp_date( 'j M · H:i', (int) $al ) : 'désactivées' ) . '</td></tr>';
	return $h . '</tbody></table>';
}
