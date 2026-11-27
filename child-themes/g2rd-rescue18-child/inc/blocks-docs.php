<?php
/**
 * Rendus documentaires : bibliothèque, nouveautés, veille juridique, fiches.
 *
 * Toutes les données viennent des types de contenu (inc/content.php).
 * Aucune donnée de démonstration n'est affichée comme réelle : un
 * catalogue vide affiche un message explicite.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Icône selon le type de document.
 *
 * @param string $type Slug du type (gdo, gto, retex, referentiel, gnr, juridique).
 * @return string
 */
function doc_icon_name( string $type ): string {
	$map = array(
		'gdo'         => 'book',
		'gto'         => 'shield',
		'gnr'         => 'book',
		'retex'       => 'clip',
		'pex'         => 'clip',
		'referentiel' => 'grad',
		'juridique'   => 'file',
	);
	return $map[ $type ] ?? 'file';
}

/**
 * Données d'affichage d'une fiche (document ou texte juridique).
 *
 * @param \WP_Post $post Contenu.
 * @return array<string,string>
 */
function doc_data( \WP_Post $post ): array {
	if ( 'r18_texte' === $post->post_type ) {
		$nature = meta( $post->ID, 'r18_nature' );
		$etat   = meta( $post->ID, 'r18_etat' );
		$etat   = '' !== $etat ? $etat : 'a-verifier';
		return array(
			'type'       => 'juridique',
			'cat'        => \__( 'Juridique', 'g2rd-rescue18-child' ),
			'nature'     => natures_textes()[ $nature ] ?? \__( 'Texte', 'g2rd-rescue18-child' ),
			'meta'       => \wp_strip_all_tags( \get_the_excerpt( $post ) ),
			'statut'     => $etat,
			'statut_lbl' => etats_juridiques()[ $etat ] ?? '',
		);
	}
	$type   = first_term( $post->ID, 'r18_type_doc' );
	$org    = first_term( $post->ID, 'r18_organisme' );
	$edit   = meta( $post->ID, 'r18_editeur' );
	$eterm  = '' !== $edit ? \get_term_by( 'slug', $edit, 'r18_organisme' ) : false;
	$org    = $eterm instanceof \WP_Term ? $eterm : $org;
	$statut = meta( $post->ID, 'r18_statut' );
	$statut = '' !== $statut ? $statut : 'en-ligne';
	$refs   = \json_decode( meta( $post->ID, 'r18_refs' ), true );
	$nsrc   = \is_array( $refs ) ? \count( $refs ) : 0;
	$bits   = \array_filter( array( $org ? $org->name : '', meta( $post->ID, 'r18_version' ), $nsrc > 1 ? \sprintf( '%d sources', $nsrc ) : '' ) );
	return array(
		'type'       => $type ? $type->slug : 'document',
		'cat'        => $type ? $type->name : \__( 'Document', 'g2rd-rescue18-child' ),
		'nature'     => '',
		'meta'       => \implode( ' · ', $bits ),
		'statut'     => $statut,
		'statut_lbl' => statuts_document()[ $statut ] ?? '',
	);
}

/**
 * Sigle affiché en grand sur la couverture d'un document.
 *
 * @param string $type Slug du type.
 * @return string
 */
function doc_sigle( string $type ): string {
	return array(
		'gdo'         => 'GDO',
		'gto'         => 'GTO',
		'gnr'         => 'GNR',
		'retex'       => 'RETEX',
		'pex'         => 'PEX',
		'pio'         => 'PIO',
		'memento'     => 'Mémento',
		'vademecum'   => 'Vadémécum',
		'referentiel' => 'Référentiel',
		'etude'       => 'Étude',
		'memoire'     => 'Mémoire',
		'juridique'   => 'Texte',
	)[ $type ] ?? 'Doc';
}

/**
 * Couverture d'une carte : vraie image si la fiche en a une (image à la
 * une, image de la source), sinon visuel aux couleurs du type, avec le logo
 * du SDIS cité dans le titre (image externe, rien n'est copié).
 *
 * @param \WP_Post $post Fiche.
 * @param string   $type Type.
 * @return string
 */
function doc_cover( \WP_Post $post, string $type ): string {
	$img = '';
	if ( \has_post_thumbnail( $post ) ) {
		$img = (string) \get_the_post_thumbnail( $post, 'medium_large', array( 'class' => 'r18-doc__photo', 'alt' => '', 'loading' => 'lazy' ) );
	} elseif ( '' !== meta( $post->ID, 'r18_image_source' ) && \function_exists( __NAMESPACE__ . '\\image_externe' ) ) {
		$img = image_externe( meta( $post->ID, 'r18_image_source' ), '', 'r18-doc__photo' );
	}
	$svc  = \function_exists( 'G2RD_Rescue18\\Annuaire\\service_cite' ) ? Annuaire\service_cite( \get_the_title( $post ) ) : null;
	if ( '' === $img && $svc && '' !== (string) $svc['image'] ) {
		$img = image_externe( (string) $svc['image'], '', 'r18-doc__photo' );
	}
	$logo = '';
	if ( $svc ) {
		$logo = '<span class="r18-doc__svc">' . ( '' !== (string) $svc['logo'] ? '<span class="r18-doc__logo">' . image_externe( (string) $svc['logo'], '' ) . repli_service( $svc ) . '</span>' : '' ) . '<span>' . \esc_html( court_service( $svc ) ) . '</span></span>';
	}
	return '<span class="r18-doc__cover' . ( '' !== $img ? ' has-photo' : '' ) . '" aria-hidden="true">' . $img
		. '<span class="r18-doc__sigle">' . \esc_html( doc_sigle( $type ) ) . '</span>'
		. '<span class="r18-doc__big">' . icone( doc_icon_name( $type ) ) . '</span>' . $logo . '</span>';
}

/**
 * Carte d'une fiche documentaire.
 *
 * @param \WP_Post $post Contenu.
 * @return string
 */
function doc_card( \WP_Post $post ): string {
	$d      = doc_data( $post );
	$url    = (string) \get_permalink( $post );
	$title  = \get_the_title( $post );
	$search = \strtolower( \remove_accents( $title . ' ' . $d['cat'] . ' ' . $d['meta'] ) );
	return \sprintf(
		'<article class="r18-doc" data-type="%1$s" data-search="%2$s">' . doc_cover( $post, $d['type'] ) . '<span class="r18-doc__ic">%3$s</span><span class="r18-doc__cat">%4$s</span><h3 class="r18-doc__title"><a href="%5$s">%6$s</a></h3><p class="r18-doc__meta">%7$s</p><div class="r18-doc__end">%8$s<span class="r18-doc__acts"><span class="r18-doc__go" aria-hidden="true">%9$s</span><button type="button" class="r18-fav" data-fav="r18-%10$d" aria-pressed="false" aria-label="%11$s">%12$s</button></span></div></article>',
		\esc_attr( $d['type'] ),
		\esc_attr( $search ),
		icone( doc_icon_name( $d['type'] ) ),
		\esc_html( $d['cat'] ),
		\esc_url( $url ),
		\esc_html( $title ),
		\esc_html( $d['meta'] ),
		pill( $d['statut_lbl'], $d['statut'] ),
		\esc_html__( 'Consulter', 'g2rd-rescue18-child' ),
		(int) $post->ID,
		/* translators: %s : titre du document. */
		\esc_attr( \sprintf( \__( 'Ajouter « %s » aux favoris', 'g2rd-rescue18-child' ), $title ) ),
		icone( 'star' )
	);
}

/**
 * Types filtrables : termes r18_type_doc + juridique.
 *
 * @return array<string,string> slug => libellé
 */
function doc_types(): array {
	$out   = array();
	$terms = \get_terms(
		array(
			'taxonomy'   => 'r18_type_doc',
			'hide_empty' => true,
			'orderby'    => 'term_order',
		)
	);
	if ( \is_array( $terms ) ) {
		foreach ( $terms as $t ) {
			$out[ $t->slug ] = $t->name;
		}
	}
	$out['juridique'] = \__( 'Juridique', 'g2rd-rescue18-child' );
	return $out;
}

/**
 * Arguments de requête selon un type présélectionné.
 *
 * @param string $type Slug de type, « juridique » ou vide.
 * @return array<string,mixed>
 */
function doc_query_for_type( string $type ): array {
	if ( 'juridique' === $type ) {
		return array( 'post_type' => array( 'r18_texte' ) );
	}
	$args = array( 'post_type' => array( 'r18_document' ) );
	if ( '' !== $type ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- filtre principal de la bibliothèque.
			array(
				'taxonomy' => 'r18_type_doc',
				'field'    => 'slug',
				'terms'    => \array_map( 'sanitize_title', \explode( ',', $type ) ),
			),
		);
	} else {
		$args['post_type'][] = 'r18_texte';
	}
	return $args;
}

/**
 * Bloc rescue18/bibliotheque.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_bibliotheque( array $attrs ): string {
	\wp_enqueue_script( 'rescue18-bibliotheque' );
	return 'page' === ( $attrs['variante'] ?? '' ) ? bibliotheque_page( $attrs ) : bibliotheque_accueil( $attrs );
}

/**
 * Variante accueil : aperçu filtrable côté navigateur, recherche complète
 * envoyée à la page Bibliothèque.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function bibliotheque_accueil( array $attrs ): string {
	$type  = \sanitize_text_field( (string) ( $attrs['type'] ?? '' ) );
	$q     = new \WP_Query(
		\array_merge(
			doc_query_for_type( $type ),
			array(
				'posts_per_page'      => \max( 1, (int) ( $attrs['nombre'] ?? 8 ) ),
				'orderby'             => 'modified',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		)
	);
	$cards = '';
	foreach ( $q->posts as $p ) {
		$cards .= doc_card( $p );
	}

	$filters = '<button type="button" class="r18-fbtn" data-filter="" aria-pressed="true">' . \esc_html__( 'Tous les documents', 'g2rd-rescue18-child' ) . '</button>';
	foreach ( doc_types() as $slug => $label ) {
		$filters .= '<button type="button" class="r18-fbtn" data-filter="' . \esc_attr( $slug ) . '" aria-pressed="false">' . \esc_html( $label ) . '</button>';
	}

	$counts = \wp_count_posts( 'r18_document' );
	$total  = (int) ( $counts->publish ?? 0 ) + (int) ( \wp_count_posts( 'r18_texte' )->publish ?? 0 );
	$uid    = \wp_unique_id( 'r18q-' );
	$empty  = '' === $cards
		? '<p class="r18-lib__empty">' . \esc_html__( 'La bibliothèque se remplit au premier cycle de synchronisation des sources officielles.', 'g2rd-rescue18-child' ) . '</p>'
		: '<p class="r18-lib__empty" data-r18-empty hidden>' . \esc_html__( 'Aucun document ne correspond dans cet aperçu. La recherche complète interroge tout le catalogue.', 'g2rd-rescue18-child' ) . '</p>';

	$html  = '<form class="r18-lib__search" role="search" method="get" action="' . \esc_url( rubrique_url( 'documentation' ) ) . '">';
	$html .= icone( 'search' );
	$html .= '<label class="screen-reader-text" for="' . \esc_attr( $uid ) . '">' . \esc_html__( 'Rechercher un document', 'g2rd-rescue18-child' ) . '</label>';
	$html .= '<input id="' . \esc_attr( $uid ) . '" name="q" type="search" autocomplete="off" data-r18-query placeholder="' . \esc_attr__( 'GDO, GTO, secours routier, RETEX, référentiel, décret…', 'g2rd-rescue18-child' ) . '">';
	$html .= '<button type="submit" class="r18-btn r18-btn--red">' . \esc_html__( 'Rechercher', 'g2rd-rescue18-child' ) . ' ' . icone( 'arrow' ) . '</button></form>';
	$html .= '<div class="r18-lib__filters" role="group" aria-label="' . \esc_attr__( 'Filtrer les documents', 'g2rd-rescue18-child' ) . '">' . $filters . '</div>';
	/* translators: %d : nombre de fiches. */
	$html .= '<div class="r18-lib__status"><span data-r18-count aria-live="polite">' . \esc_html( \sprintf( \_n( '%d document affiché', '%d documents affichés', \count( $q->posts ), 'g2rd-rescue18-child' ), \count( $q->posts ) ) ) . '</span>';
	/* translators: %d : nombre total de fiches. */
	$html .= '<span>' . \esc_html( \sprintf( \_n( 'Bibliothèque complète : %d fiche, GDO, GTO, référentiels, RETEX et veille juridique', 'Bibliothèque complète : %d fiches, GDO, GTO, référentiels, RETEX et veille juridique', $total, 'g2rd-rescue18-child' ), $total ) ) . '</span></div>';
	$html .= '<div class="r18-doc-grid" data-r18-grid>' . $cards . '</div>' . $empty;
	$html .= '<div class="r18-lib__bottom"><span>' . \esc_html__( 'Les fiches renvoient toujours vers la publication officielle et sa version en vigueur.', 'g2rd-rescue18-child' ) . '</span>';
	$html .= '<a class="r18-btn r18-btn--white" href="' . \esc_url( rubrique_url( 'documentation' ) ) . '">' . \esc_html__( 'Ouvrir la bibliothèque', 'g2rd-rescue18-child' ) . ' ' . icone( 'arrow' ) . '</a></div>';

	return wrap( 'r18-lib r18-lib--accueil', $html, 'div', array( 'data-r18-lib' => 'accueil' ) );
}

/**
 * Lecture d'un tableau de slugs en GET.
 *
 * @param string $key Paramètre.
 * @return string[]
 */
function get_slugs( string $key ): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtres publics en lecture seule.
	$raw = isset( $_GET[ $key ] ) ? (array) \wp_unslash( $_GET[ $key ] ) : array();
	return \array_values( \array_filter( \array_map( 'sanitize_title', $raw ) ) );
}

/**
 * Lecture d'une chaîne en GET.
 *
 * @param string $key Paramètre.
 * @return string
 */
function get_text( string $key ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtres publics en lecture seule.
	return isset( $_GET[ $key ] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET[ $key ] ) ) : '';
}

/**
 * Groupe de cases à cocher d'une facette.
 *
 * @param string                $title    Titre.
 * @param string                $name     Nom du paramètre.
 * @param array<string,string>  $options  slug => libellé.
 * @param string[]              $selected Sélection.
 * @param array<string,int>     $counts   Compteurs.
 * @return string
 */
function facet( string $title, string $name, array $options, array $selected, array $counts = array() ): string {
	if ( empty( $options ) ) {
		return '';
	}
	$html = '<fieldset class="r18-facet"><legend>' . \esc_html( $title ) . '</legend>';
	foreach ( $options as $slug => $label ) {
		$html .= '<label><input type="checkbox" name="' . \esc_attr( $name ) . '[]" value="' . \esc_attr( $slug ) . '"' . \checked( \in_array( $slug, $selected, true ), true, false ) . '>' . \esc_html( $label );
		if ( isset( $counts[ $slug ] ) ) {
			$html .= '<span>' . (int) $counts[ $slug ] . '</span>';
		}
		$html .= '</label>';
	}
	return $html . '</fieldset>';
}

/**
 * Options d'une taxonomie pour une facette.
 *
 * @param string $taxonomy Taxonomie.
 * @return array{0:array<string,string>,1:array<string,int>}
 */
function facet_terms( string $taxonomy ): array {
	$terms = \get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);
	$opts  = array();
	$count = array();
	if ( \is_array( $terms ) ) {
		foreach ( $terms as $t ) {
			$opts[ $t->slug ]  = $t->name;
			$count[ $t->slug ] = (int) $t->count;
		}
	}
	return array( $opts, $count );
}

/**
 * Années de publication des fiches (facette), les plus récentes d'abord.
 *
 * @return array{0:array<string,string>,1:array<string,int>}
 */
function annees_disponibles(): array {
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT YEAR(post_date) AS a, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = 'r18_document' AND post_status = 'publish' GROUP BY a ORDER BY a DESC LIMIT 12" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$opts = array();
	$cnt  = array();
	foreach ( (array) $rows as $r ) {
		$opts[ (string) $r->a ] = (string) $r->a;
		$cnt[ (string) $r->a ]  = (int) $r->n;
	}
	return array( $opts, $cnt );
}

/**
 * Versions explicites présentes (facette) : slug => libellé.
 *
 * @return array<string,string>
 */
function versions_disponibles(): array {
	global $wpdb;
	$vals = $wpdb->get_col( "SELECT DISTINCT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = 'r18_version' AND m.meta_value <> '' AND p.post_status = 'publish' ORDER BY m.meta_value LIMIT 15" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$out  = array();
	foreach ( (array) $vals as $v ) {
		$out[ \sanitize_title( (string) $v ) ] = (string) $v;
	}
	return $out;
}

/**
 * Variante page : recherche serveur, facettes, tri, pagination.
 *
 * Paramètres GET : q, type, spe[], org[], pub[], statut[], tri, pg.
 * « q » plutôt que « s » : la page reste une page, pas une recherche WordPress.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function bibliotheque_page( array $attrs ): string {
	$preset = \sanitize_text_field( (string) ( $attrs['type'] ?? '' ) );
	$type   = '' !== get_text( 'type' ) ? \sanitize_title( get_text( 'type' ) ) : $preset;
	$search = get_text( 'q' );
	$spe    = get_slugs( 'spe' );
	$org    = get_slugs( 'org' );
	$pub    = get_slugs( 'pub' );
	$stat   = \array_values( \array_intersect( get_slugs( 'statut' ), \array_keys( statuts_document() ) ) );
	$annees = \array_values( \array_filter( get_slugs( 'annee' ), static fn( string $a ): bool => (bool) \preg_match( '/^(19|20)\d{2}$/', $a ) ) );
	$vers   = get_slugs( 'version' );
	$tri    = get_text( 'tri' );
	$page   = \max( 1, (int) get_text( 'pg' ) );

	$args = \array_merge(
		doc_query_for_type( $type ),
		array(
			'posts_per_page'      => \max( 6, (int) ( $attrs['nombre'] ?? 24 ) ),
			'paged'               => $page,
			'ignore_sticky_posts' => true,
		)
	);
	if ( '' !== $search ) {
		$args['s'] = $search;
	}
	$tax = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
	foreach ( array(
		'r18_specialite' => $spe,
		'r18_organisme'  => $org,
		'r18_public'     => $pub,
	) as $taxonomy => $slugs ) {
		if ( ! empty( $slugs ) ) {
			$tax[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slugs,
			);
		}
	}
	if ( ! empty( $tax ) ) {
		$tax['relation']   = 'AND';
		$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- facettes de la bibliothèque.
	}
	if ( ! empty( $stat ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- facette statut.
			array(
				'key'     => 'r18_statut',
				'value'   => $stat,
				'compare' => 'IN',
			),
		);
	}
	if ( ! empty( $annees ) ) {
		$args['date_query'] = array_merge( array( 'relation' => 'OR' ), \array_map( static fn( string $a ): array => array( 'year' => (int) $a ), $annees ) );
	}
	if ( ! empty( $vers ) ) {
		$mq                 = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
		$vals               = \array_values( \array_intersect_key( versions_disponibles(), \array_flip( $vers ) ) );
		$mq[]               = array(
			'key'     => 'r18_version',
			'value'   => $vals ? $vals : array( '__aucune__' ),
			'compare' => 'IN',
		);
		$args['meta_query'] = $mq; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- facette version.
	}
	if ( 'titre' === $tri ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	} elseif ( 'maj' === $tri ) {
		$args['orderby'] = 'modified';
	}

	$q     = new \WP_Query( $args );
	$base  = \strtok( (string) \get_permalink(), '?' );
	$keep  = \array_filter(
		array(
			'q'      => $search,
			'spe'    => $spe,
			'org'    => $org,
			'pub'    => $pub,
			'statut'  => $stat,
			'annee'   => $annees,
			'version' => $vers,
			'tri'     => $tri,
		)
	);
	$uid   = \wp_unique_id( 'r18-facets-' );
	$field = \wp_unique_id( 'r18-q-' );

	// Barre de recherche principale.
	$html  = '<div class="r18-sbox"><form class="r18-sfield" role="search" method="get" action="' . \esc_url( $base ) . '">' . icone( 'search' );
	$html .= '<label class="screen-reader-text" for="' . \esc_attr( $field ) . '">' . \esc_html__( 'Rechercher dans la bibliothèque', 'g2rd-rescue18-child' ) . '</label>';
	$html .= '<input id="' . \esc_attr( $field ) . '" type="search" name="q" value="' . \esc_attr( $search ) . '" placeholder="' . \esc_attr__( 'Rechercher un guide, un référentiel, un RETEX, un texte…', 'g2rd-rescue18-child' ) . '">';
	if ( '' !== $type && $type !== $preset ) {
		$html .= '<input type="hidden" name="type" value="' . \esc_attr( $type ) . '">';
	}
	$html .= '<button type="submit" class="r18-btn r18-btn--red">' . \esc_html__( 'Rechercher', 'g2rd-rescue18-child' ) . ' ' . icone( 'arrow' ) . '</button></form>';

	[ $spe_opts, $spe_counts ] = facet_terms( 'r18_specialite' );
	if ( ! empty( $spe_counts ) ) {
		\arsort( $spe_counts );
		$html .= '<div class="r18-sugg"><span>' . \esc_html__( 'Recherches fréquentes :', 'g2rd-rescue18-child' ) . '</span>';
		foreach ( \array_slice( $spe_counts, 0, 5, true ) as $slug => $n ) {
			$html .= '<a href="' . \esc_url( \add_query_arg( 'spe[]', $slug, $base ) ) . '">' . \esc_html( $spe_opts[ $slug ] ) . '</a>';
		}
		$html .= '</div>';
	}
	$html .= '</div>';

	$html .= rubs_html();

	// Facettes.
	[ $org_opts, $org_counts ] = facet_terms( 'r18_organisme' );
	[ $pub_opts, $pub_counts ] = facet_terms( 'r18_public' );
	$facets  = '<form id="' . \esc_attr( $uid ) . '" class="r18-facets" method="get" action="' . \esc_url( $base ) . '" aria-label="' . \esc_attr__( 'Filtres', 'g2rd-rescue18-child' ) . '">';
	$facets .= '' !== $search ? '<input type="hidden" name="q" value="' . \esc_attr( $search ) . '">' : '';
	$facets .= '' !== $type && $type !== $preset ? '<input type="hidden" name="type" value="' . \esc_attr( $type ) . '">' : '';
	$facets .= facet( \__( 'Spécialité', 'g2rd-rescue18-child' ), 'spe', $spe_opts, $spe, $spe_counts );
	$facets .= facet( \__( 'Organisme', 'g2rd-rescue18-child' ), 'org', $org_opts, $org, $org_counts );
	$facets .= facet( \__( 'Public', 'g2rd-rescue18-child' ), 'pub', $pub_opts, $pub, $pub_counts );
	$facets .= facet( \__( 'Statut', 'g2rd-rescue18-child' ), 'statut', \array_diff_key( statuts_document(), array( 'en-ligne' => '' ) ), $stat );
	[ $an_opts, $an_counts ] = annees_disponibles();
	$facets .= facet( \__( 'Année', 'g2rd-rescue18-child' ), 'annee', $an_opts, $annees, $an_counts );
	$v_opts  = versions_disponibles();
	$facets .= facet( \__( 'Version', 'g2rd-rescue18-child' ), 'version', \array_combine( \array_keys( $v_opts ), \array_values( $v_opts ) ) ?: array(), $vers );
	$facets .= '<button type="submit" class="r18-btn r18-btn--dark">' . \esc_html__( 'Appliquer les filtres', 'g2rd-rescue18-child' ) . '</button>';
	$facets .= '<a class="r18-btn r18-btn--white" href="' . \esc_url( $base ) . '">' . \esc_html__( 'Réinitialiser', 'g2rd-rescue18-child' ) . '</a></form>';

	// Types.
	$types = '<nav class="r18-types" aria-label="' . \esc_attr__( 'Type de document', 'g2rd-rescue18-child' ) . '">';
	$all   = \array_merge( array( '' => \__( 'Tous', 'g2rd-rescue18-child' ) ), doc_types() );
	foreach ( $all as $slug => $label ) {
		$url    = '' === $slug ? \remove_query_arg( 'type', \add_query_arg( $keep, $base ) ) : \add_query_arg( \array_merge( $keep, array( 'type' => $slug ) ), $base );
		$on     = $slug === $type;
		$types .= '<a class="r18-type' . ( $on ? ' is-on' : '' ) . '" href="' . \esc_url( $url ) . '"' . ( $on ? ' aria-current="true"' : '' ) . '>' . \esc_html( $label ) . '</a>';
	}
	$types .= '</nav>';

	$tri_opts = array(
		''      => \__( 'Plus récents', 'g2rd-rescue18-child' ),
		'titre' => \__( 'Titre A à Z', 'g2rd-rescue18-child' ),
		'maj'   => \__( 'Dernière mise à jour', 'g2rd-rescue18-child' ),
	);
	$sort     = '<label class="screen-reader-text" for="' . \esc_attr( $uid ) . '-tri">' . \esc_html__( 'Trier', 'g2rd-rescue18-child' ) . '</label><select id="' . \esc_attr( $uid ) . '-tri" class="r18-select" name="tri" form="' . \esc_attr( $uid ) . '" data-r18-autosubmit>';
	foreach ( $tri_opts as $k => $l ) {
		$sort .= '<option value="' . \esc_attr( $k ) . '"' . \selected( $tri, $k, false ) . '>' . \esc_html( $l ) . '</option>';
	}
	$sort .= '</select>';
	$views = '<div class="r18-views" role="group" aria-label="' . \esc_attr__( 'Affichage', 'g2rd-rescue18-child' ) . '"><button type="button" data-r18-view="grid" aria-pressed="true" aria-label="' . \esc_attr__( 'Affichage en cartes', 'g2rd-rescue18-child' ) . '">' . icone( 'grid' ) . '</button><button type="button" data-r18-view="list" aria-pressed="false" aria-label="' . \esc_attr__( 'Affichage en liste', 'g2rd-rescue18-child' ) . '">' . icone( 'list' ) . '</button></div>';

	$cards = '';
	foreach ( $q->posts as $p ) {
		$cards .= doc_card( $p );
	}
	$results  = '<section class="r18-results" aria-label="' . \esc_attr__( 'Résultats', 'g2rd-rescue18-child' ) . '"><div class="r18-lib__bar">' . $types . $sort . $views . '</div>';
	/* translators: %d : nombre de résultats. */
	$results .= '<p class="r18-lib__count" aria-live="polite">' . \esc_html( \sprintf( \_n( '%d résultat', '%d résultats', (int) $q->found_posts, 'g2rd-rescue18-child' ), (int) $q->found_posts ) ) . '</p>';
	$results .= '' !== $cards
		? '<div class="r18-doc-grid r18-doc-grid--3" data-r18-grid>' . $cards . '</div>'
		: '<p class="r18-lib__empty">' . \esc_html__( 'Aucun document ne correspond à ces critères. Élargis la recherche ou réinitialise les filtres.', 'g2rd-rescue18-child' ) . '</p>';
	$results .= pagination( (int) $q->max_num_pages, $page, \add_query_arg( \array_merge( $keep, '' !== $type && $type !== $preset ? array( 'type' => $type ) : array() ), $base ) );
	$results .= '</section>';

	return wrap( 'r18-lib r18-lib--page', $html . '<div class="r18-lib__body">' . $facets . $results . '</div>', 'div', array( 'data-r18-lib' => 'page' ) );
}

/**
 * Les quatre rubriques historiques, avec leurs adresses conservées.
 *
 * @return string
 */
function rubs_html(): string {
	$count = static function ( string $types ): int {
		$q = new \WP_Query(
			\array_merge(
				doc_query_for_type( $types ),
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			)
		);
		return (int) $q->found_posts;
	};
	$rubs  = array(
		array( 'gdo-gto', \__( 'GDO / GTO', 'g2rd-rescue18-child' ), \__( 'Guides de doctrine et guides techniques opérationnels', 'g2rd-rescue18-child' ), 'book', false, 'gdo,gto' ),
		array( 'documentation', \__( 'Documentation et référentiels', 'g2rd-rescue18-child' ), \__( 'GNR, référentiels techniques et de formation, SUAP', 'g2rd-rescue18-child' ), 'file', true, 'referentiel,gnr' ),
		array( 'retex', \__( 'RETEX / PEX', 'g2rd-rescue18-child' ), \__( 'Retours et partages d\'expérience des sources publiques', 'g2rd-rescue18-child' ), 'clip', false, 'retex,pex' ),
		array( 'juridique', \__( 'Juridique', 'g2rd-rescue18-child' ), \__( 'Lois, décrets et arrêtés suivis via Légifrance', 'g2rd-rescue18-child' ), 'shield', true, 'juridique' ),
	);
	$html  = '';
	foreach ( $rubs as [ $key, $title, $desc, $icon, $dark, $types ] ) {
		$n     = $count( $types );
		$path  = '/' . rubriques()[ $key ] . '/';
		/* translators: %d : nombre de fiches. */
		$small = $desc . ' · ' . \sprintf( \_n( '%d fiche', '%d fiches', $n, 'g2rd-rescue18-child' ), $n );
		$html .= '<div class="r18-rub r18-i--' . \esc_attr( $icon ) . ( $dark ? ' is-dark' : '' ) . '"><h3 class="wp-block-heading"><a href="' . \esc_url( rubrique_url( $key ) ) . '">' . \esc_html( $title ) . '</a></h3><p>' . \esc_html( $small ) . '</p><code>' . \esc_html( $path ) . '</code></div>';
	}
	return '<nav class="r18-rubs" aria-label="' . \esc_attr__( 'Rubriques de la bibliothèque', 'g2rd-rescue18-child' ) . '">' . $html . '</nav>';
}

/**
 * Pagination par paramètre « pg ».
 *
 * @param int    $max  Nombre de pages.
 * @param int    $cur  Page courante.
 * @param string $base URL de base (avec filtres).
 * @return string
 */
function pagination( int $max, int $cur, string $base ): string {
	if ( $max < 2 ) {
		return '';
	}
	$links = \paginate_links(
		array(
			'base'      => \add_query_arg( 'pg', '%#%', $base ),
			'format'    => '',
			'current'   => $cur,
			'total'     => $max,
			'type'      => 'plain',
			'prev_text' => \__( 'Précédent', 'g2rd-rescue18-child' ),
			'next_text' => \__( 'Suivant', 'g2rd-rescue18-child' ),
		)
	);
	return $links ? '<nav class="r18-pagination" aria-label="' . \esc_attr__( 'Pagination', 'g2rd-rescue18-child' ) . '">' . $links . '</nav>' : '';
}

/**
 * Ligne de liste (colonne de nouveautés, encadré lié).
 *
 * @param \WP_Post $p     Contenu.
 * @param string   $icon  Icône.
 * @param string   $small Mention.
 * @param string   $class Classe.
 * @return string
 */
function list_row( \WP_Post $p, string $icon, string $small, string $class_name = 'r18-li' ): string {
	return '<a class="' . \esc_attr( $class_name ) . '" href="' . \esc_url( (string) \get_permalink( $p ) ) . '"><span class="' . \esc_attr( $class_name ) . '__ic">' . icone( $icon ) . '</span><span><strong>' . \esc_html( \get_the_title( $p ) ) . '</strong><small>' . $small . '</small></span></a>';
}

/**
 * Bloc rescue18/nouveautes : trois colonnes alimentées par la veille.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_nouveautes( array $attrs ): string {
	$n    = \max( 1, (int) ( $attrs['nombre'] ?? 3 ) );
	$cols = array(
		array( \__( 'GDO et GTO', 'g2rd-rescue18-child' ), 'book', 'gdo,gto', 'gdo-gto' ),
		array( \__( 'RETEX et PEX', 'g2rd-rescue18-child' ), 'clip', 'retex,pex', 'retex' ),
		array( \__( 'Référentiels', 'g2rd-rescue18-child' ), 'grad', 'referentiel,gnr', 'documentation' ),
	);
	$html = '';
	foreach ( $cols as [ $title, $icon, $types, $rub ] ) {
		$q    = new \WP_Query(
			\array_merge(
				doc_query_for_type( $types ),
				array(
					'posts_per_page' => $n,
					'orderby'        => 'modified',
					'no_found_rows'  => true,
				)
			)
		);
		$rows = '';
		foreach ( $q->posts as $p ) {
			$d     = doc_data( $p );
			$small = \esc_html( $d['meta'] );
			if ( \in_array( $d['statut'], array( 'en-validation', 'nouveau', 'mis-a-jour' ), true ) ) {
				$small .= ' ' . pill( $d['statut_lbl'], $d['statut'] );
			}
			$rows .= list_row( $p, doc_icon_name( $d['type'] ), $small );
		}
		if ( '' === $rows ) {
			$rows = '<p class="r18-col__empty">' . \esc_html__( 'Les prochaines publications détectées par la veille apparaîtront ici après validation.', 'g2rd-rescue18-child' ) . '</p>';
		}
		$html .= '<div class="r18-col"><div class="r18-col__h"><strong><span class="r18-li__ic">' . icone( $icon ) . '</span>' . \esc_html( $title ) . '</strong>' . arrow_link( rubrique_url( $rub ), \__( 'Tout voir', 'g2rd-rescue18-child' ), 'r18-link r18-link--small' ) . '</div>' . $rows . '</div>';
	}
	return wrap( 'r18-cols', $html );
}

/**
 * Ligne de texte juridique.
 *
 * @param \WP_Post $p Texte.
 * @return string
 */
function law_row( \WP_Post $p ): string {
	$nature = natures_textes()[ meta( $p->ID, 'r18_nature' ) ] ?? \__( 'Texte', 'g2rd-rescue18-child' );
	$etat   = meta( $p->ID, 'r18_etat' );
	$etat   = '' !== $etat ? $etat : 'a-verifier';
	return '<a class="r18-law" href="' . \esc_url( (string) \get_permalink( $p ) ) . '"><span class="r18-law__type">' . \esc_html( $nature ) . '</span><span><strong>' . \esc_html( \get_the_title( $p ) ) . '</strong><small>' . \esc_html( \wp_strip_all_tags( \get_the_excerpt( $p ) ) ) . '</small></span>' . pill( etats_juridiques()[ $etat ] ?? '', $etat ) . '</a>';
}

/**
 * Bloc rescue18/veille-juridique.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_veille( array $attrs ): string {
	$n = \max( 1, (int) ( $attrs['nombre'] ?? 3 ) );
	if ( 'page' !== ( $attrs['variante'] ?? '' ) ) {
		$q    = new \WP_Query(
			array(
				'post_type'      => 'r18_texte',
				'posts_per_page' => $n,
				'no_found_rows'  => true,
			)
		);
		$rows = '';
		foreach ( $q->posts as $p ) {
			$rows .= law_row( $p );
		}
		if ( '' === $rows ) {
			$rows = '<p class="r18-col__empty">' . \esc_html__( 'Les textes suivis apparaîtront ici après la première synchronisation Légifrance.', 'g2rd-rescue18-child' ) . '</p>';
		}
		return wrap( 'r18-laws', $rows );
	}

	$nature = \sanitize_key( get_text( 'nature' ) );
	$annee  = (int) get_text( 'annee' );
	$search = get_text( 'q' );
	$page   = \max( 1, (int) get_text( 'pg' ) );
	$base   = \strtok( (string) \get_permalink(), '?' );

	// Derniers changements constatés.
	$chg   = new \WP_Query(
		array(
			'post_type'      => 'r18_texte',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
			'meta_key'       => 'r18_date_modif', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- tri sur la date de modification constatée.
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);
	$html  = '';
	$cards = '';
	foreach ( $chg->posts as $p ) {
		$etat   = meta( $p->ID, 'r18_etat' );
		$cards .= '<a class="r18-change" href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . pill( etats_juridiques()[ $etat ] ?? '', $etat ) . '<strong>' . \esc_html( \get_the_title( $p ) ) . '</strong><small>' . \esc_html( \sprintf( /* translators: %s : date. */ \__( 'Changement constaté le %s', 'g2rd-rescue18-child' ), date_fr( meta( $p->ID, 'r18_date_modif' ) ) ) ) . '</small></a>';
	}
	if ( '' !== $cards ) {
		$html .= '<section class="r18-changes" aria-label="' . \esc_attr__( 'Derniers changements détectés', 'g2rd-rescue18-child' ) . '"><h2 class="r18-box-title">' . \esc_html__( 'Derniers changements détectés', 'g2rd-rescue18-child' ) . '</h2><div class="r18-changes__grid">' . $cards . '</div></section>';
	}

	// Filtres.
	$filters = '<form class="r18-lawfilters" method="get" action="' . \esc_url( $base ) . '"><nav class="r18-types" aria-label="' . \esc_attr__( 'Type de texte', 'g2rd-rescue18-child' ) . '">';
	foreach ( \array_merge( array( '' => \__( 'Tous', 'g2rd-rescue18-child' ) ), natures_textes() ) as $k => $l ) {
		$on       = $k === $nature;
		$url      = '' === $k ? \remove_query_arg( 'nature', $base ) : \add_query_arg( 'nature', $k, $base );
		$filters .= '<a class="r18-type' . ( $on ? ' is-on' : '' ) . '" href="' . \esc_url( $url ) . '"' . ( $on ? ' aria-current="true"' : '' ) . '>' . \esc_html( $l ) . '</a>';
	}
	$filters .= '</nav>';
	$filters .= '' !== $nature ? '<input type="hidden" name="nature" value="' . \esc_attr( $nature ) . '">' : '';
	$fid      = \wp_unique_id( 'r18-lq-' );
	$filters .= '<label class="screen-reader-text" for="' . \esc_attr( $fid ) . '">' . \esc_html__( 'Rechercher un texte', 'g2rd-rescue18-child' ) . '</label><input class="r18-input" id="' . \esc_attr( $fid ) . '" type="search" name="q" value="' . \esc_attr( $search ) . '" placeholder="' . \esc_attr__( 'Rechercher un texte', 'g2rd-rescue18-child' ) . '">';
	$filters .= '<label class="screen-reader-text" for="' . \esc_attr( $fid ) . '-a">' . \esc_html__( 'Année', 'g2rd-rescue18-child' ) . '</label><select class="r18-select" id="' . \esc_attr( $fid ) . '-a" name="annee" data-r18-autosubmit><option value="">' . \esc_html__( 'Toutes les années', 'g2rd-rescue18-child' ) . '</option>';
	for ( $y = (int) \wp_date( 'Y' ); $y >= 2000; $y-- ) {
		$filters .= '<option value="' . $y . '"' . \selected( $annee, $y, false ) . '>' . $y . '</option>';
	}
	$filters .= '</select><button type="submit" class="r18-btn r18-btn--dark">' . \esc_html__( 'Filtrer', 'g2rd-rescue18-child' ) . '</button></form>';
	$html    .= $filters;

	$args = array(
		'post_type'      => 'r18_texte',
		'posts_per_page' => \max( 10, $n ),
		'paged'          => $page,
		'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- filtres de la veille.
	);
	if ( '' !== $search ) {
		$args['s'] = $search;
	}
	if ( '' !== $nature ) {
		$args['meta_query'][] = array(
			'key'   => 'r18_nature',
			'value' => $nature,
		);
	}
	if ( $annee > 0 ) {
		$args['meta_query'][] = array(
			'key'     => 'r18_date_signature',
			'value'   => array( $annee . '-01-01', $annee . '-12-31' ),
			'compare' => 'BETWEEN',
			'type'    => 'DATE',
		);
	}
	$q    = new \WP_Query( $args );
	$rows = '';
	foreach ( $q->posts as $p ) {
		$rows .= law_row( $p );
	}
	$html .= '' !== $rows ? '<div class="r18-laws r18-laws--page">' . $rows . '</div>' : '<p class="r18-lib__empty">' . \esc_html__( 'Aucun texte ne correspond à ces critères.', 'g2rd-rescue18-child' ) . '</p>';
	$html .= pagination( (int) $q->max_num_pages, $page, \add_query_arg( \array_filter( array( 'nature' => $nature, 'annee' => $annee ?: '', 'q' => $search ) ), $base ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found -- valeur vide si pas d'année.

	return wrap( 'r18-veille', $html );
}

/**
 * Bloc rescue18/veille-statut : dernière et prochaine synchronisation.
 *
 * @return string
 */
function render_veille_statut(): string {
	$last = last_sync();
	foreach ( (array) \get_option( 'r18_veille_etat', array() ) as $e ) {
		$last = \max( $last, (int) ( $e['verif'] ?? 0 ) );
	}
	$next = \G2RD_Rescue18\prochain_passage( 'documents' );
	$fmt  = \get_option( 'date_format' ) . ' · H:i';
	$txt  = $last > 0
		/* translators: %s : date et heure. */
		? \sprintf( \__( 'Dernière synchronisation : %s', 'g2rd-rescue18-child' ), \wp_date( $fmt, $last ) )
		: \__( 'Veille des sources officielles', 'g2rd-rescue18-child' );
	if ( $next ) {
		/* translators: %s : date et heure. */
		$txt .= ' · ' . \sprintf( \__( 'Prochaine : %s', 'g2rd-rescue18-child' ), \wp_date( $fmt, (int) $next ) );
	}
	$txt .= ' · ' . \__( '1 synchronisation par 24 h', 'g2rd-rescue18-child' );
	return wrap( 'r18-live', '<i aria-hidden="true"></i>' . \esc_html( $txt ) );
}

/**
 * Bloc rescue18/fiche-etiquettes.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_fiche_etiquettes( array $attrs, ?\WP_Block $block = null ): string {
	$id   = current_id( $block );
	$type = \get_post_type( $id );
	$html = '';
	if ( 'r18_texte' === $type ) {
		$etat  = meta( $id, 'r18_etat' );
		$etat  = '' !== $etat ? $etat : 'a-verifier';
		$html .= '<span class="r18-tag">' . \esc_html( natures_textes()[ meta( $id, 'r18_nature' ) ] ?? \__( 'Texte', 'g2rd-rescue18-child' ) ) . '</span>';
		foreach ( (array) \get_the_terms( $id, 'r18_specialite' ) as $t ) {
			$html .= $t instanceof \WP_Term ? '<span class="r18-tag r18-tag--ghost">' . \esc_html( $t->name ) . '</span>' : '';
		}
		$html .= '<span class="r18-state r18-state--' . \esc_attr( $etat ) . '"><i aria-hidden="true"></i>' . \esc_html( etats_juridiques()[ $etat ] ) . '</span>';
	} elseif ( 'r18_document' === $type ) {
		$d     = doc_data( \get_post( $id ) );
		$org   = first_term( $id, 'r18_organisme' );
		$html .= '<span class="r18-tag">' . \esc_html( $d['cat'] ) . '</span>';
		$html .= $org ? '<span class="r18-tag r18-tag--ghost">' . \esc_html( $org->name ) . '</span>' : '';
		$html .= pill( $d['statut_lbl'], $d['statut'] );
	} elseif ( 'r18_episode' === $type ) {
		$num   = meta( $id, 'r18_numero_episode' );
		$html .= '<span class="r18-tag">' . \esc_html__( 'SSUAP Le Podcast', 'g2rd-rescue18-child' ) . '</span>';
		$html .= '' !== $num ? '<span class="r18-tag r18-tag--ghost">#' . \esc_html( $num ) . '</span>' : '';
	}
	return wrap( 'r18-tags', $html );
}

/**
 * Bloc rescue18/fiche-actions : boutons vers la source et le PDF.
 *
 * Le PDF n'est proposé que si sa reproduction est autorisée ; sinon on
 * renvoie à l'original (consignes, § 8).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_fiche_actions( array $attrs, ?\WP_Block $block = null ): string {
	$id   = current_id( $block );
	$type = \get_post_type( $id );
	$src  = meta( $id, 'r18_source_url' );
	$html = '';
	if ( '' !== $src ) {
		$label = 'r18_texte' === $type ? \__( 'Lire sur Légifrance', 'g2rd-rescue18-child' ) : \__( 'Publication officielle', 'g2rd-rescue18-child' );
		$html .= '<a class="r18-btn r18-btn--red" href="' . \esc_url( $src ) . '" target="_blank" rel="noopener">' . \esc_html( $label ) . ' ' . icone( 'ext' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	}
	$pdf = meta( $id, 'r18_pdf_url' );
	if ( '' !== $pdf && '1' === meta( $id, 'r18_pdf_autorise' ) ) {
		$html .= '<a class="r18-btn r18-btn--white" href="' . \esc_url( $pdf ) . '" download>' . icone( 'download' ) . \esc_html__( 'Télécharger le PDF', 'g2rd-rescue18-child' ) . '</a>';
	}
	if ( 'r18_texte' === $type ) {
		$html .= '<a class="r18-btn r18-btn--white" href="#newsletter">' . icone( 'bell' ) . \esc_html__( 'Suivre la veille juridique', 'g2rd-rescue18-child' ) . '</a>';
	}
	return wrap( 'r18-actions', $html );
}

/**
 * Bloc rescue18/fiche-references : métadonnées officielles.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_fiche_references( array $attrs, ?\WP_Block $block = null ): string {
	$id   = current_id( $block );
	$type = \get_post_type( $id );
	$na   = '<span class="r18-na">' . \esc_html__( 'Non renseigné', 'g2rd-rescue18-child' ) . '</span>';
	$f    = array();
	$list = static function ( string $tax ) use ( $id ): string {
		$t = \get_the_terms( $id, $tax );
		return \is_array( $t ) ? \esc_html( \implode( ', ', \wp_list_pluck( $t, 'name' ) ) ) : '';
	};
	if ( 'r18_texte' === $type ) {
		$jorf = \trim( meta( $id, 'r18_jorf' ) . ' / ' . meta( $id, 'r18_legi' ), ' /' );
		$f    = array(
			array( \__( 'Type de texte', 'g2rd-rescue18-child' ), \esc_html( natures_textes()[ meta( $id, 'r18_nature' ) ] ?? '' ), false ),
			array( \__( 'Numéro', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_numero' ) ), false ),
			array( \__( 'NOR', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_nor' ) ), true ),
			array( \__( 'Identifiant JORF / LEGI', 'g2rd-rescue18-child' ), \esc_html( $jorf ), true ),
			array( \__( 'Organisme', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_signataire' ) ), false ),
			array( \__( 'Date de signature', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_date_signature' ) ) ), false ),
			array( \__( 'Date de publication au JO', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_date_jo' ) ) ), false ),
			array( \__( 'Entrée en vigueur', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_date_vigueur' ) ) ), false ),
			array( \__( 'Dernière modification constatée', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_date_modif' ) ) ), false ),
			array( \__( 'Dernière synchronisation', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_derniere_synchro' ) ), false ),
		);
	} else {
		$type_term = first_term( $id, 'r18_type_doc' );
		$hash      = meta( $id, 'r18_empreinte' );
		$f         = array(
			array( \__( 'Type', 'g2rd-rescue18-child' ), $type_term ? \esc_html( $type_term->name ) : '', false ),
			array( \__( 'Organisme', 'g2rd-rescue18-child' ), $list( 'r18_organisme' ), false ),
			array( \__( 'Version / édition', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_version' ) ), false ),
			array( \__( 'Date de l\'édition', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_date_edition' ) ) ), false ),
			array( \__( 'Spécialités', 'g2rd-rescue18-child' ), $list( 'r18_specialite' ), false ),
			array( \__( 'Public', 'g2rd-rescue18-child' ), $list( 'r18_public' ), false ),
			array( \__( 'Identifiant source', 'g2rd-rescue18-child' ), \esc_html( meta( $id, 'r18_source_id' ) ), true ),
			array( \__( 'Dernière vérification', 'g2rd-rescue18-child' ), \esc_html( date_fr( meta( $id, 'r18_derniere_verif' ) ) ), false ),
			array( \__( 'Empreinte du fichier', 'g2rd-rescue18-child' ), '' !== $hash ? \esc_html( \substr( $hash, 0, 16 ) . '…' ) : '', true ),
			array( \__( 'Fichier PDF', 'g2rd-rescue18-child' ), '1' === meta( $id, 'r18_pdf_autorise' ) ? \esc_html__( 'Reproduction autorisée', 'g2rd-rescue18-child' ) : \esc_html__( 'Consultable sur le site d\'origine', 'g2rd-rescue18-child' ), false ),
		);
	}
	$html = '';
	foreach ( $f as [ $label, $value, $mono ] ) {
		$html .= '<div class="r18-fact"><small>' . \esc_html( $label ) . '</small><strong' . ( $mono ? ' class="r18-mono"' : '' ) . '>' . ( '' !== $value ? $value : $na ) . '</strong></div>';
	}
	return wrap( 'r18-facts', $html );
}

/**
 * Bloc rescue18/etat-juridique.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_etat_juridique( array $attrs, ?\WP_Block $block = null ): string {
	$id    = current_id( $block );
	$etat  = meta( $id, 'r18_etat' );
	$etat  = \array_key_exists( $etat, etats_juridiques() ) ? $etat : 'a-verifier';
	$desc  = array(
		'en-vigueur' => array( \__( 'Confirmé par la source officielle', 'g2rd-rescue18-child' ), \__( 'Le texte est en vigueur selon la dernière synchronisation avec Légifrance.', 'g2rd-rescue18-child' ), 'check' ),
		'modifie'    => array( \__( 'Nouvelle version consolidée', 'g2rd-rescue18-child' ), \__( 'Une modification a été constatée par la source officielle. Consulte la version consolidée sur Légifrance.', 'g2rd-rescue18-child' ), 'refresh' ),
		'abroge'     => array( \__( 'Abrogation officielle constatée', 'g2rd-rescue18-child' ), \__( 'L\'abrogation est confirmée par la source officielle. Le texte reste consultable pour l\'historique.', 'g2rd-rescue18-child' ), 'alert' ),
		'a-verifier' => array( \__( 'En attente de confirmation', 'g2rd-rescue18-child' ), \__( 'L\'état n\'a pas encore été confirmé par Légifrance lors de la dernière synchronisation. Il reste affiché ainsi jusqu\'à confirmation officielle.', 'g2rd-rescue18-child' ), 'help' ),
	);
	$html  = '<div class="r18-statebox r18-statebox--' . \esc_attr( $etat ) . '"><span class="r18-statebox__ic">' . icone( $desc[ $etat ][2] ) . '</span><span><strong>' . \esc_html( etats_juridiques()[ $etat ] ) . '</strong><p>' . \esc_html( $desc[ $etat ][1] ) . '</p></span></div>';
	$html .= '<div class="r18-states">';
	foreach ( array( 'en-vigueur', 'modifie', 'abroge', 'a-verifier' ) as $k ) {
		$html .= '<div class="r18-sx' . ( $k === $etat ? ' is-on' : '' ) . '"' . ( $k === $etat ? ' aria-current="true"' : '' ) . '><b>' . \esc_html( etats_juridiques()[ $k ] ) . '</b>' . \esc_html( $desc[ $k ][0] ) . '</div>';
	}
	$html .= '</div>';
	return wrap( 'r18-etat', $html );
}

/**
 * Bloc rescue18/historique : versions suivies, sans confusion avec la
 * version en vigueur (la première ligne est la version suivie).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_historique( array $attrs, ?\WP_Block $block = null ): string {
	$id   = current_id( $block );
	$rows = versions( $id );
	$html = '';
	foreach ( $rows as $i => $r ) {
		$small = \trim( date_fr( $r['date'] ) . ( '' !== $r['note'] ? ' · ' . $r['note'] : '' ), ' ·' );
		$html .= '<div class="r18-tl' . ( 0 === $i ? ' is-now' : '' ) . '"><strong>' . \esc_html( ( 0 === $i ? \__( 'Version suivie · ', 'g2rd-rescue18-child' ) : '' ) . $r['label'] ) . '</strong><small>' . \esc_html( $small ) . '</small></div>';
	}
	/* translators: %s : date. */
	$html .= '<div class="r18-tl"><strong>' . \esc_html__( 'Ajout à Rescue 18', 'g2rd-rescue18-child' ) . '</strong><small>' . \esc_html( \get_the_date( '', $id ) ) . '</small></div>';
	return wrap( 'r18-timeline', $html );
}

/**
 * Bloc rescue18/source-officielle : encadré rouge de l'aside.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_source_officielle( array $attrs, ?\WP_Block $block = null ): string {
	$id    = current_id( $block );
	$src   = meta( $id, 'r18_source_url' );
	$pdf   = meta( $id, 'r18_pdf_url' );
	$texte = 'r18_texte' === \get_post_type( $id );
	$refs  = \json_decode( meta( $id, 'r18_refs' ), true );
	$refs  = \is_array( $refs ) ? $refs : array();
	$html  = '<h2>' . \esc_html( \count( $refs ) > 1 ? \__( 'Sources officielles', 'g2rd-rescue18-child' ) : \__( 'Source officielle', 'g2rd-rescue18-child' ) ) . '</h2>';
	$html .= '<p>' . \esc_html( $texte ? \__( 'Consulte le texte consolidé, ses versions et ses liens sur Légifrance. Seule la version publiée fait foi.', 'g2rd-rescue18-child' ) : \__( 'La publication de l\'organisme d\'origine fait foi. Rescue 18 en propose une présentation, jamais une version modifiée.', 'g2rd-rescue18-child' ) ) . '</p>';
	if ( '' !== $src ) {
		$html .= '<a class="r18-btn r18-btn--white r18-btn--block" href="' . \esc_url( $src ) . '" target="_blank" rel="noopener">' . \esc_html( $texte ? \__( 'Ouvrir Légifrance', 'g2rd-rescue18-child' ) : \__( 'Ouvrir la publication', 'g2rd-rescue18-child' ) ) . ' ' . icone( 'ext' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	} else {
		$html .= '<p><em>' . \esc_html__( 'Lien officiel en cours de vérification.', 'g2rd-rescue18-child' ) . '</em></p>';
	}
	if ( '' !== $pdf ) {
		$html .= '<a class="r18-btn r18-btn--ghost-white r18-btn--block" href="' . \esc_url( $pdf ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Télécharger le PDF officiel', 'g2rd-rescue18-child' ) . ' ' . icone( 'download' ) . '<span class="screen-reader-text"> ' . \esc_html__( '(site de l\'organisme, nouvel onglet)', 'g2rd-rescue18-child' ) . '</span></a>';
	}
	if ( ! empty( $refs ) ) {
		$noms    = array(
			'dgscgc' => array( 'DGSCGC', 'Sécurité civile', 'dgscgc' ),
			'pnrs'   => array( 'PNRS', 'Portail de l\'ENSOSP', 'ensosp-pnrs' ),
			'ensosp' => array( 'ENSOSP', 'École des officiers', 'ensosp-pnrs' ),
		);
		$editeur = meta( $id, 'r18_editeur' );
		$rows    = '';
		foreach ( $refs as $s => $r ) {
			if ( ! \is_array( $r ) || ! isset( $noms[ $s ] ) ) {
				continue;
			}
			$role  = $noms[ $s ][2] === $editeur && ( 'dgscgc' === $s || ! isset( $refs['dgscgc'] ) ) ? \__( 'Éditeur', 'g2rd-rescue18-child' ) : \__( 'Référencé', 'g2rd-rescue18-child' );
			$vu    = ! empty( $r['vu'] ) ? \wp_date( 'j M Y', (int) \strtotime( (string) $r['vu'] ) ) : '';
			$rows .= '<li><span class="r18-srcs__badge">' . \esc_html( $noms[ $s ][0] ) . '</span><span class="r18-srcs__txt"><strong>' . \esc_html( $noms[ $s ][1] ) . '</strong><small>' . \esc_html( $role . ( '' !== $vu ? ' · ' . \sprintf( /* translators: %s : date. */ \__( 'vérifié le %s', 'g2rd-rescue18-child' ), $vu ) : '' ) ) . '</small></span>'
				. ( ! empty( $r['url'] ) ? '<a href="' . \esc_url( (string) $r['url'] ) . '" target="_blank" rel="noopener" aria-label="' . \esc_attr( \sprintf( /* translators: %s : organisme. */ \__( 'Voir la notice %s (nouvel onglet)', 'g2rd-rescue18-child' ), $noms[ $s ][0] ) ) . '">' . icone( 'ext' ) . '</a>' : '' ) . '</li>';
		}
		$html .= '' !== $rows ? '<ul class="r18-srcs" aria-label="' . \esc_attr__( 'Références suivies', 'g2rd-rescue18-child' ) . '">' . $rows . '</ul>' : '';
	}
	return wrap( 'r18-box r18-box--source', $html, 'aside' );
}
