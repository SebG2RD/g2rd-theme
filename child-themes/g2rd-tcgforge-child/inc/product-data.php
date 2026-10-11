<?php
/**
 * Données produit lues par le thème : précommande, date de sortie, expédition,
 * badges et caractéristiques TCG (jeu, extension, langue, type).
 *
 * Tout vient de WooCommerce ou de métadonnées réellement présentes sur le
 * produit. Le thème n'invente aucune valeur : sans donnée, rien ne s'affiche.
 *
 * Métadonnées lues (à alimenter par ton import ou par une extension) :
 *   _tcgf_preorder          « yes » si le produit est en précommande
 *   _tcgf_release_date      date de sortie, format AAAA-MM-JJ
 *   _tcgf_shipping_estimate texte libre, ex. « Expédition à partir du 14 novembre »
 *
 * Extensions de précommande reconnues sans réglage :
 *   WooCommerce Pre-Orders   _wc_pre_orders_enabled, _wc_pre_orders_availability_datetime
 *   YITH Pre-Order           _ywpo_preorder, _ywpo_for_sale_date
 *   Pre-Orders for WooCommerce (Bright Plugins)   _is_pre_order, _pre_order_date
 *
 * @package G2RD_TCGForge
 */

declare( strict_types = 1 );

namespace G2RD_TCGForge;

\defined( 'ABSPATH' ) || exit;

/**
 * Retourne un objet produit WooCommerce, ou null.
 *
 * @param mixed $product Produit, identifiant ou null (produit courant).
 * @return \WC_Product|null
 */
function get_product( $product = null ) {
	if ( ! \function_exists( 'wc_get_product' ) ) {
		return null;
	}
	if ( $product instanceof \WC_Product ) {
		return $product;
	}
	if ( null === $product ) {
		$product = \get_the_ID();
	}
	$found = $product ? \wc_get_product( $product ) : null;
	return $found instanceof \WC_Product ? $found : null;
}

/**
 * Lit une métadonnée sur le produit, puis sur son parent s'il s'agit d'une variation.
 *
 * @param \WC_Product $product Produit.
 * @param string      $key     Clé de la métadonnée.
 * @return string
 */
function product_meta( $product, string $key ): string {
	$value = $product->get_meta( $key, true );
	if ( ( '' === $value || null === $value ) && $product->get_parent_id() ) {
		$value = \get_post_meta( $product->get_parent_id(), $key, true );
	}
	return \is_scalar( $value ) ? \trim( (string) $value ) : '';
}

/**
 * Convertit une valeur de date (timestamp ou texte) en timestamp.
 *
 * @param string $value Valeur brute.
 * @return int|null
 */
function parse_date( string $value ): ?int {
	$value = \trim( $value );
	if ( '' === $value ) {
		return null;
	}
	if ( \ctype_digit( $value ) && \strlen( $value ) >= 9 ) {
		return (int) $value;
	}
	// Format français JJ/MM/AAAA.
	if ( \preg_match( '#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m ) ) {
		$value = \sprintf( '%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1] );
	}
	try {
		$date = new \DateTimeImmutable( $value, \wp_timezone() );
	} catch ( \Exception $e ) {
		return null;
	}
	return $date->getTimestamp();
}

/**
 * Date de sortie du produit (timestamp), ou null si aucune donnée.
 *
 * @param mixed $product Produit.
 * @return int|null
 */
function release_timestamp( $product = null ): ?int {
	$product = get_product( $product );
	if ( ! $product ) {
		return null;
	}

	$timestamp = null;
	foreach ( array( '_tcgf_release_date', '_wc_pre_orders_availability_datetime', '_ywpo_for_sale_date', '_pre_order_date' ) as $key ) {
		$timestamp = parse_date( product_meta( $product, $key ) );
		if ( null !== $timestamp ) {
			break;
		}
	}

	// Attribut produit « Date de sortie » (texte ou terme), si présent.
	if ( null === $timestamp ) {
		$attribute = (string) $product->get_attribute( 'date-de-sortie' );
		if ( '' !== $attribute ) {
			$timestamp = parse_date( \wp_strip_all_tags( $attribute ) );
		}
	}

	/**
	 * Filtre la date de sortie d'un produit.
	 *
	 * @param int|null    $timestamp Timestamp ou null.
	 * @param \WC_Product $product   Produit.
	 */
	$timestamp = \apply_filters( 'tcgforge_release_timestamp', $timestamp, $product );

	return \is_numeric( $timestamp ) && (int) $timestamp > 0 ? (int) $timestamp : null;
}

/**
 * Le produit est-il en précommande ?
 *
 * @param mixed $product Produit.
 * @return bool
 */
function is_preorder( $product = null ): bool {
	$product = get_product( $product );
	if ( ! $product ) {
		return false;
	}

	$flag = false;
	foreach ( array( '_tcgf_preorder', '_wc_pre_orders_enabled', '_ywpo_preorder', '_is_pre_order' ) as $key ) {
		if ( 'yes' === \strtolower( product_meta( $product, $key ) ) ) {
			$flag = true;
			break;
		}
	}

	// Une précommande dont la date de sortie est passée n'en est plus une.
	if ( $flag ) {
		$release = release_timestamp( $product );
		if ( null !== $release && $release < \time() ) {
			$flag = false;
		}
	}

	/**
	 * Filtre le statut de précommande d'un produit.
	 *
	 * @param bool        $flag    Statut.
	 * @param \WC_Product $product Produit.
	 */
	return (bool) \apply_filters( 'tcgforge_is_preorder', $flag, $product );
}

/**
 * Mention d'expédition approximative saisie sur le produit.
 *
 * @param mixed $product Produit.
 * @return string
 */
function shipping_estimate( $product = null ): string {
	$product = get_product( $product );
	if ( ! $product ) {
		return '';
	}
	$text = product_meta( $product, '_tcgf_shipping_estimate' );

	/**
	 * Filtre la mention d'expédition approximative.
	 *
	 * @param string      $text    Mention.
	 * @param \WC_Product $product Produit.
	 */
	return (string) \apply_filters( 'tcgforge_shipping_estimate', $text, $product );
}

/**
 * Le produit a-t-il été mis en ligne récemment ?
 *
 * @param mixed $product Produit.
 * @return bool
 */
function is_new( $product = null ): bool {
	$product = get_product( $product );
	if ( ! $product || ! $product->get_date_created() ) {
		return false;
	}
	/**
	 * Nombre de jours pendant lesquels un produit porte le badge « Nouveau ».
	 *
	 * @param int $days Jours.
	 */
	$days = (int) \apply_filters( 'tcgforge_new_badge_days', 30 );
	if ( $days <= 0 ) {
		return false;
	}
	return $product->get_date_created()->getTimestamp() >= ( \time() - $days * DAY_IN_SECONDS );
}

/**
 * Badges du produit, par ordre de priorité.
 *
 * @param mixed $product Produit.
 * @param int   $max     Nombre maximum de badges (0 = tous).
 * @return array<int,array{type:string,label:string}>
 */
function badges( $product = null, int $max = 0 ): array {
	$product = get_product( $product );
	if ( ! $product ) {
		return array();
	}

	$list = array();

	if ( ! $product->is_in_stock() ) {
		// Un produit en rupture ne porte que ce badge.
		$list[] = array(
			'type'  => 'rupture',
			'label' => \__( 'Rupture', 'g2rd-tcgforge-child' ),
		);
	} else {
		if ( is_preorder( $product ) ) {
			$list[] = array(
				'type'  => 'precommande',
				'label' => \__( 'Précommande', 'g2rd-tcgforge-child' ),
			);
		}
		if ( $product->is_on_sale() ) {
			$list[] = array(
				'type'  => 'promotion',
				'label' => \__( 'Promotion', 'g2rd-tcgforge-child' ),
			);
		}
		if ( is_new( $product ) ) {
			$list[] = array(
				'type'  => 'nouveau',
				'label' => \__( 'Nouveau', 'g2rd-tcgforge-child' ),
			);
		}
		if ( $product->managing_stock() ) {
			$quantity = $product->get_stock_quantity();
			$low      = \function_exists( 'wc_get_low_stock_amount' ) ? (int) \wc_get_low_stock_amount( $product ) : 0;
			if ( null !== $quantity && $quantity > 0 && $low > 0 && $quantity <= $low ) {
				$list[] = array(
					'type'  => 'stock-faible',
					'label' => \__( 'Stock faible', 'g2rd-tcgforge-child' ),
				);
			}
		}
		if ( 'onbackorder' === $product->get_stock_status() && ! is_preorder( $product ) ) {
			$list[] = array(
				'type'  => 'sur-commande',
				'label' => \__( 'Sur commande', 'g2rd-tcgforge-child' ),
			);
		}
	}

	/**
	 * Filtre la liste des badges d'un produit.
	 *
	 * @param array       $list    Badges.
	 * @param \WC_Product $product Produit.
	 */
	$list = (array) \apply_filters( 'tcgforge_product_badges', $list, $product );

	return $max > 0 ? \array_slice( $list, 0, $max ) : $list;
}

/**
 * Correspondance entre les caractéristiques affichées et les attributs WooCommerce.
 * Le premier attribut renseigné l'emporte. Les noms sont les slugs d'attributs
 * (sans le préfixe pa_) ou les noms d'attributs personnalisés.
 *
 * @return array<string,array{label:string,attributes:string[]}>
 */
function fact_map(): array {
	$map = array(
		'jeu'       => array(
			'label'      => \__( 'Jeu', 'g2rd-tcgforge-child' ),
			'attributes' => array( 'jeu', 'licence', 'univers', 'franchise' ),
		),
		'extension' => array(
			'label'      => \__( 'Extension', 'g2rd-tcgforge-child' ),
			'attributes' => array( 'extension', 'serie', 'set' ),
		),
		'langue'    => array(
			'label'      => \__( 'Langue', 'g2rd-tcgforge-child' ),
			'attributes' => array( 'langue', 'language' ),
		),
		'type'      => array(
			'label'      => \__( 'Produit', 'g2rd-tcgforge-child' ),
			'attributes' => array( 'type-de-produit', 'type-produit', 'produit', 'type' ),
		),
	);

	/**
	 * Filtre la correspondance caractéristiques → attributs WooCommerce.
	 *
	 * @param array $map Correspondance.
	 */
	return (array) \apply_filters( 'tcgforge_fact_map', $map );
}

/**
 * Caractéristiques TCG du produit, dans l'ordre jeu, extension, langue, type.
 *
 * @param mixed    $product Produit.
 * @param string[] $only    Clés à retourner (vide = toutes).
 * @return array<string,array{label:string,value:string}>
 */
function facts( $product = null, array $only = array() ): array {
	$product = get_product( $product );
	if ( ! $product ) {
		return array();
	}

	// Les attributs sont portés par le produit parent d'une variation.
	$source = $product;
	if ( $product->get_parent_id() ) {
		$parent = \wc_get_product( $product->get_parent_id() );
		if ( $parent instanceof \WC_Product ) {
			$source = $parent;
		}
	}

	$facts = array();
	foreach ( fact_map() as $key => $definition ) {
		if ( $only && ! \in_array( $key, $only, true ) ) {
			continue;
		}
		foreach ( (array) ( $definition['attributes'] ?? array() ) as $attribute ) {
			$value = \trim( \wp_strip_all_tags( (string) $source->get_attribute( (string) $attribute ) ) );
			if ( '' !== $value ) {
				$facts[ $key ] = array(
					'label' => (string) ( $definition['label'] ?? $key ),
					'value' => $value,
				);
				break;
			}
		}
	}

	// Sans attribut « jeu », on lit la marque WooCommerce si elle existe.
	if ( ! isset( $facts['jeu'] ) && ( ! $only || \in_array( 'jeu', $only, true ) ) && \taxonomy_exists( 'product_brand' ) ) {
		$brands = \get_the_terms( $source->get_id(), 'product_brand' );
		if ( \is_array( $brands ) && $brands ) {
			$facts = array(
				'jeu' => array(
					'label' => \__( 'Licence', 'g2rd-tcgforge-child' ),
					'value' => \implode( ', ', \wp_list_pluck( $brands, 'name' ) ),
				),
			) + $facts;
		}
	}

	/**
	 * Filtre les caractéristiques d'un produit.
	 *
	 * @param array       $facts   Caractéristiques.
	 * @param \WC_Product $product Produit.
	 */
	return (array) \apply_filters( 'tcgforge_product_facts', $facts, $product );
}

/**
 * HTML des badges.
 *
 * @param mixed $product Produit.
 * @param int   $max     Nombre maximum de badges.
 * @return string
 */
function badges_html( $product = null, int $max = 0 ): string {
	$items = badges( $product, $max );
	if ( ! $items ) {
		return '';
	}
	$html = '';
	foreach ( $items as $badge ) {
		$html .= \sprintf(
			'<li class="tcgf-badge tcgf-badge--%1$s">%2$s</li>',
			\esc_attr( \sanitize_html_class( $badge['type'] ) ),
			\esc_html( $badge['label'] )
		);
	}
	return '<ul class="tcgf-badges" aria-label="' . \esc_attr__( 'Statut du produit', 'g2rd-tcgforge-child' ) . '">' . $html . '</ul>';
}

/**
 * HTML des caractéristiques, en étiquettes (cartes) ou en liste (fiche).
 *
 * @param mixed    $product Produit.
 * @param string   $display « chips » ou « liste ».
 * @param string[] $only    Clés à afficher.
 * @return string
 */
function facts_html( $product = null, string $display = 'chips', array $only = array() ): string {
	$items = facts( $product, $only );
	if ( ! $items ) {
		return '';
	}

	if ( 'liste' === $display ) {
		$html = '';
		foreach ( $items as $key => $fact ) {
			$html .= \sprintf(
				'<div class="tcgf-facts__row tcgf-facts__row--%1$s"><dt>%2$s</dt><dd>%3$s</dd></div>',
				\esc_attr( \sanitize_html_class( (string) $key ) ),
				\esc_html( $fact['label'] ),
				\esc_html( $fact['value'] )
			);
		}
		return '<dl class="tcgf-facts tcgf-facts--liste">' . $html . '</dl>';
	}

	$html = '';
	foreach ( $items as $key => $fact ) {
		$html .= \sprintf(
			'<li class="tcgf-facts__chip tcgf-facts__chip--%1$s"><span class="screen-reader-text">%2$s : </span>%3$s</li>',
			\esc_attr( \sanitize_html_class( (string) $key ) ),
			\esc_html( $fact['label'] ),
			\esc_html( $fact['value'] )
		);
	}
	return '<ul class="tcgf-facts tcgf-facts--chips">' . $html . '</ul>';
}

/**
 * HTML de la disponibilité d'une précommande : date de sortie et expédition.
 * Ne rend rien si aucune donnée n'est renseignée.
 *
 * @param mixed $product Produit.
 * @param bool  $compact Version courte pour les cartes.
 * @return string
 */
function release_html( $product = null, bool $compact = false ): string {
	$product = get_product( $product );
	if ( ! $product ) {
		return '';
	}

	$preorder = is_preorder( $product );
	$release  = release_timestamp( $product );
	$shipping = shipping_estimate( $product );

	// Une date passée n'apporte rien sur une carte, et rien du tout hors précommande.
	$future = null !== $release && $release > \time();

	if ( $compact ) {
		if ( ! $preorder || ! $future ) {
			return '';
		}
		return \sprintf(
			'<p class="tcgf-release tcgf-release--compact">%s <time datetime="%s">%s</time></p>',
			\esc_html__( 'Sortie le', 'g2rd-tcgforge-child' ),
			\esc_attr( \wp_date( 'Y-m-d', $release ) ),
			\esc_html( \wp_date( 'j M Y', $release ) )
		);
	}

	if ( ! $preorder && ! $release && '' === $shipping ) {
		return '';
	}

	$rows = '';
	if ( $preorder ) {
		$rows .= '<p class="tcgf-release__status">' . \esc_html__( 'Produit en précommande', 'g2rd-tcgforge-child' ) . '</p>';
	}
	if ( null !== $release ) {
		$rows .= \sprintf(
			'<p class="tcgf-release__row"><span class="tcgf-release__label">%s</span> <time datetime="%s">%s</time></p>',
			$future ? \esc_html__( 'Date de sortie annoncée', 'g2rd-tcgforge-child' ) : \esc_html__( 'Date de sortie', 'g2rd-tcgforge-child' ),
			\esc_attr( \wp_date( 'Y-m-d', $release ) ),
			\esc_html( \wp_date( 'j F Y', $release ) )
		);
	}
	if ( '' !== $shipping ) {
		$rows .= \sprintf(
			'<p class="tcgf-release__row"><span class="tcgf-release__label">%s</span> %s</p>',
			\esc_html__( 'Expédition', 'g2rd-tcgforge-child' ),
			\esc_html( $shipping )
		);
	}
	if ( $preorder ) {
		$rows .= '<p class="tcgf-release__note">' . \esc_html__( 'La date dépend de l\'éditeur et du distributeur. Elle évolue parfois avant la sortie.', 'g2rd-tcgforge-child' ) . '</p>';
	}

	return '<div class="tcgf-release' . ( $preorder ? ' tcgf-release--precommande' : '' ) . '">' . $rows . '</div>';
}

/**
 * Champs produit dans l'onglet Général : précommande, date de sortie, expédition.
 * Simple saisie de données. Aucun moteur de précommande n'est développé ici.
 */
\add_action(
	'woocommerce_product_options_general_product_data',
	function (): void {
		if ( ! \function_exists( 'woocommerce_wp_checkbox' ) ) {
			return;
		}
		echo '<div class="options_group">';
		\woocommerce_wp_checkbox(
			array(
				'id'          => '_tcgf_preorder',
				'label'       => \__( 'Précommande', 'g2rd-tcgforge-child' ),
				'description' => \__( 'Affiche le badge et la mention de précommande sur le site.', 'g2rd-tcgforge-child' ),
			)
		);
		\woocommerce_wp_text_input(
			array(
				'id'          => '_tcgf_release_date',
				'label'       => \__( 'Date de sortie', 'g2rd-tcgforge-child' ),
				'type'        => 'date',
				'desc_tip'    => true,
				'description' => \__( 'Date annoncée par l\'éditeur. Laisser vide si elle n\'est pas connue.', 'g2rd-tcgforge-child' ),
			)
		);
		\woocommerce_wp_text_input(
			array(
				'id'          => '_tcgf_shipping_estimate',
				'label'       => \__( 'Expédition estimée', 'g2rd-tcgforge-child' ),
				'desc_tip'    => true,
				'description' => \__( 'Texte libre affiché sur la fiche, par exemple « À partir du 14 novembre ».', 'g2rd-tcgforge-child' ),
			)
		);
		echo '</div>';
	}
);

/**
 * Enregistrement des champs. Le nonce et les droits sont vérifiés par
 * WooCommerce avant le déclenchement de ce crochet.
 *
 * @param \WC_Product $product Produit en cours d'enregistrement.
 */
\add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ): void {
		if ( ! $product instanceof \WC_Product || ! \current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce vérifié par WC_Meta_Box_Product_Data.
		$preorder = isset( $_POST['_tcgf_preorder'] ) ? 'yes' : '';
		$date     = isset( $_POST['_tcgf_release_date'] ) ? \sanitize_text_field( \wp_unslash( $_POST['_tcgf_release_date'] ) ) : '';
		$shipping = isset( $_POST['_tcgf_shipping_estimate'] ) ? \sanitize_text_field( \wp_unslash( $_POST['_tcgf_shipping_estimate'] ) ) : '';
		// phpcs:enable

		if ( '' !== $date && ! \preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = '';
		}

		$product->update_meta_data( '_tcgf_preorder', $preorder );
		$product->update_meta_data( '_tcgf_release_date', $date );
		$product->update_meta_data( '_tcgf_shipping_estimate', $shipping );
	}
);
