<?php
/**
 * MCP WooCommerce Products — full product control
 *
 * Creates and edits WooCommerce products through the plugin's CRUD classes
 * (WC_Product_Simple, WC_Product_Variable, WC_Product_Variation), never through
 * post meta, so WooCommerce hooks, lookup tables and future migrations stay valid.
 *
 * ⚠ Price unit differs from FluentCart, on purpose
 * ------------------------------------------------
 * WooCommerce stores prices as DECIMAL strings in the shop currency ("19.99"),
 * confirmed by WC_Product::set_regular_price( string $price ). FluentCart stores
 * CENTS as integers. The two product tools therefore have opposite contracts:
 *
 *   g2rd_create-product      (FluentCart)  price: 20000   → 200,00 €
 *   g2rd_create-woo-product  (WooCommerce) regular_price: "200.00" → 200,00 €
 *
 * An agent that carries the cents habit over would create a 20 000 € product.
 * Three guards: the schema says so explicitly, validation refuses malformed
 * amounts, and the confirmation e-mail shows the formatted price so an
 * administrator sees "20 000,00 €" before approving anything.
 *
 * @package    G2RD
 * @since      1.29.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Allowlisted read/write access to WooCommerce products.
 */
class McpWooProducts {

	/** WooCommerce product post type. */
	public const POST_TYPE = 'product';

	/** Product types this layer can create. */
	public const PRODUCT_TYPES = [ 'simple', 'variable', 'external', 'grouped' ];

	/** Post statuses a product may take. */
	public const STATUSES = [ 'publish', 'draft', 'pending', 'private' ];

	/** Catalog visibility values. Source: WC_Product::set_catalog_visibility(). */
	public const VISIBILITIES = [ 'visible', 'catalog', 'search', 'hidden' ];

	/** Stock status values. */
	public const STOCK_STATUSES = [ 'instock', 'outofstock', 'onbackorder' ];

	/** Backorder policy values. */
	public const BACKORDERS = [ 'no', 'notify', 'yes' ];

	/** Tax status values. */
	public const TAX_STATUSES = [ 'taxable', 'shipping', 'none' ];

	/**
	 * Reports whether WooCommerce is active and its CRUD classes are loadable.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( '\WooCommerce' )
			&& \class_exists( '\WC_Product_Simple' )
			&& \function_exists( 'wc_get_product' );
	}

	/**
	 * Returns the error payload used when WooCommerce is missing.
	 *
	 * @return array{ok: false, error: string}
	 */
	private static function unavailable(): array {
		return [
			'ok'    => false,
			'error' => 'WooCommerce is not active on this site. WooCommerce product tools require WooCommerce 3.0 or later.',
		];
	}

	// ── Validation ────────────────────────────────────────────────────────────

	/**
	 * Validates a create/update payload.
	 *
	 * Reports every problem at once, each with its accepted values, so an agent
	 * can correct them all in a single retry.
	 *
	 * @param array<string, mixed> $args     Incoming payload.
	 * @param bool                 $creating True for create (name required).
	 * @return array{ok: bool, errors?: string[], data?: array<string, mixed>}
	 */
	public static function validate( array $args, bool $creating = true ): array {
		$errors = [];
		$data   = [];

		$name = \sanitize_text_field( (string) ( $args['name'] ?? '' ) );
		if ( $creating && '' === $name ) {
			$errors[] = 'name is required and must be a non-empty string.';
		}
		$data['name'] = $name;

		$data['type'] = \sanitize_key( (string) ( $args['type'] ?? 'simple' ) );
		if ( ! \in_array( $data['type'], self::PRODUCT_TYPES, true ) ) {
			$errors[] = \sprintf(
				'type "%s" is invalid. Accepted values: %s.',
				$data['type'],
				\implode( ', ', self::PRODUCT_TYPES )
			);
		}

		$data['status'] = \sanitize_key( (string) ( $args['status'] ?? 'draft' ) );
		if ( ! \in_array( $data['status'], self::STATUSES, true ) ) {
			$errors[] = \sprintf(
				'status "%s" is invalid. Accepted values: %s.',
				$data['status'],
				\implode( ', ', self::STATUSES )
			);
		}

		// ── Prix ──────────────────────────────────────────────────────────────
		foreach ( [ 'regular_price', 'sale_price' ] as $field ) {
			if ( ! isset( $args[ $field ] ) || '' === $args[ $field ] ) {
				$data[ $field ] = '';
				continue;
			}

			$price = self::normalize_price( $args[ $field ] );

			if ( null === $price ) {
				// La clé doit rester définie : les vérifications suivantes la
				// lisent, et sortir sans l'initialiser produirait un accès à un
				// index indéfini au lieu du message d'erreur attendu.
				$data[ $field ] = '';

				$errors[] = \sprintf(
					'%s must be a decimal amount in the shop currency, such as "19.99" or "200.00" — NOT a number of cents. Received: %s.',
					$field,
					\is_scalar( $args[ $field ] ) ? (string) \wp_json_encode( $args[ $field ] ) : \gettype( $args[ $field ] )
				);
				continue;
			}

			$data[ $field ] = $price;
		}

		if ( $creating && '' === $data['regular_price'] && 'grouped' !== $data['type'] && 'external' !== $data['type'] ) {
			$errors[] = 'regular_price is required: a product without a price cannot be added to the cart.';
		}

		if ( '' !== $data['sale_price'] && '' !== $data['regular_price']
			&& (float) $data['sale_price'] >= (float) $data['regular_price'] ) {
			$errors[] = \sprintf(
				'sale_price (%s) must be lower than regular_price (%s), otherwise WooCommerce ignores it.',
				$data['sale_price'],
				$data['regular_price']
			);
		}

		// ── Énumérations ──────────────────────────────────────────────────────
		$enums = [
			'catalog_visibility' => self::VISIBILITIES,
			'stock_status'       => self::STOCK_STATUSES,
			'backorders'         => self::BACKORDERS,
			'tax_status'         => self::TAX_STATUSES,
		];

		foreach ( $enums as $field => $accepted ) {
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

		// ── Champs libres ─────────────────────────────────────────────────────
		$data['slug']              = isset( $args['slug'] ) ? \sanitize_title( (string) $args['slug'] ) : null;
		$data['description']       = isset( $args['description'] ) ? \wp_kses_post( (string) $args['description'] ) : null;
		$data['short_description'] = isset( $args['short_description'] ) ? \wp_kses_post( (string) $args['short_description'] ) : null;
		$data['sku']               = isset( $args['sku'] ) ? \sanitize_text_field( (string) $args['sku'] ) : null;
		$data['purchase_note']     = isset( $args['purchase_note'] ) ? \sanitize_textarea_field( (string) $args['purchase_note'] ) : null;
		$data['tax_class']         = isset( $args['tax_class'] ) ? \sanitize_text_field( (string) $args['tax_class'] ) : null;

		// ── Booléens ──────────────────────────────────────────────────────────
		foreach ( [ 'virtual', 'downloadable', 'featured', 'manage_stock', 'sold_individually', 'reviews_allowed' ] as $flag ) {
			$data[ $flag ] = isset( $args[ $flag ] ) ? (bool) $args[ $flag ] : null;
		}

		// ── Numériques ────────────────────────────────────────────────────────
		$data['stock_quantity'] = isset( $args['stock_quantity'] ) ? (int) $args['stock_quantity'] : null;
		$data['menu_order']     = isset( $args['menu_order'] ) ? (int) $args['menu_order'] : null;

		foreach ( [ 'weight', 'length', 'width', 'height' ] as $dimension ) {
			$data[ $dimension ] = isset( $args[ $dimension ] ) ? \sanitize_text_field( (string) $args[ $dimension ] ) : null;
		}

		// ── Médias et taxonomies ──────────────────────────────────────────────
		$data['image_id']          = isset( $args['image_id'] ) ? \absint( $args['image_id'] ) : null;
		$data['gallery_image_ids'] = isset( $args['gallery_image_ids'] )
			? \array_values( \array_filter( \array_map( 'absint', (array) $args['gallery_image_ids'] ) ) )
			: null;
		$data['categories']        = isset( $args['categories'] ) ? (array) $args['categories'] : null;
		$data['tags']              = isset( $args['tags'] ) ? (array) $args['tags'] : null;

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
	 * Normalises a price into WooCommerce's decimal string format.
	 *
	 * Accepts "19.99", "19,99", 19.99 and 20. Refuses anything else, so a
	 * malformed amount never reaches the shop silently.
	 *
	 * @param mixed $value Incoming price.
	 * @return string|null Normalised price, or null when unusable.
	 */
	private static function normalize_price( $value ): ?string {
		if ( ! \is_scalar( $value ) ) {
			return null;
		}

		// La virgule décimale française est tolérée : l'agent reprend souvent la
		// valeur telle qu'affichée sur le site.
		$candidate = \str_replace( ',', '.', \trim( (string) $value ) );

		if ( ! \preg_match( '/^\d+(\.\d{1,2})?$/', $candidate ) ) {
			return null;
		}

		return $candidate;
	}

	/**
	 * Renders a human summary for the confirmation e-mail.
	 *
	 * The formatted price is the last line of defence against a cents/decimal
	 * mix-up: an administrator seeing "20 000,00 €" for a 200 € product refuses.
	 *
	 * @param array<string, mixed> $args Raw tool arguments.
	 * @return string
	 */
	public static function summarize( array $args ): string {
		$check = self::validate( $args, empty( $args['product_id'] ) );

		if ( ! $check['ok'] ) {
			return \implode( "\n", $check['errors'] );
		}

		$data  = $check['data'];
		$lines = [];

		if ( ! empty( $args['product_id'] ) ) {
			$lines[] = '• Produit ID : ' . \absint( $args['product_id'] );
		}

		$lines[] = '• Nom : ' . ( '' !== $data['name'] ? $data['name'] : '(inchangé)' );
		$lines[] = '• Type : ' . $data['type'] . ' — Statut : ' . $data['status'];

		if ( '' !== $data['regular_price'] ) {
			$lines[] = '';
			$lines[] = 'PRIX qui sera enregistré :';
			$lines[] = '   • Prix normal : ' . self::describe_price( $data['regular_price'] );

			if ( '' !== $data['sale_price'] ) {
				$lines[] = '   • Prix promo  : ' . self::describe_price( $data['sale_price'] );
			}
		}

		if ( null !== $data['sku'] && '' !== $data['sku'] ) {
			$lines[] = '• UGS : ' . $data['sku'];
		}

		return \implode( "\n", $lines );
	}

	/**
	 * Formats a price for human reading.
	 *
	 * @param string $price Decimal price.
	 * @return string
	 */
	public static function describe_price( string $price ): string {
		return \number_format( (float) $price, 2, ',', ' ' ) . ' €';
	}

	// ── Write ─────────────────────────────────────────────────────────────────

	/**
	 * Creates a WooCommerce product.
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @throws \RuntimeException When WooCommerce refuses to persist the product.
	 * @return array{ok: bool, error?: string, errors?: string[], product_id?: int, url?: string, price?: string}
	 */
	public static function create( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$check = self::validate( $args, true );
		if ( ! $check['ok'] ) {
			return [
				'ok'     => false,
				'errors' => $check['errors'],
				'error'  => \implode( ' | ', $check['errors'] ),
			];
		}

		$data      = $check['data'];
		$class     = 'variable' === $data['type'] ? '\WC_Product_Variable' : '\WC_Product_Simple';
		$product   = new $class();
		$saved_id  = 0;

		try {
			self::apply_fields( $product, $data );
			$saved_id = (int) $product->save();

			if ( $saved_id <= 0 ) {
				throw new \RuntimeException( 'WooCommerce refused to save the product.' );
			}
		} catch ( \Throwable $e ) {
			// Atomicité : un produit à moitié écrit encombre la boutique sans
			// être vendable. On le retire plutôt que de le laisser en place.
			if ( $saved_id > 0 ) {
				\wp_delete_post( $saved_id, true );
			}

			return [
				'ok'    => false,
				'error' => 'Product creation failed and was rolled back: ' . $e->getMessage(),
			];
		}

		return [
			'ok'         => true,
			'product_id' => $saved_id,
			'url'        => (string) \get_permalink( $saved_id ),
			'price'      => '' !== $data['regular_price'] ? self::describe_price( $data['regular_price'] ) : '',
		];
	}

	/**
	 * Updates an existing WooCommerce product.
	 *
	 * Only the fields actually supplied are written: a partial payload never
	 * resets a field the caller did not mention.
	 *
	 * @param array<string, mixed> $args Tool arguments (product_id required).
	 * @return array{ok: bool, error?: string, errors?: string[], product_id?: int, url?: string, price?: string}
	 */
	public static function update( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$product_id = \absint( $args['product_id'] ?? 0 );
		$product    = \wc_get_product( $product_id );

		if ( ! $product ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Product %d does not exist, or is not a WooCommerce product.', $product_id ),
			];
		}

		$check = self::validate( $args, false );
		if ( ! $check['ok'] ) {
			return [
				'ok'     => false,
				'errors' => $check['errors'],
				'error'  => \implode( ' | ', $check['errors'] ),
			];
		}

		$data = $check['data'];

		// Le prix promo est comparé au prix normal effectif : sans cela, ne
		// fournir qu'un prix promo passerait la validation contre une chaîne vide.
		if ( '' !== $data['sale_price'] && '' === $data['regular_price'] ) {
			$current = (float) $product->get_regular_price();

			if ( $current > 0 && (float) $data['sale_price'] >= $current ) {
				return [
					'ok'    => false,
					'error' => \sprintf(
						'sale_price (%s) must be lower than the product current regular_price (%s), otherwise WooCommerce ignores it.',
						$data['sale_price'],
						(string) $current
					),
				];
			}
		}

		try {
			self::apply_fields( $product, $data, true );
			$product->save();
		} catch ( \Throwable $e ) {
			return [
				'ok'    => false,
				'error' => 'Product update failed: ' . $e->getMessage() . ' The product was left in place — call g2rd_get-woo-product to inspect it.',
			];
		}

		return [
			'ok'         => true,
			'product_id' => $product_id,
			'url'        => (string) \get_permalink( $product_id ),
			'price'      => '' !== $data['regular_price'] ? self::describe_price( $data['regular_price'] ) : '',
		];
	}

	/**
	 * Applies validated fields to a WC_Product instance.
	 *
	 * Every field is optional on update: null means "not supplied", and is
	 * skipped so a partial payload never wipes existing data.
	 *
	 * @param object               $product   WC_Product instance.
	 * @param array<string, mixed> $data      Validated payload.
	 * @param bool                 $updating  True when updating an existing product.
	 * @return void
	 */
	private static function apply_fields( $product, array $data, bool $updating = false ): void {
		if ( '' !== $data['name'] ) {
			$product->set_name( $data['name'] );
		}

		if ( ! $updating || isset( $data['status'] ) ) {
			$product->set_status( $data['status'] );
		}

		// Prix : la chaîne vide est significative en mise à jour (retirer un
		// prix promo), on ne l'écrit donc que si la clé a été fournie.
		if ( '' !== $data['regular_price'] ) {
			$product->set_regular_price( $data['regular_price'] );
		}

		if ( '' !== $data['sale_price'] ) {
			$product->set_sale_price( $data['sale_price'] );
		}

		$simple_setters = [
			'slug'               => 'set_slug',
			'description'        => 'set_description',
			'short_description'  => 'set_short_description',
			'sku'                => 'set_sku',
			'purchase_note'      => 'set_purchase_note',
			'tax_class'          => 'set_tax_class',
			'tax_status'         => 'set_tax_status',
			'catalog_visibility' => 'set_catalog_visibility',
			'stock_status'       => 'set_stock_status',
			'backorders'         => 'set_backorders',
			'virtual'            => 'set_virtual',
			'downloadable'       => 'set_downloadable',
			'featured'           => 'set_featured',
			'manage_stock'       => 'set_manage_stock',
			'sold_individually'  => 'set_sold_individually',
			'reviews_allowed'    => 'set_reviews_allowed',
			'stock_quantity'     => 'set_stock_quantity',
			'menu_order'         => 'set_menu_order',
			'weight'             => 'set_weight',
			'length'             => 'set_length',
			'width'              => 'set_width',
			'height'             => 'set_height',
			'image_id'           => 'set_image_id',
			'gallery_image_ids'  => 'set_gallery_image_ids',
		];

		foreach ( $simple_setters as $field => $setter ) {
			if ( ! \array_key_exists( $field, $data ) || null === $data[ $field ] ) {
				continue;
			}

			if ( \method_exists( $product, $setter ) ) {
				$product->$setter( $data[ $field ] );
			}
		}

		// Taxonomies : les entrées numériques sont des IDs, le reste des slugs.
		foreach ( [ 'categories' => 'product_cat', 'tags' => 'product_tag' ] as $field => $taxonomy ) {
			if ( null === $data[ $field ] ) {
				continue;
			}

			$ids = self::resolve_term_ids( (array) $data[ $field ], $taxonomy );

			if ( 'categories' === $field ) {
				$product->set_category_ids( $ids );
			} else {
				$product->set_tag_ids( $ids );
			}
		}
	}

	/**
	 * Resolves term slugs or IDs into term IDs.
	 *
	 * @param array<int|string> $terms    Slugs or IDs.
	 * @param string            $taxonomy Taxonomy name.
	 * @return int[]
	 */
	private static function resolve_term_ids( array $terms, string $taxonomy ): array {
		$ids = [];

		foreach ( $terms as $term ) {
			$term = (string) $term;

			if ( \ctype_digit( $term ) ) {
				$ids[] = (int) $term;
				continue;
			}

			$found = \get_term_by( 'slug', \sanitize_title( $term ), $taxonomy );

			if ( $found && ! \is_wp_error( $found ) ) {
				$ids[] = (int) $found->term_id;
			}
		}

		return \array_values( \array_unique( $ids ) );
	}

	/**
	 * Moves a product to the trash. Permanent deletion is never exposed.
	 *
	 * @param int $product_id Product ID.
	 * @return array{ok: bool, error?: string, product_id?: int}
	 */
	public static function trash( int $product_id ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$product = \wc_get_product( $product_id );

		if ( ! $product ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Product %d does not exist, or is not a WooCommerce product.', $product_id ),
			];
		}

		// false = corbeille, jamais de suppression définitive.
		$product->delete( false );

		return [
			'ok'         => true,
			'product_id' => $product_id,
		];
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	/**
	 * Returns the full state of a product, as the admin screen shows it.
	 *
	 * @param int $product_id Product ID.
	 * @return array{ok: bool, error?: string, product?: array<string, mixed>}
	 */
	public static function get( int $product_id ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$product = \wc_get_product( $product_id );

		if ( ! $product ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Product %d does not exist, or is not a WooCommerce product.', $product_id ),
			];
		}

		return [
			'ok'      => true,
			'product' => [
				'id'                 => $product_id,
				'name'               => $product->get_name(),
				'slug'               => $product->get_slug(),
				'type'               => $product->get_type(),
				'status'             => $product->get_status(),
				'url'                => (string) \get_permalink( $product_id ),
				'sku'                => $product->get_sku(),
				'regular_price'      => $product->get_regular_price(),
				'sale_price'         => $product->get_sale_price(),
				'price'              => $product->get_price(),
				'price_formatted'    => self::describe_price( (string) $product->get_price() ),
				'on_sale'            => $product->is_on_sale(),
				'purchasable'        => $product->is_purchasable(),
				'in_stock'           => $product->is_in_stock(),
				'manage_stock'       => $product->get_manage_stock(),
				'stock_quantity'     => $product->get_stock_quantity(),
				'stock_status'       => $product->get_stock_status(),
				'backorders'         => $product->get_backorders(),
				'virtual'            => $product->is_virtual(),
				'downloadable'       => $product->is_downloadable(),
				'featured'           => $product->is_featured(),
				'catalog_visibility' => $product->get_catalog_visibility(),
				'tax_status'         => $product->get_tax_status(),
				'tax_class'          => $product->get_tax_class(),
				'weight'             => $product->get_weight(),
				'dimensions'         => [
					'length' => $product->get_length(),
					'width'  => $product->get_width(),
					'height' => $product->get_height(),
				],
				'description'        => $product->get_description(),
				'short_description'  => $product->get_short_description(),
				'image_id'           => $product->get_image_id(),
				'gallery_image_ids'  => $product->get_gallery_image_ids(),
				'category_ids'       => $product->get_category_ids(),
				'tag_ids'            => $product->get_tag_ids(),
				'menu_order'         => $product->get_menu_order(),
			],
		];
	}

	/**
	 * Lists WooCommerce products.
	 *
	 * @param array<string, mixed> $args Tool arguments (per_page, page, status, search).
	 * @return array{ok: bool, error?: string, products?: array, total?: int}
	 */
	public static function list_products( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$per_page = \min( 100, \max( 1, \absint( $args['per_page'] ?? 20 ) ) );
		$page     = \max( 1, \absint( $args['page'] ?? 1 ) );
		$status   = \sanitize_key( (string) ( $args['status'] ?? 'any' ) );

		$query = new \WP_Query(
			[
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'any' === $status ? self::STATUSES : $status,
				'posts_per_page'         => $per_page,
				'paged'                  => $page,
				's'                      => \sanitize_text_field( (string) ( $args['search'] ?? '' ) ),
				'update_post_term_cache' => false,
			]
		);

		$products = [];

		foreach ( $query->posts as $post ) {
			$product = \wc_get_product( $post->ID );

			if ( ! $product ) {
				continue;
			}

			$products[] = [
				'id'              => $post->ID,
				'name'            => $product->get_name(),
				'type'            => $product->get_type(),
				'status'          => $product->get_status(),
				'sku'             => $product->get_sku(),
				'price_formatted' => self::describe_price( (string) $product->get_price() ),
				'in_stock'        => $product->is_in_stock(),
				'url'             => (string) \get_permalink( $post->ID ),
			];
		}

		return [
			'ok'       => true,
			'products' => $products,
			'total'    => (int) $query->found_posts,
		];
	}
}
