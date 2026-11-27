<?php
/**
 * Suivi des clics sortants : documents, fichiers, liens externes, partenaires.
 *
 * Complète le suivi des appels et e-mails du thème parent, sans le modifier.
 * Aucun cookie, aucune adresse IP conservée : un compteur par mois et par lien.
 *
 * @package G2RD_Rescue18
 */

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const CLICS_PAGE    = 'rescue18-clics';
const CLICS_DB_VER  = '1';
const CLICS_MAX_MOIS = 5000; // Liens distincts conservés au plus par mois.

/**
 * Nom de la table des clics.
 *
 * @return string
 */
function table_clics(): string {
	global $wpdb;
	return $wpdb->prefix . 'r18_clics';
}

/**
 * Types de clics : clé => libellé.
 *
 * @return array<string,string>
 */
function types_clics(): array {
	return array(
		'document'   => 'Documents de la bibliothèque',
		'fichier'    => 'Fichiers (PDF, Word, Excel…)',
		'partenaire' => 'Sites des partenaires',
		'externe'    => 'Autres liens externes',
	);
}

/* Table : créée ou mise à jour une fois. */
\add_action(
	'init',
	static function (): void {
		if ( \get_option( 'r18_clics_db' ) === CLICS_DB_VER ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$t = table_clics();
		\dbDelta(
			"CREATE TABLE {$t} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				mois char(7) NOT NULL,
				type varchar(12) NOT NULL,
				url varchar(500) NOT NULL,
				url_hash char(32) NOT NULL,
				post_id bigint(20) unsigned NOT NULL DEFAULT 0,
				n int(10) unsigned NOT NULL DEFAULT 0,
				maj datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY mois_lien (mois,type,url_hash),
				KEY mois_type (mois,type)
			) {$wpdb->get_charset_collate()};"
		);
		\update_option( 'r18_clics_db', CLICS_DB_VER, false );
	},
	5
);

/**
 * Classe un lien cliqué.
 *
 * @param string $url    Adresse absolue.
 * @param int    $page   Contenu d'où part le clic.
 * @return array{0:string,1:int} Type et contenu lié (document de la bibliothèque).
 */
function classer_clic( string $url, int $page ): array {
	if ( $page > 0 && \in_array( \get_post_type( $page ), array( 'r18_document', 'r18_texte' ), true ) ) {
		foreach ( array( 'r18_source_url', 'r18_pdf_url' ) as $cle ) {
			$m = (string) \get_post_meta( $page, $cle, true );
			if ( '' !== $m && \untrailingslashit( $m ) === \untrailingslashit( $url ) ) {
				return array( 'document', $page );
			}
		}
	}
	$chemin = (string) \wp_parse_url( $url, PHP_URL_PATH );
	if ( \preg_match( '/\.(pdf|docx?|xlsx?|pptx?|odt|ods|odp|zip|csv)$/i', $chemin ) ) {
		return array( 'fichier', 0 );
	}
	return array( 'externe', 0 );
}

/**
 * Ajoute un clic au compteur du mois.
 *
 * @param string $type    Type.
 * @param string $url     Adresse.
 * @param int    $post_id Contenu lié.
 */
function compter_clic( string $type, string $url, int $post_id = 0 ): void {
	global $wpdb;
	$t    = table_clics();
	$mois = \wp_date( 'Y-m' );
	$hash = \md5( $url );
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$existe = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE mois = %s AND type = %s AND url_hash = %s", $mois, $type, $hash ) );
	if ( ! $existe && (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE mois = %s", $mois ) ) >= CLICS_MAX_MOIS ) {
		return;
	}
	// Une seule requête : deux clics simultanés ne créent jamais de doublon.
	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$t} (mois, type, url, url_hash, post_id, n, maj) VALUES (%s, %s, %s, %s, %d, 1, %s) ON DUPLICATE KEY UPDATE n = n + 1, maj = VALUES(maj)",
			$mois,
			$type,
			\substr( $url, 0, 500 ),
			$hash,
			$post_id,
			\current_time( 'mysql' )
		)
	);
	// phpcs:enable
}

/* Script de suivi, sur toutes les pages publiques. */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$a = asset( 'assets/js/suivi.js' );
		if ( ! $a ) {
			return;
		}
		\wp_enqueue_script( 'rescue18-suivi', $a[0], array(), $a[1], array( 'strategy' => 'defer', 'in_footer' => true ) );
		\wp_localize_script(
			'rescue18-suivi',
			'r18Suivi',
			array(
				'url'  => \rest_url( 'rescue18/v1/clic' ),
				'page' => \is_singular() ? (int) \get_queried_object_id() : 0,
			)
		);
	}
);

\add_action(
	'rest_api_init',
	static function (): void {
		\register_rest_route(
			'rescue18/v1',
			'/clic',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'args'                => array(
					'url'  => array(
						'type'     => 'string',
						'required' => true,
					),
					'page' => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'pt'   => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
				'callback'            => __NAMESPACE__ . '\\recevoir_clic',
			)
		);
	}
);

/**
 * Enregistre un clic reçu du navigateur.
 *
 * @param \WP_REST_Request $r Requête.
 * @return \WP_REST_Response
 */
function recevoir_clic( \WP_REST_Request $r ): \WP_REST_Response {
	$vide = new \WP_REST_Response( null, 204 );
	$ua   = \strtolower( (string) $r->get_header( 'user_agent' ) );
	if ( '' === $ua || \preg_match( '/bot|crawl|spider|preview|headless|lighthouse/', $ua ) ) {
		return $vide;
	}
	$url  = \esc_url_raw( \trim( (string) $r['url'] ), array( 'http', 'https' ) );
	$hote = (string) \wp_parse_url( $url, PHP_URL_HOST );
	if ( '' === $url || '' === $hote || \strlen( $url ) > 500 ) {
		return $vide;
	}
	$site  = (string) \wp_parse_url( \home_url(), PHP_URL_HOST );
	$local = \strtolower( $hote ) === \strtolower( $site );
	$page  = \max( 0, (int) $r['page'] );
	$pt    = \max( 0, (int) $r['pt'] );

	// Garde-fous : un clic par lien, visiteur et heure, 60 clics par heure au plus.
	$ip     = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$heure  = \gmdate( 'YmdH' );
	$cle    = 'r18c_' . \md5( $ip . '|' . $url . '|' . $heure . \wp_salt( 'nonce' ) );
	$quota  = 'r18cq_' . \md5( $ip . '|' . $heure . \wp_salt( 'nonce' ) );
	$compte = (int) \get_transient( $quota );
	if ( \get_transient( $cle ) || $compte >= 60 ) {
		return $vide;
	}
	\set_transient( $cle, 1, HOUR_IN_SECONDS );
	\set_transient( $quota, $compte + 1, HOUR_IN_SECONDS );

	if ( $pt > 0 && PT_TYPE === \get_post_type( $pt ) && 'publish' === \get_post_status( $pt ) ) {
		compter_clic_partenaire( $pt );
		compter_clic( 'partenaire', $url, $pt );
		return $vide;
	}
	[ $type, $lie ] = classer_clic( $url, $page );
	// Un lien interne ne compte que s'il mène à un fichier.
	if ( $local && 'fichier' !== $type && 'document' !== $type ) {
		return $vide;
	}
	compter_clic( $type, $url, $lie );
	return $vide;
}

/* Back-office : écran « Clics sortants » et widget du tableau de bord. */

\add_action(
	'admin_menu',
	static function (): void {
		\add_submenu_page( 'index.php', 'Clics sortants', 'Clics sortants', 'edit_pages', CLICS_PAGE, __NAMESPACE__ . '\\ecran_clics' );
	}
);

/**
 * Lignes d'un mois, triées par nombre de clics.
 *
 * @param string $mois Mois AAAA-MM.
 * @param string $type Type, ou vide pour tous.
 * @param int    $max  Nombre de lignes.
 * @return array<int,object>
 */
function lignes_clics( string $mois, string $type = '', int $max = 500 ): array {
	global $wpdb;
	$t = table_clics();
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( '' === $type ) {
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE mois = %s ORDER BY n DESC LIMIT %d", $mois, $max ) );
	}
	return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE mois = %s AND type = %s ORDER BY n DESC LIMIT %d", $mois, $type, $max ) );
	// phpcs:enable
}

/**
 * Libellé lisible d'une ligne : titre du document ou du partenaire, sinon le lien.
 *
 * @param object $l Ligne.
 * @return string
 */
function libelle_clic( object $l ): string {
	if ( (int) $l->post_id > 0 ) {
		$titre = \get_the_title( (int) $l->post_id );
		if ( '' !== $titre ) {
			return $titre;
		}
	}
	$chemin = (string) \wp_parse_url( (string) $l->url, PHP_URL_PATH );
	$nom    = \rawurldecode( \basename( $chemin ) );
	return '' !== $nom && '/' !== $chemin ? $nom : (string) \wp_parse_url( (string) $l->url, PHP_URL_HOST );
}

/**
 * Écran des clics sortants.
 */
function ecran_clics(): void {
	global $wpdb;
	$t     = table_clics();
	$mois  = isset( $_GET['mois'] ) && \preg_match( '/^\d{4}-\d{2}$/', (string) $_GET['mois'] ) ? (string) $_GET['mois'] : \wp_date( 'Y-m' ); // phpcs:ignore WordPress.Security.NonceVerification
	$type  = isset( $_GET['type'] ) && isset( types_clics()[ (string) $_GET['type'] ] ) ? (string) $_GET['type'] : 'document'; // phpcs:ignore WordPress.Security.NonceVerification
	$liste = (array) $wpdb->get_col( "SELECT DISTINCT mois FROM {$t} ORDER BY mois DESC LIMIT 24" ); // phpcs:ignore WordPress.DB
	if ( ! \in_array( $mois, $liste, true ) ) {
		\array_unshift( $liste, $mois );
	}
	$totaux = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT type, SUM(n) AS n FROM {$t} WHERE mois = %s GROUP BY type", $mois ) ) as $row ) { // phpcs:ignore WordPress.DB
		$totaux[ $row->type ] = (int) $row->n;
	}
	$base = \admin_url( 'index.php?page=' . CLICS_PAGE );

	echo '<div class="wrap"><h1>Clics sortants</h1>';
	echo '<p>Clics des visiteurs vers les documents, les fichiers, les partenaires et les autres sites. Un clic par visiteur, lien et heure, robots exclus, sans cookie. Les appels et les e-mails restent dans le widget du thème sur le tableau de bord.</p>';
	echo '<form method="get" style="margin:12px 0"><input type="hidden" name="page" value="' . \esc_attr( CLICS_PAGE ) . '"><input type="hidden" name="type" value="' . \esc_attr( $type ) . '"><label for="r18c-mois">Mois </label><select id="r18c-mois" name="mois">';
	foreach ( $liste as $m ) {
		echo '<option value="' . \esc_attr( $m ) . '"' . \selected( $m, $mois, false ) . '>' . \esc_html( \date_i18n( 'F Y', (int) \strtotime( $m . '-01' ) ) ) . '</option>';
	}
	echo '</select> <button class="button">Afficher</button> <a class="button" href="' . \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=r18_clics_csv&mois=' . $mois ), 'r18_clics_csv' ) ) . '">Exporter le mois (CSV)</a></form>';

	echo '<nav class="nav-tab-wrapper" aria-label="Types de clics">';
	foreach ( types_clics() as $k => $label ) {
		echo '<a class="nav-tab' . ( $k === $type ? ' nav-tab-active' : '' ) . '" href="' . \esc_url( \add_query_arg( array( 'mois' => $mois, 'type' => $k ), $base ) ) . '"' . ( $k === $type ? ' aria-current="page"' : '' ) . '>' . \esc_html( $label ) . ' (' . (int) ( $totaux[ $k ] ?? 0 ) . ')</a>';
	}
	echo '</nav>';

	$lignes = lignes_clics( $mois, $type );
	if ( ! $lignes ) {
		echo '<p style="margin-top:16px">Aucun clic pour ce mois.</p></div>';
		return;
	}
	echo '<table class="widefat striped" style="margin-top:16px;max-width:1100px"><thead><tr><th scope="col">Lien</th><th scope="col">Site</th><th scope="col" style="width:90px">Clics</th></tr></thead><tbody>';
	foreach ( $lignes as $l ) {
		$edit = (int) $l->post_id > 0 ? \get_edit_post_link( (int) $l->post_id ) : '';
		echo '<tr><td><a href="' . \esc_url( (string) $l->url ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html( libelle_clic( $l ) ) . '</a>' . ( $edit ? ' · <a href="' . \esc_url( $edit ) . '">fiche</a>' : '' ) . '</td><td>' . \esc_html( (string) \wp_parse_url( (string) $l->url, PHP_URL_HOST ) ) . '</td><td><strong>' . (int) $l->n . '</strong></td></tr>';
	}
	echo '</tbody></table></div>';
}

/* Export CSV d'un mois. */
\add_action(
	'admin_post_r18_clics_csv',
	static function (): void {
		if ( ! \current_user_can( 'edit_pages' ) ) {
			\wp_die( 'Accès refusé.', '', array( 'response' => 403 ) );
		}
		\check_admin_referer( 'r18_clics_csv' );
		$mois = isset( $_GET['mois'] ) && \preg_match( '/^\d{4}-\d{2}$/', (string) $_GET['mois'] ) ? (string) $_GET['mois'] : \wp_date( 'Y-m' );
		\nocache_headers();
		\header( 'Content-Type: text/csv; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="rescue18-clics-' . $mois . '.csv"' );
		$f = \fopen( 'php://output', 'w' );
		\fwrite( $f, "\xEF\xBB\xBF" );
		\fputcsv( $f, array( 'Mois', 'Type', 'Libellé', 'Lien', 'Clics' ), ';' );
		foreach ( lignes_clics( $mois, '', CLICS_MAX_MOIS ) as $l ) {
			// Une cellule qui commence par = + - @ serait lue comme une formule par le tableur.
			$lib = \preg_replace( '/^([=+\-@])/', "'$1", libelle_clic( $l ) );
			\fputcsv( $f, array( $l->mois, types_clics()[ $l->type ] ?? $l->type, $lib, $l->url, (int) $l->n ), ';' );
		}
		\fclose( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

\add_action(
	'wp_dashboard_setup',
	static function (): void {
		if ( ! \current_user_can( 'edit_pages' ) ) {
			return;
		}
		\wp_add_dashboard_widget(
			'r18_clics',
			'Documents les plus consultés ce mois',
			static function (): void {
				$l = lignes_clics( \wp_date( 'Y-m' ), 'document', 5 );
				if ( ! $l ) {
					echo '<p>Aucun clic sur un document ce mois-ci.</p>';
				} else {
					echo '<ol>';
					foreach ( $l as $row ) {
						echo '<li>' . \esc_html( libelle_clic( $row ) ) . ' : <strong>' . (int) $row->n . '</strong></li>';
					}
					echo '</ol>';
				}
				echo '<p><a href="' . \esc_url( \admin_url( 'index.php?page=' . CLICS_PAGE ) ) . '">Tous les clics sortants</a></p>';
			}
		);
	}
);
