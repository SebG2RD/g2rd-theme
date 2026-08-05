<?php
/**
 * MCP WooCommerce Variations — reading and editing product variations
 *
 * The product tools stop at the parent. On a variable product the price, SKU and
 * stock live on the variations, so g2rd_get-woo-product returns empty strings for
 * regular_price and sale_price, and `price` holds only the range floor.
 *
 * Real case that motivated this layer: a product page advertising "500 g jar only,
 * 12 €" while the shop showed "from 7 €" — a 250 g variation still existed and was
 * still purchasable. Nothing in the tooling could show it, let alone remove it.
 *
 * Design notes
 * ------------
 * Prices are DECIMAL strings in the shop currency, never cents — the same contract
 * as g2rd_update-woo-product, enforced by reusing McpWooProducts::normalize_price()
 * rather than a copy that could drift. Amounts are never cast to float for
 * comparison or storage: "7.10" and "7.1" are different strings to WooCommerce, and
 * 0.1 + 0.2 is not 0.3.
 *
 * Every write invalidates the parent's product transients. Without that the shop
 * keeps showing the stale price range, which reads as a bug that is not one.
 *
 * @package    G2RD
 * @since      1.31.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Read/write access to WooCommerce product variations.
 */
class McpWooVariations {

	/** Stock status values accepted on a variation. */
	public const STOCK_STATUSES = [ 'instock', 'outofstock', 'onbackorder' ];

	/** Backorder policy values. */
	public const BACKORDERS = [ 'no', 'notify', 'yes' ];

	/**
	 * Reports whether WooCommerce is usable.
	 *
	 * Delegates to the product layer on purpose: two divergent probes for the same
	 * question is how they end up disagreeing.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( '\G2RD\McpWooProducts' )
			&& McpWooProducts::is_available()
			&& \class_exists( '\WC_Product_Variation' );
	}

	/**
	 * Error payload used when WooCommerce is missing.
	 *
	 * @return array{ok: false, error: string}
	 */
	private static function unavailable(): array {
		return [
			'ok'    => false,
			'error' => 'WooCommerce is not active on this site. Variation tools require WooCommerce 3.0 or later.',
		];
	}

	// ── Lecture ───────────────────────────────────────────────────────────────

	/**
	 * Lists the variations of a variable product.
	 *
	 * @param array<string, mixed> $args Tool arguments (product_id required).
	 * @return array{ok: bool, error?: string, variations?: array, total?: int, product_type?: string}
	 */
	public static function list_variations( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$product_id = \absint( $args['product_id'] ?? 0 );
		$parent     = \wc_get_product( $product_id );

		if ( ! $parent ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Product %d does not exist, or is not a WooCommerce product.', $product_id ),
			];
		}

		// Message explicite plutôt qu'un tableau vide : « aucune variation » et
		// « ce produit n'en a pas par nature » sont deux réponses différentes.
		if ( ! $parent->is_type( 'variable' ) ) {
			return [
				'ok'           => false,
				'product_type' => $parent->get_type(),
				'error'        => \sprintf(
					'Product %d is of type "%s", which has no variations. Price and stock live on the product itself — use g2rd_get-woo-product.',
					$product_id,
					$parent->get_type()
				),
			];
		}

		$children = $parent->get_children();
		$total    = \count( $children );

		$per_page = \min( 100, \max( 1, \absint( $args['per_page'] ?? 50 ) ) );
		$page     = \max( 1, \absint( $args['page'] ?? 1 ) );
		$slice    = \array_slice( $children, ( $page - 1 ) * $per_page, $per_page );

		$variations = [];

		foreach ( $slice as $variation_id ) {
			$row = self::describe_variation( (int) $variation_id, $parent );

			if ( null !== $row ) {
				$variations[] = $row;
			}
		}

		return [
			'ok'           => true,
			'product_id'   => $product_id,
			'product_name' => $parent->get_name(),
			'product_type' => $parent->get_type(),
			'variations'   => $variations,
			'total'        => $total,
			'total_pages'  => (int) \ceil( $total / $per_page ),
		];
	}

	/**
	 * Returns one variation with its parent reference.
	 *
	 * @param int $variation_id Variation ID.
	 * @return array{ok: bool, error?: string, variation?: array<string, mixed>}
	 */
	public static function get_variation( int $variation_id ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$variation = \wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Variation %d does not exist.', $variation_id ),
			];
		}

		$parent = \wc_get_product( $variation->get_parent_id() );
		$row    = self::describe_variation( $variation_id, $parent ?: null );

		if ( null === $row ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Variation %d could not be read.', $variation_id ),
			];
		}

		return [
			'ok'        => true,
			'variation' => $row,
		];
	}

	/**
	 * Builds the payload describing one variation.
	 *
	 * @param int         $variation_id Variation ID.
	 * @param object|null $parent       Parent product, when already loaded.
	 * @return array<string, mixed>|null
	 */
	private static function describe_variation( int $variation_id, $parent = null ): ?array {
		$variation = \wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return null;
		}

		$parent_id = $variation->get_parent_id();

		if ( null === $parent ) {
			$parent = \wc_get_product( $parent_id );
		}

		/*
		 * Le stock existe à deux niveaux. Renvoyer un stock_quantity nul sans
		 * dire lequel s'applique laisse l'appelant incapable de distinguer
		 * « illimité » de « hérité du parent ».
		 */
		if ( $variation->get_manage_stock() ) {
			$stock_source = 'variation';
		} elseif ( $parent && $parent->get_manage_stock() ) {
			$stock_source = 'parent';
		} else {
			$stock_source = 'none';
		}

		$stock_quantity = 'parent' === $stock_source && $parent
			? $parent->get_stock_quantity()
			: $variation->get_stock_quantity();

		return [
			'variation_id'   => $variation_id,
			'parent_id'      => $parent_id,
			'attributes'     => self::readable_attributes( $variation ),
			'sku'            => $variation->get_sku(),
			// Chaînes décimales, jamais de float : « 7.10 » et « 7.1 » ne sont
			// pas la même valeur pour WooCommerce.
			'regular_price'  => (string) $variation->get_regular_price(),
			'sale_price'     => (string) $variation->get_sale_price(),
			'price'          => (string) $variation->get_price(),
			'manage_stock'   => (bool) $variation->get_manage_stock(),
			'stock_source'   => $stock_source,
			'stock_quantity' => $stock_quantity,
			'stock_status'   => $variation->get_stock_status(),
			'backorders'     => $variation->get_backorders(),
			'weight'         => (string) $variation->get_weight(),
			'dimensions'     => [
				'length' => (string) $variation->get_length(),
				'width'  => (string) $variation->get_width(),
				'height' => (string) $variation->get_height(),
			],
			'image_id'       => (int) $variation->get_image_id(),
			'purchasable'    => (bool) $variation->is_purchasable(),
			'on_sale'        => (bool) $variation->is_on_sale(),
			// Une variation « private » reste en base mais n'est pas achetable.
			'enabled'        => 'publish' === $variation->get_status(),
		];
	}

	/**
	 * Resolves a variation's attributes into readable labels.
	 *
	 * WooCommerce stores slugs (`pa_poids` => `500-g`). Returning those raw makes
	 * the output unusable for a human and for an agent writing product copy, so
	 * both the raw pair and the readable one are provided.
	 *
	 * @param object $variation WC_Product_Variation.
	 * @return array<int, array<string, string>>
	 */
	private static function readable_attributes( $variation ): array {
		$out = [];

		foreach ( (array) $variation->get_attributes() as $taxonomy => $slug ) {
			$taxonomy = (string) $taxonomy;
			$slug     = (string) $slug;

			$label = \function_exists( 'wc_attribute_label' )
				? (string) \wc_attribute_label( $taxonomy )
				: $taxonomy;

			$value = $slug;

			// Attribut global : le slug renvoie à un terme dont le nom est le
			// libellé affiché en boutique (« 500 g » et non « 500-g »).
			if ( \taxonomy_exists( $taxonomy ) ) {
				$term = \get_term_by( 'slug', $slug, $taxonomy );

				if ( $term && ! \is_wp_error( $term ) ) {
					$value = $term->name;
				}
			}

			$out[] = [
				'taxonomy' => $taxonomy,
				'label'    => $label,
				'slug'     => $slug,
				'value'    => $value,
			];
		}

		return $out;
	}

	// ── Écriture ──────────────────────────────────────────────────────────────

	/**
	 * Updates a variation, writing only the fields actually supplied.
	 *
	 * @param array<string, mixed> $args Tool arguments (variation_id required).
	 * @return array{ok: bool, error?: string, variation?: array<string, mixed>}
	 */
	public static function update_variation( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$variation_id = \absint( $args['variation_id'] ?? 0 );
		$variation    = \wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Variation %d does not exist.', $variation_id ),
			];
		}

		$check = self::validate( $args, $variation );

		if ( ! $check['ok'] ) {
			return [
				'ok'     => false,
				'errors' => $check['errors'],
				'error'  => \implode( ' | ', $check['errors'] ),
			];
		}

		try {
			self::apply_fields( $variation, $check['data'] );
			$variation->save();
		} catch ( \Throwable $e ) {
			return [
				'ok'    => false,
				'error' => 'Variation update failed: ' . $e->getMessage(),
			];
		}

		self::refresh_parent( (int) $variation->get_parent_id() );

		// État après écriture : l'appelant vérifie sans second appel.
		$after = self::get_variation( $variation_id );

		return [
			'ok'        => true,
			'variation' => $after['variation'] ?? null,
		];
	}

	/**
	 * Validates the writable fields of a variation payload.
	 *
	 * Publique comme son homologue McpWooProducts::validate() : le contrat de
	 * prix doit rester testable sans WooCommerce installé.
	 *
	 * @param array<string, mixed> $args      Incoming payload.
	 * @param object|null          $variation Existing variation, for price comparison.
	 * @return array{ok: bool, errors?: string[], data?: array<string, mixed>}
	 */
	public static function validate( array $args, $variation = null ): array {
		$errors = [];
		$data   = [];

		foreach ( [ 'regular_price', 'sale_price' ] as $field ) {
			if ( ! \array_key_exists( $field, $args ) ) {
				continue;
			}

			if ( '' === $args[ $field ] || null === $args[ $field ] ) {
				/*
				 * Vider le prix promo est une intention valide. Vider le prix
				 * NORMAL rendrait la variation non achetable sans le dire, et
				 * neutraliserait au passage la comparaison promo/normal ci-dessous.
				 * McpWooProducts ignore déjà un prix normal vide : on refuse ici
				 * explicitement plutôt que d'ignorer en silence.
				 */
				if ( 'regular_price' === $field ) {
					$errors[] = 'regular_price cannot be emptied: a variation without a regular price is not purchasable. Disable it with enabled=false instead.';
					continue;
				}

				$data[ $field ] = '';
				continue;
			}

			$price = McpWooProducts::normalize_price( $args[ $field ] );

			if ( null === $price ) {
				$errors[] = \sprintf(
					'%s must be a decimal amount in the shop currency, such as "12.00" — NOT a number of cents. Received: %s.',
					$field,
					\is_scalar( $args[ $field ] ) ? (string) \wp_json_encode( $args[ $field ] ) : \gettype( $args[ $field ] )
				);
				continue;
			}

			$data[ $field ] = $price;
		}

		/*
		 * Le prix promo est comparé au prix normal EFFECTIF : celui fourni s'il
		 * l'est, sinon celui déjà en base. Ne comparer qu'aux valeurs transmises
		 * laisserait passer un promo supérieur au prix existant.
		 */
		$effective_regular = $data['regular_price']
			?? ( $variation ? (string) $variation->get_regular_price() : '' );

		if ( ! empty( $data['sale_price'] ) && '' !== $effective_regular
			&& (float) $data['sale_price'] >= (float) $effective_regular ) {
			$errors[] = \sprintf(
				'sale_price (%s) must be lower than regular_price (%s), otherwise WooCommerce ignores it and the variation keeps its old price.',
				$data['sale_price'],
				$effective_regular
			);
		}

		foreach ( [ 'stock_status' => self::STOCK_STATUSES, 'backorders' => self::BACKORDERS ] as $field => $accepted ) {
			if ( ! isset( $args[ $field ] ) ) {
				continue;
			}

			$value = \sanitize_key( (string) $args[ $field ] );

			if ( ! \in_array( $value, $accepted, true ) ) {
				$errors[] = \sprintf(
					'%s "%s" is invalid. Accepted values: %s.',
					$field,
					$value,
					\implode( ', ', $accepted )
				);
				continue;
			}

			$data[ $field ] = $value;
		}

		if ( \array_key_exists( 'sku', $args ) ) {
			$data['sku'] = \sanitize_text_field( (string) $args['sku'] );
		}

		if ( \array_key_exists( 'weight', $args ) ) {
			$data['weight'] = \sanitize_text_field( (string) $args['weight'] );
		}

		if ( \array_key_exists( 'image_id', $args ) ) {
			$data['image_id'] = \absint( $args['image_id'] );
		}

		if ( \array_key_exists( 'manage_stock', $args ) ) {
			$data['manage_stock'] = (bool) $args['manage_stock'];
		}

		if ( \array_key_exists( 'stock_quantity', $args ) ) {
			$data['stock_quantity'] = (int) $args['stock_quantity'];
		}

		if ( \array_key_exists( 'enabled', $args ) ) {
			$data['enabled'] = (bool) $args['enabled'];
		}

		if ( [] !== $errors ) {
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		return [
			'ok'   => true,
			'data' => $data,
		];
	}

	/**
	 * Applies validated fields to a variation instance.
	 *
	 * Only keys present in $data are written: a field the caller did not mention
	 * is never reset.
	 *
	 * @param object               $variation WC_Product_Variation.
	 * @param array<string, mixed> $data      Validated payload.
	 * @return void
	 */
	private static function apply_fields( $variation, array $data ): void {
		$setters = [
			'regular_price'  => 'set_regular_price',
			'sale_price'     => 'set_sale_price',
			'sku'            => 'set_sku',
			'weight'         => 'set_weight',
			'image_id'       => 'set_image_id',
			'manage_stock'   => 'set_manage_stock',
			'stock_quantity' => 'set_stock_quantity',
			'stock_status'   => 'set_stock_status',
			'backorders'     => 'set_backorders',
		];

		foreach ( $setters as $field => $setter ) {
			if ( \array_key_exists( $field, $data ) && \method_exists( $variation, $setter ) ) {
				$variation->$setter( $data[ $field ] );
			}
		}

		// « enabled » n'est pas un champ WooCommerce : c'est le statut du post.
		if ( \array_key_exists( 'enabled', $data ) ) {
			$variation->set_status( $data['enabled'] ? 'publish' : 'private' );
		}
	}

	/**
	 * Creates a variation on a variable product.
	 *
	 * @param array<string, mixed> $args Tool arguments (product_id, attributes, regular_price).
	 * @throws \RuntimeException When WooCommerce refuses to persist the variation.
	 * @return array{ok: bool, error?: string, errors?: string[], variation?: array<string, mixed>}
	 */
	public static function create_variation( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$product_id = \absint( $args['product_id'] ?? 0 );
		$parent     = \wc_get_product( $product_id );

		if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Product %d is not a variable product, so it cannot hold variations.', $product_id ),
			];
		}

		$attributes = isset( $args['attributes'] ) && \is_array( $args['attributes'] ) ? $args['attributes'] : [];

		if ( [] === $attributes ) {
			return [
				'ok'    => false,
				'error' => 'attributes is required: a variation is identified by its attribute combination.',
			];
		}

		$declared   = \array_change_key_case( (array) $parent->get_variation_attributes(), \CASE_LOWER );
		$normalised = [];

		foreach ( $attributes as $taxonomy => $value ) {
			$taxonomy = \strtolower( (string) $taxonomy );

			if ( ! \array_key_exists( $taxonomy, $declared ) ) {
				return [
					'ok'    => false,
					'error' => \sprintf(
						'Attribute "%s" is not declared for variations on product %d. Declared attributes: %s.',
						$taxonomy,
						$product_id,
						\implode( ', ', \array_keys( $declared ) )
					),
				];
			}

			$value = (string) $value;

			/*
			 * La valeur doit exister parmi les options déclarées. Une coquille
			 * s'enregistrerait sans erreur et produirait une variation que les
			 * sélecteurs de la boutique ne peuvent jamais faire correspondre.
			 */
			$allowed = \array_map( 'strval', (array) $declared[ $taxonomy ] );

			if ( ! \in_array( $value, $allowed, true ) ) {
				return [
					'ok'    => false,
					'error' => \sprintf(
						'Value "%s" is not an option of attribute "%s" on product %d. Accepted values: %s.',
						$value,
						$taxonomy,
						$product_id,
						\implode( ', ', $allowed )
					),
				];
			}

			$normalised[ $taxonomy ] = $value;
		}

		/*
		 * Tous les attributs de variation doivent être fournis. WooCommerce
		 * interprète un attribut absent comme « n'importe quelle valeur », ce qui
		 * crée une variation chevauchant les autres — et la détection de doublon
		 * ci-dessous, qui compare des combinaisons complètes, la manquerait.
		 */
		$missing = \array_diff( \array_keys( $declared ), \array_keys( $normalised ) );

		if ( [] !== $missing ) {
			return [
				'ok'    => false,
				'error' => \sprintf(
					'Missing attribute(s): %s. Every variation attribute declared on product %d must be supplied, otherwise WooCommerce treats the missing one as "any value" and the variation overlaps the existing ones.',
					\implode( ', ', $missing ),
					$product_id
				),
			];
		}

		if ( null !== self::find_matching_variation( $parent, $normalised ) ) {
			return [
				'ok'    => false,
				'error' => 'A variation with this exact attribute combination already exists. Update it with g2rd_update-woo-variation instead.',
			];
		}

		$check = self::validate( $args, null );

		if ( ! $check['ok'] ) {
			return [
				'ok'     => false,
				'errors' => $check['errors'],
				'error'  => \implode( ' | ', $check['errors'] ),
			];
		}

		if ( empty( $check['data']['regular_price'] ) ) {
			return [
				'ok'    => false,
				'error' => 'regular_price is required: a variation without a price is not purchasable.',
			];
		}

		$variation_id = 0;

		try {
			$variation = new \WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( $normalised );
			$variation->set_status( 'publish' );

			self::apply_fields( $variation, $check['data'] );

			$variation_id = (int) $variation->save();

			if ( $variation_id <= 0 ) {
				throw new \RuntimeException( 'WooCommerce refused to save the variation.' );
			}
		} catch ( \Throwable $e ) {
			if ( $variation_id > 0 ) {
				\wp_delete_post( $variation_id, true );
			}

			return [
				'ok'    => false,
				'error' => 'Variation creation failed and was rolled back: ' . $e->getMessage(),
			];
		}

		self::refresh_parent( $product_id );

		$after = self::get_variation( $variation_id );

		return [
			'ok'        => true,
			'variation' => $after['variation'] ?? null,
		];
	}

	/**
	 * Finds an existing variation matching an attribute combination.
	 *
	 * @param object                $parent     Parent product.
	 * @param array<string, string> $attributes Normalised attributes.
	 * @return int|null Variation ID, or null when none matches.
	 */
	private static function find_matching_variation( $parent, array $attributes ): ?int {
		foreach ( (array) $parent->get_children() as $child_id ) {
			$child = \wc_get_product( (int) $child_id );

			if ( ! $child ) {
				continue;
			}

			$existing = \array_change_key_case( (array) $child->get_attributes(), \CASE_LOWER );

			if ( \count( $existing ) !== \count( $attributes ) ) {
				continue;
			}

			$same = true;

			foreach ( $attributes as $taxonomy => $value ) {
				if ( ! isset( $existing[ $taxonomy ] ) || (string) $existing[ $taxonomy ] !== $value ) {
					$same = false;
					break;
				}
			}

			if ( $same ) {
				return (int) $child_id;
			}
		}

		return null;
	}

	/**
	 * Trashes a variation, or deletes it permanently when forced.
	 *
	 * @param array<string, mixed> $args Tool arguments (variation_id, force).
	 * @return array{ok: bool, error?: string, variation_id?: int, remaining?: int, warning?: string}
	 */
	public static function delete_variation( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$variation_id = \absint( $args['variation_id'] ?? 0 );
		$variation    = \wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Variation %d does not exist.', $variation_id ),
			];
		}

		$parent_id = (int) $variation->get_parent_id();
		$parent    = \wc_get_product( $parent_id );
		$siblings  = $parent ? \count( (array) $parent->get_children() ) : 0;

		/*
		 * Un produit variable sans aucune variation n'est plus achetable : il
		 * disparaît des paniers sans que rien ne le signale en boutique.
		 */
		if ( $siblings <= 1 ) {
			return [
				'ok'    => false,
				'error' => \sprintf(
					'Variation %d is the last one on product %d. Deleting it would leave a variable product with no purchasable variation. Convert the product to "simple" first, or add another variation.',
					$variation_id,
					$parent_id
				),
			];
		}

		$force = ! empty( $args['force'] );

		try {
			$variation->delete( $force );
		} catch ( \Throwable $e ) {
			return [
				'ok'    => false,
				'error' => 'Variation deletion failed: ' . $e->getMessage(),
			];
		}

		self::refresh_parent( $parent_id );

		$remaining = $siblings - 1;

		$result = [
			'ok'           => true,
			'variation_id' => $variation_id,
			'parent_id'    => $parent_id,
			'permanent'    => $force,
			'remaining'    => $remaining,
		];

		// Signalé sans bloquer : un produit variable à une seule variation est un
		// état valide, mais rarement celui qu'on veut.
		if ( 1 === $remaining ) {
			$result['warning'] = \sprintf(
				'Product %d now has a single variation. That is valid, but a one-variation variable product is usually meant to be a simple product.',
				$parent_id
			);
		}

		return $result;
	}

	/**
	 * Invalidates the parent's caches after a variation write.
	 *
	 * Without this the shop keeps displaying the previous price range — which
	 * gets reported as a bug when it is only a stale cache. The page cache is
	 * purged too, since LiteSpeed and friends serve the old range otherwise.
	 *
	 * @param int $parent_id Parent product ID.
	 * @return void
	 */
	private static function refresh_parent( int $parent_id ): void {
		if ( $parent_id <= 0 ) {
			return;
		}

		if ( \function_exists( 'wc_delete_product_transients' ) ) {
			\wc_delete_product_transients( $parent_id );
		}

		// Laisse WooCommerce recalculer la fourchette de prix du parent.
		if ( \class_exists( '\WC_Product_Variable' ) && \method_exists( '\WC_Product_Variable', 'sync' ) ) {
			\WC_Product_Variable::sync( $parent_id );
		}

		// Pas de wp_cache_flush() : vider tout le cache objet du site pour une
		// variation pénaliserait chaque requête en cours. Les transients ci-dessus
		// ciblent déjà le produit concerné ; seul le cache PAGE doit suivre, car
		// il sert encore l'ancienne fourchette de prix.
		if ( \function_exists( 'rocket_clean_domain' ) ) {
			\rocket_clean_domain();
		}

		if ( \has_action( 'litespeed_purge_all' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache third-party hook
			\do_action( 'litespeed_purge_all' );
		}
	}
}
