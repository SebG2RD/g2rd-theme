<?php
/**
 * Partenaires : type de contenu, réglages au back-office, bloc d'affichage,
 * suivi des clics et rappel des conventions à renouveler.
 *
 * Deux niveaux :
 * - convention : partenaires liés par une convention, mis en avant partout ;
 * - soutien    : soutiens.
 * L'ancien niveau « partenaire rémunéré » est fusionné dans les soutiens.
 *
 * @package G2RD_Rescue18
 */

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

const PT_TYPE = 'r18_partenaire';

/**
 * Niveaux de partenariat : clé => [ libellé admin, titre public ].
 *
 * @return array<string,array{0:string,1:string}>
 */
function niveaux_partenaires(): array {
	return array(
		'convention' => array( 'Partenaire officiel (convention, mis en avant partout)', 'Partenaires officiels' ),
		'soutien'    => array( 'Soutien', 'Ils nous soutiennent' ),
	);
}

/**
 * Champs d'un partenaire : clé méta => [ libellé, type, aide ].
 *
 * @return array<string,array{0:string,1:string,2:string}>
 */
function champs_partenaire(): array {
	return array(
		'r18_p_niveau' => array( 'Niveau', 'niveau', 'Les conventions s’affichent sur l’accueil, en pied de page et en tête de la page Partenaires.' ),
		'r18_p_url'    => array( 'Site du partenaire', 'url', 'Adresse complète, en https.' ),
		'r18_p_logo'   => array( 'Logo (adresse de l’image)', 'url', 'Image hébergée chez le partenaire ou sur rescue18.com. L’image mise en avant, si elle existe, passe avant.' ),
		'r18_p_fond'   => array( 'Fond du logo', 'fond', 'Choisis « sombre » pour un logo blanc.' ),
		'r18_p_debut'  => array( 'Début du partenariat', 'date', '' ),
		'r18_p_fin'    => array( 'Fin du partenariat', 'date', 'Le partenaire disparaît du site le lendemain. Un rappel s’affiche 45 jours avant.' ),
		'r18_p_activite' => array( 'Activité', 'texte', 'En quelques mots, par exemple « Constructeur d’engins d’incendie ». Fiche des conventions.' ),
		'r18_p_ville'  => array( 'Siège ou implantation', 'texte', 'Ville, département ou pays.' ),
		'r18_p_apport' => array( 'Ce que le partenariat apporte', 'liste', 'Une ligne par apport : financement, matériel, expertise, contenus… Affiché sur la fiche.' ),
		'r18_p_avantage' => array( 'Avantage pour les pompiers', 'zone', 'Offre réservée aux pompiers ou aux lecteurs de Rescue 18, avec ses conditions. Laisse vide s’il n’y en a pas.' ),
		'r18_p_reseaux' => array( 'Réseaux sociaux', 'liste', 'Une adresse complète par ligne (LinkedIn, Facebook, Instagram, YouTube…).' ),
	);
}

\add_action(
	'init',
	static function (): void {
		\register_post_type(
			PT_TYPE,
			array(
				'labels'          => array(
					'name'          => 'Partenaires',
					'singular_name' => 'Partenaire',
					'add_new'       => 'Ajouter un partenaire',
					'add_new_item'  => 'Ajouter un partenaire',
					'edit_item'     => 'Modifier le partenaire',
					'all_items'     => 'Tous les partenaires',
					'menu_name'     => 'Partenaires',
				),
				'public'          => true,
				'has_archive'     => false,
				'rewrite'         => array(
					'slug'       => 'partenaire',
					'with_front' => false,
				),
				'show_ui'         => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-heart',
				'menu_position'   => 26,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
				'capability_type' => 'page',
				'map_meta_cap'    => true,
			)
		);
		foreach ( \array_keys( champs_partenaire() ) as $cle ) {
			\register_post_meta(
				PT_TYPE,
				$cle,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static fn(): bool => \current_user_can( 'edit_pages' ),
				)
			);
		}
		\register_post_meta(
			PT_TYPE,
			'r18_p_clics',
			array(
				'type'          => 'object',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => static fn(): bool => \current_user_can( 'edit_pages' ),
			)
		);
	}
);

/**
 * Liste des partenaires actifs, triés par ordre puis par nom.
 *
 * @param string $niveau Niveau, ou vide pour tous.
 * @return array<int,array<string,mixed>>
 */
function partenaires( string $niveau = '' ): array {
	$cache = \wp_cache_get( 'liste', 'r18_partenaires' );
	if ( ! \is_array( $cache ) ) {
		$cache = array();
		$jour  = \wp_date( 'Y-m-d' );
		$ids   = \get_posts(
			array(
				'post_type'      => PT_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$fin = (string) \get_post_meta( $id, 'r18_p_fin', true );
			$deb = (string) \get_post_meta( $id, 'r18_p_debut', true );
			if ( ( '' !== $fin && $fin < $jour ) || ( '' !== $deb && $deb > $jour ) ) {
				continue;
			}
			$niv   = (string) \get_post_meta( $id, 'r18_p_niveau', true );
			$logo  = (string) \get_the_post_thumbnail_url( $id, 'medium' );
			$cache[] = array(
				'id'     => $id,
				'fiche'  => (string) \get_permalink( $id ),
				'nom'    => \get_the_title( $id ),
				'texte'  => \get_post_field( 'post_excerpt', $id ),
				'niveau' => isset( niveaux_partenaires()[ $niv ] ) ? $niv : 'soutien',
				'url'    => (string) \get_post_meta( $id, 'r18_p_url', true ),
				'logo'   => '' !== $logo ? $logo : (string) \get_post_meta( $id, 'r18_p_logo', true ),
				'fond'   => 'sombre' === \get_post_meta( $id, 'r18_p_fond', true ) ? 'sombre' : 'clair',
			);
		}
		\wp_cache_set( 'liste', $cache, 'r18_partenaires', HOUR_IN_SECONDS );
	}
	if ( '' === $niveau ) {
		return $cache;
	}
	$niveaux = \array_map( 'trim', \explode( ',', $niveau ) );
	return \array_values( \array_filter( $cache, static fn( array $p ): bool => \in_array( $p['niveau'], $niveaux, true ) ) );
}

\add_action(
	'save_post_' . PT_TYPE,
	static function (): void {
		\wp_cache_delete( 'liste', 'r18_partenaires' );
	}
);
\add_action(
	'deleted_post',
	static function (): void {
		\wp_cache_delete( 'liste', 'r18_partenaires' );
	}
);

/**
 * Attributs rel du lien sortant : aucune relation rémunérée, lien simple.
 *
 * @param array<string,mixed> $p Partenaire.
 * @return string
 */
function rel_partenaire( array $p ): string {
	return 'noopener';
}

/**
 * Tuile logo d'un partenaire (lien vers son site si renseigné).
 *
 * @param array<string,mixed> $p     Partenaire.
 * @param bool                $lien  Rendre la tuile cliquable.
 * @return string
 */
function tuile_partenaire( array $p, bool $lien = true ): string {
	$nom    = (string) $p['nom'];
	$visuel = '' !== $p['logo']
		? '<img src="' . \esc_url( $p['logo'] ) . '" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" data-r18-img>'
		: '';
	$inner  = $visuel . '<span class="r18-pt__nom' . ( '' !== $visuel ? ' has-logo' : '' ) . '">' . \esc_html( $nom ) . '</span>';
	$cls    = 'r18-pt__logo is-' . $p['fond'];
	// Les conventions mènent à leur fiche sur Rescue 18, les autres à leur site.
	if ( $lien && 'convention' === $p['niveau'] ) {
		return '<a class="' . $cls . '" href="' . \esc_url( $p['fiche'] ) . '">' . $inner . '<span class="screen-reader-text"> : fiche du partenaire</span></a>';
	}
	if ( $lien && '' !== $p['url'] ) {
		return '<a class="' . $cls . '" href="' . \esc_url( $p['url'] ) . '" rel="' . rel_partenaire( $p ) . '" data-r18-pt="' . (int) $p['id'] . '">' . $inner . '<span class="screen-reader-text"> (site du partenaire)</span></a>';
	}
	return '<span class="' . $cls . '">' . $inner . '</span>';
}

/**
 * Bloc rescue18/partenaires.
 *
 * Variantes :
 * - ruban   : bande fine en haut de l'accueil et des articles ;
 * - vitrine : tuiles larges, pour les conventions ;
 * - cartes  : logo, nom, présentation et lien (page Partenaires) ;
 * - grille  : logos seuls ;
 * - bandeau : défilement horizontal (accueil).
 *
 * @param array<string,mixed> $a Attributs.
 * @return string
 */
function render_partenaires( array $a ): string {
	$liste = partenaires( (string) ( $a['niveau'] ?? '' ) );
	if ( ! $liste ) {
		return \current_user_can( 'edit_pages' )
			? '<p class="r18-pt-vide">Aucun partenaire actif pour ce niveau. <a href="' . \esc_url( \admin_url( 'edit.php?post_type=' . PT_TYPE ) ) . '">Gérer les partenaires</a></p>'
			: '';
	}
	$var = (string) ( $a['variante'] ?? 'grille' );
	// Colonnes équilibrées : les rangées remplissent la largeur sans logo isolé
	// (11 logos sur 6 colonnes max : 6 + 5, et non 10 + 1).
	$cols = static function ( int $n, int $max ): string {
		$rangs = (int) \ceil( $n / $max );
		return ' style="--r18-pt-cols:' . (int) \ceil( $n / \max( 1, $rangs ) ) . '"';
	};
	$cls = 'r18-pt r18-pt--' . \sanitize_html_class( $var ) . ( empty( $a['className'] ) ? '' : ' ' . \esc_attr( $a['className'] ) );

	if ( 'bandeau' === $var ) {
		$tuiles = \array_map( static fn( array $p ): string => tuile_partenaire( $p ), $liste );
		// Deux lignes en sens inverses : la seconde reprend les logos dans l'ordre
		// inverse, masquée aux lecteurs d'écran (même contenu que la première).
		$ligne  = static function ( array $t, bool $inverse ): string {
			$n    = \count( $t );
			$html = '';
			// Au moins 10 tuiles par ligne : le défilement reste continu sur grand écran.
			for ( $i = 0, $max = \max( 10, $n ); $i < $max; $i++ ) {
				$h = $t[ $i % $n ];
				if ( $inverse || $i >= $n ) {
					$h = (string) \preg_replace( '/^<(a|span)\b/', '<$1 aria-hidden="true" data-r18-dup' . ( \str_starts_with( $h, '<a' ) ? ' tabindex="-1"' : '' ), $h, 1 );
				}
				$html .= $h;
			}
			return '<div class="r18-partners__row' . ( $inverse ? ' is-inverse' : '' ) . '"' . ( $inverse ? ' aria-hidden="true"' : '' ) . '><div class="r18-partners__track">' . $html . '</div></div>';
		};
		return '<div class="' . $cls . ' r18-partners" role="region" aria-label="Partenaires et soutiens">' . $ligne( $tuiles, false ) . $ligne( \array_reverse( $tuiles ), true ) . '</div>';
	}

	if ( 'ruban' === $var ) {
		$out = '';
		foreach ( $liste as $p ) {
			$out .= '<li>' . tuile_partenaire( $p ) . '</li>';
		}
		return '<aside class="' . $cls . '" aria-label="Partenaires officiels"><div class="r18-pt-ruban__in"><p class="r18-pt-ruban__titre"><a href="' . \esc_url( \home_url( '/partenaires/' ) ) . '">Partenaires officiels</a></p><ul role="list">' . $out . '</ul></div></aside>';
	}

	if ( 'encart' === $var ) {
		$out = '';
		foreach ( $liste as $p ) {
			$out .= '<li>' . tuile_partenaire( $p ) . '</li>';
		}
		return '<aside class="' . $cls . '" aria-labelledby="r18-pt-encart-titre"><p class="r18-pt-encart__titre" id="r18-pt-encart-titre">Rescue 18 est soutenu par ses partenaires officiels</p><ul class="r18-pt r18-pt--grille" role="list"' . $cols( \count( $liste ), 4 ) . '>' . $out . '</ul><p class="r18-pt-encart__lien"><a href="' . \esc_url( \home_url( '/partenaires/#devenir-partenaire' ) ) . '">Devenir partenaire</a></p></aside>';
	}

	if ( 'cartes' === $var ) {
		$out = '';
		foreach ( $liste as $p ) {
			$go   = '<p class="r18-pt__go"><a href="' . \esc_url( $p['fiche'] ) . '">Qui sont-ils ?<span class="screen-reader-text"> Fiche de ' . \esc_html( $p['nom'] ) . '</span></a></p>';
			$out .= '<li class="r18-pt__carte">' . tuile_partenaire( $p, false )
				. '<h3>' . \esc_html( $p['nom'] ) . '</h3>'
				. ( '' !== $p['texte'] ? '<p>' . \esc_html( $p['texte'] ) . '</p>' : '' )
				. $go . '</li>';
		}
		return '<ul class="' . $cls . '" role="list">' . $out . '</ul>';
	}

	$out = '';
	foreach ( $liste as $p ) {
		$out .= '<li>' . tuile_partenaire( $p ) . '</li>';
	}
	return '<ul class="' . $cls . '" role="list"' . $cols( \count( $liste ), 'vitrine' === $var ? 4 : 6 ) . '>' . $out . '</ul>';
}

/**
 * Définition du bloc, ajoutée aux autres blocs du thème.
 *
 * @return array<string,array<string,mixed>>
 */
function partenaires_block_definitions(): array {
	return array(
		'partenaires' => array(
			'title'  => 'Partenaires',
			'icon'   => 'heart',
			'attrs'  => array(
				'niveau'   => array(
					'string',
					'',
					'Partenaires affichés',
					array(
						''                    => 'Tous',
						'convention'          => 'Conventions',
						'soutien'             => 'Soutiens',
					),
				),
				'variante' => array(
					'string',
					'grille',
					'Présentation',
					array(
						'ruban'   => 'Ruban fin (haut de page)',
						'encart'  => 'Encart de fin d\'article',
						'vitrine' => 'Vitrine (grandes tuiles)',
						'cartes'  => 'Cartes avec présentation',
						'grille'  => 'Grille de logos',
						'bandeau' => 'Bandeau défilant',
					),
				),
			),
			'render' => 'render_partenaires',
		),
		'partenaire-details' => array(
			'title'   => 'Fiche partenaire : apports, avantage, contenus liés',
			'icon'    => 'heart',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_partenaire_details',
		),
		'partenaire-fiche' => array(
			'title'   => 'Fiche partenaire : logo et lien',
			'icon'    => 'heart',
			'attrs'   => array(),
			'context' => array( 'postId', 'postType' ),
			'render'  => 'render_partenaire_fiche',
		),
	);
}

/**
 * Bloc rescue18/partenaire-fiche : logo, lien vers le site et ancienneté,
 * sur la fiche d’un partenaire officiel.
 *
 * @param array<string,mixed> $a     Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_partenaire_fiche( array $a, ?\WP_Block $block = null ): string {
	$id = (int) ( $block->context['postId'] ?? \get_the_ID() );
	if ( PT_TYPE !== \get_post_type( $id ) ) {
		return '';
	}
	$p = null;
	foreach ( partenaires() as $x ) {
		if ( $x['id'] === $id ) {
			$p = $x;
		}
	}
	if ( ! $p ) {
		return '';
	}
	$html = tuile_partenaire( $p, false );
	if ( '' !== $p['url'] ) {
		$hote  = (string) \preg_replace( '/^www\./', '', (string) \wp_parse_url( $p['url'], PHP_URL_HOST ) );
		$html .= '<a class="r18-btn r18-btn--red r18-pt-fiche__site" href="' . \esc_url( $p['url'] ) . '" rel="' . rel_partenaire( $p ) . '" data-r18-pt="' . (int) $p['id'] . '">' . \esc_html( 'Visiter ' . $hote ) . ' ' . icone( 'ext' ) . '</a>';
	}
	$infos = array();
	foreach ( array( 'r18_p_activite' => 'Activité', 'r18_p_ville' => 'Implantation' ) as $cle => $lib ) {
		$v = (string) \get_post_meta( $id, $cle, true );
		if ( '' !== $v ) {
			$infos[] = '<div><dt>' . \esc_html( $lib ) . '</dt><dd>' . \esc_html( $v ) . '</dd></div>';
		}
	}
	$deb = (string) \get_post_meta( $id, 'r18_p_debut', true );
	if ( '' !== $deb ) {
		$infos[] = '<div><dt>Partenaire depuis</dt><dd>' . \esc_html( \date_i18n( 'F Y', (int) \strtotime( $deb ) ) ) . '</dd></div>';
	}
	$infos[] = '<div><dt>Partenariat</dt><dd>Convention avec Rescue 18</dd></div>';
	$html   .= '<dl class="r18-pt-fiche__infos">' . \implode( '', $infos ) . '</dl>';
	$reseaux = \array_filter( \explode( "\n", (string) \get_post_meta( $id, 'r18_p_reseaux', true ) ) );
	if ( $reseaux ) {
		$liens = '';
		foreach ( $reseaux as $r ) {
			$hote   = (string) \preg_replace( '/^www\./', '', (string) \wp_parse_url( $r, PHP_URL_HOST ) );
			$nom    = \ucfirst( (string) \strtok( $hote, '.' ) );
			$liens .= '<li><a href="' . \esc_url( $r ) . '" rel="noopener">' . \esc_html( $nom ) . '<span class="screen-reader-text"> de ' . \esc_html( $p['nom'] ) . '</span></a></li>';
		}
		$html .= '<ul class="r18-pt-fiche__reseaux" role="list" aria-label="Réseaux sociaux">' . $liens . '</ul>';
	}
	return '<div class="r18-pt-fiche">' . $html . '</div>';
}

/**
 * Bloc rescue18/partenaire-details : apports du partenariat, avantage pour
 * les pompiers, contenus du site qui citent le partenaire.
 *
 * @param array<string,mixed> $a     Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_partenaire_details( array $a, ?\WP_Block $block = null ): string {
	$id = (int) ( $block->context['postId'] ?? \get_the_ID() );
	if ( PT_TYPE !== \get_post_type( $id ) ) {
		return '';
	}
	$nom  = \get_the_title( $id );
	$html = '';
	$apports = \array_filter( \array_map( 'trim', \explode( "\n", (string) \get_post_meta( $id, 'r18_p_apport', true ) ) ) );
	if ( $apports ) {
		$li = '';
		foreach ( $apports as $l ) {
			$li .= '<li>' . \esc_html( $l ) . '</li>';
		}
		$html .= '<section class="r18-pt-det"><h2>Ce que ' . \esc_html( $nom ) . ' apporte à Rescue 18</h2><ul class="r18-pt-avantages" role="list">' . $li . '</ul></section>';
	}
	$avantage = \trim( (string) \get_post_meta( $id, 'r18_p_avantage', true ) );
	if ( '' !== $avantage ) {
		$html .= '<section class="r18-pt-det r18-pt-offre"><p class="r18-eyebrow">Avantage pompiers</p><h2>Une offre pour toi</h2>' . \wpautop( \esc_html( $avantage ) ) . '</section>';
	}
	// Contenus du site qui citent le partenaire : articles, documents, épisodes.
	$q = new \WP_Query(
		array(
			'post_type'           => array( 'post', 'r18_document', 'r18_episode' ),
			'post_status'         => 'publish',
			's'                   => '"' . $nom . '"',
			'posts_per_page'      => 4,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( $q->have_posts() ) {
		$li = '';
		foreach ( $q->posts as $post ) {
			$type = array(
				'post'         => 'Article',
				'r18_document' => 'Document',
				'r18_episode'  => 'Podcast',
			)[ $post->post_type ] ?? '';
			$li  .= '<li><a href="' . \esc_url( (string) \get_permalink( $post ) ) . '"><span>' . \esc_html( $type ) . '</span>' . \esc_html( \get_the_title( $post ) ) . '</a></li>';
		}
		$html .= '<section class="r18-pt-det"><h2>' . \esc_html( $nom ) . ' sur Rescue 18</h2><ul class="r18-pt-liens" role="list">' . $li . '</ul></section>';
	}
	return '' !== $html ? '<div class="r18-pt-details">' . $html . '</div>' : '';
}

/* Seules les conventions ont une fiche publique. Les autres renvoient vers la page Partenaires. */
\add_action(
	'template_redirect',
	static function (): void {
		if ( \is_singular( PT_TYPE ) && 'convention' !== \get_post_meta( (int) \get_queried_object_id(), 'r18_p_niveau', true ) ) {
			\wp_safe_redirect( \home_url( '/partenaires/' ), 302 );
			exit;
		}
	}
);

/* Suivi des clics : le script commun (inc/clics.php) appelle cette fonction.
 * Un clic sortant est compté par mois, sans cookie ni donnée personnelle.
 * Les chiffres servent au bilan présenté aux partenaires.
 */

/**
 * Ajoute un clic au compteur mensuel d'un partenaire.
 *
 * @param int $id Partenaire.
 */
function compter_clic_partenaire( int $id ): void {
	$clics = \get_post_meta( $id, 'r18_p_clics', true );
	$clics = \is_array( $clics ) ? $clics : array();
	$mois  = \wp_date( 'Y-m' );
	$clics[ $mois ] = (int) ( $clics[ $mois ] ?? 0 ) + 1;
	\krsort( $clics );
	\update_post_meta( $id, 'r18_p_clics', \array_slice( $clics, 0, 24, true ) );
}

/* Back-office ------------------------------------------------------------- */

\add_action(
	'add_meta_boxes_' . PT_TYPE,
	static function (): void {
		\add_meta_box( 'r18-partenaire', 'Partenariat', __NAMESPACE__ . '\\boite_partenaire', PT_TYPE, 'normal', 'high' );
		\add_meta_box( 'r18-partenaire-clics', 'Clics vers le site', __NAMESPACE__ . '\\boite_clics', PT_TYPE, 'side' );
	}
);

/**
 * Boîte de réglages d'un partenaire.
 *
 * @param \WP_Post $post Partenaire.
 */
function boite_partenaire( \WP_Post $post ): void {
	\wp_nonce_field( 'r18_partenaire', 'r18_partenaire_nonce' );
	echo '<p>Le nom est le titre. La courte présentation est l’extrait, affiché sur la page Partenaires. L’ordre d’affichage se règle dans « Attributs ».</p><table class="form-table" role="presentation">';
	foreach ( champs_partenaire() as $cle => [ $label, $type, $aide ] ) {
		$v  = (string) \get_post_meta( $post->ID, $cle, true );
		$id = \esc_attr( $cle );
		echo '<tr><th scope="row"><label for="' . $id . '">' . \esc_html( $label ) . '</label></th><td>';
		if ( 'niveau' === $type ) {
			echo '<select id="' . $id . '" name="' . $id . '">';
			foreach ( niveaux_partenaires() as $k => [ $l ] ) {
				echo '<option value="' . \esc_attr( $k ) . '"' . \selected( '' === $v ? 'soutien' : $v, $k, false ) . '>' . \esc_html( $l ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'fond' === $type ) {
			echo '<select id="' . $id . '" name="' . $id . '"><option value="clair"' . \selected( $v, 'clair', false ) . '>Clair</option><option value="sombre"' . \selected( $v, 'sombre', false ) . '>Sombre</option></select>';
		} elseif ( 'date' === $type ) {
			echo '<input type="date" id="' . $id . '" name="' . $id . '" value="' . \esc_attr( $v ) . '">';
		} elseif ( 'texte' === $type ) {
			echo '<input type="text" class="large-text" id="' . $id . '" name="' . $id . '" value="' . \esc_attr( $v ) . '">';
		} elseif ( 'liste' === $type || 'zone' === $type ) {
			echo '<textarea class="large-text" rows="4" id="' . $id . '" name="' . $id . '">' . \esc_textarea( $v ) . '</textarea>';
		} else {
			echo '<input type="url" class="large-text" id="' . $id . '" name="' . $id . '" value="' . \esc_attr( $v ) . '" placeholder="https://">';
			if ( 'r18_p_logo' === $cle && '' !== $v ) {
				echo '<p><img src="' . \esc_url( $v ) . '" alt="" style="max-height:60px;max-width:220px;margin-top:8px" referrerpolicy="no-referrer"></p>';
			}
		}
		if ( '' !== $aide ) {
			echo '<p class="description">' . \esc_html( $aide ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

/**
 * Boîte des clics par mois.
 *
 * @param \WP_Post $post Partenaire.
 */
function boite_clics( \WP_Post $post ): void {
	$clics = \get_post_meta( $post->ID, 'r18_p_clics', true );
	if ( ! \is_array( $clics ) || ! $clics ) {
		echo '<p>Aucun clic compté pour l’instant.</p>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th scope="col">Mois</th><th scope="col">Clics</th></tr></thead><tbody>';
	foreach ( \array_slice( $clics, 0, 12, true ) as $mois => $n ) {
		echo '<tr><td>' . \esc_html( \date_i18n( 'F Y', (int) \strtotime( $mois . '-01' ) ) ) . '</td><td>' . (int) $n . '</td></tr>';
	}
	echo '</tbody></table><p class="description">Un clic par visiteur et par heure, robots exclus.</p>';
}

\add_action(
	'save_post_' . PT_TYPE,
	static function ( int $id ): void {
		if ( ! isset( $_POST['r18_partenaire_nonce'] ) || ! \wp_verify_nonce( \sanitize_key( $_POST['r18_partenaire_nonce'] ), 'r18_partenaire' ) ) {
			return;
		}
		if ( ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! \current_user_can( 'edit_post', $id ) ) {
			return;
		}
		foreach ( champs_partenaire() as $cle => [ , $type ] ) {
			$brut = isset( $_POST[ $cle ] ) ? \wp_unslash( $_POST[ $cle ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$v    = \in_array( $type, array( 'liste', 'zone' ), true ) ? \sanitize_textarea_field( (string) $brut ) : \sanitize_text_field( (string) $brut );
			if ( 'r18_p_reseaux' === $cle ) {
				$v = \implode( "\n", \array_filter( \array_map( static fn( string $l ): string => \esc_url_raw( \trim( $l ), array( 'https', 'http' ) ), \explode( "\n", $v ) ) ) );
			} elseif ( 'url' === $type ) {
				$v = \esc_url_raw( $v, array( 'https', 'http' ) );
			} elseif ( 'date' === $type ) {
				$v = \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
			} elseif ( 'niveau' === $type ) {
				$v = isset( niveaux_partenaires()[ $v ] ) ? $v : 'soutien';
			} elseif ( 'fond' === $type ) {
				$v = 'sombre' === $v ? 'sombre' : 'clair';
			}
			\update_post_meta( $id, $cle, $v );
		}
		\wp_cache_delete( 'liste', 'r18_partenaires' );
	},
	5
);

\add_filter(
	'manage_' . PT_TYPE . '_posts_columns',
	static function ( array $c ): array {
		return array(
			'cb'           => $c['cb'] ?? '',
			'r18_logo'     => 'Logo',
			'title'        => 'Partenaire',
			'r18_niveau'   => 'Niveau',
			'r18_fin'      => 'Fin',
			'r18_clics'    => 'Clics (mois)',
			'menu_order'   => 'Ordre',
		);
	}
);
\add_action(
	'manage_' . PT_TYPE . '_posts_custom_column',
	static function ( string $col, int $id ): void {
		switch ( $col ) {
			case 'r18_logo':
				$l = (string) \get_the_post_thumbnail_url( $id, 'thumbnail' );
				$l = '' !== $l ? $l : (string) \get_post_meta( $id, 'r18_p_logo', true );
				$f = 'sombre' === \get_post_meta( $id, 'r18_p_fond', true ) ? '#11161f' : '#fff';
				if ( '' !== $l ) {
					echo '<img src="' . \esc_url( $l ) . '" alt="" referrerpolicy="no-referrer" style="max-width:110px;max-height:40px;padding:4px;background:' . \esc_attr( $f ) . ';border:1px solid #dcdcde;border-radius:4px">';
				}
				break;
			case 'r18_niveau':
				$n = (string) \get_post_meta( $id, 'r18_p_niveau', true );
				echo \esc_html( niveaux_partenaires()[ $n ][0] ?? niveaux_partenaires()['soutien'][0] );
				break;
			case 'r18_fin':
				$f = (string) \get_post_meta( $id, 'r18_p_fin', true );
				if ( '' === $f ) {
					echo '—';
				} else {
					$passe = $f < \wp_date( 'Y-m-d' );
					echo '<span style="color:' . ( $passe ? '#b32d2e' : 'inherit' ) . '">' . \esc_html( \date_i18n( 'j M Y', (int) \strtotime( $f ) ) ) . ( $passe ? ' (terminé)' : '' ) . '</span>';
				}
				break;
			case 'r18_clics':
				$c = \get_post_meta( $id, 'r18_p_clics', true );
				echo (int) ( \is_array( $c ) ? ( $c[ \wp_date( 'Y-m' ) ] ?? 0 ) : 0 );
				break;
			case 'menu_order':
				echo (int) \get_post_field( 'menu_order', $id );
				break;
		}
	},
	10,
	2
);

/* Rappel des conventions à renouveler, sur le tableau de bord et la liste. */
\add_action(
	'admin_notices',
	static function (): void {
		$ecran = \get_current_screen();
		if ( ! $ecran || ! \in_array( $ecran->id, array( 'dashboard', 'edit-' . PT_TYPE ), true ) || ! \current_user_can( 'edit_pages' ) ) {
			return;
		}
		$jour  = \wp_date( 'Y-m-d' );
		$limite = \wp_date( 'Y-m-d', \time() + 45 * DAY_IN_SECONDS );
		$ids   = \get_posts(
			array(
				'post_type'      => PT_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => 'r18_p_fin',
						'value'   => array( $jour, $limite ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
			)
		);
		if ( ! $ids ) {
			return;
		}
		$items = array();
		foreach ( $ids as $id ) {
			$items[] = '<a href="' . \esc_url( (string) \get_edit_post_link( $id ) ) . '">' . \esc_html( \get_the_title( $id ) ) . '</a> (' . \esc_html( \date_i18n( 'j F Y', (int) \strtotime( (string) \get_post_meta( $id, 'r18_p_fin', true ) ) ) ) . ')';
		}
		echo '<div class="notice notice-warning"><p><strong>Partenariats à renouveler :</strong> ' . \wp_kses_post( \implode( ', ', $items ) ) . '.</p></div>';
	}
);

/**
 * Partenaires de départ, repris du site actuel. Créés une seule fois,
 * jamais réécrits : tout se modifie ensuite au back-office.
 */
function amorcer_partenaires(): void {
	if ( \get_option( 'r18_partenaires_amorce' ) ) {
		return;
	}
	\update_option( 'r18_partenaires_amorce', 1, false );
	if ( \get_posts( array( 'post_type' => PT_TYPE, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) ) ) {
		return;
	}
	$u    = 'https://rescue18.com/wp-content/uploads/';
	$base = array(
		array( 'Gelucal Incendie', 'convention', 'https://gelucal-incendie.fr/', $u . '2023/09/ideoh_gelucal_09122025-1-scaled.png', 'Matériel et équipements de lutte contre l’incendie.' ),
		array( 'NP Avocats Associés', 'convention', 'https://cabinet-np.com/', $u . '2025/11/avocats-associes.png', 'Cabinet d’avocats, conseil juridique.' ),
		array( 'Magirus', 'convention', 'https://www.magirusgroup.com/', $u . '2025/04/magirus_horizontal_cmyk-scaled.jpg', 'Constructeur d’engins et d’échelles de lutte contre l’incendie.' ),
		array( 'G2RD Agence Web', 'soutien', 'https://g2rd.fr/', $u . '2023/09/logo-g2rd-agence-web-couleur-horizontal-version-2025.png', 'Agence web, conception et hébergement du site Rescue 18.' ),
		array( 'Aum Biosync', 'soutien', 'https://www.aumbiosync.com/', $u . '2023/09/aum.png.webp', '' ),
		array( 'Pas de panique 112', 'soutien', 'https://www.pasdepanique112.org/', $u . '2023/09/sans-titre-2.png.webp', '' ),
		array( 'Info Pompiers', 'soutien', 'https://infopompiers.com/', $u . '2023/09/info-pompier.png.webp', '' ),
		array( 'Atraksis', 'soutien', 'https://atraksis.fr/', $u . '2023/09/atraksis.png.webp', '' ),
		array( 'APTE', 'soutien', 'https://asso-apte.fr/', $u . '2023/09/logo-apte-310123-baseline.png', '' ),
		array( 'RO Innovation', 'soutien', 'https://ro-innovation.fr/', $u . '2023/09/ro-innovation.png', '' ),
		array( 'Gyro 2 Tons', 'soutien', 'https://www.bretagnebroderie.fr/', $u . '2023/09/gyro-2-tons.png.webp', '' ),
	);
	$base = \array_merge( $base, soutiens_ajoutes() );
	foreach ( $base as $i => [ $nom, $niveau, $url, $logo, $texte ] ) {
		$id = (int) \wp_insert_post(
			array(
				'post_type'    => PT_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $nom,
				'post_excerpt' => $texte,
				'menu_order'   => $i + 1,
			)
		);
		if ( $id > 0 ) {
			\update_post_meta( $id, 'r18_p_niveau', $niveau );
			\update_post_meta( $id, 'r18_p_url', $url );
			\update_post_meta( $id, 'r18_p_logo', $logo );
			\update_post_meta( $id, 'r18_p_fond', 'clair' );
		}
	}
	\wp_cache_delete( 'liste', 'r18_partenaires' );
}
\add_action( 'wp_loaded', __NAMESPACE__ . '\\amorcer_partenaires', 20 );

/* Fiche sans texte rédigé : présentation neutre, remplacée dès qu'un texte est saisi. */
\add_filter(
	'render_block_core/post-content',
	static function ( string $html, array $bloc, \WP_Block $instance ): string {
		$id = (int) ( $instance->context['postId'] ?? 0 );
		if ( PT_TYPE !== \get_post_type( $id ) || '' !== \trim( (string) \get_post_field( 'post_content', $id ) ) ) {
			return $html;
		}
		$nom = \get_the_title( $id );
		return '<div class="entry-content wp-block-post-content"><h2>' . \esc_html( $nom ) . ' et Rescue 18</h2><p>' . \esc_html( $nom ) . ' soutient Rescue 18 par une convention de partenariat. Ce soutien finance la documentation, la veille juridique et les outils mis gratuitement à disposition de tous les pompiers.</p><p>Pour connaître ses produits et services, rends-toi sur son site.</p></div>';
	},
	10,
	3
);


/* Fiche G2RD : informations fournies par l'agence, posées une fois si les champs sont vides. */
\add_action(
	'wp_loaded',
	static function (): void {
		if ( \get_option( 'r18_pt_fiches_v1' ) ) {
			return;
		}
		\update_option( 'r18_pt_fiches_v1', 1, false );
		$g = \get_page_by_path( 'g2rd-agence-web', OBJECT, PT_TYPE );
		if ( ! $g instanceof \WP_Post ) {
			return;
		}
		$valeurs = array(
			'r18_p_activite' => 'Agence web WordPress',
			'r18_p_ville'    => 'Gaillon, Normandie (27)',
			'r18_p_apport'   => "Conception du site Rescue 18\nHébergement et maintenance\nSécurité et sauvegardes\nAutomatisation de la veille documentaire",
		);
		foreach ( $valeurs as $cle => $v ) {
			if ( '' === (string) \get_post_meta( $g->ID, $cle, true ) ) {
				\update_post_meta( $g->ID, $cle, $v );
			}
		}
		if ( '' === \trim( $g->post_content ) ) {
			\wp_update_post(
				array(
					'ID'           => $g->ID,
					'post_content' => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Qui est G2RD ?</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p>G2RD est une agence web spécialisée dans WordPress, basée à Gaillon en Normandie et qui travaille à distance avec des clients partout en France.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>L’agence conçoit des sites WordPress et WooCommerce, s’occupe de leur référencement, de leur maintenance et de leur sécurité, et automatise les tâches répétitives de ses clients.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">G2RD et Rescue 18</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p>G2RD a conçu le site Rescue 18 et en assure l’hébergement, la maintenance et la sécurité. L’agence a aussi développé les automatisations qui alimentent le site : veille des documents officiels, actualités des SDIS, offres d’emploi et textes juridiques.</p>\n<!-- /wp:paragraph -->",
				)
			);
		}
		\wp_cache_delete( 'liste', 'r18_partenaires' );
	},
	25
);

/**
 * Soutiens repris du site actuel, absents de la première importation.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string,4:string}>
 */
function soutiens_ajoutes(): array {
	$u = 'https://rescue18.com/wp-content/uploads/';
	return array(
		array( 'Au cœur de l’action', 'soutien', '', $u . '2023/09/au-coeur-de-laction.png.webp', '' ),
		array( 'Le Jargon Pompier', 'soutien', 'https://www.facebook.com/LeJargonPompier', $u . '2023/09/jargon.png.webp', '' ),
		array( 'Drakkar', 'soutien', 'https://www.facebook.com/profile.php?id=100045496159818', $u . '2023/09/drakkar.png.webp', '' ),
	);
}

/**
 * Migration unique vers deux niveaux : les anciens partenaires rémunérés
 * deviennent des soutiens, G2RD Agence Web aussi.
 */
\add_action(
	'init',
	static function (): void {
		if ( '3' === \get_option( 'r18_pt_niveaux' ) ) {
			return;
		}
		$ids = \get_posts(
			array(
				'post_type'      => PT_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			$niv = (string) \get_post_meta( $id, 'r18_p_niveau', true );
			if ( 'partenaire' === $niv || ( 'convention' === $niv && \str_starts_with( \strtolower( \get_the_title( $id ) ), 'g2rd' ) ) ) {
				\update_post_meta( $id, 'r18_p_niveau', 'soutien' );
			}
		}
		// Soutiens manquants : ajoutés s'ils n'existent pas déjà (même nom).
		$noms = \array_map( static fn( int $id ): string => \sanitize_title( \get_the_title( $id ) ), $ids );
		$ordre = \count( $ids );
		foreach ( soutiens_ajoutes() as [ $nom, $niveau, $url, $logo ] ) {
			if ( \in_array( \sanitize_title( $nom ), $noms, true ) ) {
				continue;
			}
			$id = (int) \wp_insert_post(
				array(
					'post_type'   => PT_TYPE,
					'post_status' => 'publish',
					'post_title'  => $nom,
					'menu_order'  => ++$ordre,
				)
			);
			if ( $id > 0 ) {
				\update_post_meta( $id, 'r18_p_niveau', $niveau );
				\update_post_meta( $id, 'r18_p_url', $url );
				\update_post_meta( $id, 'r18_p_logo', $logo );
				\update_post_meta( $id, 'r18_p_fond', 'clair' );
			}
		}
		\wp_cache_delete( 'liste', 'r18_partenaires' );
		\update_option( 'r18_pt_niveaux', '3', false );
	},
	30
);
