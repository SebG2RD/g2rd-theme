<?php
/**
 * Rendus éditoriaux et médias : contenus liés, spécialités, ticker,
 * podcasts, quiz, produits, fil d'Ariane, outils d'article, newsletter.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Spécialités par défaut (installateur et repli d'affichage).
 *
 * @return array<string,array{0:string,1:string,2:string}> slug => [ nom, accroche, icône ]
 */
function specialites_defaut(): array {
	return array(
		'incendie'           => array( 'Incendie', 'Fiches feu, forcement, TASSS', 'flame' ),
		'feux-de-forets'     => array( 'Feux de forêts', 'Fiches FDF', 'tree' ),
		'ssuap'              => array( 'SSUAP', 'Secours à personne', 'med' ),
		'secours-routier'    => array( 'Secours routier', 'IUV, désincarcération', 'car' ),
		'smpm-usar'          => array( 'SMPM / USAR', 'Milieux effondrés, périlleux', 'layers' ),
		'nrbc'               => array( 'NRBC / Menaces', 'Risques technologiques', 'alert' ),
		'goc-cos'            => array( 'GOC / COS', 'Aide à la décision', 'compass' ),
		'sic-cta-codis'      => array( 'SIC / CTA / CODIS', 'Transmissions, alerte', 'radio' ),
		'aasc'               => array( 'AASC', 'Associations agréées', 'users' ),
		'opd-div'            => array( 'OPD / DIV', 'Opérations diverses', 'box' ),
		'qsvt-fh-pmo'        => array( 'QSVT / FH / PMO', 'Facteurs humains, santé', 'heart' ),
		'vehicules-secours'  => array( 'Véhicules de secours', 'Engins, matériels', 'truck' ),
	);
}

/**
 * Icône d'une spécialité (méta de terme, sinon table par défaut).
 *
 * @param \WP_Term $term Terme.
 * @return string
 */
function specialite_icon( \WP_Term $term ): string {
	$icon = (string) \get_term_meta( $term->term_id, 'r18_icone', true );
	return '' !== $icon ? $icon : ( specialites_defaut()[ $term->slug ][2] ?? 'flame' );
}

/**
 * Carte d'article (couverture, rubrique, titre, extrait, méta).
 *
 * @param \WP_Post $p     Article.
 * @param bool     $short Sans extrait.
 * @return string
 */
function post_card( \WP_Post $p, bool $short = false ): string {
	$cat   = first_term( $p->ID, 'category' );
	$img   = \get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy' ) );
	$cover = '<div class="r18-cover">' . ( '' !== $img ? $img : '<span class="r18-cover__mark" aria-hidden="true">18</span>' ) . '</div>';
	$meta  = \get_the_author_meta( 'display_name', (int) $p->post_author ) . ' · ' . \get_the_date( '', $p );
	return '<article class="r18-card">' . $cover . '<div class="r18-card__body">' . ( $cat ? '<span class="r18-chip">' . \esc_html( $cat->name ) . '</span>' : '' ) . '<h3><a href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . \esc_html( \get_the_title( $p ) ) . '</a></h3>' . ( $short ? '' : '<p>' . \esc_html( \wp_trim_words( \get_the_excerpt( $p ), 22 ) ) . '</p>' ) . '<span class="r18-meta">' . \esc_html( $meta ) . '</span></div></article>';
}

/**
 * Bloc rescue18/lies : contenus partageant une spécialité.
 *
 * Sur une archive de spécialité, le terme courant sert de filtre.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_lies( array $attrs, ?\WP_Block $block = null ): string {
	$type  = \in_array( $attrs['type'] ?? '', array( 'r18_document', 'r18_texte', 'post', 'r18_episode', 'r18_quiz' ), true ) ? $attrs['type'] : 'r18_document';
	$n     = \max( 1, (int) ( $attrs['nombre'] ?? 3 ) );
	$args  = array(
		'post_type'           => $type,
		'posts_per_page'      => $n,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);
	$terms = array();
	if ( \is_tax( 'r18_specialite' ) ) {
		$terms = array( (int) \get_queried_object_id() );
	} else {
		$id = current_id( $block );
		if ( $id > 0 ) {
			$args['post__not_in'] = array( $id );
			$t                    = \get_the_terms( $id, 'r18_specialite' );
			$terms                = \is_array( $t ) ? \wp_list_pluck( $t, 'term_id' ) : array();
		}
	}
	if ( ! empty( $terms ) ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- maillage par spécialité.
			array(
				'taxonomy' => 'r18_specialite',
				'terms'    => $terms,
			),
		);
	}
	$q = new \WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '';
	}

	if ( 'cartes' === ( $attrs['variante'] ?? '' ) ) {
		$html = '';
		foreach ( $q->posts as $p ) {
			$html .= 'post' === $type ? post_card( $p, true ) : ( \in_array( $type, array( 'r18_document', 'r18_texte' ), true ) ? doc_card( $p ) : list_row( $p, 'r18_episode' === $type ? 'headphones' : 'check', '', 'r18-rel' ) );
		}
		if ( \in_array( $type, array( 'r18_document', 'r18_texte' ), true ) ) {
			\wp_enqueue_script( 'rescue18-bibliotheque' );
		}
		return wrap( 'post' === $type ? 'r18-cards r18-cards--4' : 'r18-doc-grid', $html );
	}

	$icons = array(
		'r18_document' => 'book',
		'r18_texte'    => 'file',
		'post'         => 'file',
		'r18_episode'  => 'headphones',
		'r18_quiz'     => 'check',
	);
	$title = (string) ( $attrs['titre'] ?? '' );
	$html  = '' !== $title ? '<h2>' . \esc_html( $title ) . '</h2>' : '';
	foreach ( $q->posts as $p ) {
		$small = 'r18_document' === $type || 'r18_texte' === $type ? doc_data( $p )['meta'] : \get_the_date( '', $p );
		$html .= list_row( $p, $icons[ $type ], \esc_html( $small ), 'r18-rel' );
	}
	return wrap( 'r18-box r18-box--rel', $html, 'div' );
}

/**
 * Bloc rescue18/specialites.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_specialites( array $attrs ): string {
	$terms = \get_terms(
		array(
			'taxonomy'   => 'r18_specialite',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);
	$items = array();
	if ( \is_array( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $t ) {
			$link    = \get_term_link( $t );
			$items[] = array( $t->name, $t->description ? \wp_trim_words( $t->description, 5, '' ) : ( specialites_defaut()[ $t->slug ][1] ?? '' ), specialite_icon( $t ), \is_string( $link ) ? $link : '', \is_tax( 'r18_specialite', $t->term_id ) );
		}
	} else {
		foreach ( specialites_defaut() as [ $name, $desc, $icon ] ) {
			$items[] = array( $name, $desc, $icon, \add_query_arg( 's', $name, \home_url( '/' ) ), false );
		}
	}

	$puces = 'puces' === ( $attrs['variante'] ?? '' );
	$html  = '';
	foreach ( $items as [ $name, $desc, $icon, $url, $current ] ) {
		$cur   = $current ? ' aria-current="page"' : '';
		$html .= $puces
			? '<a class="r18-puce' . ( $current ? ' is-on' : '' ) . '" href="' . \esc_url( $url ) . '"' . $cur . '>' . \esc_html( $name ) . '</a>'
			: '<a class="r18-spec" href="' . \esc_url( $url ) . '"' . $cur . '><span class="r18-spec__ic">' . icone( $icon ) . '</span><strong>' . \esc_html( $name ) . '</strong><small>' . \esc_html( $desc ) . '</small>' . icone( 'arrow', 'r18-svg r18-spec__arr' ) . '</a>';
	}
	return wrap( $puces ? 'r18-puces' : 'r18-spec-grid', $html, $puces ? 'nav' : 'div', $puces ? array( 'aria-label' => \__( 'Spécialités', 'g2rd-rescue18-child' ) ) : array() );
}

/**
 * Bloc rescue18/ticker : dernières publications, tous types confondus.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_ticker( array $attrs ): string {
	$labels = array(
		'post'         => \__( 'Article', 'g2rd-rescue18-child' ),
		'r18_document' => \__( 'Document', 'g2rd-rescue18-child' ),
		'r18_texte'    => \__( 'Juridique', 'g2rd-rescue18-child' ),
		'r18_episode'  => \__( 'Podcast', 'g2rd-rescue18-child' ),
		'product'      => \__( 'Boutique', 'g2rd-rescue18-child' ),
	);
	$types  = \array_values( \array_filter( \array_keys( $labels ), 'post_type_exists' ) );
	$q      = new \WP_Query(
		array(
			'post_type'           => $types,
			'posts_per_page'      => \max( 3, (int) ( $attrs['nombre'] ?? 6 ) ),
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return '';
	}
	$items = '';
	$list  = '';
	foreach ( $q->posts as $p ) {
		$label = $labels[ $p->post_type ] ?? '';
		if ( 'r18_document' === $p->post_type ) {
			$label = doc_data( $p )['cat'];
		}
		$items .= '<span><em>' . \esc_html( $label ) . '</em>' . \esc_html( \get_the_title( $p ) ) . '</span><b aria-hidden="true">✦</b>';
		$list  .= '<li><a href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . \esc_html( $label . ' : ' . \get_the_title( $p ) ) . '</a></li>';
	}
	// La piste défilante est décorative ; la liste accessible porte les liens.
	$html = '<ul class="screen-reader-text">' . $list . '</ul><div class="r18-ticker__track" aria-hidden="true">' . $items . $items . '</div>';
	return wrap( 'r18-ticker', $html, 'div', array( 'aria-label' => \__( 'Dernières publications', 'g2rd-rescue18-child' ) ) );
}

/**
 * Bloc rescue18/episodes : liste d'épisodes avec lecteur intégré.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_episodes( array $attrs ): string {
	\wp_enqueue_script( 'rescue18-podcasts' );
	$page = 'page' === ( $attrs['variante'] ?? '' );
	$pg   = $page ? \max( 1, (int) get_text( 'pg' ) ) : 1;
	$spe  = $page ? \sanitize_title( get_text( 'theme' ) ) : '';
	$args = array(
		'post_type'      => 'r18_episode',
		'posts_per_page' => $page ? \max( 10, (int) ( $attrs['nombre'] ?? 20 ) ) : \max( 1, (int) ( $attrs['nombre'] ?? 4 ) ),
		'paged'          => $pg,
		'no_found_rows'  => ! $page,
	);
	if ( '' !== $spe ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- filtre par thème.
			array(
				'taxonomy' => 'r18_specialite',
				'field'    => 'slug',
				'terms'    => $spe,
			),
		);
	}
	$q    = new \WP_Query( $args );
	$html = '';

	if ( $page ) {
		$base  = \strtok( (string) \get_permalink(), '?' );
		$terms = \get_terms(
			array(
				'taxonomy'   => 'r18_specialite',
				'object_ids' => \get_posts(
					array(
						'post_type'      => 'r18_episode',
						'posts_per_page' => 300,
						'fields'         => 'ids',
						'no_found_rows'  => true,
					)
				),
			)
		);
		$html .= '<nav class="r18-types" aria-label="' . \esc_attr__( 'Thèmes des épisodes', 'g2rd-rescue18-child' ) . '"><a class="r18-type' . ( '' === $spe ? ' is-on' : '' ) . '" href="' . \esc_url( $base ) . '">' . \esc_html__( 'Tous', 'g2rd-rescue18-child' ) . '</a>';
		if ( \is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				$html .= '<a class="r18-type' . ( $t->slug === $spe ? ' is-on' : '' ) . '" href="' . \esc_url( \add_query_arg( 'theme', $t->slug, $base ) ) . '">' . \esc_html( $t->name ) . '</a>';
			}
		}
		$html .= '</nav>';
	}

	foreach ( $q->posts as $p ) {
		$num   = meta( $p->ID, 'r18_numero_episode' );
		$audio = meta( $p->ID, 'r18_audio_url' );
		$title = \get_the_title( $p );
		$num_l = '' !== $num ? ( \ctype_digit( $num ) ? '#' . $num : \__( 'Hors-série', 'g2rd-rescue18-child' ) . ' #' . $num ) : '';
		$btn   = '' !== $audio
			/* translators: %s : titre de l'épisode. */
			? '<button type="button" class="r18-play" data-r18-audio="' . \esc_url( $audio ) . '" data-r18-title="' . \esc_attr( $title ) . '" aria-label="' . \esc_attr( \sprintf( \__( 'Écouter : %s', 'g2rd-rescue18-child' ), $title ) ) . '">' . icone( 'play', 'r18-svg r18-play__on' ) . icone( 'pause', 'r18-svg r18-play__off' ) . '</button>'
			: '<a class="r18-play" href="' . \esc_url( (string) \get_permalink( $p ) ) . '" aria-label="' . \esc_attr( $title ) . '">' . icone( 'play' ) . '</a>';
		$html .= '<div class="r18-ep">' . $btn . '<span class="r18-ep__txt"><span class="r18-ep__num">' . \esc_html( $num_l ) . '</span><strong><a href="' . \esc_url( (string) \get_permalink( $p ) ) . '">' . \esc_html( $title ) . '</a></strong><small>' . \esc_html( \trim( \__( 'SSUAP Le Podcast', 'g2rd-rescue18-child' ) . ' · ' . meta( $p->ID, 'r18_duree' ), ' ·' ) ) . '</small></span></div>';
	}
	if ( ! $q->have_posts() ) {
		$html .= '<p class="r18-col__empty">' . \esc_html__( 'Les épisodes importés apparaîtront ici.', 'g2rd-rescue18-child' ) . '</p>';
	}
	if ( $page ) {
		$html .= pagination( (int) $q->max_num_pages, $pg, '' !== $spe ? \add_query_arg( 'theme', $spe, (string) \strtok( (string) \get_permalink(), '?' ) ) : (string) \strtok( (string) \get_permalink(), '?' ) );
	}
	// Lecteur unique, piloté par podcasts.js ; reste caché sans JS.
	$html .= '<div class="r18-player" data-r18-player hidden><span class="r18-player__now" aria-live="polite"></span><audio controls preload="none"></audio></div>';
	return wrap( 'r18-eps' . ( $page ? ' r18-eps--page' : '' ), $html );
}

/**
 * Questions d'un quiz.
 *
 * @param int $id ID du quiz.
 * @return array<int,array{q:string,c:array<int,string>,a:int,why:string}>
 */
function quiz_questions( int $id ): array {
	$raw  = meta( $id, 'r18_questions' );
	$data = '' !== $raw ? \json_decode( $raw, true ) : null;
	$out  = array();
	foreach ( \is_array( $data ) ? $data : array() as $row ) {
		if ( ! \is_array( $row ) || empty( $row['q'] ) || empty( $row['c'] ) || ! \is_array( $row['c'] ) ) {
			continue;
		}
		$out[] = array(
			'q'   => (string) $row['q'],
			'c'   => \array_values( \array_map( 'strval', $row['c'] ) ),
			'a'   => (int) ( $row['a'] ?? 0 ),
			'why' => (string) ( $row['why'] ?? '' ),
		);
	}
	return $out;
}

/**
 * Bloc rescue18/quiz : une question à la fois, correction immédiate,
 * score final. Aucun score n'est enregistré. Sans JavaScript, les questions
 * et leurs explications restent lisibles (contenu indexable).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_quiz( array $attrs, ?\WP_Block $block = null ): string {
	$id = (int) ( $attrs['quizId'] ?? 0 );
	$id = $id > 0 ? $id : current_id( $block );
	$qs = quiz_questions( $id );
	if ( empty( $qs ) ) {
		return wrap( 'r18-qcard', '<p>' . \esc_html__( 'Les questions de ce quiz sont en cours de relecture par la rédaction.', 'g2rd-rescue18-child' ) . '</p>' );
	}
	\wp_enqueue_script( 'rescue18-quiz' );

	$static = '<ol class="r18-quiz__static">';
	foreach ( $qs as $q ) {
		$static .= '<li><p><strong>' . \esc_html( $q['q'] ) . '</strong></p><ul>';
		foreach ( $q['c'] as $i => $c ) {
			$static .= '<li>' . \esc_html( $c ) . ( $i === $q['a'] ? ' <em>(' . \esc_html__( 'bonne réponse', 'g2rd-rescue18-child' ) . ')</em>' : '' ) . '</li>';
		}
		$static .= '</ul>' . ( '' !== $q['why'] ? '<p>' . \esc_html( $q['why'] ) . '</p>' : '' ) . '</li>';
	}
	$static .= '</ol>';

	$cfg  = array(
		'questions' => $qs,
		'more'      => \get_page_by_path( 'formation/quiz' ) ? \home_url( '/formation/quiz/' ) : rubrique_url( 'formation' ),
		'labels'    => array(
			'question' => \__( 'Question', 'g2rd-rescue18-child' ),
			'score'    => \__( 'Score', 'g2rd-rescue18-child' ),
			'good'     => \__( 'Bonne réponse.', 'g2rd-rescue18-child' ),
			'bad'      => \__( 'Pas tout à fait.', 'g2rd-rescue18-child' ),
			'next'     => \__( 'Question suivante', 'g2rd-rescue18-child' ),
			'result'   => \__( 'Voir mon résultat', 'g2rd-rescue18-child' ),
			'checked'  => \__( 'Correction validée par la rédaction', 'g2rd-rescue18-child' ),
			'perfect'  => \__( 'Sans faute, bravo !', 'g2rd-rescue18-child' ),
			'fine'     => \__( 'Bon résultat.', 'g2rd-rescue18-child' ),
			'retry'    => \__( 'À retravailler.', 'g2rd-rescue18-child' ),
			'note'     => \__( 'Chaque question renvoie vers le document officiel correspondant pour approfondir. Aucun compte requis, ton score n\'est pas enregistré.', 'g2rd-rescue18-child' ),
			'restart'  => \__( 'Recommencer', 'g2rd-rescue18-child' ),
			'share'    => \__( 'Partager mon score', 'g2rd-rescue18-child' ),
			'others'   => \__( 'Autres quiz', 'g2rd-rescue18-child' ),
			'copied'   => \__( 'Score copié dans le presse-papiers.', 'g2rd-rescue18-child' ),
		),
	);
	$html = '<script type="application/json" data-r18-quiz-config>' . \wp_json_encode( $cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script><div class="r18-quiz__app" data-r18-quiz-app aria-live="polite"></div>' . $static;
	return wrap( 'r18-qcard', $html, 'section', array( 'data-r18-quiz' => '1' ) );
}

/**
 * Carte produit WooCommerce.
 *
 * @param \WC_Product $product Produit.
 * @return string
 */
function product_card( $product ): string {
	$cat   = \get_the_terms( $product->get_id(), 'product_cat' );
	$cat   = \is_array( $cat ) && isset( $cat[0] ) ? $cat[0]->name : '';
	$img   = $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) );
	$url   = $product->get_permalink();
	$add   = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock()
		/* translators: %s : nom du produit. */
		? '<a class="r18-add add_to_cart_button ajax_add_to_cart" href="' . \esc_url( $product->add_to_cart_url() ) . '" data-product_id="' . (int) $product->get_id() . '" data-quantity="1" rel="nofollow" aria-label="' . \esc_attr( \sprintf( \__( 'Ajouter « %s » au panier', 'g2rd-rescue18-child' ), $product->get_name() ) ) . '">' . icone( 'plus' ) . '</a>'
		/* translators: %s : nom du produit. */
		: '<a class="r18-add" href="' . \esc_url( $url ) . '" aria-label="' . \esc_attr( \sprintf( \__( 'Choisir les options de « %s »', 'g2rd-rescue18-child' ), $product->get_name() ) ) . '">' . icone( 'arrow' ) . '</a>';
	return '<article class="r18-prod"><a class="r18-prod__vis" href="' . \esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . ( '' !== $img ? $img : icone( 'store' ) ) . '</a><div class="r18-prod__b">' . ( '' !== $cat ? '<small>' . \esc_html( $cat ) . '</small>' : '' ) . '<strong><a href="' . \esc_url( $url ) . '">' . \esc_html( $product->get_name() ) . '</a></strong><span class="r18-price">' . \wp_kses_post( $product->get_price_html() ) . $add . '</span></div></article>';
}

/**
 * Bloc rescue18/produits.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_produits( array $attrs, ?\WP_Block $block = null ): string {
	if ( ! \function_exists( 'wc_get_product' ) ) {
		return '';
	}
	if ( 'carte' === ( $attrs['variante'] ?? '' ) ) {
		$product = \wc_get_product( current_id( $block ) );
		return $product ? product_card( $product ) : '';
	}
	$products = \wc_get_products(
		array(
			'status'     => 'publish',
			'limit'      => \max( 1, (int) ( $attrs['nombre'] ?? 4 ) ),
			'orderby'    => 'date',
			'order'      => 'DESC',
			'visibility' => 'catalog',
		)
	);
	$html     = '';
	foreach ( $products as $product ) {
		$html .= product_card( $product );
	}
	return wrap( 'r18-shop-grid', $html );
}

/**
 * Bloc rescue18/fil-ariane : SEOPress s'il est actif (données structurées
 * sans doublon), sinon un fil simple.
 *
 * @return string
 */
function render_fil_ariane(): string {
	if ( \function_exists( 'seopress_display_breadcrumbs' ) ) {
		\ob_start();
		\seopress_display_breadcrumbs();
		$out = (string) \ob_get_clean();
		if ( '' !== \trim( $out ) ) {
			return wrap( 'r18-crumbs r18-crumbs--seopress', $out, 'div' );
		}
	}
	$items = breadcrumb_items();
	$html = '';
	$last = \count( $items ) - 1;
	foreach ( $items as $i => [ $url, $label ] ) {
		$html .= $i > 0 ? '<span aria-hidden="true">/</span>' : '';
		$html .= $i === $last || '' === $url ? '<span aria-current="page">' . \esc_html( $label ) . '</span>' : '<a href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . '</a>';
	}
	return wrap( 'r18-crumbs', $html, 'nav', array( 'aria-label' => \__( 'Fil d\'Ariane', 'g2rd-rescue18-child' ) ) );
}

/**
 * Étapes du fil d'Ariane de la page courante : [ URL, libellé ].
 * Partagé par le bloc et par les données structurées BreadcrumbList.
 *
 * @return array<int,array{0:string,1:string}>
 */
function breadcrumb_items(): array {
	$items = array( array( \home_url( '/' ), \__( 'Accueil', 'g2rd-rescue18-child' ) ) );
	$obj   = \get_queried_object();
	if ( $obj instanceof \WP_Post ) {
		$parents = array(
			'post'         => array( 'articles', \__( 'Actualités', 'g2rd-rescue18-child' ) ),
			'r18_document' => array( 'documentation', \__( 'Documentation', 'g2rd-rescue18-child' ) ),
			'r18_texte'    => array( 'juridique', \__( 'Juridique', 'g2rd-rescue18-child' ) ),
			'r18_episode'  => array( 'podcasts', \__( 'Podcasts', 'g2rd-rescue18-child' ) ),
			'r18_quiz'     => array( 'formation', \__( 'Formation', 'g2rd-rescue18-child' ) ),
			'r18_fiche'    => array( 'formation', \__( 'Formation', 'g2rd-rescue18-child' ) ),
			'product'      => array( 'boutique', \__( 'Boutique', 'g2rd-rescue18-child' ) ),
		);
		if ( 'r18_service' === $obj->post_type ) {
			$items[] = array( (string) \get_post_type_archive_link( 'r18_service' ), \__( 'Annuaire des SDIS', 'g2rd-rescue18-child' ) );
		} elseif ( 'r18_partenaire' === $obj->post_type ) {
			$items[] = array( \home_url( '/partenaires/' ), \__( 'Partenaires', 'g2rd-rescue18-child' ) );
		} elseif ( 'r18_concours' === $obj->post_type ) {
			$items[] = array( \home_url( '/recrutement/' ), \__( 'Recrutement', 'g2rd-rescue18-child' ) );
			$items[] = array( \home_url( '/recrutement/concours/' ), \__( 'Concours', 'g2rd-rescue18-child' ) );
		} elseif ( 'r18_offre' === $obj->post_type ) {
			$items[] = array( \home_url( '/recrutement/' ), \__( 'Recrutement', 'g2rd-rescue18-child' ) );
			$items[] = array( \home_url( '/recrutement/offres-emploi/' ), \__( 'Offres d\'emploi', 'g2rd-rescue18-child' ) );
		}
		if ( 'r18_texte' === $obj->post_type ) {
			$items[] = array( rubrique_url( 'documentation' ), \__( 'Documentation', 'g2rd-rescue18-child' ) );
		}
		if ( isset( $parents[ $obj->post_type ] ) ) {
			$items[] = array( rubrique_url( $parents[ $obj->post_type ][0] ), $parents[ $obj->post_type ][1] );
		}
		foreach ( \array_reverse( \get_post_ancestors( $obj ) ) as $anc ) {
			$items[] = array( (string) \get_permalink( $anc ), \get_the_title( $anc ) );
		}
		$items[] = array( '', \get_the_title( $obj ) );
	} elseif ( $obj instanceof \WP_Term ) {
		if ( 'r18_specialite' === $obj->taxonomy ) {
			$items[] = array( '', \__( 'Spécialités', 'g2rd-rescue18-child' ) );
		} elseif ( 'category' === $obj->taxonomy ) {
			$items[] = array( rubrique_url( 'articles' ), \__( 'Actualités', 'g2rd-rescue18-child' ) );
		}
		$items[] = array( '', $obj->name );
	} elseif ( \is_post_type_archive( 'r18_service' ) ) {
		$items[] = array( '', \__( 'Annuaire des SDIS', 'g2rd-rescue18-child' ) );
	} elseif ( \is_home() ) {
		$items[] = array( '', \__( 'Actualités', 'g2rd-rescue18-child' ) );
	} elseif ( \is_search() ) {
		$items[] = array( '', \__( 'Recherche', 'g2rd-rescue18-child' ) );
	}
	return $items;
}

/**
 * Bloc rescue18/sommaire : rempli côté navigateur depuis les intertitres H2.
 *
 * @return string
 */
function render_sommaire(): string {
	return wrap( 'r18-toc', '<h2>' . \esc_html__( 'Sommaire', 'g2rd-rescue18-child' ) . '</h2><ol data-r18-toc></ol>', 'nav', array( 'aria-label' => \__( 'Sommaire', 'g2rd-rescue18-child' ), 'hidden' => 'hidden' ) );
}

/**
 * Bloc rescue18/outils-article : PDF et partage.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @param \WP_Block|null      $block Bloc.
 * @return string
 */
function render_outils_article( array $attrs, ?\WP_Block $block = null ): string {
	$id    = current_id( $block );
	$url   = (string) \get_permalink( $id );
	$title = \get_the_title( $id );
	$pdf   = meta( $id, 'r18_pdf_url' );
	$html  = '' !== $pdf ? '<a class="r18-dl" href="' . \esc_url( $pdf ) . '" download>' . icone( 'download' ) . '<span><strong>' . \esc_html__( 'Télécharger l\'article', 'g2rd-rescue18-child' ) . '</strong><small>' . \esc_html__( 'Version PDF', 'g2rd-rescue18-child' ) . '</small></span></a>' : '';
	$enc   = \rawurlencode( $url );
	$share = array(
		'FB' => array( 'https://www.facebook.com/sharer/sharer.php?u=' . $enc, 'Facebook' ),
		'IN' => array( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $enc, 'LinkedIn' ),
		'X'  => array( 'https://x.com/intent/post?url=' . $enc . '&text=' . \rawurlencode( $title ), 'X' ),
		'WA' => array( 'https://wa.me/?text=' . \rawurlencode( $title . ' ' . $url ), 'WhatsApp' ),
	);
	$links = '';
	foreach ( $share as $abbr => [ $href, $net ] ) {
		/* translators: %s : réseau social. */
		$links .= '<a href="' . \esc_url( $href ) . '" target="_blank" rel="noopener" aria-label="' . \esc_attr( \sprintf( \__( 'Partager sur %s', 'g2rd-rescue18-child' ), $net ) ) . '">' . \esc_html( $abbr ) . '</a>';
	}
	$links .= '<button type="button" data-r18-copy="' . \esc_url( $url ) . '" aria-label="' . \esc_attr__( 'Copier le lien', 'g2rd-rescue18-child' ) . '">' . icone( 'link' ) . '</button>';
	$html  .= '<div class="r18-box"><h2>' . \esc_html__( 'Partager', 'g2rd-rescue18-child' ) . '</h2><div class="r18-share">' . $links . '</div></div>';
	return wrap( 'r18-art-tools', $html );
}

/**
 * Bloc rescue18/newsletter : formulaire MailPoet s'il est actif.
 * Sans MailPoet, aucun faux formulaire : un lien vers la page contact.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_newsletter( array $attrs ): string {
	$id = \max( 1, (int) ( $attrs['formulaire'] ?? 1 ) );
	if ( \shortcode_exists( 'mailpoet_form' ) ) {
		return wrap( 'r18-nl-form', \do_shortcode( '[mailpoet_form id="' . $id . '"]' ) );
	}
	if ( \is_admin() || ( \defined( 'REST_REQUEST' ) && \REST_REQUEST ) ) {
		return wrap( 'r18-nl-form', '<p><em>' . \esc_html__( 'Activer MailPoet pour afficher le formulaire d\'inscription.', 'g2rd-rescue18-child' ) . '</em></p>' );
	}
	return wrap( 'r18-nl-form', '<a class="r18-btn r18-btn--red" href="' . \esc_url( page_url( 'contactez-nous' ) ) . '">' . \esc_html__( 'Nous écrire', 'g2rd-rescue18-child' ) . ' ' . icone( 'arrow' ) . '</a>' );
}

/**
 * Bloc rescue18/panier : icône et nombre d'articles (WooCommerce).
 *
 * @return string
 */
function render_panier(): string {
	if ( ! \function_exists( 'wc_get_cart_url' ) ) {
		return '';
	}
	$count = ( \function_exists( 'WC' ) && \WC()->cart ) ? (int) \WC()->cart->get_cart_contents_count() : 0;
	/* translators: %d : nombre d'articles. */
	$label = \sprintf( \_n( 'Panier, %d article', 'Panier, %d articles', $count, 'g2rd-rescue18-child' ), $count );
	$badge = $count > 0 ? '<span class="r18-badge" aria-hidden="true">' . $count . '</span>' : '';
	return wrap( 'r18-cart', '<a class="r18-icon-btn" href="' . \esc_url( \wc_get_cart_url() ) . '" aria-label="' . \esc_attr( $label ) . '">' . icone( 'cart' ) . $badge . '</a>' );
}
