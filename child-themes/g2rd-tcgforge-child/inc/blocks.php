<?php
/**
 * Blocs dynamiques du thème enfant (rendu PHP, aucun build).
 *
 * Ces blocs affichent des données réelles de WooCommerce. Ils ne rendent rien
 * tant que la donnée n'existe pas : pas de faux badge, pas de fausse date.
 *
 *   tcgforge/product-badges    Nouveau, Précommande, Promotion, Stock faible, Rupture
 *   tcgforge/product-facts     Jeu, extension, langue, type de produit
 *   tcgforge/product-release   Précommande : date de sortie, expédition estimée
 *   tcgforge/product-related   Même licence, ou accessoires complémentaires
 *   tcgforge/products          Rayon de produits (nouveautés, précommandes, promotions, catégorie…)
 *   tcgforge/licence-products  Catalogue d'une page de licence
 *   tcgforge/payment-methods   Moyens de paiement réellement activés
 *   tcgforge/wishlist-link     Lien liste de souhaits, si une extension est active
 *   tcgforge/newsletter        Inscription à la newsletter, si un formulaire est configuré
 *
 * Côté éditeur, assets/js/editor-blocks.js les déclare avec un aperçu serveur.
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

/**
 * Identifiant du produit dans le contexte du bloc (boucle ou fiche).
 *
 * @param mixed $block Instance WP_Block.
 * @return int
 */
function block_product_id( $block ): int {
	if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	return (int) \get_the_ID();
}

/**
 * Enveloppe un rendu dans les attributs du bloc.
 *
 * @param string $class Classe du bloc.
 * @param string $inner Contenu.
 * @param string $tag   Balise.
 * @return string
 */
function block_wrap( string $class, string $inner, string $tag = 'div' ): string {
	if ( '' === $inner ) {
		return '';
	}
	return \sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, \get_block_wrapper_attributes( array( 'class' => $class ) ), $inner );
}

/**
 * Rendu : badges.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @param string              $content    Contenu.
 * @param mixed               $block      Bloc.
 * @return string
 */
function render_product_badges( $attributes, $content = '', $block = null ): string {
	$max = isset( $attributes['max'] ) ? \absint( $attributes['max'] ) : 2;
	return block_wrap( 'tcgf-block-badges', badges_html( block_product_id( $block ), $max ) );
}

/**
 * Rendu : caractéristiques.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @param string              $content    Contenu.
 * @param mixed               $block      Bloc.
 * @return string
 */
function render_product_facts( $attributes, $content = '', $block = null ): string {
	$display = isset( $attributes['display'] ) && 'liste' === $attributes['display'] ? 'liste' : 'chips';
	$only    = isset( $attributes['only'] ) && \is_array( $attributes['only'] ) ? \array_map( 'sanitize_key', $attributes['only'] ) : array();
	return block_wrap( 'tcgf-block-facts', facts_html( block_product_id( $block ), $display, $only ) );
}

/**
 * Rendu : disponibilité de précommande.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @param string              $content    Contenu.
 * @param mixed               $block      Bloc.
 * @return string
 */
function render_product_release( $attributes, $content = '', $block = null ): string {
	return block_wrap( 'tcgf-block-release', release_html( block_product_id( $block ), ! empty( $attributes['compact'] ) ) );
}

/**
 * Rend une liste de produits avec le gabarit de boucle WooCommerce.
 *
 * @param int[]  $ids     Identifiants de produits.
 * @param int    $columns Colonnes.
 * @return string
 */
function products_loop_html( array $ids, int $columns = 4 ): string {
	$ids = \array_values( \array_filter( \array_map( 'absint', $ids ) ) );
	if ( ! $ids || ! \function_exists( 'wc_get_template_part' ) ) {
		return '';
	}

	$query = new \WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'post__in'            => $ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => \count( $ids ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( ! $query->have_posts() ) {
		return '';
	}

	\wc_set_loop_prop( 'columns', $columns );
	\ob_start();
	\woocommerce_product_loop_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		\wc_get_template_part( 'content', 'product' );
	}
	\woocommerce_product_loop_end();
	\wp_reset_postdata();
	\wc_reset_loop();

	return '<div class="woocommerce columns-' . $columns . '">' . \ob_get_clean() . '</div>';
}

/**
 * Rendu : produits liés (même licence, ou accessoires complémentaires).
 *
 * - licence     : produits qui partagent le terme de l'attribut « jeu » (ou
 *                 « licence »), sinon la catégorie principale du produit.
 * - accessoires : ventes croisées saisies sur le produit, sinon les produits
 *                 de la catégorie « accessoires ».
 *
 * @param array<string,mixed> $attributes Attributs.
 * @param string              $content    Contenu.
 * @param mixed               $block      Bloc.
 * @return string
 */
function render_product_related( $attributes, $content = '', $block = null ): string {
	$product = get_product( block_product_id( $block ) );
	if ( ! $product ) {
		return '';
	}

	$mode    = isset( $attributes['mode'] ) && 'accessoires' === $attributes['mode'] ? 'accessoires' : 'licence';
	$limit   = isset( $attributes['limit'] ) ? \max( 1, \min( 12, \absint( $attributes['limit'] ) ) ) : 4;
	$columns = isset( $attributes['columns'] ) ? \max( 2, \min( 6, \absint( $attributes['columns'] ) ) ) : 4;
	$title   = isset( $attributes['title'] ) ? \sanitize_text_field( (string) $attributes['title'] ) : '';
	$ids     = array();
	$exclude = array( $product->get_id() );

	$base = array(
		'status'     => 'publish',
		'limit'      => $limit,
		'return'     => 'ids',
		'exclude'    => $exclude,
		'visibility' => 'catalog',
		'orderby'    => 'date',
		'order'      => 'DESC',
	);

	if ( 'accessoires' === $mode ) {
		$ids = \array_slice( \array_map( 'absint', (array) $product->get_cross_sell_ids() ), 0, $limit );
		if ( ! $ids ) {
			/**
			 * Slug de la catégorie d'accessoires utilisée en repli.
			 *
			 * @param string $slug Slug de la catégorie produit.
			 */
			$slug = (string) \apply_filters( 'tcgforge_accessories_category', 'accessoires' );
			if ( \term_exists( $slug, 'product_cat' ) && ! \has_term( $slug, 'product_cat', $product->get_id() ) ) {
				$ids = (array) \wc_get_products( $base + array( 'category' => array( $slug ) ) );
			}
		}
		if ( '' === $title ) {
			$title = \__( 'Accessoires complémentaires', 'g2rd-tcgforge-child' );
		}
	} else {
		$tax_query = array();
		foreach ( array( 'pa_jeu', 'pa_licence' ) as $taxonomy ) {
			if ( ! \taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = \wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'ids' ) );
			if ( $terms ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $terms,
				);
				break;
			}
		}
		if ( $tax_query ) {
			$ids = (array) \wc_get_products( $base + array( 'tax_query' => $tax_query ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		} else {
			$categories = $product->get_category_ids();
			if ( $categories ) {
				$ids = (array) \wc_get_products(
					$base + array(
						'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => $categories,
							),
						),
					)
				);
			}
		}
		if ( '' === $title ) {
			$title = \__( 'Dans la même licence', 'g2rd-tcgforge-child' );
		}
	}

	$loop = products_loop_html( $ids, $columns );
	if ( '' === $loop ) {
		return '';
	}

	return block_wrap(
		'tcgf-related tcgf-related--' . $mode,
		'<h2 class="tcgf-related__title">' . \esc_html( $title ) . '</h2>' . $loop,
		'section'
	);
}

/**
 * Rendu : rayon de produits.
 *
 * Même moteur que le shortcode [tcgforge_produits]. Un bloc est nécessaire
 * dans les modèles et les compositions : WordPress exécute les shortcodes d'un
 * modèle avant d'y insérer les compositions, un shortcode placé dans une
 * composition resterait donc affiché en clair.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_products( $attributes ): string {
	$attributes = \is_array( $attributes ) ? $attributes : array();

	$html = products_section(
		array(
			'type'       => (string) ( $attributes['type'] ?? 'nouveautes' ),
			'categorie'  => (string) ( $attributes['category'] ?? '' ),
			'attribut'   => (string) ( $attributes['attribute'] ?? '' ),
			'termes'     => (string) ( $attributes['terms'] ?? '' ),
			'etiquette'  => (string) ( $attributes['tag'] ?? '' ),
			'limite'     => (int) ( $attributes['limit'] ?? 8 ),
			'colonnes'   => (int) ( $attributes['columns'] ?? 4 ),
			'pagination' => ! empty( $attributes['paginate'] ) ? 'oui' : 'non',
			'titre'      => (string) ( $attributes['title'] ?? '' ),
			'lien'       => (string) ( $attributes['link'] ?? '' ),
			'vide'       => (string) ( $attributes['empty'] ?? '' ),
		)
	);

	return block_wrap( 'tcgf-shelf', $html );
}

/**
 * Trouve la catégorie produit associée à une page de licence.
 *
 * Ordre : métadonnée `_tcgf_product_cat` (slug), sinon catégorie produit dont
 * le slug est celui de la page.
 *
 * @param int $page_id Identifiant de la page.
 * @return \WP_Term|null
 */
function licence_term( int $page_id ) {
	if ( ! \taxonomy_exists( 'product_cat' ) || ! $page_id ) {
		return null;
	}
	$slug = (string) \get_post_meta( $page_id, '_tcgf_product_cat', true );
	if ( '' === $slug ) {
		$slug = (string) \get_post_field( 'post_name', $page_id );
	}
	/**
	 * Filtre le slug de la catégorie produit liée à une page de licence.
	 *
	 * @param string $slug    Slug de catégorie.
	 * @param int    $page_id Page.
	 */
	$slug = (string) \apply_filters( 'tcgforge_licence_category', $slug, $page_id );
	$term = '' !== $slug ? \get_term_by( 'slug', $slug, 'product_cat' ) : false;

	return $term instanceof \WP_Term ? $term : null;
}

/**
 * Rendu : catalogue d'une page de licence ou de rayon.
 *
 * Une seule architecture pour Pokémon, Yu-Gi-Oh!, One Piece, Magic, Lorcana,
 * Accessoires, Figurines… La page porte le texte éditorial, ce bloc affiche le
 * catalogue de la catégorie produit qui a le même slug que la page.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @param string              $content    Contenu.
 * @param mixed               $block      Bloc.
 * @return string
 */
function render_licence_products( $attributes, $content = '', $block = null ): string {
	$page_id = block_product_id( $block );
	$columns = isset( $attributes['columns'] ) ? \max( 2, \min( 6, \absint( $attributes['columns'] ) ) ) : 4;
	$limit   = isset( $attributes['limit'] ) ? \max( 2, \min( 24, \absint( $attributes['limit'] ) ) ) : 8;
	$term    = licence_term( $page_id );

	if ( ! $term || ! \shortcode_exists( 'products' ) ) {
		$message = '<p class="tcgf-empty__text">' . \esc_html__( 'Aucun produit en ligne dans ce rayon pour le moment.', 'g2rd-tcgforge-child' ) . '</p>';
		if ( \current_user_can( 'edit_pages' ) ) {
			$slug     = (string) \get_post_field( 'post_name', $page_id );
			$message .= '<p class="tcgf-empty__admin">' . \sprintf(
				/* translators: %s : slug attendu. */
				\esc_html__( 'Visible par l\'équipe seulement : créez la catégorie produit « %s » (même slug que la page) pour afficher son catalogue ici.', 'g2rd-tcgforge-child' ),
				\esc_html( $slug )
			) . '</p>';
		}
		return block_wrap( 'tcgf-licence-products tcgf-empty', $message, 'section' );
	}

	$html = '';

	// 1. Dernières arrivées du rayon.
	$html .= products_section(
		array(
			'type'      => 'nouveautes',
			'categorie' => $term->slug,
			'limite'    => $limit,
			'colonnes'  => $columns,
			/* translators: %s : nom de la licence ou du rayon. */
			'titre'     => \sprintf( \__( 'Dernières arrivées %s', 'g2rd-tcgforge-child' ), $term->name ),
			'vide'      => '',
		)
	);

	// 2. Précommandes du rayon, seulement s'il y en a.
	$html .= products_section(
		array(
			'type'      => 'precommandes',
			'categorie' => $term->slug,
			'limite'    => $columns,
			'colonnes'  => $columns,
			/* translators: %s : nom de la licence ou du rayon. */
			'titre'     => \sprintf( \__( 'Précommandes %s', 'g2rd-tcgforge-child' ), $term->name ),
			'vide'      => '',
		)
	);

	// 3. Une section par sous-catégorie réelle (boosters, displays, coffrets…).
	$children = \get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => true,
			'number'     => 8,
			'orderby'    => 'menu_order',
		)
	);
	if ( \is_array( $children ) ) {
		foreach ( $children as $child ) {
			$link  = \get_term_link( $child );
			$html .= products_section(
				array(
					'type'      => 'categorie',
					'categorie' => $child->slug,
					'limite'    => $columns,
					'colonnes'  => $columns,
					'titre'     => $child->name,
					'lien'      => \is_wp_error( $link ) ? '' : $link,
					'vide'      => '',
				)
			);
		}
	}

	if ( '' === $html ) {
		return block_wrap(
			'tcgf-licence-products tcgf-empty',
			'<p class="tcgf-empty__text">' . \esc_html__( 'Aucun produit en ligne dans ce rayon pour le moment.', 'g2rd-tcgforge-child' ) . '</p>',
			'section'
		);
	}

	$link = \get_term_link( $term );
	if ( ! \is_wp_error( $link ) ) {
		$html .= \sprintf(
			'<p class="tcgf-licence-products__all"><a class="tcgf-link-arrow" href="%s">%s</a></p>',
			\esc_url( $link ),
			/* translators: %s : nom de la licence ou du rayon. */
			\esc_html( \sprintf( \__( 'Voir tout le rayon %s', 'g2rd-tcgforge-child' ), $term->name ) )
		);
	}

	return block_wrap( 'tcgf-licence-products', $html );
}

/**
 * Rendu : moyens de paiement réellement activés dans WooCommerce.
 * Texte seul. Aucun logo n'est affiché pour un moyen non activé.
 *
 * @return string
 */
function render_payment_methods(): string {
	if ( ! \function_exists( 'WC' ) || ! \WC()->payment_gateways() ) {
		return '';
	}
	$items = '';
	foreach ( \WC()->payment_gateways()->payment_gateways() as $gateway ) {
		if ( 'yes' !== $gateway->enabled ) {
			continue;
		}
		$title = \trim( \wp_strip_all_tags( (string) $gateway->get_title() ) );
		if ( '' !== $title ) {
			$items .= '<li class="tcgf-payments__item">' . \esc_html( $title ) . '</li>';
		}
	}
	if ( '' === $items ) {
		return '';
	}
	return block_wrap( 'tcgf-payments', '<ul class="tcgf-payments__list">' . $items . '</ul>' );
}

/**
 * URL de la liste de souhaits si une extension compatible est active.
 */
function wishlist_url(): string {
	$url = '';
	if ( \function_exists( 'YITH_WCWL' ) && \is_object( \YITH_WCWL() ) && \method_exists( \YITH_WCWL(), 'get_wishlist_url' ) ) {
		$url = (string) \YITH_WCWL()->get_wishlist_url();
	} elseif ( \function_exists( 'tinv_url_wishlist_default' ) ) {
		$url = (string) \tinv_url_wishlist_default();
	}
	/**
	 * Filtre l'URL de la liste de souhaits (vide = lien masqué).
	 *
	 * @param string $url URL.
	 */
	return (string) \apply_filters( 'tcgforge_wishlist_url', $url );
}

/**
 * Rendu : lien liste de souhaits. Rien si aucune extension n'est installée.
 *
 * @return string
 */
function render_wishlist_link(): string {
	$url = wishlist_url();
	if ( '' === $url ) {
		return '';
	}
	$label = \__( 'Liste de souhaits', 'g2rd-tcgforge-child' );
	$icon  = '<svg aria-hidden="true" focusable="false" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>';
	return block_wrap(
		'tcgf-wishlist',
		\sprintf( '<a class="tcgf-wishlist__link" href="%s" aria-label="%s">%s</a>', \esc_url( $url ), \esc_attr( $label ), $icon )
	);
}

/**
 * Formulaire de newsletter configuré pour la boutique.
 *
 * Le thème ne fournit pas d'outil d'e-mailing. Il affiche le formulaire de
 * l'outil choisi (FluentCRM, Fluent Forms, Brevo, Mailchimp…) à partir du
 * shortcode saisi dans Réglages › Général › « TCGForge ». Sans shortcode
 * valide, toute la section est masquée : pas de formulaire factice.
 *
 * @return string HTML du formulaire, ou chaîne vide.
 */
function newsletter_form(): string {
	$shortcode = \trim( (string) \get_option( 'tcgforge_newsletter_shortcode', '' ) );
	$form      = '';

	if ( '' !== $shortcode && \preg_match( '/^\[([a-zA-Z0-9_-]+)/', $shortcode, $m ) && \shortcode_exists( $m[1] ) ) {
		$form = \do_shortcode( $shortcode );
	}

	/**
	 * Filtre le HTML du formulaire de newsletter.
	 *
	 * @param string $form HTML du formulaire (vide = section masquée).
	 */
	return \trim( (string) \apply_filters( 'tcgforge_newsletter_form', $form ) );
}

/**
 * Rendu : section newsletter du pied de page.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_newsletter( $attributes ): string {
	$form = newsletter_form();
	if ( '' === $form ) {
		return '';
	}
	$title = isset( $attributes['title'] ) && '' !== $attributes['title'] ? (string) $attributes['title'] : \__( 'Les sorties et les précommandes par e-mail', 'g2rd-tcgforge-child' );
	$text  = isset( $attributes['text'] ) && '' !== $attributes['text'] ? (string) $attributes['text'] : \__( 'Un message à chaque sortie importante et à l\'ouverture des précommandes. Désinscription en un clic.', 'g2rd-tcgforge-child' );

	return block_wrap(
		'tcgf-newsletter',
		'<div class="tcgf-newsletter__inner"><div class="tcgf-newsletter__text"><h2 class="tcgf-newsletter__title">' . \esc_html( $title ) . '</h2><p>' . \esc_html( $text ) . '</p></div><div class="tcgf-newsletter__form">' . $form . '</div></div>',
		'section'
	);
}

/**
 * Formulaire de contact : Fluent Forms.
 *
 * Le shortcode [tcgforge_contact] affiche le formulaire Fluent Forms dont
 * l'identifiant est saisi dans Réglages › Général › « TCGForge ». L'identifiant
 * dépend du site : il n'est donc pas écrit en dur dans le contenu de la page.
 * Sans Fluent Forms ou sans identifiant, rien n'est affiché aux visiteurs.
 *
 * @return string
 */
function contact_form_shortcode(): string {
	$form_id = \absint( \get_option( 'tcgforge_contact_form_id', 0 ) );

	if ( $form_id > 0 && \shortcode_exists( 'fluentform' ) ) {
		return '<div class="tcgf-form">' . \do_shortcode( '[fluentform id="' . $form_id . '"]' ) . '</div>';
	}

	if ( \current_user_can( 'manage_options' ) ) {
		return '<p class="tcgf-empty__admin">' . \esc_html__( 'Visible par l\'équipe seulement : activez Fluent Forms, puis indiquez l\'identifiant du formulaire de contact dans Réglages › Général › TCGForge.', 'g2rd-tcgforge-child' ) . '</p>';
	}

	return '';
}
\add_shortcode( 'tcgforge_contact', __NAMESPACE__ . '\contact_form_shortcode' );

/**
 * Réglages : formulaire de contact et formulaire de newsletter (Réglages › Général).
 */
\add_action(
	'admin_init',
	function (): void {
		\register_setting(
			'general',
			'tcgforge_newsletter_shortcode',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		\register_setting(
			'general',
			'tcgforge_contact_form_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
		\add_settings_section( 'tcgforge', \__( 'TCGForge', 'g2rd-tcgforge-child' ), '__return_false', 'general' );
		\add_settings_field(
			'tcgforge_contact_form_id',
			\__( 'Formulaire de contact', 'g2rd-tcgforge-child' ),
			function (): void {
				\printf(
					'<input type="number" min="0" class="small-text" id="tcgforge_contact_form_id" name="tcgforge_contact_form_id" value="%d"><p class="description">%s</p>',
					\absint( \get_option( 'tcgforge_contact_form_id', 0 ) ),
					\esc_html__( 'Identifiant du formulaire Fluent Forms affiché par le shortcode [tcgforge_contact] sur la page Contact.', 'g2rd-tcgforge-child' )
				);
			},
			'general',
			'tcgforge',
			array( 'label_for' => 'tcgforge_contact_form_id' )
		);
		\add_settings_field(
			'tcgforge_newsletter_shortcode',
			\__( 'Formulaire de newsletter', 'g2rd-tcgforge-child' ),
			function (): void {
				\printf(
					'<input type="text" class="regular-text code" id="tcgforge_newsletter_shortcode" name="tcgforge_newsletter_shortcode" value="%s" placeholder="[fluentform id=&quot;1&quot;]"><p class="description">%s</p>',
					\esc_attr( (string) \get_option( 'tcgforge_newsletter_shortcode', '' ) ),
					\esc_html__( 'Shortcode du formulaire d\'inscription de votre outil d\'e-mailing. Vide : la section newsletter du pied de page reste masquée.', 'g2rd-tcgforge-child' )
				);
			},
			'general',
			'tcgforge',
			array( 'label_for' => 'tcgforge_newsletter_shortcode' )
		);
	}
);

/**
 * Enregistrement des blocs.
 */
\add_action(
	'init',
	function (): void {
		$blocks = array(
			'product-badges'   => array(
				'title'       => \__( 'TCGForge — Badges produit', 'g2rd-tcgforge-child' ),
				'callback'    => __NAMESPACE__ . '\render_product_badges',
				'context'     => array( 'postId', 'postType' ),
				'attributes'  => array(
					'max' => array(
						'type'    => 'number',
						'default' => 2,
					),
				),
			),
			'product-facts'    => array(
				'title'      => \__( 'TCGForge — Jeu, extension, langue', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_product_facts',
				'context'    => array( 'postId', 'postType' ),
				'attributes' => array(
					'display' => array(
						'type'    => 'string',
						'default' => 'chips',
					),
					'only'    => array(
						'type'    => 'array',
						'default' => array(),
						'items'   => array( 'type' => 'string' ),
					),
				),
			),
			'product-release'  => array(
				'title'      => \__( 'TCGForge — Précommande et date de sortie', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_product_release',
				'context'    => array( 'postId', 'postType' ),
				'attributes' => array(
					'compact' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			),
			'product-related'  => array(
				'title'      => \__( 'TCGForge — Même licence ou accessoires', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_product_related',
				'context'    => array( 'postId', 'postType' ),
				'attributes' => array(
					'mode'    => array(
						'type'    => 'string',
						'default' => 'licence',
					),
					'title'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'limit'   => array(
						'type'    => 'number',
						'default' => 4,
					),
					'columns' => array(
						'type'    => 'number',
						'default' => 4,
					),
				),
			),
			'products'         => array(
				'title'      => \__( 'TCGForge — Rayon de produits', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_products',
				'context'    => array(),
				'attributes' => array(
					'type'      => array(
						'type'    => 'string',
						'default' => 'nouveautes',
					),
					'title'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'link'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'limit'     => array(
						'type'    => 'number',
						'default' => 8,
					),
					'columns'   => array(
						'type'    => 'number',
						'default' => 4,
					),
					'category'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'attribute' => array(
						'type'    => 'string',
						'default' => '',
					),
					'terms'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'tag'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'paginate'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'empty'     => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			),
			'licence-products' => array(
				'title'      => \__( 'TCGForge — Catalogue de la licence', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_licence_products',
				'context'    => array( 'postId', 'postType' ),
				'attributes' => array(
					'limit'   => array(
						'type'    => 'number',
						'default' => 8,
					),
					'columns' => array(
						'type'    => 'number',
						'default' => 4,
					),
				),
			),
			'payment-methods'  => array(
				'title'      => \__( 'TCGForge — Moyens de paiement activés', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_payment_methods',
				'context'    => array(),
				'attributes' => array(),
			),
			'wishlist-link'    => array(
				'title'      => \__( 'TCGForge — Lien liste de souhaits', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_wishlist_link',
				'context'    => array(),
				'attributes' => array(),
			),
			'newsletter'       => array(
				'title'      => \__( 'TCGForge — Newsletter', 'g2rd-tcgforge-child' ),
				'callback'   => __NAMESPACE__ . '\render_newsletter',
				'context'    => array(),
				'attributes' => array(
					'title' => array(
						'type'    => 'string',
						'default' => '',
					),
					'text'  => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			),
		);

		foreach ( $blocks as $slug => $block ) {
			\register_block_type(
				'tcgforge/' . $slug,
				array(
					'api_version'     => 3,
					'title'           => $block['title'],
					'category'        => 'woocommerce',
					'icon'            => 'tag',
					'render_callback' => $block['callback'],
					'uses_context'    => $block['context'],
					'attributes'      => $block['attributes'],
					'supports'        => array(
						'html'     => false,
						'multiple' => true,
						'align'    => array( 'wide', 'full' ),
					),
				)
			);
		}
	}
);

/**
 * Déclaration des blocs dans l'éditeur (aperçu serveur, script sans build).
 */
\add_action(
	'enqueue_block_editor_assets',
	function (): void {
		$file = \get_stylesheet_directory() . '/assets/js/editor-blocks.js';
		if ( ! \is_readable( $file ) ) {
			return;
		}
		\wp_enqueue_script(
			'tcgforge-editor-blocks',
			\get_stylesheet_directory_uri() . '/assets/js/editor-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			(string) \filemtime( $file ),
			true
		);
	}
);
