<?php
/**
 * Fiches mémo : révision en une page, à partir des documents officiels.
 *
 * Chaque fiche : points clés, à retenir, pièges à éviter, quiz lié, sources.
 * Tout se modifie au back-office (une ligne par élément). Les fiches livrées
 * dans data/fiches/*.json arrivent « en attente de relecture » et ne sont
 * jamais réécrites après import. Chaque fiche se télécharge en PDF A4 avec le
 * logo Rescue 18 (FPDF, licence permissive, embarqué dans le thème).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Fiches;

\defined( 'ABSPATH' ) || exit;

const CPT = 'r18_fiche';

/**
 * Champs de la fiche : clé => [libellé, type, aide].
 *
 * @return array<string,array{0:string,1:string,2:string}>
 */
function champs(): array {
	return array(
		'r18_f_theme'    => array( 'Thème', 'select', '' ),
		'r18_f_niveau'   => array( 'Niveau', 'select', '' ),
		'r18_f_document' => array( 'Document source', 'text', 'Titre exact du GDO, GTO, GNR ou référentiel.' ),
		'r18_f_cles'     => array( 'Points clés', 'lignes', 'Un point par ligne, 6 à 10 points.' ),
		'r18_f_retenir'  => array( 'À retenir', 'lignes', 'Un élément par ligne : chiffres, règles de sécurité, définitions.' ),
		'r18_f_pieges'   => array( 'Pièges à éviter', 'lignes', 'Une erreur fréquente par ligne.' ),
		'r18_f_quiz'     => array( 'Quiz lié', 'quiz', '' ),
		'r18_f_sources'  => array( 'Sources', 'lignes', 'Une source par ligne : Titre | URL' ),
		'r18_f_image2'   => array( 'Seconde image (ID du média)', 'text', 'La première image est l\'image mise en avant. Le crédit vient de la légende du média.' ),
	);
}

/**
 * Thèmes et niveaux.
 *
 * @return array{0:string[],1:string[]}
 */
function listes(): array {
	return array(
		array( 'Incendie', 'Feux de forêts', 'Risques technologiques', 'Secours à personne', 'Sauvetage et milieux périlleux', 'Commandement' ),
		array( 'Tous niveaux', 'Équipier', 'Chef d\'équipe', 'Chef d\'agrès' ),
	);
}

\add_action(
	'init',
	static function (): void {
		\register_post_type(
			CPT,
			array(
				'labels'        => array(
					'name'          => 'Fiches mémo',
					'singular_name' => 'Fiche mémo',
					'add_new'       => 'Ajouter',
					'add_new_item'  => 'Ajouter une fiche mémo',
					'edit_item'     => 'Modifier la fiche mémo',
					'all_items'     => 'Fiches mémo',
					'menu_name'     => 'Fiches mémo',
					'search_items'  => 'Rechercher',
					'not_found'     => 'Aucune fiche',
				),
				'public'        => true,
				'has_archive'   => false,
				'rewrite'       => array( 'slug' => 'fiche-memo', 'with_front' => false ),
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 26,
				'supports'      => array( 'title', 'editor', 'excerpt', 'revisions', 'thumbnail' ),
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

/**
 * Lignes non vides d'un champ.
 *
 * @param int    $id  Fiche.
 * @param string $cle Champ.
 * @return string[]
 */
function lignes( int $id, string $cle ): array {
	return \array_values( \array_filter( \array_map( 'trim', \preg_split( '/\R/u', (string) \get_post_meta( $id, $cle, true ) ) ?: array() ) ) );
}

/**
 * Sources : [titre, url].
 *
 * @param int $id Fiche.
 * @return array<int,array{0:string,1:string}>
 */
function sources( int $id ): array {
	$out = array();
	foreach ( lignes( $id, 'r18_f_sources' ) as $l ) {
		$p     = \array_map( 'trim', \explode( '|', $l, 2 ) );
		$out[] = array( $p[0], \esc_url_raw( $p[1] ?? '' ) );
	}
	return $out;
}

/**
 * Quiz lié (publié) : identifiant ou 0.
 *
 * @param int $id Fiche.
 * @return int
 */
function quiz( int $id ): int {
	$slug = (string) \get_post_meta( $id, 'r18_f_quiz', true );
	if ( '' === $slug ) {
		return 0;
	}
	$q = \get_posts(
		array(
			'post_type'      => 'r18_quiz',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'r18_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'quiz:pack:' . \sanitize_key( $slug ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) ( $q[0] ?? 0 );
}

/* -------------------------------------------------------------------------
 * Administration
 * ---------------------------------------------------------------------- */

\add_action(
	'add_meta_boxes_' . CPT,
	static function (): void {
		\add_meta_box( 'r18-fiche', 'Contenu de la fiche', __NAMESPACE__ . '\boite', CPT, 'normal', 'high' );
	}
);

/**
 * Boîte de saisie.
 *
 * @param \WP_Post $p Fiche.
 */
function boite( \WP_Post $p ): void {
	\wp_nonce_field( 'r18_fiche', 'r18_fiche_nonce' );
	[ $themes, $niveaux ] = listes();
	$quiz = array();
	foreach ( \glob( \get_stylesheet_directory() . '/data/quiz/*.json' ) ?: array() as $f ) {
		$d = \json_decode( (string) \file_get_contents( $f ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! empty( $d['slug'] ) ) {
			$quiz[ (string) $d['slug'] ] = (string) $d['titre'];
		}
	}
	\asort( $quiz );
	echo '<p>Les listes acceptent une ligne par élément. Le PDF A4 se met à jour à chaque enregistrement.</p><table class="form-table" role="presentation">';
	foreach ( champs() as $k => [ $lib, $type, $aide ] ) {
		$v = (string) \get_post_meta( $p->ID, $k, true );
		echo '<tr><th scope="row"><label for="' . \esc_attr( $k ) . '">' . \esc_html( $lib ) . '</label></th><td>';
		if ( 'select' === $type ) {
			echo '<select id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '">';
			foreach ( 'r18_f_theme' === $k ? $themes : $niveaux as $o ) {
				echo '<option' . \selected( $v, $o, false ) . '>' . \esc_html( $o ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'quiz' === $type ) {
			echo '<select id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '"><option value="">Aucun</option>';
			foreach ( $quiz as $s => $t ) {
				echo '<option value="' . \esc_attr( $s ) . '"' . \selected( $v, $s, false ) . '>' . \esc_html( $t ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'lignes' === $type ) {
			echo '<textarea id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '" rows="6" class="large-text">' . \esc_textarea( $v ) . '</textarea>';
		} else {
			echo '<input id="' . \esc_attr( $k ) . '" name="' . \esc_attr( $k ) . '" type="text" class="large-text" value="' . \esc_attr( $v ) . '">';
		}
		echo '' !== $aide ? '<p class="description">' . \esc_html( $aide ) . '</p>' : '';
		echo '</td></tr>';
	}
	echo '</table>';
	if ( 'publish' === $p->post_status ) {
		echo '<p><a class="button" href="' . \esc_url( url_pdf( $p->ID ) ) . '" target="_blank" rel="noopener">Voir le PDF</a></p>';
	}
}

\add_action(
	'save_post_' . CPT,
	static function ( int $id ): void {
		if ( ! isset( $_POST['r18_fiche_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['r18_fiche_nonce'] ) ), 'r18_fiche' ) || ! \current_user_can( 'edit_post', $id ) || \wp_is_post_revision( $id ) ) {
			return;
		}
		foreach ( champs() as $k => [ , $type ] ) {
			if ( ! isset( $_POST[ $k ] ) ) {
				continue;
			}
			$v = \wp_unslash( $_POST[ $k ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			\update_post_meta( $id, $k, 'lignes' === $type ? \sanitize_textarea_field( (string) $v ) : \sanitize_text_field( (string) $v ) );
		}
	}
);

/* Colonnes de la liste. */
\add_filter(
	'manage_' . CPT . '_posts_columns',
	static function ( array $c ): array {
		$c['r18_f_theme']  = 'Thème';
		$c['r18_f_niveau'] = 'Niveau';
		return $c;
	}
);
\add_action(
	'manage_' . CPT . '_posts_custom_column',
	static function ( string $col, int $id ): void {
		if ( \in_array( $col, array( 'r18_f_theme', 'r18_f_niveau' ), true ) ) {
			echo \esc_html( (string) \get_post_meta( $id, $col, true ) );
		}
	},
	10,
	2
);

/* Rappel de relecture. */
\add_action(
	'admin_notices',
	static function (): void {
		$e = \get_current_screen();
		if ( ! $e || ! \in_array( $e->id, array( 'dashboard' ), true ) || ! \current_user_can( 'edit_posts' ) ) {
			return;
		}
		$n = (int) ( \wp_count_posts( CPT )->pending ?? 0 );
		if ( $n > 0 ) {
			echo '<div class="notice notice-info"><p><strong>' . (int) $n . ' fiche(s) mémo en attente de relecture.</strong> Compare chaque point au document source, puis publie. <a href="' . \esc_url( \admin_url( 'edit.php?post_type=' . CPT . '&post_status=pending' ) ) . '">Voir les fiches à relire</a>.</p></div>';
		}
	}
);

/* -------------------------------------------------------------------------
 * Import du lot livré avec le thème
 * ---------------------------------------------------------------------- */

/**
 * Importe les fiches absentes du site (en attente de relecture).
 *
 * @return string[] Titres importés.
 */
function importer(): array {
	$faits = array();
	foreach ( \glob( \get_stylesheet_directory() . '/data/fiches/*.json' ) ?: array() as $f ) {
		$d = \json_decode( (string) \file_get_contents( $f ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! \is_array( $d ) || empty( $d['slug'] ) || empty( $d['titre'] ) ) {
			continue;
		}
		$src = 'fiche:pack:' . \sanitize_key( (string) $d['slug'] );
		if ( \get_posts( array( 'post_type' => CPT, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'r18_source_id', 'meta_value' => $src ) ) ) { // phpcs:ignore WordPress.DB.SlowDBQuery
			continue;
		}
		$id = (int) \wp_insert_post(
			\wp_slash(
				array(
					'post_type'    => CPT,
					'post_status'  => 'pending',
					'post_title'   => (string) $d['titre'],
					'post_name'    => \sanitize_title( (string) $d['slug'] ),
					'post_excerpt' => (string) ( $d['resume'] ?? '' ),
				)
			)
		);
		if ( $id <= 0 ) {
			continue;
		}
		$src_l = array();
		foreach ( (array) ( $d['sources'] ?? array() ) as $s ) {
			if ( \is_array( $s ) && ! empty( $s[0] ) ) {
				$src_l[] = $s[0] . ( ! empty( $s[1] ) ? ' | ' . $s[1] : '' );
			}
		}
		$meta = array(
			'r18_source_id'  => $src,
			'r18_f_theme'    => (string) ( $d['theme'] ?? 'Incendie' ),
			'r18_f_niveau'   => (string) ( $d['niveau'] ?? 'Tous niveaux' ),
			'r18_f_document' => (string) ( $d['document'] ?? '' ),
			'r18_f_cles'     => \implode( "\n", (array) ( $d['cles'] ?? array() ) ),
			'r18_f_retenir'  => \implode( "\n", (array) ( $d['retenir'] ?? array() ) ),
			'r18_f_pieges'   => \implode( "\n", (array) ( $d['pieges'] ?? array() ) ),
			'r18_f_quiz'     => (string) ( $d['quiz'] ?? '' ),
			'r18_f_sources'  => \implode( "\n", $src_l ),
		);
		foreach ( $meta as $k => $v ) {
			\update_post_meta( $id, $k, \wp_slash( $v ) );
		}
		if ( ! empty( $d['images'] ) && \is_array( $d['images'] ) ) {
			\update_post_meta( $id, 'r18_f_images_a_faire', \wp_slash( (string) \wp_json_encode( $d['images'], JSON_UNESCAPED_UNICODE ) ) );
			\wp_schedule_single_event( \time() + 60 + 20 * \count( $faits ), 'r18_fiche_illustrer', array( $id ) );
		}
		$faits[] = (string) $d['titre'];
	}
	return $faits;
}

/**
 * Illustre une fiche avec des images libres de Wikimedia Commons : téléchargement
 * dans la médiathèque, crédit dans la légende, lien vers la page de l'image.
 *
 * @param int                              $id   Fiche.
 * @param array<int,array<string,string>> $imgs Images : fichier, auteur, licence, page, alt.
 * @return int Images ajoutées.
 */
function illustrer( int $id, array $imgs ): int {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$n    = 0;
	$slug = (string) \get_post_field( 'post_name', $id );
	$slug = '' !== $slug ? $slug : 'fiche-' . $id;
	foreach ( \array_slice( $imgs, 0, 2 ) as $i => $im ) {
		$titre = (string) ( $im['fichier'] ?? '' );
		if ( '' === $titre || ( 0 === $i && \has_post_thumbnail( $id ) ) || ( 1 === $i && \get_post_meta( $id, 'r18_f_image2', true ) ) ) {
			continue;
		}
		$api = 'https://commons.wikimedia.org/w/api.php?' . \http_build_query( array( 'action' => 'query', 'format' => 'json', 'titles' => $titre, 'prop' => 'imageinfo', 'iiprop' => 'url', 'iiurlwidth' => 1600 ) );
		$r   = \wp_safe_remote_get( $api, array( 'timeout' => 20, 'user-agent' => 'Rescue18FichesMemo/1.0 (https://rescue18.com)' ) );
		if ( \is_wp_error( $r ) ) {
			continue;
		}
		$d   = \json_decode( (string) \wp_remote_retrieve_body( $r ), true );
		$pg  = \is_array( $d['query']['pages'] ?? null ) ? \reset( $d['query']['pages'] ) : array();
		$url = (string) ( $pg['imageinfo'][0]['thumburl'] ?? $pg['imageinfo'][0]['url'] ?? '' );
		if ( ! \in_array( (string) \wp_parse_url( $url, PHP_URL_HOST ), array( 'upload.wikimedia.org', 'thumb.wikimedia.org' ), true ) ) {
			continue;
		}
		$tmp = \download_url( $url, 60 );
		if ( \is_wp_error( $tmp ) ) {
			continue;
		}
		$ext = \strtolower( (string) \pathinfo( (string) \wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$att = \media_handle_sideload( array( 'name' => $slug . '-' . ( $i + 1 ) . '.' . ( \in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ? $ext : 'jpg' ), 'tmp_name' => $tmp ), $id, (string) ( $im['alt'] ?? '' ) );
		if ( \is_wp_error( $att ) ) {
			\wp_delete_file( $tmp );
			continue;
		}
		$auteur = \trim( (string) ( $im['auteur'] ?? '' ) );
		$credit = 'Photo : ' . ( '' !== $auteur ? $auteur . ', ' : '' ) . (string) ( $im['licence'] ?? '' ) . ', Wikimedia Commons';
		\wp_update_post( array( 'ID' => $att, 'post_excerpt' => \wp_slash( $credit ) ) );
		\update_post_meta( $att, '_wp_attachment_image_alt', \wp_slash( \sanitize_text_field( (string) ( $im['alt'] ?? '' ) ) ) );
		\update_post_meta( $att, 'r18_credit_url', \esc_url_raw( (string) ( $im['page'] ?? '' ) ) );
		if ( 0 === $i ) {
			\set_post_thumbnail( $id, $att );
		} else {
			\update_post_meta( $id, 'r18_f_image2', (string) $att );
		}
		++$n;
	}
	return $n;
}

/* Illustration en tâche de fond : une fiche par passage, sans ralentir l'import. */
\add_action(
	'r18_fiche_illustrer',
	static function ( int $id = 0 ): void {
		$imgs = \json_decode( (string) \get_post_meta( $id, 'r18_f_images_a_faire', true ), true );
		if ( \is_array( $imgs ) ) {
			illustrer( $id, $imgs );
			\delete_post_meta( $id, 'r18_f_images_a_faire' );
		}
	}
);

\add_action(
	'wp_loaded',
	static function (): void {
		$f = \glob( \get_stylesheet_directory() . '/data/fiches/*.json' ) ?: array();
		$e = \md5( \implode( '|', \array_map( 'basename', $f ) ) );
		if ( \get_option( 'r18_fiches_pack' ) === $e || \wp_installing() ) {
			return;
		}
		\update_option( 'r18_fiches_pack', $e, false );
		importer();
	},
	41
);

/* Une fiche du lot garde son adresse courte à la publication. */
\add_filter(
	'wp_insert_post_data',
	static function ( array $data, array $arr ): array {
		if ( CPT !== ( $data['post_type'] ?? '' ) || '' !== (string) $data['post_name'] || 'publish' !== ( $data['post_status'] ?? '' ) || empty( $arr['ID'] ) ) {
			return $data;
		}
		$src = (string) \get_post_meta( (int) $arr['ID'], 'r18_source_id', true );
		if ( 0 === \strpos( $src, 'fiche:pack:' ) ) {
			$data['post_name'] = \wp_unique_post_slug( \substr( $src, 11 ), (int) $arr['ID'], 'publish', CPT, 0 );
		}
		return $data;
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Affichage
 * ---------------------------------------------------------------------- */

/**
 * Icône associée à un thème.
 *
 * @param string $theme Thème.
 * @return string Nom d'icône.
 */
function icone_theme( string $theme ): string {
	$m = array(
		'Incendie'                       => 'flame',
		'Feux de forêts'                 => 'tree',
		'Secours à personne'             => 'med',
		'Secours routier'                => 'car',
		'Risques technologiques'         => 'alert',
		'Sauvetage et milieux périlleux' => 'compass',
		'Commandement'                   => 'radio',
	);
	return $m[ $theme ] ?? 'book';
}

/**
 * Liste d'éléments, texte enveloppé pour le mode révision.
 *
 * @param string[] $l    Éléments.
 * @param string   $tag  ol ou ul.
 * @param string   $cls  Classe.
 * @return string
 */
function liste_fm( array $l, string $tag, string $cls ): string {
	$h = '';
	foreach ( $l as $i => $x ) {
		$h .= '<li data-n="' . \sprintf( '%02d', $i + 1 ) . '"><span class="r18-fm__t">' . \esc_html( $x ) . '</span></li>';
	}
	return '<' . $tag . ' class="' . \esc_attr( $cls ) . '" role="list">' . $h . '</' . $tag . '>';
}

/**
 * Images de la fiche : image mise en avant puis seconde image.
 *
 * @param int $id Fiche.
 * @return int[]
 */
function images( int $id ): array {
	$l = array( (int) \get_post_thumbnail_id( $id ), \absint( \get_post_meta( $id, 'r18_f_image2', true ) ) );
	return \array_values( \array_filter( $l, static fn( int $x ): bool => $x > 0 && \wp_attachment_is_image( $x ) ) );
}

/**
 * Figure avec crédit (légende du média, lien vers la source).
 *
 * @param int    $att   Média.
 * @param string $cls   Classe.
 * @param string $taille Taille.
 * @return string
 */
function figure( int $att, string $cls, string $taille = 'large' ): string {
	$img = \wp_get_attachment_image( $att, $taille, false, array( 'class' => 'r18-fm__img', 'loading' => 'lazy', 'decoding' => 'async' ) );
	if ( '' === $img ) {
		return '';
	}
	$cr  = \trim( (string) \wp_get_attachment_caption( $att ) );
	$src = (string) \get_post_meta( $att, 'r18_credit_url', true );
	$cap = '' !== $cr ? '<figcaption>' . ( '' !== $src ? '<a href="' . \esc_url( $src ) . '" rel="noopener" target="_blank">' . \esc_html( $cr ) . '</a>' : \esc_html( $cr ) ) . '</figcaption>' : '';
	return '<figure class="' . \esc_attr( $cls ) . '">' . $img . $cap . '</figure>';
}

/**
 * Bloc rescue18/fiche-memo : contenu structuré de la fiche courante.
 *
 * @return string
 */
function render_fiche(): string {
	$id = (int) \get_the_ID();
	if ( CPT !== \get_post_type( $id ) ) {
		return '';
	}
	$ic      = static fn( string $n ): string => \G2RD_Rescue18\icone( $n, 'r18-svg' );
	$cles    = lignes( $id, 'r18_f_cles' );
	$retenir = lignes( $id, 'r18_f_retenir' );
	$pieges  = lignes( $id, 'r18_f_pieges' );
	$q       = quiz( $id );

	$bar = '<div class="r18-fm__bar"><ul class="r18-fm__sum" role="list">'
		. ( $retenir ? '<li><a href="#fm-ret"><strong>' . \count( $retenir ) . '</strong> à retenir</a></li>' : '' )
		. ( $cles ? '<li><a href="#fm-cles"><strong>' . \count( $cles ) . '</strong> points clés</a></li>' : '' )
		. ( $pieges ? '<li><a href="#fm-pie"><strong>' . \count( $pieges ) . '</strong> pièges</a></li>' : '' )
		. '</ul><button type="button" class="r18-fm__rev" aria-pressed="false" data-fm-rev>' . $ic( 'help' ) . '<span>Mode révision</span></button></div>'
		. '<p class="r18-fm__revaide" data-fm-aide hidden>Les réponses sont masquées. Récite chaque point, puis touche-le pour vérifier.</p>';

	$imgs = images( $id );
	$h    = isset( $imgs[0] ) ? figure( $imgs[0], 'r18-fm__fig r18-fm__fig--une' ) : '';
	if ( $retenir ) {
		$h .= '<section class="r18-fm__ret" aria-labelledby="fm-ret"><header class="r18-fm__hd"><span class="r18-fm__ico">' . $ic( 'star' ) . '</span><div><h2 id="fm-ret">À retenir</h2><p>Les chiffres et les règles à connaître par cœur.</p></div></header>' . liste_fm( $retenir, 'ul', 'r18-fm__retl' ) . '</section>';
	}
	if ( $cles ) {
		$h .= '<section class="r18-fm__cles" aria-labelledby="fm-cles"><header class="r18-fm__hd"><span class="r18-fm__ico">' . $ic( 'list' ) . '</span><div><h2 id="fm-cles">Points clés</h2><p>Dans l\'ordre du document officiel.</p></div></header>' . liste_fm( $cles, 'ol', 'r18-fm__steps' ) . '</section>';
	}
	if ( isset( $imgs[1] ) ) {
		$h .= figure( $imgs[1], 'r18-fm__fig' );
	}
	if ( $pieges ) {
		$h .= '<section class="r18-fm__pie" aria-labelledby="fm-pie"><header class="r18-fm__hd"><span class="r18-fm__ico">' . $ic( 'alert' ) . '</span><div><h2 id="fm-pie">Pièges à éviter</h2><p>Les erreurs relevées en formation et en intervention.</p></div></header>' . liste_fm( $pieges, 'ul', 'r18-fm__piel' ) . '</section>';
	}

	$n_pages = \count( $cles ) + \count( $retenir ) + \count( $pieges ) > 22 ? '2 pages' : '1 page';
	$a  = '<div class="r18-fm__dl"><div class="r18-fm__a4" aria-hidden="true"><i></i><b></b><b></b><b></b><b class="s"></b><b></b><b class="s"></b></div>'
		. '<p class="r18-fm__k">Fiche à imprimer</p><p class="r18-fm__dlt">Format A4, ' . $n_pages . ', logo Rescue 18 et source officielle.</p>'
		. '<a class="r18-fm__btn" href="' . \esc_url( url_pdf( $id ) ) . '" download>' . $ic( 'download' ) . '<span>Télécharger le PDF</span></a></div>';
	if ( $q ) {
		$a .= '<a class="r18-fm__quiz" href="' . \esc_url( (string) \get_permalink( $q ) ) . '"><span class="r18-fm__ico">' . $ic( 'check' ) . '</span><span><strong>Teste-toi</strong><small>Le quiz lié à cette fiche</small></span>' . $ic( 'arrow' ) . '</a>';
	}
	$s = sources( $id );
	if ( $s ) {
		$a .= '<div class="r18-fm__src"><p class="r18-fm__k">' . $ic( 'book' ) . 'Source officielle</p><ul role="list">';
		foreach ( $s as [ $t, $u ] ) {
			$a .= '<li>' . ( '' !== $u ? '<a href="' . \esc_url( $u ) . '" rel="noopener" target="_blank">' . \esc_html( $t ) . $ic( 'ext' ) . '<span class="screen-reader-text"> (nouvel onglet)</span></a>' : \esc_html( $t ) ) . '</li>';
		}
		$a .= '</ul><p class="r18-fm__note">Fiche de révision rédigée par Rescue 18 à partir du document officiel. En cas de doute, le document source fait foi.</p></div>';
	}
	$js = '<script>(function(){var r=document.currentScript.closest(".r18-fm");if(!r)return;var b=r.querySelector("[data-fm-rev]"),ai=r.querySelector("[data-fm-aide]");var it=r.querySelectorAll(".r18-fm__retl li,.r18-fm__steps li");function set(on){r.classList.toggle("is-rev",on);b.setAttribute("aria-pressed",on?"true":"false");ai.hidden=!on;it.forEach(function(li){li.classList.remove("is-vu");if(on){li.setAttribute("tabindex","0");li.setAttribute("role","button");li.setAttribute("aria-pressed","false");}else{li.removeAttribute("tabindex");li.removeAttribute("role");li.removeAttribute("aria-pressed");}});}b.addEventListener("click",function(){set(!r.classList.contains("is-rev"));});function vu(li){if(!r.classList.contains("is-rev"))return;var v=li.classList.toggle("is-vu");li.setAttribute("aria-pressed",v?"true":"false");}it.forEach(function(li){li.addEventListener("click",function(){vu(li);});li.addEventListener("keydown",function(e){if(e.key==="Enter"||e.key===" "){e.preventDefault();vu(li);}});});})();</script>';
	return '<div class="r18-fm"><div class="r18-fm__main">' . $bar . $h . '</div><aside class="r18-fm__aside" aria-label="Télécharger et réviser">' . $a . '</aside>' . $js . '</div>';
}

/**
 * Bloc rescue18/fiche-meta : thème, niveau et document de la fiche courante.
 *
 * @return string
 */
function render_meta(): string {
	$id = (int) \get_the_ID();
	if ( CPT !== \get_post_type( $id ) ) {
		return '';
	}
	$th  = (string) \get_post_meta( $id, 'r18_f_theme', true );
	$niv = (string) \get_post_meta( $id, 'r18_f_niveau', true );
	$doc = (string) \get_post_meta( $id, 'r18_f_document', true );
	$h   = '<div class="r18-fm-meta">';
	$h  .= '' !== $th ? '<span class="r18-fm-meta__th">' . \G2RD_Rescue18\icone( icone_theme( $th ) ) . \esc_html( $th ) . '</span>' : '';
	$h  .= '' !== $niv ? '<span class="r18-fm-meta__niv">' . \G2RD_Rescue18\icone( 'users' ) . \esc_html( $niv ) . '</span>' : '';
	$h  .= '' !== $doc ? '<span class="r18-fm-meta__doc">' . \G2RD_Rescue18\icone( 'file' ) . '<span>' . \esc_html( $doc ) . '</span></span>' : '';
	return $h . '</div>';
}

/**
 * Bloc rescue18/fiches-catalogue : toutes les fiches publiées, filtrables.
 *
 * @return string
 */
function render_catalogue(): string {
	$ids = \get_posts(
		array(
			'post_type'      => CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	if ( ! $ids ) {
		return '<p class="r18-empty">Les premières fiches mémo arrivent après relecture par l’équipe.</p>';
	}
	$themes = array();
	$cartes = '';
	foreach ( $ids as $id ) {
		$t   = (string) \get_post_meta( $id, 'r18_f_theme', true );
		$k   = \sanitize_title( $t );
		$niv = (string) \get_post_meta( $id, 'r18_f_niveau', true );
		$nc  = \count( lignes( $id, 'r18_f_cles' ) );
		$themes[ $k ] = array( $t, ( $themes[ $k ][1] ?? 0 ) + 1 );
		$titre = \get_the_title( $id );
		$vig   = \has_post_thumbnail( $id ) ? '<span class="r18-fmc__img">' . \get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ) . '</span>' : '';
		$cartes .= '<li class="r18-fmc__item" data-theme="' . \esc_attr( $k ) . '" data-q="' . \esc_attr( \strtolower( \remove_accents( $titre . ' ' . $t ) ) ) . '"><a class="r18-fmc__card' . ( '' !== $vig ? ' has-img' : '' ) . '" href="' . \esc_url( (string) \get_permalink( $id ) ) . '">' . $vig
			. '<span class="r18-fmc__top"><span class="r18-fmc__ico">' . \G2RD_Rescue18\icone( icone_theme( $t ) ) . '</span><span class="r18-fmc__theme">' . \esc_html( $t ) . '</span></span>'
			. '<strong class="r18-fmc__t">' . \esc_html( $titre ) . '</strong>'
			. '<span class="r18-fmc__ft"><span class="r18-fmc__niv">' . \esc_html( $niv ) . '</span><span class="r18-fmc__n">' . (int) $nc . ' points · PDF</span>' . \G2RD_Rescue18\icone( 'arrow', 'r18-svg r18-fmc__go' ) . '</span></a></li>';
	}
	\uasort( $themes, static fn( $x, $y ) => \strcmp( $x[0], $y[0] ) );
	$chips = '<button type="button" class="r18-tk-chip" aria-pressed="true" data-theme="">Toutes <span>' . \count( $ids ) . '</span></button>';
	foreach ( $themes as $k => [ $t, $n ] ) {
		$chips .= '<button type="button" class="r18-tk-chip" aria-pressed="false" data-theme="' . \esc_attr( $k ) . '">' . \G2RD_Rescue18\icone( icone_theme( $t ) ) . \esc_html( $t ) . ' <span>' . (int) $n . '</span></button>';
	}
	$js = '<script>(function(){var r=document.currentScript.closest(".r18-fmc");var b=r.querySelectorAll("button[data-theme]"),l=r.querySelectorAll("li[data-theme]"),s=r.querySelector("[data-n]"),q=r.querySelector("input"),v=r.querySelector("[data-vide]"),th="";function plat(x){return x.normalize("NFD").replace(/[̀-ͯ]/g,"").toLowerCase();}function go(){var t=plat(q.value.trim()),n=0;l.forEach(function(i){var ok=(!th||i.dataset.theme===th)&&(!t||i.dataset.q.indexOf(t)>-1);i.hidden=!ok;n+=ok?1:0;});s.textContent=n+" fiche"+(n>1?"s":"");v.hidden=n>0;}b.forEach(function(x){x.addEventListener("click",function(){th=x.dataset.theme;b.forEach(function(o){o.setAttribute("aria-pressed",o===x?"true":"false");});go();});});q.addEventListener("input",go);})();</script>';
	return '<div class="r18-fmc"><div class="r18-fmc__tools"><label class="r18-fmc__search"><span class="screen-reader-text">Rechercher une fiche</span>' . \G2RD_Rescue18\icone( 'search' ) . '<input type="search" placeholder="Rechercher : garrot, BLEVE, balisage…" autocomplete="off"></label><p class="r18-fmc__count" aria-live="polite"><strong data-n>' . \count( $ids ) . ' fiches</strong></p></div>'
		. '<div class="r18-fmc__chips" role="group" aria-label="Filtrer par thème">' . $chips . '</div>'
		. '<ul class="r18-fmc__list" role="list">' . $cartes . '</ul><p class="r18-empty" data-vide hidden>Aucune fiche ne correspond. Essaie un autre mot.</p>' . $js . '</div>';
}

/**
 * Définitions des blocs.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	return array(
		'fiche-memo'       => array(
			'title'  => 'Fiche mémo : contenu',
			'icon'   => 'media-document',
			'attrs'  => array(),
			'render' => 'Fiches\render_fiche',
		),
		'fiche-meta'       => array(
			'title'  => 'Fiche mémo : thème et niveau',
			'icon'   => 'tag',
			'attrs'  => array(),
			'render' => 'Fiches\render_meta',
		),
		'fiches-catalogue' => array(
			'title'  => 'Catalogue des fiches mémo',
			'icon'   => 'index-card',
			'attrs'  => array(),
			'render' => 'Fiches\render_catalogue',
		),
	);
}

/* -------------------------------------------------------------------------
 * PDF A4
 * ---------------------------------------------------------------------- */

/**
 * Adresse du PDF.
 *
 * @param int $id Fiche.
 * @return string
 */
function url_pdf( int $id ): string {
	return \add_query_arg( 'pdf', '1', (string) \get_permalink( $id ) );
}

\add_filter(
	'query_vars',
	static function ( array $v ): array {
		$v[] = 'pdf';
		return $v;
	}
);

\add_action(
	'template_redirect',
	static function (): void {
		if ( ! \is_singular( CPT ) || '1' !== (string) \get_query_var( 'pdf' ) ) {
			return;
		}
		$id = (int) \get_queried_object_id();
		$p  = \get_post( $id );
		if ( ! $p instanceof \WP_Post || ( 'publish' !== $p->post_status && ! \current_user_can( 'edit_post', $id ) ) ) {
			return;
		}
		$up   = \wp_upload_dir();
		$dir  = \trailingslashit( $up['basedir'] ) . 'rescue18/fiches';
		$nom  = '' !== $p->post_name ? $p->post_name : 'fiche-' . $id;
		$file = $dir . '/' . $nom . '-v2-' . \strtotime( $p->post_modified ) . '-' . \substr( \md5( (string) \wp_json_encode( \get_post_meta( $id ) ) ), 0, 8 ) . '.pdf';
		if ( ! \is_file( $file ) ) {
			\wp_mkdir_p( $dir );
			foreach ( \glob( $dir . '/' . $nom . '-*.pdf' ) ?: array() as $vieux ) {
				\wp_delete_file( $vieux );
			}
			\file_put_contents( $file, pdf( $id ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		\nocache_headers();
		\header( 'Content-Type: application/pdf' );
		\header( 'Content-Disposition: attachment; filename="fiche-memo-' . \sanitize_file_name( $nom ) . '.pdf"' );
		\header( 'Content-Length: ' . (string) \filesize( $file ) );
		\readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/**
 * Texte UTF-8 vers l'encodage des polices FPDF (Windows-1252).
 *
 * @param string $t Texte.
 * @return string
 */
function w( string $t ): string {
	$t = \strtr( $t, array( "\u{2009}" => ' ', "\u{202F}" => ' ', "\u{00A0}" => ' ', '≈' => '~', '≥' => '>=', '≤' => '<=', '→' => '->', '×' => 'x' ) );
	$r = \iconv( 'UTF-8', 'windows-1252//TRANSLIT', $t );
	return false === $r ? $t : $r;
}

/**
 * Génère le PDF A4 d'une fiche.
 *
 * @param int $id Fiche.
 * @return string Contenu binaire.
 */
function pdf( int $id ): string {
	require_once \get_stylesheet_directory() . '/assets/vendor/fpdf/fpdf.php';
	$d = array(
		'titre'   => \get_the_title( $id ),
		'resume'  => (string) \get_post_field( 'post_excerpt', $id ),
		'theme'   => (string) \get_post_meta( $id, 'r18_f_theme', true ),
		'niveau'  => (string) \get_post_meta( $id, 'r18_f_niveau', true ),
		'doc'     => (string) \get_post_meta( $id, 'r18_f_document', true ),
		'cles'    => lignes( $id, 'r18_f_cles' ),
		'retenir' => lignes( $id, 'r18_f_retenir' ),
		'pieges'  => lignes( $id, 'r18_f_pieges' ),
		'sources' => sources( $id ),
		'url'     => (string) \get_permalink( $id ),
		'logo'       => \get_stylesheet_directory() . '/assets/img/logo-rescue18-signature.png',
		'logo_blanc' => \get_stylesheet_directory() . '/assets/img/logo-rescue18-signature-blanc.png',
		'image'      => image_pdf( $id ),
		'credit'     => ( $t = (int) \get_post_thumbnail_id( $id ) ) ? (string) \wp_get_attachment_caption( $t ) : '',
		'date'    => \wp_date( 'j F Y', \max( (int) \get_post_modified_time( 'U', true, $id ), (int) \get_post_modified_time( 'U', false, $id ) ) ),
	);
	return generer_pdf( $d );
}

/**
 * Fichier de l'image principale pour le PDF (JPEG, taille « large » si possible).
 *
 * @param int $id Fiche.
 * @return string Chemin ou vide.
 */
function image_pdf( int $id ): string {
	$att = (int) \get_post_thumbnail_id( $id );
	if ( ! $att ) {
		return '';
	}
	$f = (string) \get_attached_file( $att );
	$t = \image_get_intermediate_size( $att, 'large' );
	if ( \is_array( $t ) && ! empty( $t['path'] ) ) {
		$f = \trailingslashit( \wp_upload_dir()['basedir'] ) . $t['path'];
	}
	return \is_readable( $f ) && \preg_match( '/\\.(jpe?g|png)$/i', $f ) ? $f : '';
}

/**
 * Mise en page A4 (sans WordPress : testable).
 *
 * @param array<string,mixed> $d Données.
 * @return string
 */
function generer_pdf( array $d ): string {
	$pdf = new class( 'P', 'mm', 'A4' ) extends \FPDF {
		/** @var string */
		public $pied = '';
		/** @var string */
		public $logo_petit = '';
		/** @var string */
		public $titre_court = '';

		/* Rectangle à coins arrondis (F, D ou DF). */
		public function Arrondi( float $x, float $y, float $w, float $h, float $r, string $style = 'F' ): void { // phpcs:ignore
			$k  = $this->k;
			$hp = $this->h;
			$op = array( 'F' => 'f', 'D' => 'S', 'DF' => 'B', 'CLIP' => 'W n' )[ $style ] ?? 'f';
			$m  = 4 / 3 * ( \sqrt( 2 ) - 1 );
			$this->_out( \sprintf( '%.2F %.2F m', ( $x + $r ) * $k, ( $hp - $y ) * $k ) );
			$this->_out( \sprintf( '%.2F %.2F l', ( $x + $w - $r ) * $k, ( $hp - $y ) * $k ) );
			$this->arc( $x + $w - $r + $r * $m, $y, $x + $w, $y + $r - $r * $m, $x + $w, $y + $r );
			$this->_out( \sprintf( '%.2F %.2F l', ( $x + $w ) * $k, ( $hp - ( $y + $h - $r ) ) * $k ) );
			$this->arc( $x + $w, $y + $h - $r + $r * $m, $x + $w - $r + $r * $m, $y + $h, $x + $w - $r, $y + $h );
			$this->_out( \sprintf( '%.2F %.2F l', ( $x + $r ) * $k, ( $hp - ( $y + $h ) ) * $k ) );
			$this->arc( $x + $r - $r * $m, $y + $h, $x, $y + $h - $r + $r * $m, $x, $y + $h - $r );
			$this->_out( \sprintf( '%.2F %.2F l', $x * $k, ( $hp - ( $y + $r ) ) * $k ) );
			$this->arc( $x, $y + $r - $r * $m, $x + $r - $r * $m, $y, $x + $r, $y );
			$this->_out( $op );
		}
		private function arc( float $x1, float $y1, float $x2, float $y2, float $x3, float $y3 ): void {
			$h = $this->h;
			$this->_out( \sprintf( '%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ( $h - $y1 ) * $this->k, $x2 * $this->k, ( $h - $y2 ) * $this->k, $x3 * $this->k, ( $h - $y3 ) * $this->k ) );
		}
		/* Disque plein. */
		public function Disque( float $x, float $y, float $r ): void { // phpcs:ignore
			$this->Arrondi( $x - $r, $y - $r, 2 * $r, 2 * $r, $r, 'F' );
		}
		/* Image recadrée dans un cadre arrondi (remplissage, sans déformation). */
		public function ImageCadre( string $f, float $x, float $y, float $w, float $h, float $r ): void { // phpcs:ignore
			$s = @\getimagesize( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$t = array( 'image/jpeg' => 'JPG', 'image/png' => 'PNG' )[ $s['mime'] ?? '' ] ?? '';
			if ( ! $s || $s[0] < 1 || '' === $t ) {
				return;
			}
			$ratio = $s[0] / $s[1];
			$iw    = $w;
			$ih    = $w / $ratio;
			if ( $ih < $h ) {
				$ih = $h;
				$iw = $h * $ratio;
			}
			$this->_out( 'q' );
			$this->Arrondi( $x, $y, $w, $h, $r, 'CLIP' );
			$this->Image( $f, $x - ( $iw - $w ) / 2, $y - ( $ih - $h ) / 2, $iw, $ih, $t );
			$this->_out( 'Q' );
		}
		/* Nombre de lignes d'un texte dans une largeur donnée. */
		public function NbLignes( float $w, string $txt ): int { // phpcs:ignore
			$cw   = $this->CurrentFont['cw'];
			$wmax = ( $w - 2 * $this->cMargin ) * 1000 / $this->FontSize;
			$s    = \str_replace( "\r", '', $txt );
			$nb   = \strlen( $s );
			$sep  = -1;
			$i    = 0;
			$j    = 0;
			$l    = 0;
			$nl   = 1;
			while ( $i < $nb ) {
				$c = $s[ $i ];
				if ( "\n" === $c ) {
					++$i;
					$sep = -1;
					$j   = $i;
					$l   = 0;
					++$nl;
					continue;
				}
				if ( ' ' === $c ) {
					$sep = $i;
				}
				$l += $cw[ $c ] ?? 500;
				if ( $l > $wmax ) {
					if ( -1 === $sep ) {
						if ( $i === $j ) {
							++$i;
						}
					} else {
						$i = $sep + 1;
					}
					$sep = -1;
					$j   = $i;
					$l   = 0;
					++$nl;
				} else {
					++$i;
				}
			}
			return $nl;
		}
		public function Header() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			if ( $this->PageNo() < 2 ) {
				return;
			}
			$this->SetFillColor( 16, 20, 29 );
			$this->Rect( 0, 0, 210, 14, 'F' );
			$this->SetFillColor( 226, 20, 34 );
			$this->Rect( 0, 14, 210, 0.9, 'F' );
			if ( '' !== $this->logo_petit && \is_readable( $this->logo_petit ) ) {
				$this->Image( $this->logo_petit, 14, 3.6, 24 );
			}
			$this->SetXY( 60, 4 );
			$this->SetFont( 'Helvetica', 'B', 8 );
			$this->SetTextColor( 255, 255, 255 );
			$this->Cell( 136, 6, $this->titre_court, 0, 0, 'R' );
			$this->SetY( 22 );
		}
		public function Footer() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
			$this->SetY( -13 );
			$this->SetDrawColor( 225, 229, 233 );
			$this->SetLineWidth( 0.2 );
			$this->Line( 14, $this->GetY() - 1.5, 196, $this->GetY() - 1.5 );
			$this->SetFont( 'Helvetica', '', 7 );
			$this->SetTextColor( 83, 93, 106 );
			$this->Cell( 150, 5, $this->pied, 0, 0, 'L' );
			$this->SetFont( 'Helvetica', 'B', 7 );
			$this->Cell( 32, 5, w( 'Page ' ) . $this->PageNo() . '/{nb}', 0, 0, 'R' );
		}
	};
	$rouge = array( 226, 20, 34 );
	$rtext = array( 190, 12, 25 );
	$encre = array( 17, 19, 25 );
	$gris  = array( 79, 89, 104 );
	$nuit  = array( 16, 20, 29 );
	$rose  = array( 255, 240, 242 );
	$L     = 14;   // Marge gauche.
	$W     = 182;  // Largeur utile.
	$bas   = 280;  // Limite basse du contenu.

	$pdf->AliasNbPages();
	$pdf->SetMargins( $L, 22, $L );
	$pdf->SetAutoPageBreak( true, 18 );
	$pdf->SetTitle( w( 'Fiche mémo : ' . $d['titre'] ) );
	$pdf->SetAuthor( 'Rescue 18' );
	$pdf->SetCreator( 'rescue18.com' );
	$pdf->pied        = w( \preg_replace( '#^https?://#', '', $d['url'] ) . '   |   Mise à jour le ' . $d['date'] . '   |   Le document officiel fait foi' );
	$pdf->logo_petit  = (string) ( $d['logo_blanc'] ?? '' );
	$pdf->titre_court = w( \mb_strimwidth( (string) $d['titre'], 0, 90, '…' ) );
	$pdf->AddPage();

	// Bandeau : logo, nature du document, thème et niveau.
	$pdf->SetFillColor( ...$nuit );
	$pdf->Rect( 0, 0, 210, 32, 'F' );
	$pdf->SetFillColor( ...$rouge );
	$pdf->Rect( 0, 32, 210, 1.2, 'F' );
	if ( ! empty( $d['logo_blanc'] ) && \is_readable( $d['logo_blanc'] ) ) {
		$pdf->Image( $d['logo_blanc'], $L, 8.5, 50 );
	}
	$pdf->SetFont( 'Helvetica', 'B', 7.5 );
	$pdf->SetTextColor( 255, 138, 147 );
	$pdf->SetXY( 110, 8 );
	$pdf->Cell( 86, 4, w( 'FICHE MÉMO DE RÉVISION' ), 0, 2, 'R' );
	$th = w( (string) $d['theme'] );
	$pdf->SetFont( 'Helvetica', 'B', 8.5 );
	$lt = $pdf->GetStringWidth( $th ) + 8;
	$nv = w( (string) $d['niveau'] );
	$ln = '' !== $nv ? $pdf->GetStringWidth( $nv ) + 8 : 0;
	$xp = 196 - $lt - ( $ln ? $ln + 2 : 0 );
	if ( '' !== $th ) {
		$pdf->SetFillColor( ...$rouge );
		$pdf->Arrondi( $xp, 15, $lt, 7, 3.5, 'F' );
		$pdf->SetTextColor( 255, 255, 255 );
		$pdf->SetXY( $xp, 15 );
		$pdf->Cell( $lt, 7, $th, 0, 0, 'C' );
	}
	if ( $ln ) {
		$pdf->SetDrawColor( 120, 128, 140 );
		$pdf->SetLineWidth( 0.3 );
		$pdf->Arrondi( 196 - $ln, 15, $ln, 7, 3.5, 'D' );
		$pdf->SetTextColor( 230, 233, 238 );
		$pdf->SetXY( 196 - $ln, 15 );
		$pdf->Cell( $ln, 7, $nv, 0, 0, 'C' );
	}

	// Titre, résumé et image.
	$img  = (string) ( $d['image'] ?? '' );
	$aimg = '' !== $img && \is_readable( $img );
	$tw   = $aimg ? 112 : $W;
	$y0   = 39;
	$pdf->SetXY( $L, $y0 );
	$pdf->SetFont( 'Helvetica', 'B', 16 );
	$pdf->SetTextColor( ...$encre );
	$pdf->MultiCell( $tw, 6.6, w( $d['titre'] ), 0, 'L' );
	$pdf->Ln( 1.5 );
	if ( '' !== $d['resume'] ) {
		$pdf->SetFont( 'Helvetica', '', 8.8 );
		$pdf->SetTextColor( ...$gris );
		$pdf->MultiCell( $tw, 4.2, w( $d['resume'] ), 0, 'L' );
	}
	if ( '' !== $d['doc'] ) {
		$pdf->Ln( 1.5 );
		$pdf->SetFont( 'Helvetica', 'I', 7.5 );
		$pdf->SetTextColor( ...$gris );
		$pdf->MultiCell( $tw, 3.8, w( 'Source : ' . $d['doc'] ), 0, 'L' );
	}
	$yt = $pdf->GetY();
	if ( $aimg ) {
		$pdf->ImageCadre( $img, 134, $y0, 62, 40, 3 );
		$cr = (string) ( $d['credit'] ?? '' );
		if ( '' !== $cr ) {
			$pdf->SetFont( 'Helvetica', '', 5.6 );
			$pdf->SetTextColor( 130, 138, 150 );
			$pdf->SetXY( 134, $y0 + 40.8 );
			$pdf->MultiCell( 62, 2.6, w( $cr ), 0, 'R' );
		}
		$yt = \max( $yt, $y0 + 46 );
	}
	$pdf->SetY( $yt + 3 );

	$entete = static function ( string $t, string $sous, array $c ) use ( $pdf, $L ): void {
		$y = $pdf->GetY();
		$pdf->SetFillColor( ...$c );
		$pdf->Arrondi( $L, $y + 0.6, 2.2, 6, 1.1, 'F' );
		$pdf->SetXY( $L + 5, $y );
		$pdf->SetFont( 'Helvetica', 'B', 12 );
		$pdf->SetTextColor( 17, 19, 25 );
		$tw = $pdf->GetStringWidth( w( $t ) );
		$pdf->Cell( $tw + 3, 7, w( $t ), 0, 0 );
		$pdf->SetFont( 'Helvetica', '', 8 );
		$pdf->SetTextColor( 120, 128, 140 );
		$pdf->Cell( 100, 7.4, w( $sous ), 0, 1 );
		$pdf->Ln( 0.8 );
	};

	// À retenir : encadré rose, numéros rouges.
	if ( $d['retenir'] ) {
		$pdf->SetFont( 'Helvetica', 'B', 8.6 );
		$h = 5;
		foreach ( $d['retenir'] as $l ) {
			$h += 4.1 * $pdf->NbLignes( $W - 22, w( $l ) ) + 1.6;
		}
		if ( $pdf->GetY() + $h + 10 > $bas ) {
			$pdf->AddPage();
		}
		$entete( 'À retenir', 'Les chiffres et les règles à connaître par cœur', $rouge );
		$y0 = $pdf->GetY();
		$pdf->SetFillColor( ...$rose );
		$pdf->Arrondi( $L, $y0, $W, $h, 3, 'F' );
		$pdf->SetFillColor( ...$rouge );
		$pdf->Rect( $L, $y0 + 3, 1.2, $h - 6, 'F' );
		$pdf->SetY( $y0 + 2.8 );
		foreach ( $d['retenir'] as $i => $l ) {
			$y = $pdf->GetY();
			$pdf->SetXY( $L + 5, $y );
			$pdf->SetFont( 'Helvetica', 'B', 9 );
			$pdf->SetTextColor( ...$rouge );
			$pdf->Cell( 9, 4.1, \sprintf( '%02d', $i + 1 ) );
			$pdf->SetXY( $L + 15, $y );
			$pdf->SetFont( 'Helvetica', 'B', 8.6 );
			$pdf->SetTextColor( ...$encre );
			$pdf->MultiCell( $W - 20, 4.1, w( $l ), 0, 'L' );
			$pdf->Ln( 1.6 );
		}
		$pdf->SetY( $y0 + $h + 4 );
	}

	// Points clés : pastilles numérotées.
	if ( $d['cles'] ) {
		if ( $pdf->GetY() + 30 > $bas ) {
			$pdf->AddPage();
		}
		$entete( 'Points clés', 'Dans l\'ordre du document officiel', $encre );
		$pdf->SetDrawColor( 230, 233, 238 );
		$pdf->SetLineWidth( 0.2 );
		foreach ( $d['cles'] as $i => $l ) {
			$pdf->SetFont( 'Helvetica', '', 8.6 );
			$hl = 4.0 * $pdf->NbLignes( $W - 12, w( $l ) );
			if ( $pdf->GetY() + $hl > $bas ) {
				$pdf->AddPage();
			}
			$y = $pdf->GetY();
			$pdf->SetFillColor( ...$rouge );
			$pdf->Disque( $L + 3, $y + 2, 2.5 );
			$pdf->SetFont( 'Helvetica', 'B', 7 );
			$pdf->SetTextColor( 255, 255, 255 );
			$pdf->SetXY( $L + 0.3, $y );
			$pdf->Cell( 5.4, 4, (string) ( $i + 1 ), 0, 0, 'C' );
			$pdf->SetXY( $L + 9, $y );
			$pdf->SetFont( 'Helvetica', '', 8.6 );
			$pdf->SetTextColor( ...$encre );
			$pdf->MultiCell( $W - 9, 4.0, w( $l ), 0, 'L' );
			$yn = $pdf->GetY() + 0.9;
			if ( $i < \count( $d['cles'] ) - 1 ) {
				$pdf->Line( $L + 9, $yn, $L + $W, $yn );
			}
			$pdf->SetY( $yn + 1.0 );
		}
		$pdf->Ln( 2.5 );
	}

	// Pièges : encadré blanc filet rouge, croix.
	if ( $d['pieges'] ) {
		$pdf->SetFont( 'Helvetica', '', 8.6 );
		$h = 4;
		foreach ( $d['pieges'] as $l ) {
			$h += 4.0 * $pdf->NbLignes( $W - 16, w( $l ) ) + 1.4;
		}
		if ( $pdf->GetY() + $h + 10 > $bas ) {
			$pdf->AddPage();
		}
		$entete( 'Pièges à éviter', 'Les erreurs relevées en formation et en intervention', $rtext );
		$y0 = $pdf->GetY();
		$pdf->SetDrawColor( 243, 196, 202 );
		$pdf->SetLineWidth( 0.35 );
		$pdf->Arrondi( $L, $y0, $W, $h, 3, 'D' );
		$pdf->SetY( $y0 + 2.4 );
		foreach ( $d['pieges'] as $l ) {
			$y = $pdf->GetY();
			$pdf->SetFillColor( ...$rtext );
			$pdf->Disque( $L + 6, $y + 2, 2.1 );
			$pdf->SetDrawColor( 255, 255, 255 );
			$pdf->SetLineWidth( 0.45 );
			$pdf->Line( $L + 5.1, $y + 1.1, $L + 6.9, $y + 2.9 );
			$pdf->Line( $L + 6.9, $y + 1.1, $L + 5.1, $y + 2.9 );
			$pdf->SetXY( $L + 11, $y );
			$pdf->SetFont( 'Helvetica', '', 8.6 );
			$pdf->SetTextColor( ...$encre );
			$pdf->MultiCell( $W - 14, 4.0, w( $l ), 0, 'L' );
			$pdf->Ln( 1.4 );
		}
		$pdf->SetY( $y0 + $h + 3 );
	}

	// Source officielle : une ligne, le lien vers le document.
	if ( $d['sources'] ) {
		$txt = array();
		foreach ( $d['sources'] as [ $t, $u ] ) {
			$txt[] = $t . ( '' !== $u ? ' : ' . $u : '' );
		}
		$pdf->SetFont( 'Helvetica', '', 7 );
		// Le document est déjà cité sous le titre : sans place, la ligne du lien saute plutôt que d'ouvrir une page.
		if ( $pdf->GetY() + 3.4 * $pdf->NbLignes( $W - 26, w( \implode( ' | ', $txt ) ) ) > $bas ) {
			return $pdf->Output( 'S' );
		}
		$pdf->SetX( $L );
		$pdf->SetFont( 'Helvetica', 'B', 7 );
		$pdf->SetTextColor( ...$encre );
		$pdf->Cell( 24, 3.4, w( 'Source officielle' ), 0, 0 );
		$pdf->SetFont( 'Helvetica', '', 7 );
		$pdf->SetTextColor( ...$gris );
		$pdf->MultiCell( $W - 24, 3.4, w( \implode( ' | ', $txt ) ), 0, 'L' );
	}
	return $pdf->Output( 'S' );
}
