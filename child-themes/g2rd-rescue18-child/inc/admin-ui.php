<?php
/**
 * Back-office : un tableau de bord « Rescue 18 » qui range les contenus par
 * mission, un en-tête clair sur chaque liste, et des champs lisibles dans les
 * écrans de modification. Aucune donnée n'est modifiée ici : affichage seul.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Admin_UI;

\defined( 'ABSPATH' ) || exit;

const HUB = 'rescue18';

/**
 * Types de contenu gérés, rangés par mission.
 *
 * @return array<string,array{titre:string,intro:string,types:array<string,array{0:string,1:string,2:string}>}>
 */
function missions(): array {
	return array(
		'former'    => array(
			'titre' => 'Former',
			'intro' => 'Les contenus de révision pour les sapeurs-pompiers.',
			'types' => array(
				'r18_fiche'  => array( 'Fiches mémo', 'Une page de révision par document officiel, téléchargeable en PDF A4.', 'media-document' ),
				'r18_quiz'   => array( 'Quiz', 'Questions à choix multiple, avec la correction et la source officielle.', 'welcome-learn-more' ),
				'r18_annale' => array( 'Sujets des concours', 'Sujets, rapports de jury et notes de cadrage, en lien vers l\'organisateur.', 'portfolio' ),
			),
		),
		'documenter' => array(
			'titre' => 'Documenter',
			'intro' => 'La bibliothèque et la veille réglementaire.',
			'types' => array(
				'r18_document' => array( 'Documents', 'Guides de doctrine, référentiels et retours d\'expérience.', 'book-alt' ),
				'r18_texte'    => array( 'Textes juridiques', 'Lois, décrets et arrêtés suivis par la veille du Journal officiel.', 'shield' ),
			),
		),
		'recruter'  => array(
			'titre' => 'Recruter',
			'intro' => 'Concours et offres, mis à jour chaque jour.',
			'types' => array(
				'r18_concours' => array( 'Concours', 'Dates d\'inscription et d\'épreuves, reprises de Concours-Territorial.', 'calendar-alt' ),
				'r18_offre'    => array( 'Offres d\'emploi', 'Postes publiés par les SDIS sur leurs sites.', 'id-alt' ),
			),
		),
		'annuaire'  => array(
			'titre' => 'Annuaire',
			'intro' => 'Les SDIS et les casernes de France.',
			'types' => array(
				'r18_service'   => array( 'SDIS', 'Fiche de chaque service : site, contacts, réseaux sociaux.', 'location-alt' ),
				'r18_caserne'   => array( 'Casernes corrigées', 'Corrections et ajouts appliqués à la carte des casernes.', 'location' ),
				'r18_actu_sdis' => array( 'Actualités des SDIS', 'Articles repris des sites des SDIS, avec le lien d\'origine.', 'megaphone' ),
			),
		),
		'diffuser'  => array(
			'titre' => 'Diffuser',
			'intro' => 'Le podcast et les partenaires du site.',
			'types' => array(
				'r18_episode'    => array( 'Épisodes du podcast', 'Épisodes de SSUAP Le Podcast.', 'microphone' ),
				'r18_partenaire' => array( 'Partenaires', 'Logos et liens affichés selon le niveau de partenariat.', 'heart' ),
			),
		),
	);
}

/**
 * Tous les types, à plat.
 *
 * @return array<string,array{0:string,1:string,2:string,3:string}> type => [nom, rôle, icône, mission]
 */
function types(): array {
	$out = array();
	foreach ( missions() as $m => $d ) {
		foreach ( $d['types'] as $t => $v ) {
			$out[ $t ] = array( $v[0], $v[1], $v[2], $d['titre'] );
		}
	}
	return $out;
}

/* Contenus éditoriaux rangés sous « Rescue 18 » : un seul menu au lieu de six. */
\add_filter(
	'register_post_type_args',
	static function ( array $args, string $type ): array {
		if ( 'r18_fiche' === $type ) {
			$args['labels'] = \array_merge(
				(array) ( $args['labels'] ?? array() ),
				array(
					'featured_image'        => 'Image principale',
					'set_featured_image'    => 'Choisir l\'image principale',
					'remove_featured_image' => 'Retirer l\'image principale',
				)
			);
		}
		if ( \in_array( $type, array( 'r18_fiche', 'r18_quiz', 'r18_annale', 'r18_document', 'r18_texte', 'r18_episode', 'r18_partenaire' ), true ) ) {
			$args['show_in_menu'] = HUB;
		}
		return $args;
	},
	20,
	2
);

\add_action(
	'admin_menu',
	static function (): void {
		$n = 0;
		foreach ( \array_keys( types() ) as $t ) {
			$n += \post_type_exists( $t ) ? (int) ( \wp_count_posts( $t )->pending ?? 0 ) : 0;
		}
		$bulle = $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '';
		$icone = 'data:image/svg+xml;base64,' . \base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M7.5 1h5v6.5H19v5h-6.5V19h-5v-6.5H1v-5h6.5z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		\add_menu_page( 'Rescue 18 · Contenus', 'Rescue 18' . $bulle, 'edit_posts', HUB, __NAMESPACE__ . '\hub', $icone, 3 );
		\add_submenu_page( HUB, 'Contenus du site', 'Vue d\'ensemble', 'edit_posts', HUB, __NAMESPACE__ . '\hub', 0 );
	},
	9
);

/**
 * Compteurs d'un type.
 *
 * @param string $t Type.
 * @return array{publish:int,pending:int,draft:int}
 */
function compte( string $t ): array {
	$c = \wp_count_posts( $t );
	return array(
		'publish' => (int) ( $c->publish ?? 0 ),
		'pending' => (int) ( $c->pending ?? 0 ),
		'draft'   => (int) ( $c->draft ?? 0 ),
	);
}

/**
 * Tableau de bord Rescue 18.
 */
function hub(): void {
	$types   = types();
	$relire  = array();
	foreach ( $types as $t => $d ) {
		if ( ! \post_type_exists( $t ) ) {
			continue;
		}
		$c = compte( $t );
		if ( $c['pending'] > 0 ) {
			$relire[ $t ] = $c['pending'];
		}
	}
	echo '<div class="wrap r18ui r18ui-hub"><hr class="wp-header-end"><header class="r18ui-head"><div class="r18ui-head__txt"><p class="r18ui-kicker">Rescue 18</p><h1>Contenus du site</h1><p>Chaque type de contenu, rangé par mission. Les nombres se mettent à jour à chaque visite.</p></div>';
	echo '<nav class="r18ui-head__liens" aria-label="Automatisations et outils">';
	foreach ( array(
		array( 'admin.php?page=rescue18-automatisations', 'update-alt', 'Automatisations' ),
		array( 'admin.php?page=rescue18-annuaire', 'location-alt', 'Annuaire et recrutement' ),
		array( 'index.php?page=rescue18-alertes', 'warning', 'Alertes interventions' ),
		array( 'admin.php?page=rescue18-outils', 'calculator', 'Boîte à outils' ),
	) as [ $u, $i, $l ] ) {
		echo '<a href="' . \esc_url( \admin_url( $u ) ) . '"><span class="dashicons dashicons-' . \esc_attr( $i ) . '" aria-hidden="true"></span>' . \esc_html( $l ) . '</a>';
	}
	echo '</nav></header>';

	// File de relecture : ce qui attend une décision passe en premier.
	echo '<section class="r18ui-relire" aria-labelledby="r18ui-relire-t"><h2 id="r18ui-relire-t">' . ( $relire ? 'À relire avant publication' : 'Rien à relire' ) . '</h2>';
	if ( $relire ) {
		echo '<p>Ces contenus sont prêts mais pas encore en ligne. Ouvre chacun, compare-le à sa source, puis publie-le.</p><ul class="r18ui-relire__l" role="list">';
		foreach ( $relire as $t => $n ) {
			echo '<li><a href="' . \esc_url( \admin_url( 'edit.php?post_type=' . $t . '&post_status=pending' ) ) . '"><span class="dashicons dashicons-' . \esc_attr( $types[ $t ][2] ) . '" aria-hidden="true"></span><strong>' . (int) $n . '</strong><span>' . \esc_html( $types[ $t ][0] ) . '</span></a></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>Tous les contenus importés ont été relus. Les nouveaux arriveront ici.</p>';
	}
	echo '</section>';

	foreach ( missions() as $m => $d ) {
		echo '<section class="r18ui-mission" aria-labelledby="r18ui-m-' . \esc_attr( $m ) . '"><div class="r18ui-mission__hd"><h2 id="r18ui-m-' . \esc_attr( $m ) . '">' . \esc_html( $d['titre'] ) . '</h2><p>' . \esc_html( $d['intro'] ) . '</p></div><table class="r18ui-types"><thead><tr><th scope="col">Contenu</th><th scope="col" class="num">En ligne</th><th scope="col" class="num">À relire</th><th scope="col" class="num">Brouillons</th><th scope="col"><span class="screen-reader-text">Actions</span></th></tr></thead><tbody>';
		foreach ( $d['types'] as $t => [ $nom, $role, $ic ] ) {
			if ( ! \post_type_exists( $t ) ) {
				continue;
			}
			$c   = compte( $t );
			$obj = \get_post_type_object( $t );
			$add = $obj && \current_user_can( $obj->cap->create_posts ) && ! \in_array( $t, array( 'r18_actu_sdis' ), true );
			$cel = static fn( int $n, string $st ): string => $n > 0 ? '<a href="' . \esc_url( \admin_url( 'edit.php?post_type=' . $t . '&post_status=' . $st ) ) . '">' . (int) $n . '</a>' : '<span class="is-zero">0</span>';
			echo '<tr><th scope="row"><span class="r18ui-ic"><span class="dashicons dashicons-' . \esc_attr( $ic ) . '" aria-hidden="true"></span></span><span class="r18ui-types__nom"><a href="' . \esc_url( \admin_url( 'edit.php?post_type=' . $t ) ) . '">' . \esc_html( $nom ) . '</a><small>' . \esc_html( $role ) . '</small></span></th>'
				. '<td class="num">' . $cel( $c['publish'], 'publish' ) . '</td>'
				. '<td class="num' . ( $c['pending'] ? ' is-wait' : '' ) . '">' . $cel( $c['pending'], 'pending' ) . '</td>'
				. '<td class="num">' . $cel( $c['draft'], 'draft' ) . '</td>'
				. '<td class="act">' . ( $add ? '<a class="r18ui-btn" href="' . \esc_url( \admin_url( 'post-new.php?post_type=' . $t ) ) . '">Ajouter</a>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table></section>';
	}
	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Listes : en-tête explicatif
 * ---------------------------------------------------------------------- */

/**
 * Type géré par l'écran courant, ou chaîne vide.
 *
 * @return string
 */
function type_ecran(): string {
	$e = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;
	if ( ! $e ) {
		return '';
	}
	$t = (string) $e->post_type;
	if ( isset( types()[ $t ] ) || ( 'post' === $t && 'post' === $e->base ) ) {
		return $t;
	}
	return '';
}

/**
 * Types de contenu fait de champs : écran de modification classique, plus lisible
 * que des boîtes repliées sous l'éditeur de blocs.
 *
 * @return string[]
 */
function types_classiques(): array {
	return array( 'r18_fiche', 'r18_annale', 'r18_quiz', 'r18_service', 'r18_offre', 'r18_actu_sdis' );
}

\add_filter(
	'use_block_editor_for_post_type',
	static fn( bool $oui, string $t ): bool => \in_array( $t, types_classiques(), true ) ? false : $oui,
	20,
	2
);

/* Écran classique : sans les champs bruts ni le choix de modèle, déjà gérés ailleurs. */
\add_action(
	'add_meta_boxes',
	static function ( string $type ): void {
		if ( \in_array( $type, types_classiques(), true ) ) {
			\remove_meta_box( 'postcustom', $type, 'normal' );
			\remove_meta_box( 'pageparentdiv', $type, 'side' );
			\remove_meta_box( 'slugdiv', $type, 'normal' );
		}
	},
	100
);

/* Écran classique : les champs passent avant le texte libre. */
\add_action(
	'edit_form_after_title',
	static function ( \WP_Post $p ): void {
		if ( ! \in_array( $p->post_type, types_classiques(), true ) ) {
			return;
		}
		global $wp_meta_boxes;
		$type = $p->post_type;
		foreach ( array( 'r18-fiche', 'r18-annale', 'rescue18-meta', 'r18a-fiche' ) as $id ) {
			foreach ( array( 'high', 'core', 'default', 'low' ) as $prio ) {
				if ( isset( $wp_meta_boxes[ $type ]['normal'][ $prio ][ $id ] ) ) {
					$wp_meta_boxes[ $type ]['r18ui_avant']['high'][ $id ] = $wp_meta_boxes[ $type ]['normal'][ $prio ][ $id ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					unset( $wp_meta_boxes[ $type ]['normal'][ $prio ][ $id ] );
				}
			}
		}
		echo '<div class="r18ui-avant">';
		\do_meta_boxes( \get_current_screen(), 'r18ui_avant', $p );
		echo '</div>';
		if ( \post_type_supports( $p->post_type, 'editor' ) ) {
			echo '<h2 class="r18ui-libre">Texte libre <span>Facultatif. Il s\'affiche sous le contenu structuré.</span></h2>';
		}
	}
);

/* En-tête de l'écran de modification classique. */
\add_action(
	'edit_form_top',
	static function ( \WP_Post $p ): void {
		if ( ! \in_array( $p->post_type, types_classiques(), true ) ) {
			return;
		}
		[ $nom, $role, $ic, $mission ] = types()[ $p->post_type ];
		$etats = array(
			'publish'    => array( 'En ligne', 'is-ok' ),
			'pending'    => array( 'À relire', 'is-wait' ),
			'draft'      => array( 'Brouillon', '' ),
			'auto-draft' => array( 'Nouveau', '' ),
			'future'     => array( 'Programmé', '' ),
			'private'    => array( 'Privé', '' ),
		);
		[ $etat, $cls ] = $etats[ $p->post_status ] ?? array( $p->post_status, '' );
		$voir = 'publish' === $p->post_status && \is_post_type_viewable( $p->post_type ) ? \get_permalink( $p ) : '';
		echo '<div class="r18ui-ehead"><a class="r18ui-ehead__back" href="' . \esc_url( \admin_url( 'edit.php?post_type=' . $p->post_type ) ) . '"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>' . \esc_html( $nom ) . '</a>'
			. '<span class="r18ui-etat ' . \esc_attr( $cls ) . '">' . \esc_html( $etat ) . '</span>'
			. ( $voir ? '<a class="r18ui-ehead__voir" href="' . \esc_url( $voir ) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-external" aria-hidden="true"></span>Voir sur le site<span class="screen-reader-text"> (nouvel onglet)</span></a>' : '' )
			. '</div>';
	}
);

\add_action(
	'all_admin_notices',
	static function (): void {
		$e = \get_current_screen();
		$t = type_ecran();
		if ( '' === $t || 'edit' !== $e->base ) {
			return;
		}
		[ $nom, $role, $ic, $mission ] = types()[ $t ];
		$c   = compte( $t );
		$obj = \get_post_type_object( $t );
		echo '<div class="r18ui-lhead"><span class="r18ui-ic is-lg"><span class="dashicons dashicons-' . \esc_attr( $ic ) . '" aria-hidden="true"></span></span><div class="r18ui-lhead__txt"><p class="r18ui-kicker"><a href="' . \esc_url( \admin_url( 'admin.php?page=' . HUB ) ) . '">Rescue 18</a> / ' . \esc_html( $mission ) . '</p><h1>' . \esc_html( $nom ) . '</h1><p>' . \esc_html( $role ) . '</p></div>'
			. '<dl class="r18ui-lhead__n"><div><dt>En ligne</dt><dd>' . (int) $c['publish'] . '</dd></div><div class="' . ( $c['pending'] ? 'is-wait' : '' ) . '"><dt>À relire</dt><dd>' . (int) $c['pending'] . '</dd></div></dl>'
			. ( $obj && \current_user_can( $obj->cap->create_posts ) && 'r18_actu_sdis' !== $t ? '<a class="r18ui-btn is-red" href="' . \esc_url( \admin_url( 'post-new.php?post_type=' . $t ) ) . '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>Ajouter</a>' : '' ) . '</div>';
	},
	1
);

/* -------------------------------------------------------------------------
 * Boîtes de saisie : classe commune pour le style
 * ---------------------------------------------------------------------- */

\add_action(
	'add_meta_boxes',
	static function ( string $type ): void {
		if ( ! isset( types()[ $type ] ) && 'post' !== $type ) {
			return;
		}
		foreach ( array( 'r18-fiche', 'r18-annale', 'r18-partenaire', 'r18-partenaire-clics', 'r18-caserne', 'r18a-fiche', 'rescue18-meta' ) as $id ) {
			\add_filter(
				'postbox_classes_' . $type . '_' . $id,
				static function ( array $c ): array {
					$c[] = 'r18ui-box';
					return $c;
				}
			);
		}
	},
	99
);

/* -------------------------------------------------------------------------
 * Feuille de style et petits scripts
 * ---------------------------------------------------------------------- */

\add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( 'toplevel_page_' . HUB !== $hook && '' === type_ecran() ) {
			return;
		}
		if ( 'r18_fiche' === type_ecran() ) {
			\wp_enqueue_media();
		}
		$css = \G2RD_Rescue18\asset( 'assets/css/admin-ui.css' );
		if ( $css ) {
			\wp_enqueue_style( 'r18-admin-ui', $css[0], array(), $css[1] );
		}
		$js = \G2RD_Rescue18\asset( 'assets/js/admin-ui.js' );
		if ( $js ) {
			\wp_enqueue_script( 'r18-admin-ui', $js[0], array(), $js[1], true );
		}
		// Compteur de lignes sous les champs « une ligne par élément ».
		\wp_add_inline_script(
			'common',
			'document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".r18ui-box textarea:not([name=r18_questions]):not([name=r18_versions])").forEach(function(t){var p=document.createElement("p");p.className="r18ui-lignes";p.setAttribute("aria-live","polite");t.insertAdjacentElement("afterend",p);function n(){var k=t.value.split(/\\n/).filter(function(x){return x.trim()!==""}).length;p.textContent=k+(k>1?" lignes remplies":" ligne remplie");t.style.height="auto";t.style.height=Math.min(Math.max(t.scrollHeight+4,120),640)+"px";}t.addEventListener("input",n);n();});});'
		);
	}
);

\add_filter(
	'admin_body_class',
	static function ( string $c ): string {
		$e = \get_current_screen();
		return '' !== type_ecran() ? $c . ' r18ui-screen' . ( $e && 'edit' === $e->base ? ' r18ui-liste' : '' ) . ( $e && 'post' === $e->base && \in_array( (string) $e->post_type, types_classiques(), true ) ? ' r18ui-classique' : '' ) : $c;
	}
);
