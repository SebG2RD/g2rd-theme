<?php
/**
 * Types de contenu et taxonomies métier.
 *
 * Les structures et contenus restent dans WordPress (consigne de transmission) :
 * le thème ne fait qu'afficher. Les URL historiques des rubriques
 * (/documentations-referentiels/, /retex/, /guides-…-gto/, /…/juridique/)
 * restent des pages ; les fiches individuelles ont leurs propres URL,
 * sans archive concurrente (has_archive à false).
 *
 *   r18_document  GDO, GTO, GNR, RETEX / PEX, référentiels       /document/<fiche>/
 *   r18_texte     Lois, décrets, arrêtés suivis via Légifrance  /texte-juridique/<texte>/
 *   r18_episode   Épisodes de SSUAP Le Podcast                  /podcast/<episode>/
 *   r18_quiz      Quiz publics, sans compte ni score enregistré /quiz/<quiz>/
 *
 * Taxonomies : r18_type_doc, r18_specialite (partagée avec les articles),
 * r18_organisme, r18_public.
 *
 * Les métadonnées sont déclarées une fois (fields()) : register_post_meta
 * pour l'API REST et le cycle de synchronisation, et une boîte de saisie
 * native dans l'éditeur, sans build.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Valeurs de statut documentaire.
 *
 * @return array<string,string>
 */
function statuts_document(): array {
	return array(
		'en-ligne'      => \__( 'En ligne', 'g2rd-rescue18-child' ),
		'nouveau'       => \__( 'Nouveau', 'g2rd-rescue18-child' ),
		'mis-a-jour'    => \__( 'Mis à jour', 'g2rd-rescue18-child' ),
		'a-verifier'    => \__( 'Version à vérifier', 'g2rd-rescue18-child' ),
		'en-validation' => \__( 'En validation', 'g2rd-rescue18-child' ),
	);
}

/**
 * États juridiques. Un état non confirmé par la source reste « À vérifier ».
 *
 * @return array<string,string>
 */
function etats_juridiques(): array {
	return array(
		'a-verifier' => \__( 'À vérifier', 'g2rd-rescue18-child' ),
		'en-vigueur' => \__( 'En vigueur', 'g2rd-rescue18-child' ),
		'modifie'    => \__( 'Modifié', 'g2rd-rescue18-child' ),
		'abroge'     => \__( 'Abrogé', 'g2rd-rescue18-child' ),
	);
}

/**
 * Natures de textes juridiques.
 *
 * @return array<string,string>
 */
function natures_textes(): array {
	return array(
		'loi'         => \__( 'Loi', 'g2rd-rescue18-child' ),
		'ordonnance'  => \__( 'Ordonnance', 'g2rd-rescue18-child' ),
		'decret'      => \__( 'Décret', 'g2rd-rescue18-child' ),
		'arrete'      => \__( 'Arrêté', 'g2rd-rescue18-child' ),
		'circulaire'  => \__( 'Circulaire', 'g2rd-rescue18-child' ),
		'instruction' => \__( 'Instruction', 'g2rd-rescue18-child' ),
	);
}

/**
 * Champs par type de contenu : clé => [ libellé, type, options ].
 *
 * Types : text, url, date, select, textarea, json, bool, int.
 * Le champ « versions » attend un JSON : [{"date":"2026-07-01","label":"Édition juillet 2026","note":"…"}].
 *
 * @return array<string,array<string,array{0:string,1:string,2?:array<string,string>}>>
 */
function fields(): array {
	$versions = array( \__( 'Historique des versions (JSON)', 'g2rd-rescue18-child' ), 'json' );
	return array(
		'r18_document' => array(
			'r18_statut'         => array( \__( 'Statut', 'g2rd-rescue18-child' ), 'select', statuts_document() ),
			'r18_version'        => array( \__( 'Version / édition', 'g2rd-rescue18-child' ), 'text' ),
			'r18_date_edition'   => array( \__( 'Date de l\'édition', 'g2rd-rescue18-child' ), 'date' ),
			'r18_source_url'     => array( \__( 'Publication officielle (URL)', 'g2rd-rescue18-child' ), 'url' ),
			'r18_pdf_url'        => array( \__( 'Fichier PDF (URL)', 'g2rd-rescue18-child' ), 'url' ),
			'r18_pdf_autorise'   => array( \__( 'Reproduction du PDF autorisée', 'g2rd-rescue18-child' ), 'bool' ),
			'r18_source_id'      => array( \__( 'Identifiant source', 'g2rd-rescue18-child' ), 'text' ),
			'r18_empreinte'      => array( \__( 'Empreinte du fichier (sha256)', 'g2rd-rescue18-child' ), 'text' ),
			'r18_derniere_verif' => array( \__( 'Dernière vérification', 'g2rd-rescue18-child' ), 'date' ),
			'r18_versions'       => $versions,
		),
		'r18_texte'    => array(
			'r18_nature'           => array( \__( 'Nature du texte', 'g2rd-rescue18-child' ), 'select', natures_textes() ),
			'r18_etat'             => array( \__( 'État juridique', 'g2rd-rescue18-child' ), 'select', etats_juridiques() ),
			'r18_numero'           => array( \__( 'Numéro', 'g2rd-rescue18-child' ), 'text' ),
			'r18_nor'              => array( \__( 'NOR', 'g2rd-rescue18-child' ), 'text' ),
			'r18_jorf'             => array( \__( 'Identifiant JORF', 'g2rd-rescue18-child' ), 'text' ),
			'r18_legi'             => array( \__( 'Identifiant LEGI', 'g2rd-rescue18-child' ), 'text' ),
			'r18_signataire'       => array( \__( 'Ministère signataire', 'g2rd-rescue18-child' ), 'text' ),
			'r18_date_signature'   => array( \__( 'Date de signature', 'g2rd-rescue18-child' ), 'date' ),
			'r18_date_jo'          => array( \__( 'Publication au JO', 'g2rd-rescue18-child' ), 'date' ),
			'r18_date_vigueur'     => array( \__( 'Entrée en vigueur', 'g2rd-rescue18-child' ), 'date' ),
			'r18_date_modif'       => array( \__( 'Dernière modification constatée', 'g2rd-rescue18-child' ), 'date' ),
			'r18_derniere_synchro' => array( \__( 'Dernière synchronisation', 'g2rd-rescue18-child' ), 'text' ),
			'r18_source_url'       => array( \__( 'Lien Légifrance', 'g2rd-rescue18-child' ), 'url' ),
			'r18_versions'         => $versions,
		),
		'r18_episode'  => array(
			'r18_numero_episode' => array( \__( 'Numéro (ex. 99 ou HS01)', 'g2rd-rescue18-child' ), 'text' ),
			'r18_audio_url'      => array( \__( 'Fichier audio (URL MP3)', 'g2rd-rescue18-child' ), 'url' ),
			'r18_duree'          => array( \__( 'Durée (ex. 12 min)', 'g2rd-rescue18-child' ), 'text' ),
			'r18_youtube_url'    => array( \__( 'Vidéo YouTube (URL)', 'g2rd-rescue18-child' ), 'url' ),
		),
		'r18_quiz'     => array(
			'r18_niveau'    => array( \__( 'Niveau', 'g2rd-rescue18-child' ), 'text' ),
			'r18_duree'     => array( \__( 'Durée (ex. 2 min)', 'g2rd-rescue18-child' ), 'text' ),
			'r18_questions' => array( \__( 'Questions (JSON) : [{"q":"…","c":["A","B","C"],"a":1,"why":"…"}]', 'g2rd-rescue18-child' ), 'json' ),
		),
		'post'         => array(
			'r18_pdf_url' => array( \__( 'Adresse du PDF de l\'article', 'g2rd-rescue18-child' ), 'url' ),
		),
	);
}

/**
 * Lecture d'une métadonnée en chaîne.
 *
 * @param int    $post_id ID du contenu.
 * @param string $key     Clé.
 * @return string
 */
function meta( int $post_id, string $key ): string {
	$v = \get_post_meta( $post_id, $key, true );
	return \is_scalar( $v ) ? \trim( (string) $v ) : '';
}

/**
 * Historique des versions décodé.
 *
 * @param int $post_id ID du contenu.
 * @return array<int,array{date:string,label:string,note:string}>
 */
function versions( int $post_id ): array {
	$raw  = meta( $post_id, 'r18_versions' );
	$data = '' !== $raw ? \json_decode( $raw, true ) : null;
	if ( ! \is_array( $data ) ) {
		return array();
	}
	$out = array();
	foreach ( $data as $row ) {
		if ( ! \is_array( $row ) ) {
			continue;
		}
		$out[] = array(
			'date'  => (string) ( $row['date'] ?? '' ),
			'label' => (string) ( $row['label'] ?? '' ),
			'note'  => (string) ( $row['note'] ?? '' ),
		);
	}
	return $out;
}

/**
 * Nettoyage d'une valeur selon le type de champ.
 *
 * @param mixed  $value Valeur reçue.
 * @param string $type  Type de champ.
 * @param array  $opts  Options d'une liste.
 * @return string
 */
function sanitize_field( $value, string $type, array $opts = array() ): string {
	$value = \is_scalar( $value ) ? (string) $value : '';
	switch ( $type ) {
		case 'url':
			return \esc_url_raw( \trim( $value ) );
		case 'date':
			return \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		case 'select':
			return \array_key_exists( $value, $opts ) ? $value : '';
		case 'bool':
			return '1' === $value ? '1' : '';
		case 'int':
			return (string) \absint( $value );
		case 'textarea':
			return \sanitize_textarea_field( $value );
		case 'json':
			$value = \trim( $value );
			if ( '' === $value ) {
				return '';
			}
			$data = \json_decode( $value, true );
			return \is_array( $data ) ? (string) \wp_json_encode( map_deep( $data, 'sanitize_text_field' ), JSON_UNESCAPED_UNICODE ) : '';
		default:
			return \sanitize_text_field( $value );
	}
}

/**
 * Applique sanitize_text_field en profondeur sans perdre les entiers.
 *
 * @param mixed    $data     Données.
 * @param callable $callback Nettoyage des chaînes.
 * @return mixed
 */
function map_deep( $data, callable $callback ) {
	if ( \is_array( $data ) ) {
		foreach ( $data as $k => $v ) {
			$data[ $k ] = map_deep( $v, $callback );
		}
		return $data;
	}
	return \is_string( $data ) ? $callback( $data ) : $data;
}

/**
 * Types de contenu, taxonomies et métadonnées.
 */
\add_action(
	'init',
	static function (): void {
		$common = array(
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => false,
			'menu_icon'    => 'dashicons-media-document',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
		);

		$types = array(
			'r18_document' => array( 'document', \__( 'Documents', 'g2rd-rescue18-child' ), \__( 'Document', 'g2rd-rescue18-child' ), 'dashicons-book-alt' ),
			'r18_texte'    => array( 'texte-juridique', \__( 'Textes juridiques', 'g2rd-rescue18-child' ), \__( 'Texte juridique', 'g2rd-rescue18-child' ), 'dashicons-shield' ),
			'r18_episode'  => array( 'podcast', \__( 'Épisodes', 'g2rd-rescue18-child' ), \__( 'Épisode', 'g2rd-rescue18-child' ), 'dashicons-microphone' ),
			'r18_quiz'     => array( 'quiz', \__( 'Quiz', 'g2rd-rescue18-child' ), \__( 'Quiz', 'g2rd-rescue18-child' ), 'dashicons-welcome-learn-more' ),
		);
		foreach ( $types as $type => [ $slug, $plural, $singular, $icon ] ) {
			\register_post_type(
				$type,
				\array_merge(
					$common,
					array(
						'labels'    => array(
							'name'          => $plural,
							'singular_name' => $singular,
							/* translators: %s : type de contenu. */
							'add_new_item'  => \sprintf( \__( 'Ajouter : %s', 'g2rd-rescue18-child' ), $singular ),
							/* translators: %s : type de contenu. */
							'edit_item'     => \sprintf( \__( 'Modifier : %s', 'g2rd-rescue18-child' ), $singular ),
						),
						'rewrite'   => array(
							'slug'       => $slug,
							'with_front' => false,
						),
						'menu_icon' => $icon,
					)
				)
			);
		}

		$tax = array(
			'r18_type_doc'   => array( array( 'r18_document' ), 'type-document', \__( 'Types de document', 'g2rd-rescue18-child' ), \__( 'Type de document', 'g2rd-rescue18-child' ) ),
			'r18_specialite' => array( array( 'post', 'r18_document', 'r18_texte', 'r18_episode', 'r18_quiz' ), 'specialite', \__( 'Spécialités', 'g2rd-rescue18-child' ), \__( 'Spécialité', 'g2rd-rescue18-child' ) ),
			'r18_organisme'  => array( array( 'r18_document', 'r18_texte' ), 'organisme', \__( 'Organismes', 'g2rd-rescue18-child' ), \__( 'Organisme', 'g2rd-rescue18-child' ) ),
			'r18_public'     => array( array( 'r18_document', 'r18_quiz' ), 'public', \__( 'Publics', 'g2rd-rescue18-child' ), \__( 'Public', 'g2rd-rescue18-child' ) ),
		);
		foreach ( $tax as $name => [ $objects, $slug, $plural, $singular ] ) {
			\register_taxonomy(
				$name,
				$objects,
				array(
					'labels'            => array(
						'name'          => $plural,
						'singular_name' => $singular,
					),
					'public'            => true,
					'hierarchical'      => 'r18_specialite' === $name,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'rewrite'           => array(
						'slug'       => $slug,
						'with_front' => false,
					),
				)
			);
		}

		// Icône d'une spécialité (nom d'icône du thème : flame, car, med…).
		\register_term_meta(
			'r18_specialite',
			'r18_icone',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $v ) => \array_key_exists( (string) $v, icones() ) ? (string) $v : '',
			)
		);

		foreach ( fields() as $type => $list ) {
			foreach ( $list as $key => $def ) {
				$opts = $def[2] ?? array();
				\register_post_meta(
					$type,
					$key,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => static fn( $v ) => sanitize_field( $v, $def[1], $opts ),
						'auth_callback'     => static fn() => \current_user_can( 'edit_posts' ),
					)
				);
			}
		}
	}
);

/**
 * Boîte de saisie des métadonnées dans l'éditeur (meta box native).
 */
\add_action(
	'add_meta_boxes',
	static function (): void {
		foreach ( \array_keys( fields() ) as $type ) {
			// Un seul champ pour les articles : dans la colonne, sans voler la place de l'éditeur.
			\add_meta_box( 'rescue18-meta', array( 'post' => 'Version PDF', 'r18_document' => 'Références du document', 'r18_texte' => 'Références du texte', 'r18_episode' => 'Fichiers de l\'épisode', 'r18_quiz' => 'Réglages et questions' )[ $type ] ?? 'Références', __NAMESPACE__ . '\render_meta_box', $type, 'post' === $type ? 'side' : 'normal', 'default' );
		}
	}
);

/**
 * Rendu de la boîte de saisie.
 *
 * @param \WP_Post $post Contenu édité.
 */
function render_meta_box( \WP_Post $post ): void {
	$list = fields()[ $post->post_type ] ?? array();
	\wp_nonce_field( 'rescue18_meta', 'rescue18_meta_nonce' );
	echo '<div class="r18-meta-box" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px 20px">';
	foreach ( $list as $key => $def ) {
		[ $label, $type ] = $def;
		$opts  = $def[2] ?? array();
		$value = meta( $post->ID, $key );
		$id    = 'r18-' . $key;
		$wide  = 'json' === $type || 'textarea' === $type ? ' style="grid-column:1/-1"' : '';
		echo '<p' . $wide . '><label for="' . \esc_attr( $id ) . '"><strong>' . \esc_html( $label ) . '</strong></label><br>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wide est une constante.
		switch ( $type ) {
			case 'select':
				echo '<select id="' . \esc_attr( $id ) . '" name="' . \esc_attr( $key ) . '" style="width:100%"><option value="">—</option>';
				foreach ( $opts as $k => $l ) {
					echo '<option value="' . \esc_attr( $k ) . '"' . \selected( $value, $k, false ) . '>' . \esc_html( $l ) . '</option>';
				}
				echo '</select>';
				break;
			case 'bool':
				echo '<input type="checkbox" id="' . \esc_attr( $id ) . '" name="' . \esc_attr( $key ) . '" value="1"' . \checked( $value, '1', false ) . '>';
				break;
			case 'json':
			case 'textarea':
				echo '<textarea id="' . \esc_attr( $id ) . '" name="' . \esc_attr( $key ) . '" rows="5" style="width:100%;font-family:monospace">' . \esc_textarea( $value ) . '</textarea>';
				break;
			default:
				$html_type = 'url' === $type ? 'url' : ( 'date' === $type ? 'date' : 'text' );
				echo '<input type="' . \esc_attr( $html_type ) . '" id="' . \esc_attr( $id ) . '" name="' . \esc_attr( $key ) . '" value="' . \esc_attr( $value ) . '" style="width:100%">';
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Enregistrement des métadonnées saisies.
 *
 * @param int $post_id ID du contenu.
 */
function save_meta_box( int $post_id ): void {
	if ( ! isset( $_POST['rescue18_meta_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['rescue18_meta_nonce'] ) ), 'rescue18_meta' ) ) {
		return;
	}
	if ( ( \defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE ) || ! \current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$list = fields()[ \get_post_type( $post_id ) ] ?? array();
	foreach ( $list as $key => $def ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par sanitize_field().
		$raw   = isset( $_POST[ $key ] ) ? \wp_unslash( $_POST[ $key ] ) : '';
		$clean = sanitize_field( $raw, $def[1], $def[2] ?? array() );
		if ( '' === $clean ) {
			\delete_post_meta( $post_id, $key );
		} else {
			// update_post_meta attend des données échappées (wp_unslash interne).
			\update_post_meta( $post_id, $key, \wp_slash( $clean ) );
		}
	}
}
\add_action( 'save_post', __NAMESPACE__ . '\save_meta_box' );

/**
 * Libellé de date à la française.
 *
 * @param string $ymd Date AAAA-MM-JJ.
 * @return string
 */
function date_fr( string $ymd ): string {
	$ts = '' !== $ymd ? \strtotime( $ymd ) : false;
	return false !== $ts ? \wp_date( 'j F Y', $ts ) : '';
}
