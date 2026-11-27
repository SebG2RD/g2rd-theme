<?php
/**
 * Sujets des concours (type r18_annale) : sujets officiels, corrigés, rapports de jury et notes
 * de cadrage publiés par les organisateurs (CDG, CIG, CNFPT).
 *
 * Rescue 18 ne copie pas les sujets : chaque entrée pointe vers le fichier
 * officiel. Les entrées se modifient au back-office. Le lot livré dans
 * data/annales/annales.json arrive à l'installation, jamais réécrit ensuite.
 * Un contrôle hebdomadaire des liens signale les fichiers disparus.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Annales;

\defined( 'ABSPATH' ) || exit;

const CPT = 'r18_annale';

/**
 * Champs : clé => [libellé, type].
 *
 * @return array<string,array{0:string,1:string}>
 */
function champs(): array {
	return array(
		'r18_a_grade'        => array( 'Grade', 'select' ),
		'r18_a_voie'         => array( 'Voie', 'select' ),
		'r18_a_session'      => array( 'Session (année)', 'number' ),
		'r18_a_epreuve'      => array( 'Épreuve', 'text' ),
		'r18_a_type'         => array( 'Type de document', 'select' ),
		'r18_a_organisateur' => array( 'Organisateur', 'text' ),
		'r18_a_url'          => array( 'Adresse du fichier officiel', 'url' ),
		'r18_a_pages'        => array( 'Nombre de pages', 'number' ),
	);
}

/**
 * Valeurs des listes.
 *
 * @return array<string,string[]>
 */
function listes(): array {
	return array(
		'r18_a_grade' => array( 'Caporal', 'Sergent', 'Adjudant', 'Lieutenant de 2e classe', 'Lieutenant de 1re classe', 'Lieutenant hors classe', 'Capitaine', 'Commandant', 'Lieutenant-colonel', 'Colonel', 'Contrôleur général', 'Infirmier', 'Cadre de santé', 'Médecin', 'Pharmacien' ),
		'r18_a_voie'  => array( 'Externe', 'Interne', 'Troisième voie', 'Examen professionnel' ),
		'r18_a_type'  => array( 'sujet', 'corrigé', 'rapport du jury', 'note de cadrage' ),
	);
}

/**
 * Libellés publics des types.
 *
 * @return array<string,string>
 */
function types(): array {
	return array(
		'sujet'           => 'Sujet',
		'corrigé'         => 'Corrigé officiel',
		'rapport du jury' => 'Rapport du jury',
		'note de cadrage' => 'Note de cadrage',
	);
}

\add_action(
	'init',
	static function (): void {
		\register_post_type(
			CPT,
			array(
				'labels'              => array(
					'name'          => 'Sujets des concours',
					'singular_name' => 'Sujet de concours',
					'add_new'       => 'Ajouter',
					'add_new_item'  => 'Ajouter un sujet',
					'edit_item'     => 'Modifier le sujet',
					'all_items'     => 'Sujets',
					'menu_name'     => 'Sujets de concours',
					'search_items'  => 'Rechercher',
					'not_found'     => 'Aucun sujet',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=r18_concours',
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
			)
		);
		foreach ( \array_keys( champs() ) as $k ) {
			\register_post_meta(
				CPT,
				$k,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static fn(): bool => \current_user_can( 'edit_posts' ),
				)
			);
		}
	}
);

/* -------------------------------------------------------------------------
 * Administration
 * ---------------------------------------------------------------------- */

\add_action(
	'add_meta_boxes_' . CPT,
	static function (): void {
		\add_meta_box( 'r18-annale', 'Document officiel', __NAMESPACE__ . '\boite', CPT, 'normal', 'high' );
	}
);

/**
 * Boîte de saisie.
 *
 * @param \WP_Post $p Annale.
 */
function boite( \WP_Post $p ): void {
	\wp_nonce_field( 'r18_annale', 'r18_annale_nonce' );
	$l = listes();
	echo '<p>Indique le fichier publié par l\'organisateur. Rescue 18 renvoie vers lui sans le copier.</p><table class="form-table" role="presentation">';
	foreach ( champs() as $k => [ $lib, $type ] ) {
		$v = (string) \get_post_meta( $p->ID, $k, true );
		echo '<tr><th scope="row"><label for="' . \esc_attr( $k ) . '">' . \esc_html( $lib ) . '</label></th><td>';
		if ( 'select' === $type ) {
			echo '<select id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '">';
			foreach ( $l[ $k ] as $o ) {
				echo '<option' . \selected( $v, $o, false ) . '>' . \esc_html( $o ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '" type="' . \esc_attr( $type ) . '" class="' . ( 'number' === $type ? 'small-text' : 'large-text' ) . '" value="' . \esc_attr( $v ) . '">';
		}
		echo '</td></tr>';
	}
	$etat = (string) \get_post_meta( $p->ID, 'r18_a_etat', true );
	if ( '' !== $etat ) {
		echo '<tr><th scope="row">Dernier contrôle</th><td>' . \esc_html( $etat ) . '</td></tr>';
	}
	echo '</table>';
}

\add_action(
	'save_post_' . CPT,
	static function ( int $id ): void {
		if ( ! isset( $_POST['r18_annale_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['r18_annale_nonce'] ) ), 'r18_annale' ) || ! \current_user_can( 'edit_post', $id ) || \wp_is_post_revision( $id ) ) {
			return;
		}
		foreach ( champs() as $k => [ , $type ] ) {
			if ( ! isset( $_POST[ $k ] ) ) {
				continue;
			}
			$v = (string) \wp_unslash( $_POST[ $k ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$v = 'url' === $type ? \esc_url_raw( $v, array( 'https', 'http' ) ) : ( 'number' === $type ? (string) \absint( $v ) : \sanitize_text_field( $v ) );
			\update_post_meta( $id, $k, $v );
		}
		\delete_post_meta( $id, 'r18_a_etat' );
		\delete_transient( 'r18_annales_html' );
	}
);

\add_filter(
	'manage_' . CPT . '_posts_columns',
	static function ( array $c ): array {
		unset( $c['date'] );
		$c['r18_a_grade']   = 'Grade';
		$c['r18_a_session'] = 'Session';
		$c['r18_a_type']    = 'Type';
		$c['r18_a_etat']    = 'Lien';
		return $c;
	}
);
\add_action(
	'manage_' . CPT . '_posts_custom_column',
	static function ( string $col, int $id ): void {
		if ( 'r18_a_etat' === $col ) {
			$e = (string) \get_post_meta( $id, 'r18_a_etat', true );
			echo '' === $e ? '<span aria-hidden="true">·</span>' : ( 0 === \strpos( $e, 'OK' ) ? '<span style="color:#0a7a3e">' . \esc_html( $e ) . '</span>' : '<strong style="color:#be0c19">' . \esc_html( $e ) . '</strong>' );
		} elseif ( isset( champs()[ $col ] ) ) {
			echo \esc_html( (string) \get_post_meta( $id, $col, true ) );
		}
	},
	10,
	2
);
\add_action( 'trashed_post', static fn() => \delete_transient( 'r18_annales_html' ) );
\add_action( 'save_post_r18_quiz', static fn() => \delete_transient( 'r18_annales_html' ) );

/* -------------------------------------------------------------------------
 * Import du lot livré avec le thème
 * ---------------------------------------------------------------------- */

/**
 * Importe les entrées absentes (identifiées par leur adresse).
 *
 * @return int Nombre d'entrées ajoutées.
 */
function importer(): int {
	$f = \get_stylesheet_directory() . '/data/annales/annales.json';
	$d = \is_readable( $f ) ? \json_decode( (string) \file_get_contents( $f ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! \is_array( $d ) ) {
		return 0;
	}
	$l = listes();
	$n = 0;
	foreach ( $d as $e ) {
		$url = \esc_url_raw( (string) ( $e['url'] ?? '' ), array( 'https' ) );
		if ( '' === $url || ! \in_array( (string) ( $e['type'] ?? '' ), $l['r18_a_type'], true ) ) {
			continue;
		}
		$src = 'annale:' . \md5( $url );
		if ( \get_posts( array( 'post_type' => CPT, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'r18_source_id', 'meta_value' => $src ) ) ) { // phpcs:ignore WordPress.DB.SlowDBQuery
			continue;
		}
		$titre = \sanitize_text_field( (string) ( $e['titre'] ?? '' ) );
		if ( '' === $titre ) {
			$titre = \trim( \ucfirst( (string) $e['type'] ) . ' ' . ( $e['grade'] ?? '' ) . ' ' . ( $e['voie'] ?? '' ) . ' ' . ( $e['session'] ?? '' ) );
		}
		$id = (int) \wp_insert_post(
			array(
				'post_type'   => CPT,
				'post_status' => 'publish',
				'post_title'  => \wp_slash( $titre ),
			)
		);
		if ( $id <= 0 ) {
			continue;
		}
		$meta = array(
			'r18_source_id'      => $src,
			'r18_a_grade'        => (string) ( $e['grade'] ?? '' ),
			'r18_a_voie'         => (string) ( $e['voie'] ?? '' ),
			'r18_a_session'      => (string) \absint( $e['session'] ?? 0 ),
			'r18_a_epreuve'      => (string) ( $e['epreuve'] ?? '' ),
			'r18_a_type'         => (string) $e['type'],
			'r18_a_organisateur' => (string) ( $e['organisateur'] ?? '' ),
			'r18_a_url'          => $url,
			'r18_a_pages'        => (string) \absint( $e['pages'] ?? 0 ),
		);
		foreach ( $meta as $k => $v ) {
			\update_post_meta( $id, $k, \wp_slash( \sanitize_text_field( $v ) ) );
		}
		++$n;
	}
	\delete_transient( 'r18_annales_html' );
	return $n;
}

\add_action(
	'wp_loaded',
	static function (): void {
		$f = \get_stylesheet_directory() . '/data/annales/annales.json';
		if ( ! \is_readable( $f ) || \wp_installing() ) {
			return;
		}
		$e = (string) \md5_file( $f );
		if ( \get_option( 'r18_annales_pack' ) === $e ) {
			return;
		}
		\update_option( 'r18_annales_pack', $e, false );
		importer();
	},
	42
);

/* -------------------------------------------------------------------------
 * Contrôle des liens (hebdomadaire, par lots)
 * ---------------------------------------------------------------------- */


\add_action(
	'r18_annales_liens',
	static function (): void {
		if ( \G2RD_Rescue18\reporter_si_occupe( 'r18_annales_liens' ) ) {
			return;
		}
		$ids = \get_posts(
			array(
				'post_type'      => CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 15,
				'fields'         => 'ids',
				'orderby'        => 'meta_value_num',
				'meta_key'       => 'r18_a_controle', // phpcs:ignore WordPress.DB.SlowDBQuery
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'OR',
					array( 'key' => 'r18_a_controle', 'compare' => 'NOT EXISTS' ),
					array( 'key' => 'r18_a_controle', 'value' => \time() - 7 * DAY_IN_SECONDS, 'compare' => '<', 'type' => 'NUMERIC' ),
				),
			)
		);
		foreach ( $ids as $id ) {
			$url = (string) \get_post_meta( $id, 'r18_a_url', true );
			$r   = \wp_safe_remote_head( $url, array( 'timeout' => 15, 'redirection' => 3 ) );
			$c   = \is_wp_error( $r ) ? 0 : (int) \wp_remote_retrieve_response_code( $r );
			$t   = \is_wp_error( $r ) ? '' : (string) \wp_remote_retrieve_header( $r, 'content-type' );
			// 403 et 405 : le serveur refuse la requête courte, le fichier reste présumé en ligne.
			$ok  = ( $c >= 200 && $c < 300 && ( '' === $t || false !== \stripos( $t, 'pdf' ) || false !== \stripos( $t, 'octet' ) ) ) || \in_array( $c, array( 403, 405 ), true );
			\update_post_meta( $id, 'r18_a_controle', (string) \time() );
			\update_post_meta( $id, 'r18_a_etat', ( $ok ? 'OK' : 'Introuvable' ) . ' (HTTP ' . $c . ', ' . \wp_date( 'j/m/Y' ) . ')' );
		}
		\delete_transient( 'r18_annales_html' );
	}
);

/* -------------------------------------------------------------------------
 * Affichage
 * ---------------------------------------------------------------------- */

/**
 * Quiz « annales » publié pour un grade et une session.
 *
 * @param string $grade   Grade.
 * @param string $session Année.
 * @return int
 */
function quiz_lie( string $grade, string $session ): int {
	$slug = 'annales-' . \sanitize_title( \remove_accents( \explode( ' ', $grade )[0] ) ) . '-' . $session;
	$q    = \get_posts(
		array(
			'post_type'      => 'r18_quiz',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'quiz:pack:' . $slug, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) ( $q[0] ?? 0 );
}

/**
 * Bloc rescue18/annales : sujets classés par grade, filtrables.
 *
 * @param array<string,mixed> $attrs Attributs (categorie : A, B, C ou vide).
 * @return string
 */
function render_annales( array $attrs = array() ): string {
	$cache = \get_transient( 'r18_annales_html' );
	if ( \is_string( $cache ) && '' !== $cache ) {
		return $cache;
	}
	$ids = \get_posts(
		array(
			'post_type'      => CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	if ( ! $ids ) {
		return '<p class="r18-empty">Les premiers sujets arrivent après vérification de chaque lien officiel.</p>';
	}
	$ordre  = \array_flip( listes()['r18_a_grade'] );
	$torder = array( 'sujet' => 0, 'corrigé' => 1, 'rapport du jury' => 2, 'note de cadrage' => 3 );
	$lignes = array();
	foreach ( $ids as $id ) {
		$m = array();
		foreach ( \array_keys( champs() ) as $k ) {
			$m[ \substr( $k, 6 ) ] = (string) \get_post_meta( $id, $k, true );
		}
		if ( '' === $m['url'] || 0 === \strpos( (string) \get_post_meta( $id, 'r18_a_etat', true ), 'Introuvable' ) ) {
			continue;
		}
		$m['id']  = $id;
		$lignes[] = $m;
	}
	\usort(
		$lignes,
		static fn( $a, $b ) => array( $ordre[ $a['grade'] ] ?? 99, -(int) $a['session'], $a['voie'], $torder[ $a['type'] ] ?? 9 ) <=> array( $ordre[ $b['grade'] ] ?? 99, -(int) $b['session'], $b['voie'], $torder[ $b['type'] ] ?? 9 )
	);
	$groupes = array();
	foreach ( $lignes as $m ) {
		$groupes[ $m['grade'] ][] = $m;
	}
	$types = types();
	$chips = '<button type="button" class="r18-tk-chip" aria-pressed="true" data-g="">Tous les grades</button>';
	$tchip = '<button type="button" class="r18-tk-chip" aria-pressed="true" data-t="">Tous les documents</button>';
	foreach ( \array_keys( $groupes ) as $g ) {
		$chips .= '<button type="button" class="r18-tk-chip" aria-pressed="false" data-g="' . \esc_attr( \sanitize_title( $g ) ) . '">' . \esc_html( $g ) . '</button>';
	}
	$vus = array_unique( \array_column( $lignes, 'type' ) );
	foreach ( $types as $k => $lib ) {
		if ( \in_array( $k, $vus, true ) ) {
			$tchip .= '<button type="button" class="r18-tk-chip" aria-pressed="false" data-t="' . \esc_attr( \sanitize_title( $k ) ) . '">' . \esc_html( $lib ) . '</button>';
		}
	}
	$h = '';
	foreach ( $groupes as $g => $liste ) {
		$h .= '<section class="r18-ann__grp" data-g="' . \esc_attr( \sanitize_title( $g ) ) . '" aria-labelledby="ann-' . \esc_attr( \sanitize_title( $g ) ) . '"><h2 id="ann-' . \esc_attr( \sanitize_title( $g ) ) . '" class="r18-ann__g">' . \esc_html( $g ) . ' <span>' . \count( $liste ) . ' document' . ( \count( $liste ) > 1 ? 's' : '' ) . '</span></h2><ul class="r18-ann__list" role="list">';
		foreach ( $liste as $m ) {
			$host = (string) \wp_parse_url( $m['url'], PHP_URL_HOST );
			$q    = 'sujet' === $m['type'] ? quiz_lie( $m['grade'], $m['session'] ) : 0;
			$h   .= '<li class="r18-ann__item" data-t="' . \esc_attr( \sanitize_title( $m['type'] ) ) . '">'
				. '<span class="r18-ann__an' . ( (int) $m['session'] > 0 ? '' : ' is-nd' ) . '">' . \esc_html( (int) $m['session'] > 0 ? $m['session'] : 'Sans date' ) . '</span>'
				. '<div class="r18-ann__txt"><span class="r18-ann__type r18-ann__type--' . \esc_attr( \sanitize_title( $m['type'] ) ) . '">' . \esc_html( $types[ $m['type'] ] ?? $m['type'] ) . '</span>'
				. '<strong>' . \esc_html( $m['epreuve'] ) . '</strong>'
				. '<span class="r18-ann__meta">' . \esc_html( \implode( ' · ', \array_filter( array( $m['voie'], $m['organisateur'], (int) $m['pages'] > 0 ? $m['pages'] . ' p.' : '' ) ) ) ) . '</span></div>'
				. '<div class="r18-ann__act"><a class="r18-ann__lien" href="' . \esc_url( $m['url'] ) . '" target="_blank" rel="noopener">Ouvrir le PDF<span class="screen-reader-text"> : ' . \esc_html( $types[ $m['type'] ] ?? '' ) . ' ' . \esc_html( $m['epreuve'] . ( (int) $m['session'] > 0 ? ' ' . $m['session'] : '' ) ) . ', sur ' . \esc_html( $host ) . ' (nouvel onglet)</span></a>'
				. ( $q ? '<a class="r18-ann__quiz" href="' . \esc_url( (string) \get_permalink( $q ) ) . '">S\'entraîner en quiz</a>' : '' ) . '</div></li>';
		}
		$h .= '</ul></section>';
	}
	$js = '<script>(function(){var r=document.currentScript.closest(".r18-ann");var g="",t="";var bg=r.querySelectorAll("[data-g]:is(button)"),bt=r.querySelectorAll("[data-t]:is(button)");function go(){var n=0;r.querySelectorAll("section[data-g]").forEach(function(s){var vis=0;s.querySelectorAll("li[data-t]").forEach(function(li){var ok=!t||li.dataset.t===t;li.hidden=!ok;vis+=ok?1:0});var on=(!g||s.dataset.g===g)&&vis>0;s.hidden=!on;n+=on?vis:0});r.querySelector("[data-n]").textContent=n+" document"+(n>1?"s":"")}function bind(L,f){L.forEach(function(b){b.addEventListener("click",function(){L.forEach(function(o){o.setAttribute("aria-pressed",o===b?"true":"false")});f(b);go()})})}bind(bg,function(b){g=b.dataset.g});bind(bt,function(b){t=b.dataset.t});})();</script>';
	$out = '<div class="r18-ann"><div class="r18-ann__f"><div class="r18-qcat__chips" role="group" aria-label="Filtrer par grade">' . $chips . '</div><div class="r18-qcat__chips" role="group" aria-label="Filtrer par type de document">' . $tchip . '</div></div><p class="r18-qcat__count" data-n aria-live="polite">' . \count( $lignes ) . ' documents</p>' . $h . '<p class="r18-ann__note">Chaque lien ouvre le fichier publié par l\'organisateur du concours. Rescue 18 vérifie les liens chaque semaine. Un fichier a disparu ? <a href="/contactez-nous/">Signale-le</a>.</p>' . $js . '</div>';
	\set_transient( 'r18_annales_html', $out, DAY_IN_SECONDS );
	return $out;
}

/**
 * Définition du bloc.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	return array(
		'annales' => array(
			'title'  => 'Sujets des concours',
			'icon'   => 'portfolio',
			'attrs'  => array(),
			'render' => 'Annales\render_annales',
		),
	);
}

/* -------------------------------------------------------------------------
 * « Annales » devient « sujets des concours » (migration unique)
 * ---------------------------------------------------------------------- */

\add_action(
	'init',
	static function (): void {
		if ( '1' === \get_option( 'r18_sujets_migres' ) ) {
			return;
		}
		$seo = '\\G2RD_Rescue18\\SEO\\appliquer';
		// La page : nouvelle adresse, nouveau titre, nouveaux intitulés.
		$page = \get_page_by_path( 'recrutement/concours/annales' );
		if ( $page instanceof \WP_Post ) {
			\wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_name'    => 'sujets',
					'post_title'   => 'Sujets des concours',
					'post_content' => \str_replace( array( 'Les annales des <mark', 'Réviser avec les annales' ), array( 'Les sujets des <mark', 'Réviser avec les sujets' ), $page->post_content ),
				)
			);
			if ( \function_exists( $seo ) ) {
				$seo( \get_post( $page->ID ), true );
			}
		}
		// Les quiz tirés des QCM officiels.
		$quiz = \get_posts(
			array(
				'post_type'      => 'r18_quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'quiz:pack:annales-', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_compare'   => 'LIKE',
			)
		);
		foreach ( $quiz as $q ) {
			\wp_update_post(
				array(
					'ID'         => $q->ID,
					'post_title' => (string) \preg_replace( '/^Annales\s+/u', 'Concours ', $q->post_title ),
					'post_name'  => (string) \preg_replace( '/^annales-/', 'concours-', $q->post_name ),
				)
			);
			if ( \function_exists( $seo ) && 'publish' === $q->post_status ) {
				$seo( \get_post( $q->ID ), true );
			}
		}
		\delete_transient( 'r18_annales_html' );
		\update_option( 'r18_sujets_migres', '1', false );
	},
	30
);

/* Anciennes adresses : redirection permanente. */
\add_action(
	'template_redirect',
	static function (): void {
		$chemin = (string) \wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( \preg_match( '#^/recrutement/concours/annales/?$#', $chemin ) ) {
			\wp_safe_redirect( \home_url( '/recrutement/concours/sujets/' ), 301 );
			exit;
		}
	},
	1
);
