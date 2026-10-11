<?php
/**
 * Intégration WooCommerce : affichage seulement.
 *
 * Ce fichier ne contient aucun appel à un fournisseur, aucun import, aucun
 * cron, aucun mapping d'API. Il habille ce que WooCommerce sait déjà.
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

/**
 * Clés de métadonnées qui signalent une précommande (valeur « yes »).
 *
 * @return string[]
 */
function preorder_meta_keys(): array {
	return array( '_tcgf_preorder', '_wc_pre_orders_enabled', '_ywpo_preorder', '_is_pre_order' );
}

/**
 * Section de produits : titre, grille WooCommerce, état vide honnête.
 *
 * Types : nouveautes, mis-en-avant, precommandes, promotions, meilleures-ventes, categorie, attribut.
 *
 * @param array<string,mixed> $args Arguments.
 * @return string HTML, ou chaîne vide si rien à afficher et `vide` est vide.
 */
function products_section( array $args ): string {
	if ( ! \shortcode_exists( 'products' ) ) {
		return '';
	}

	$args = \wp_parse_args(
		$args,
		array(
			'type'       => 'nouveautes',
			'categorie'  => '',
			'attribut'   => '',
			'termes'     => '',
			'etiquette'  => '',
			'limite'     => 8,
			'colonnes'   => 4,
			'pagination' => 'non',
			'titre'      => '',
			'lien'       => '',
			'vide'       => \__( 'Aucun produit en ligne dans cette sélection pour le moment.', 'g2rd-tcgforge-child' ),
		)
	);

	$limit   = \max( 1, \min( 48, \absint( $args['limite'] ) ) );
	$columns = \max( 2, \min( 6, \absint( $args['colonnes'] ) ) );
	$type    = \sanitize_key( (string) $args['type'] );

	$atts = array(
		'limit'      => (string) $limit,
		'columns'    => (string) $columns,
		'visibility' => 'visible',
		'orderby'    => 'date',
		'order'      => 'DESC',
	);

	if ( '' !== $args['categorie'] ) {
		$atts['category'] = \implode( ',', \array_map( 'sanitize_title', \explode( ',', (string) $args['categorie'] ) ) );
	}
	if ( '' !== $args['etiquette'] ) {
		$atts['tag'] = \implode( ',', \array_map( 'sanitize_title', \explode( ',', (string) $args['etiquette'] ) ) );
	}
	if ( '' !== $args['attribut'] && '' !== $args['termes'] ) {
		$atts['attribute'] = \sanitize_title( (string) $args['attribut'] );
		$atts['terms']     = \implode( ',', \array_map( 'sanitize_title', \explode( ',', (string) $args['termes'] ) ) );
	}
	if ( \in_array( \strtolower( (string) $args['pagination'] ), array( 'oui', 'yes', 'true', '1' ), true ) ) {
		$atts['paginate'] = 'true';
	}

	$preorder_filter = null;

	switch ( $type ) {
		case 'promotions':
			$atts['on_sale'] = 'true';
			break;
		case 'mis-en-avant':
			// Produits marqués « Mis en avant » dans WooCommerce (étoile de la liste des produits).
			$atts['visibility'] = 'featured';
			break;
		case 'meilleures-ventes':
			$atts['best_selling'] = 'true';
			unset( $atts['orderby'], $atts['order'] );
			break;
		case 'precommandes':
			$atts['cache']   = 'false';
			$preorder_filter = static function ( $query_args ) {
				$meta = array( 'relation' => 'OR' );
				foreach ( preorder_meta_keys() as $key ) {
					$meta[] = array(
						'key'   => $key,
						'value' => 'yes',
					);
				}
				$existing                 = isset( $query_args['meta_query'] ) && \is_array( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();
				$existing[]               = $meta;
				$query_args['meta_query'] = $existing; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				return $query_args;
			};
			\add_filter( 'woocommerce_shortcode_products_query', $preorder_filter, 20 );
			break;
		default:
			break;
	}

	$shortcode = '[products';
	foreach ( $atts as $key => $value ) {
		$shortcode .= \sprintf( ' %s="%s"', $key, \esc_attr( $value ) );
	}
	$shortcode .= ']';

	$loop = \do_shortcode( $shortcode );

	if ( $preorder_filter ) {
		\remove_filter( 'woocommerce_shortcode_products_query', $preorder_filter, 20 );
	}

	$has_products = false !== \strpos( $loop, '<li' );

	if ( ! $has_products ) {
		if ( '' === (string) $args['vide'] ) {
			return '';
		}
		$loop = '<p class="tcgf-empty__text">' . \esc_html( (string) $args['vide'] ) . '</p>';
	}

	$head = '';
	if ( '' !== (string) $args['titre'] ) {
		$head = '<h2 class="tcgf-products__title">' . \esc_html( (string) $args['titre'] ) . '</h2>';
		if ( '' !== (string) $args['lien'] && $has_products ) {
			$head .= \sprintf(
				'<a class="tcgf-link-arrow tcgf-products__more" href="%s">%s<span class="screen-reader-text"> : %s</span></a>',
				\esc_url( (string) $args['lien'] ),
				\esc_html__( 'Tout voir', 'g2rd-tcgforge-child' ),
				\esc_html( (string) $args['titre'] )
			);
		}
		$head = '<div class="tcgf-products__head">' . $head . '</div>';
	}

	return \sprintf(
		'<section class="tcgf-products tcgf-products--%1$s%2$s">%3$s%4$s</section>',
		\esc_attr( $type ),
		$has_products ? '' : ' tcgf-empty',
		$head,
		$loop
	);
}

/**
 * Shortcode [tcgforge_produits].
 *
 * Exemples :
 *   [tcgforge_produits type="nouveautes" limite="8"]
 *   [tcgforge_produits type="mis-en-avant" titre="Dernières sorties" vide=""]
 *   [tcgforge_produits type="precommandes" limite="12" pagination="oui"]
 *   [tcgforge_produits type="categorie" categorie="accessoires" limite="4"]
 *   [tcgforge_produits type="attribut" attribut="jeu" termes="pokemon"]
 *
 * @param array<string,string>|string $atts Attributs.
 * @return string
 */
function products_shortcode( $atts ): string {
	$atts = \shortcode_atts(
		array(
			'type'       => 'nouveautes',
			'categorie'  => '',
			'attribut'   => '',
			'termes'     => '',
			'etiquette'  => '',
			'limite'     => 8,
			'colonnes'   => 4,
			'pagination' => 'non',
			'titre'      => '',
			'lien'       => '',
			'vide'       => \__( 'Aucun produit en ligne dans cette sélection pour le moment.', 'g2rd-tcgforge-child' ),
		),
		\is_array( $atts ) ? $atts : array(),
		'tcgforge_produits'
	);

	if ( ! \shortcode_exists( 'products' ) ) {
		return \current_user_can( 'edit_pages' )
			? '<p class="tcgf-empty__admin">' . \esc_html__( 'Visible par l\'équipe seulement : activez WooCommerce pour afficher les produits ici.', 'g2rd-tcgforge-child' ) . '</p>'
			: '';
	}

	return products_section( $atts );
}
\add_shortcode( 'tcgforge_produits', __NAMESPACE__ . '\products_shortcode' );

/**
 * Réglages de boucle et crochets des cartes produit classiques
 * (shortcodes, ventes croisées du panier, blocs hérités).
 */
\add_action(
	'init',
	function (): void {
		if ( ! \class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Le badge « Promo » natif fait doublon avec les badges TCGForge.
		\remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

		/*
		 * Dans les modèles en blocs, WooCommerce rejoue ces crochets autour des
		 * blocs de la grille (couche de compatibilité, exécutée dans un filtre
		 * render_block). Les blocs tcgforge/* du modèle affichent déjà badges,
		 * caractéristiques et date de sortie : on ne les imprime pas deux fois.
		 */
		$is_block_card = static fn(): bool => \doing_filter( 'render_block' );

		\add_action(
			'woocommerce_before_shop_loop_item_title',
			static function () use ( $is_block_card ): void {
				if ( $is_block_card() ) {
					return;
				}
				echo badges_html( null, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans badges_html().
			},
			9
		);

		\add_action(
			'woocommerce_shop_loop_item_title',
			static function () use ( $is_block_card ): void {
				if ( $is_block_card() ) {
					return;
				}
				echo facts_html( null, 'chips', array( 'jeu', 'extension', 'langue' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans facts_html().
			},
			5
		);

		\add_action(
			'woocommerce_after_shop_loop_item_title',
			static function () use ( $is_block_card ): void {
				if ( $is_block_card() ) {
					return;
				}
				echo release_html( null, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans release_html().
			},
			12
		);
	},
	20
);

/**
 * Colonnes et volume du catalogue.
 */
\add_filter( 'loop_shop_columns', static fn(): int => 4 );
\add_filter( 'loop_shop_per_page', static fn(): int => 24 );

/**
 * Vignettes non recadrées : un booster est vertical, un display horizontal.
 * Le cadrage est géré en CSS (object-fit: contain), le visuel reste entier.
 *
 * @param array<string,mixed> $size Taille.
 * @return array<string,mixed>
 */
\add_filter(
	'woocommerce_get_image_size_thumbnail',
	static function ( $size ) {
		$size         = \is_array( $size ) ? $size : array();
		$size['crop'] = 0;
		if ( empty( $size['width'] ) || (int) $size['width'] < 480 ) {
			$size['width'] = 480;
		}
		$size['height'] = '';
		return $size;
	}
);

/**
 * Libellé du bouton d'achat pour une précommande.
 *
 * @param string      $text    Libellé.
 * @param \WC_Product $product Produit.
 * @return string
 */
function preorder_button_text( $text, $product = null ) {
	if ( $product instanceof \WC_Product && $product->is_purchasable() && $product->is_in_stock() && is_preorder( $product ) ) {
		return $product->is_type( 'simple' ) || $product->is_type( 'variation' )
			? \__( 'Précommander', 'g2rd-tcgforge-child' )
			: $text;
	}
	return $text;
}
\add_filter( 'woocommerce_product_add_to_cart_text', __NAMESPACE__ . '\preorder_button_text', 20, 2 );
\add_filter( 'woocommerce_product_single_add_to_cart_text', __NAMESPACE__ . '\preorder_button_text', 20, 2 );

/**
 * Filtres par attribut du catalogue.
 *
 * Le bloc woocommerce/product-filter-attribute attend un identifiant
 * d'attribut propre à chaque site. Les templates du thème portent une classe
 * `tcgf-filter--{slug}` (jeu, extension, langue, type-de-produit) : on la
 * traduit ici en identifiant. Tant que l'attribut n'existe pas dans
 * Produits › Attributs, le filtre n'est pas rendu.
 *
 * @param string $class_name Classes du bloc.
 * @return string Slug d'attribut, ou chaîne vide.
 */
function filter_attribute_slug( string $class_name ): string {
	return \preg_match( '/(?:^|\s)tcgf-filter--([a-z0-9-]+)/', $class_name, $m ) ? $m[1] : '';
}

/*
 * L'identifiant est injecté au moment du rendu. Le filtre render_block_data ne
 * suffit pas : pour un bloc imbriqué qui fournit du contexte, WordPress fige
 * les attributs dès la construction du bloc. On enveloppe donc la fonction de
 * rendu du bloc, sans modifier WooCommerce.
 */
\add_filter(
	'register_block_type_args',
	static function ( $args, $block_name ) {
		if ( 'woocommerce/product-filter-attribute' !== $block_name || ! \is_array( $args ) || empty( $args['render_callback'] ) || ! \is_callable( $args['render_callback'] ) ) {
			return $args;
		}
		$original                = $args['render_callback'];
		$args['render_callback'] = static function ( $attributes, $content = '', $block = null ) use ( $original ) {
			$attributes = \is_array( $attributes ) ? $attributes : array();
			$slug       = filter_attribute_slug( (string) ( $attributes['className'] ?? '' ) );
			if ( '' !== $slug && \function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
				$id = (int) \wc_attribute_taxonomy_id_by_name( $slug );
				if ( $id > 0 ) {
					$attributes['attributeId'] = $id;
				}
			}
			return \call_user_func( $original, $attributes, $content, $block );
		};
		return $args;
	},
	10,
	2
);

\add_filter(
	'pre_render_block',
	static function ( $pre_render, $parsed_block ) {
		if ( ! \is_array( $parsed_block ) || 'woocommerce/product-filter-attribute' !== ( $parsed_block['blockName'] ?? '' ) ) {
			return $pre_render;
		}
		$slug = filter_attribute_slug( (string) ( $parsed_block['attrs']['className'] ?? '' ) );
		if ( '' === $slug ) {
			return $pre_render;
		}
		if ( ! \function_exists( 'wc_attribute_taxonomy_id_by_name' ) || (int) \wc_attribute_taxonomy_id_by_name( $slug ) <= 0 ) {
			return '';
		}
		return $pre_render;
	},
	10,
	2
);

/**
 * Message du panier vide et de la recherche sans résultat : ton direct.
 */
\add_filter(
	'wc_empty_cart_message',
	static fn(): string => \__( 'Votre panier est vide. Parcourez les nouveautés ou choisissez un univers pour commencer.', 'g2rd-tcgforge-child' )
);

/**
 * Produits apparentés : quatre colonnes, comme le reste du catalogue.
 *
 * @param array<string,mixed> $args Arguments.
 * @return array<string,mixed>
 */
\add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		$args                   = \is_array( $args ) ? $args : array();
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);
