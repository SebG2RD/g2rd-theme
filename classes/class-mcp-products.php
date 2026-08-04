<?php
/**
 * MCP Products — FluentCart product layer
 *
 * Creates and reads FluentCart products through the plugin's own models, never
 * through raw SQL, so FluentCart hooks and future migrations keep working.
 *
 * Why this class exists
 * ---------------------
 * g2rd/create-post only calls wp_insert_post(). A FluentCart product needs three
 * things to be sellable, and the last two live in the plugin's own tables:
 *
 *   1. a `fluent-products` post                        (wp_posts)
 *   2. one product_details row                         (fct_product_details)
 *   3. at least one product_variations row with a price (fct_product_variations)
 *
 * A post created without 2 and 3 looks fine in the posts list but the admin
 * Pricing screen cannot save anything and the product is not purchasable.
 *
 * Creation sequence — mirrors FluentCart\App\Http\Controllers\ProductController::create()
 * (FluentCart 1.6.0), which is the reference implementation used by the admin UI.
 *
 * Prices are stored in CENTS in `item_price` / `compare_price`, confirmed by
 * FluentCart\App\Helpers\CurrenciesHelper::centsToDecimal(). Subscription terms
 * live in the `other_info` JSON column, not in dedicated columns.
 *
 * Atomicity: if any fct_* write fails, the WordPress post is force-deleted so no
 * orphan product remains — the exact failure mode this class was written to fix.
 *
 * @package    G2RD
 * @since      1.28.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Allowlisted read/write access to FluentCart products.
 */
class McpProducts {

	/** FluentCart product post type. Source: FluentProducts::CPT_NAME. */
	public const POST_TYPE = 'fluent-products';

	/** Meta key holding the product gallery. */
	public const GALLERY_META = 'fluent-products-gallery-image';

	/** Accepted fulfillment types. Source: fct_product_details.fulfillment_type. */
	public const FULFILLMENT_TYPES = [ 'physical', 'digital', 'service' ];

	/** Accepted payment types on a variation. */
	public const PAYMENT_TYPES = [ 'onetime', 'subscription' ];

	/** Accepted billing intervals for subscriptions. */
	public const BILLING_INTERVALS = [ 'day', 'week', 'month', 'year' ];

	/** Post statuses a product may take. */
	public const STATUSES = [ 'publish', 'draft', 'pending', 'private' ];

	/**
	 * Reports whether FluentCart is active and its models are loadable.
	 *
	 * Checked before every operation: without it the tools would fail with a
	 * PHP fatal error instead of a readable message.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( '\FluentCart\App\Models\ProductDetail' )
			&& \class_exists( '\FluentCart\App\Models\ProductVariation' )
			&& \post_type_exists( self::POST_TYPE );
	}

	/**
	 * Returns the error payload used when FluentCart is missing.
	 *
	 * @return array{ok: false, error: string}
	 */
	private static function unavailable(): array {
		return [
			'ok'    => false,
			'error' => 'FluentCart is not active on this site, or its version does not expose the expected models. Product tools require FluentCart 1.6.0 or later.',
		];
	}

	// ── Validation ────────────────────────────────────────────────────────────

	/**
	 * Validates a create/update payload.
	 *
	 * Returns every problem at once rather than the first one: an agent that
	 * gets one error per round trip needs as many attempts as it made mistakes.
	 * Each message names the accepted values so the agent can self-correct.
	 *
	 * @param array<string, mixed> $args     Incoming payload.
	 * @param bool                 $creating True for create (title required).
	 * @return array{ok: bool, errors?: string[], data?: array<string, mixed>}
	 */
	public static function validate( array $args, bool $creating = true ): array {
		$errors = [];

		$title = \sanitize_text_field( (string) ( $args['title'] ?? '' ) );
		if ( $creating && '' === $title ) {
			$errors[] = 'title is required and must be a non-empty string.';
		}

		$status = \sanitize_key( (string) ( $args['status'] ?? 'draft' ) );
		if ( ! \in_array( $status, self::STATUSES, true ) ) {
			$errors[] = \sprintf(
				'status "%s" is invalid. Accepted values: %s.',
				$status,
				\implode( ', ', self::STATUSES )
			);
		}

		$fulfillment = \sanitize_key( (string) ( $args['fulfillment_type'] ?? 'digital' ) );
		if ( ! \in_array( $fulfillment, self::FULFILLMENT_TYPES, true ) ) {
			$errors[] = \sprintf(
				'fulfillment_type "%s" is invalid. Accepted values: %s.',
				$fulfillment,
				\implode( ', ', self::FULFILLMENT_TYPES )
			);
		}

		$variations = $args['variations'] ?? [];
		if ( ! \is_array( $variations ) ) {
			$errors[] = 'variations must be an array of pricing objects.';
			$variations = [];
		}

		// Vide est refusé dès que la clé est fournie, création ou mise à jour :
		// en update, un tableau vide supprimait tous les tarifs sans en écrire
		// aucun, laissant un produit non achetable — et l'opération réussissait.
		if ( ( $creating || \array_key_exists( 'variations', $args ) ) && [] === $variations ) {
			$errors[] = 'variations must contain at least one entry: a product without a priced variation is not purchasable, which is exactly the failure this tool exists to prevent. Omit the key entirely to leave pricing untouched.';
		}

		$clean_variations = [];
		foreach ( \array_values( $variations ) as $index => $variation ) {
			$result = self::validate_variation( \is_array( $variation ) ? $variation : [], $index );

			if ( [] !== $result['errors'] ) {
				$errors = \array_merge( $errors, $result['errors'] );
				continue;
			}

			$clean_variations[] = $result['data'];
		}

		// Exactly one default variation, so product_details.default_variation_id
		// can always be resolved.
		if ( [] !== $clean_variations ) {
			$defaults = \array_keys( \array_filter( $clean_variations, static fn( array $v ): bool => $v['is_default'] ) );

			if ( [] === $defaults ) {
				$clean_variations[0]['is_default'] = true;
			} elseif ( \count( $defaults ) > 1 ) {
				$errors[] = 'Only one variation may have is_default set to true.';
			}
		}

		if ( [] !== $errors ) {
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		return [
			'ok'   => true,
			'data' => [
				'title'             => $title,
				'slug'              => \sanitize_title( (string) ( $args['slug'] ?? $title ) ),
				'content'           => \wp_kses_post( (string) ( $args['content'] ?? '' ) ),
				'excerpt'           => \sanitize_textarea_field( (string) ( $args['excerpt'] ?? '' ) ),
				'status'            => $status,
				'fulfillment_type'  => $fulfillment,
				'manage_stock'      => ! empty( $args['manage_stock'] ),
				'featured_image_id' => \absint( $args['featured_image_id'] ?? 0 ),
				'gallery_image_ids' => \array_values( \array_filter( \array_map( 'absint', (array) ( $args['gallery_image_ids'] ?? [] ) ) ) ),
				'categories'        => \array_values( \array_filter( \array_map( 'strval', (array) ( $args['product_categories'] ?? [] ) ) ) ),
				'variations'        => $clean_variations,
			],
		];
	}

	/**
	 * Validates one variation entry.
	 *
	 * @param array<string, mixed> $variation Raw variation.
	 * @param int                  $index     Position, used in error messages.
	 * @return array{errors: string[], data: array<string, mixed>}
	 */
	private static function validate_variation( array $variation, int $index ): array {
		$errors = [];
		$label  = \sprintf( 'variations[%d]', $index );

		$payment_type = \sanitize_key( (string) ( $variation['payment_type'] ?? 'onetime' ) );
		if ( ! \in_array( $payment_type, self::PAYMENT_TYPES, true ) ) {
			$errors[] = \sprintf(
				'%s.payment_type "%s" is invalid. Accepted values: %s.',
				$label,
				$payment_type,
				\implode( ', ', self::PAYMENT_TYPES )
			);
		}

		// Price must be an integer number of cents. Accepting a float here is how
		// "19.99" silently becomes 19 cents.
		$raw_price = $variation['price'] ?? null;
		if ( ! \is_int( $raw_price ) && ! ( \is_string( $raw_price ) && \ctype_digit( $raw_price ) ) ) {
			$errors[] = \sprintf(
				'%s.price must be an integer number of CENTS (20000 for 200.00), not a decimal amount. Received: %s.',
				$label,
				\is_scalar( $raw_price ) ? (string) \wp_json_encode( $raw_price ) : \gettype( $raw_price )
			);
		}
		$price = \absint( $raw_price );

		// Même contrat que price : absint() sur une décimale la tronquerait en
		// silence, ce que la promesse « centimes entiers » interdit.
		$raw_compare = $variation['compare_at_price'] ?? 0;
		if ( ! \is_int( $raw_compare ) && ! ( \is_string( $raw_compare ) && \ctype_digit( $raw_compare ) ) ) {
			$errors[] = \sprintf(
				'%s.compare_at_price must be an integer number of CENTS, not a decimal amount. Received: %s.',
				$label,
				\is_scalar( $raw_compare ) ? (string) \wp_json_encode( $raw_compare ) : \gettype( $raw_compare )
			);
		}
		$compare = \absint( $raw_compare );

		$billing_interval = \sanitize_key( (string) ( $variation['billing_interval'] ?? 'month' ) );
		$interval_count   = \max( 1, \absint( $variation['billing_interval_count'] ?? 1 ) );
		$trial_days       = \absint( $variation['trial_days'] ?? 0 );
		$cycles           = \absint( $variation['cycles'] ?? 0 );

		if ( 'subscription' === $payment_type && ! \in_array( $billing_interval, self::BILLING_INTERVALS, true ) ) {
			$errors[] = \sprintf(
				'%s.billing_interval "%s" is invalid for a subscription. Accepted values: %s.',
				$label,
				$billing_interval,
				\implode( ', ', self::BILLING_INTERVALS )
			);
		}

		return [
			'errors' => $errors,
			'data'   => [
				'label'                  => \sanitize_text_field( (string) ( $variation['label'] ?? '' ) ),
				'payment_type'           => $payment_type,
				'price'                  => $price,
				'compare_at_price'       => $compare,
				'billing_interval'       => $billing_interval,
				'billing_interval_count' => $interval_count,
				'trial_days'             => $trial_days,
				// 0 = renews forever. FluentCart stores this as an empty string.
				'cycles'                 => $cycles,
				'unlimited_stock'        => ! isset( $variation['stock'] ),
				'stock'                  => \absint( $variation['stock'] ?? 0 ),
				'is_default'             => ! empty( $variation['is_default'] ),
			],
		];
	}

	/**
	 * Renders a human summary of what will be created, for the confirmation e-mail.
	 *
	 * The administrator approving a write must see the price, not just a title.
	 *
	 * @param array<string, mixed> $args Raw tool arguments.
	 * @return string Plain-text summary.
	 */
	public static function summarize( array $args ): string {
		$check = self::validate( $args, true );

		if ( ! $check['ok'] ) {
			return \implode( "\n", $check['errors'] );
		}

		$data  = $check['data'];
		$lines = [
			\sprintf( 'Produit : %s', $data['title'] ),
			\sprintf( 'Statut : %s — Type : %s', $data['status'], $data['fulfillment_type'] ),
		];

		foreach ( $data['variations'] as $variation ) {
			$lines[] = '  • ' . self::describe_price( $variation );
		}

		return \implode( "\n", $lines );
	}

	/**
	 * Describes one variation's price in human terms.
	 *
	 * @param array<string, mixed> $variation Validated variation.
	 * @return string
	 */
	public static function describe_price( array $variation ): string {
		$amount = \number_format( $variation['price'] / 100, 2, ',', ' ' );
		$label  = '' !== $variation['label'] ? $variation['label'] . ' — ' : '';

		if ( 'subscription' !== $variation['payment_type'] ) {
			return $label . $amount . ' € (paiement unique)';
		}

		$every = 1 === $variation['billing_interval_count']
			? $variation['billing_interval']
			: $variation['billing_interval_count'] . ' ' . $variation['billing_interval'];

		$suffix = 0 === $variation['cycles']
			? ' (renouvellement illimité)'
			: \sprintf( ' (%d cycles)', $variation['cycles'] );

		if ( $variation['trial_days'] > 0 ) {
			$suffix .= \sprintf( ', %d jours d\'essai', $variation['trial_days'] );
		}

		return $label . $amount . ' € par ' . $every . $suffix;
	}

	// ── Write ─────────────────────────────────────────────────────────────────

	/**
	 * Creates a complete, purchasable FluentCart product.
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @return array{ok: bool, error?: string, errors?: string[], product_id?: int, variations?: array, url?: string}
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

		$data = $check['data'];

		$post_id = \wp_insert_post(
			[
				'post_title'   => $data['title'],
				'post_name'    => $data['slug'],
				'post_content' => $data['content'],
				'post_excerpt' => $data['excerpt'],
				'post_status'  => $data['status'],
				'post_type'    => self::POST_TYPE,
			],
			true
		);

		if ( \is_wp_error( $post_id ) || ! $post_id ) {
			return [
				'ok'    => false,
				'error' => \is_wp_error( $post_id ) ? $post_id->get_error_message() : 'wp_insert_post failed.',
			];
		}

		$post_id = (int) $post_id;

		try {
			self::write_product_rows( $post_id, $data );
			self::apply_media_and_terms( $post_id, $data );
		} catch ( \Throwable $e ) {
			// Atomicity: a product post without its fct_* rows is the orphan this
			// class exists to prevent. Remove it rather than leave it behind.
			\wp_delete_post( $post_id, true );

			return [
				'ok'    => false,
				'error' => 'Product creation failed and was rolled back: ' . $e->getMessage(),
			];
		}

		return [
			'ok'         => true,
			'product_id' => $post_id,
			'url'        => (string) \get_permalink( $post_id ),
			'variations' => \array_map( [ self::class, 'describe_price' ], $data['variations'] ),
		];
	}

	/**
	 * Writes the product_details row and every variation row.
	 *
	 * @param int                  $post_id Product post ID.
	 * @param array<string, mixed> $data    Validated payload.
	 * @throws \RuntimeException When FluentCart refuses a write.
	 * @return void
	 */
	private static function write_product_rows( int $post_id, array $data, string $effective_title = '' ): void {
		$detail_model    = '\FluentCart\App\Models\ProductDetail';
		$variation_model = '\FluentCart\App\Models\ProductVariation';

		$detail_fields = [
			'fulfillment_type'   => $data['fulfillment_type'],
			'variation_type'     => 'simple',
			'manage_stock'       => $data['manage_stock'] ? 1 : 0,
			'stock_availability' => 'in-stock',
		];

		/*
		 * Upsert, jamais create() aveugle : sur une mise à jour, créer une seconde
		 * ligne laisserait deux product_details pour un même produit, et FluentCart
		 * lirait l'une ou l'autre — donc potentiellement le mauvais
		 * fulfillment_type ou le mauvais default_variation_id.
		 */
		$existing = $detail_model::query()->where( 'post_id', $post_id )->first();

		if ( $existing ) {
			$detail_model::query()->where( 'post_id', $post_id )->update( $detail_fields );
		} else {
			$detail = $detail_model::query()->create(
				\array_merge( [ 'post_id' => $post_id ], $detail_fields )
			);

			if ( ! $detail ) {
				throw new \RuntimeException( 'FluentCart refused to create the product_details row.' );
			}
		}

		// Sur une mise à jour sans titre fourni, $data['title'] est vide : sans ce
		// repli les variations recevraient un variation_title vide.
		$fallback_title = '' !== $data['title'] ? $data['title'] : $effective_title;

		$default_variation_id = 0;
		$serial               = 1;

		foreach ( $data['variations'] as $variation ) {
			$row = $variation_model::query()->create(
				[
					'post_id'          => $post_id,
					'serial_index'     => $serial,
					'variation_title'  => '' !== $variation['label'] ? $variation['label'] : $fallback_title,
					'payment_type'     => $variation['payment_type'],
					'item_price'       => $variation['price'],
					'compare_price'    => $variation['compare_at_price'],
					'stock_status'     => 'in-stock',
					'manage_stock'     => $variation['unlimited_stock'] ? 0 : 1,
					'total_stock'      => $variation['unlimited_stock'] ? 0 : $variation['stock'],
					'available'        => $variation['unlimited_stock'] ? 0 : $variation['stock'],
					'fulfillment_type' => $data['fulfillment_type'],
					'item_status'      => 'active',
					'other_info'       => self::build_other_info( $variation ),
				]
			);

			if ( ! $row ) {
				throw new \RuntimeException( 'FluentCart refused to create a product_variations row.' );
			}

			if ( $variation['is_default'] && 0 === $default_variation_id ) {
				$default_variation_id = (int) $row->id;
			}

			++$serial;
		}

		// Without default_variation_id the admin Pricing screen has no row to bind
		// to and the front end cannot resolve a price.
		if ( $default_variation_id > 0 ) {
			$detail_model::query()
				->where( 'post_id', $post_id )
				->update( [ 'default_variation_id' => $default_variation_id ] );
		}
	}

	/**
	 * Builds the other_info payload FluentCart expects on a variation.
	 *
	 * Subscription terms live here, not in dedicated columns. Keys and empty-string
	 * conventions mirror ProductController::create() in FluentCart 1.6.0.
	 *
	 * @param array<string, mixed> $variation Validated variation.
	 * @return array<string, mixed>
	 */
	private static function build_other_info( array $variation ): array {
		$is_subscription = 'subscription' === $variation['payment_type'];

		return [
			'description'        => '',
			'payment_type'       => $variation['payment_type'],
			'tax_class'          => 'standard',
			'tax_exempt'         => 'no',
			// Empty string, not 0: FluentCart reads "" as "renews forever".
			'times'              => $is_subscription && $variation['cycles'] > 0 ? (string) $variation['cycles'] : '',
			'repeat_interval'    => $is_subscription ? $variation['billing_interval'] : '',
			'interval_count'     => $is_subscription ? $variation['billing_interval_count'] : '',
			'trial_days'         => $is_subscription && $variation['trial_days'] > 0 ? (string) $variation['trial_days'] : '',
			'billing_summary'    => $is_subscription ? self::describe_price( $variation ) : '',
			'manage_setup_fee'   => 'no',
			'signup_fee_name'    => '',
			'signup_fee'         => '',
			'setup_fee_per_item' => 'no',
			'is_bundle_product'  => 'no',
		];
	}

	/**
	 * Applies featured image, gallery and product categories.
	 *
	 * @param int                  $post_id Product post ID.
	 * @param array<string, mixed> $data    Validated payload.
	 * @return void
	 */
	private static function apply_media_and_terms( int $post_id, array $data ): void {
		if ( $data['featured_image_id'] > 0 ) {
			\set_post_thumbnail( $post_id, $data['featured_image_id'] );
		}

		if ( [] !== $data['gallery_image_ids'] ) {
			$gallery = [];

			foreach ( $data['gallery_image_ids'] as $attachment_id ) {
				$gallery[] = [
					'id'    => $attachment_id,
					'url'   => (string) \wp_get_attachment_url( $attachment_id ),
					'title' => (string) \get_the_title( $attachment_id ),
				];
			}

			\update_post_meta( $post_id, self::GALLERY_META, $gallery );
		}

		if ( [] !== $data['categories'] ) {
			$taxonomy = 'product-categories';

			if ( \taxonomy_exists( $taxonomy ) ) {
				// Numeric entries are term IDs, everything else is a slug.
				$terms = \array_map(
					static fn( string $term ) => \ctype_digit( $term ) ? (int) $term : $term,
					$data['categories']
				);

				\wp_set_object_terms( $post_id, $terms, $taxonomy );
			}
		}
	}

	/**
	 * Updates an existing product, optionally replacing its pricing.
	 *
	 * Pricing is replaced wholesale rather than merged: FluentCart identifies a
	 * variation by row id, and a partial merge from an agent that omits a field
	 * would silently reset it. Omitting `variations` leaves pricing untouched.
	 *
	 * @param array<string, mixed> $args Tool arguments (product_id required).
	 * @return array{ok: bool, error?: string, errors?: string[], product_id?: int, variations?: array, url?: string}
	 */
	public static function update( array $args ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$post_id = \absint( $args['product_id'] ?? 0 );
		$post    = \get_post( $post_id );

		if ( ! $post instanceof \WP_Post || self::POST_TYPE !== $post->post_type ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Post %d is not a FluentCart product.', $post_id ),
			];
		}

		$replaces_pricing = isset( $args['variations'] ) && \is_array( $args['variations'] );

		$check = self::validate( $args, false );
		if ( ! $check['ok'] ) {
			return [
				'ok'     => false,
				'errors' => $check['errors'],
				'error'  => \implode( ' | ', $check['errors'] ),
			];
		}

		$data    = $check['data'];
		$postarr = [ 'ID' => $post_id ];

		// Only overwrite what the caller actually supplied.
		if ( isset( $args['title'] ) && '' !== $data['title'] ) {
			$postarr['post_title'] = $data['title'];
		}
		if ( isset( $args['slug'] ) ) {
			$postarr['post_name'] = $data['slug'];
		}
		if ( isset( $args['content'] ) ) {
			$postarr['post_content'] = $data['content'];
		}
		if ( isset( $args['excerpt'] ) ) {
			$postarr['post_excerpt'] = $data['excerpt'];
		}
		if ( isset( $args['status'] ) ) {
			$postarr['post_status'] = $data['status'];
		}

		if ( \count( $postarr ) > 1 ) {
			$updated = \wp_update_post( $postarr, true );

			if ( \is_wp_error( $updated ) ) {
				return [
					'ok'    => false,
					'error' => $updated->get_error_message(),
				];
			}
		}

		$variation_model = '\FluentCart\App\Models\ProductVariation';
		$snapshot        = [];

		try {
			if ( $replaces_pricing ) {
				/*
				 * Remplacer un tarif suppose de supprimer puis réécrire. Si la
				 * réécriture échoue à mi-chemin, le produit se retrouve sans aucun
				 * tarif — donc non achetable — alors que l'appelant demandait
				 * seulement une modification. On garde donc une copie des lignes
				 * existantes pour pouvoir les remettre en place.
				 */
				$snapshot = \array_map(
					static fn( $row ): array => \array_diff_key( (array) $row->getAttributes(), [ 'id' => true ] ),
					$variation_model::query()->where( 'post_id', $post_id )->get()->all()
				);

				$variation_model::query()->where( 'post_id', $post_id )->delete();

				self::write_product_rows( $post_id, $data, $post->post_title );
			}

			self::apply_media_and_terms( $post_id, $data );
		} catch ( \Throwable $e ) {
			$restored = false;

			if ( [] !== $snapshot ) {
				try {
					$variation_model::query()->where( 'post_id', $post_id )->delete();

					foreach ( $snapshot as $row ) {
						$variation_model::query()->create( $row );
					}

					$restored = true;
				} catch ( \Throwable $restore_error ) {
					$restored = false;
				}
			}

			// Le post survit volontairement : il existait avant l'appel, le
			// supprimer détruirait des données que personne n'a demandé d'effacer.
			return [
				'ok'    => false,
				'error' => 'Product update failed: ' . $e->getMessage() . ( $restored
					? ' Previous pricing was restored.'
					: ' Pricing may be incomplete — call g2rd_get-product to inspect it.' ),
			];
		}

		return [
			'ok'         => true,
			'product_id' => $post_id,
			'url'        => (string) \get_permalink( $post_id ),
			'variations' => \array_map( [ self::class, 'describe_price' ], $data['variations'] ),
		];
	}

	/**
	 * Moves a product to the trash. Permanent deletion is never exposed.
	 *
	 * @param int $post_id Product post ID.
	 * @return array{ok: bool, error?: string, product_id?: int}
	 */
	public static function trash( int $post_id ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$post = \get_post( $post_id );

		if ( ! $post instanceof \WP_Post || self::POST_TYPE !== $post->post_type ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Post %d is not a FluentCart product.', $post_id ),
			];
		}

		$result = \wp_trash_post( $post_id );

		return $result
			? [
				'ok'         => true,
				'product_id' => $post_id,
			]
			: [
				'ok'    => false,
				'error' => 'WordPress refused to trash the product.',
			];
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	/**
	 * Returns a product with its pricing, as the admin sees it.
	 *
	 * @param int $post_id Product post ID.
	 * @return array{ok: bool, error?: string, product?: array<string, mixed>}
	 */
	public static function get( int $post_id ): array {
		if ( ! self::is_available() ) {
			return self::unavailable();
		}

		$post = \get_post( $post_id );

		if ( ! $post instanceof \WP_Post || self::POST_TYPE !== $post->post_type ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Post %d is not a FluentCart product.', $post_id ),
			];
		}

		$detail_model    = '\FluentCart\App\Models\ProductDetail';
		$variation_model = '\FluentCart\App\Models\ProductVariation';

		$detail     = $detail_model::query()->where( 'post_id', $post_id )->first();
		$variations = $variation_model::query()->where( 'post_id', $post_id )->get();

		$rows = [];
		foreach ( $variations as $variation ) {
			$rows[] = [
				'id'            => (int) $variation->id,
				'title'         => (string) $variation->variation_title,
				'payment_type'  => (string) $variation->payment_type,
				'price_cents'   => (int) $variation->item_price,
				'compare_cents' => (int) $variation->compare_price,
				'stock_status'  => (string) $variation->stock_status,
				'other_info'    => $variation->other_info,
			];
		}

		return [
			'ok'      => true,
			'product' => [
				'id'                   => $post_id,
				'title'                => $post->post_title,
				'slug'                 => $post->post_name,
				'status'               => $post->post_status,
				'url'                  => (string) \get_permalink( $post_id ),
				'fulfillment_type'     => $detail ? (string) $detail->fulfillment_type : null,
				'default_variation_id' => $detail ? (int) $detail->default_variation_id : 0,
				// A product with no priced variation is not purchasable — surfaced
				// explicitly so an agent can detect and repair it.
				'is_purchasable'       => $detail && [] !== $rows,
				'variations'           => $rows,
			],
		];
	}

	/**
	 * Lists products.
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
				'no_found_rows'          => false,
				'update_post_term_cache' => false,
			]
		);

		$products = [];
		foreach ( $query->posts as $post ) {
			$products[] = [
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'slug'   => $post->post_name,
				'status' => $post->post_status,
				'url'    => (string) \get_permalink( $post->ID ),
			];
		}

		return [
			'ok'       => true,
			'products' => $products,
			'total'    => (int) $query->found_posts,
		];
	}
}
