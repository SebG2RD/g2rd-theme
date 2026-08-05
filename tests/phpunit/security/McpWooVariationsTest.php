<?php
/**
 * Tests — McpWooVariations (WooCommerce product variations)
 *
 * WooCommerce is not installed in the unit test environment, so these tests cover
 * what does not depend on it: the decimal price contract, the sale/regular
 * comparison against the EFFECTIVE regular price, partial-update semantics, and
 * the guard that keeps the tools from fataling when WooCommerce is absent.
 *
 * The scenarios that need a real shop — readable attribute labels, price range
 * recalculation after a delete, refusing the last variation — are covered by the
 * manual protocol in docs/internal/mcp-woocommerce-variations.md.
 *
 * @package    G2RD\Tests
 * @since      1.31.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpWooVariations;
use PHPUnit\Framework\TestCase;

/**
 * Verifies validation, the price contract and the WooCommerce guard.
 */
final class McpWooVariationsTest extends TestCase {

	// ── Contrat de prix ───────────────────────────────────────────────────────

	/**
	 * Les prix restent des chaînes décimales, jamais des centimes.
	 *
	 * Même contrat que g2rd_update-woo-product : « 12.00 » vaut douze euros.
	 */
	public function test_decimal_prices_are_accepted_as_strings(): void {
		$result = McpWooVariations::validate( [ 'regular_price' => '12.00' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '12.00', $result['data']['regular_price'] );
		$this->assertIsString( $result['data']['regular_price'] );
	}

	/**
	 * « 7.10 » n'est pas ramené à « 7.1 ».
	 *
	 * WooCommerce stocke le prix en chaîne : le convertir en float pour le
	 * réécrire perdrait le zéro final, et 0.1 + 0.2 ne vaut pas 0.3.
	 */
	public function test_trailing_zero_is_preserved(): void {
		$result = McpWooVariations::validate( [ 'regular_price' => '7.10' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '7.10', $result['data']['regular_price'] );
	}

	/**
	 * Un montant mal formé est refusé, avec le rappel qu'il ne s'agit pas de
	 * centimes — le piège que partagent les outils FluentCart et WooCommerce.
	 */
	public function test_malformed_price_is_refused_and_warns_about_cents(): void {
		$result = McpWooVariations::validate( [ 'regular_price' => '12.999' ] );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'NOT a number of cents', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * La virgule décimale française est acceptée.
	 */
	public function test_french_decimal_comma_is_accepted(): void {
		$result = McpWooVariations::validate( [ 'regular_price' => '12,50' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '12.50', $result['data']['regular_price'] );
	}

	// ── Prix promotionnel ─────────────────────────────────────────────────────

	/**
	 * Un prix promo supérieur ou égal au prix normal est refusé.
	 *
	 * WooCommerce l'ignorerait : l'opération semblerait réussir alors que la
	 * variation garderait son ancien prix.
	 */
	public function test_sale_price_not_below_regular_is_refused(): void {
		$result = McpWooVariations::validate(
			[
				'regular_price' => '12.00',
				'sale_price'    => '12.00',
			]
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'must be lower', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Un prix promo valide passe.
	 */
	public function test_valid_sale_price_is_accepted(): void {
		$result = McpWooVariations::validate(
			[
				'regular_price' => '12.00',
				'sale_price'    => '9.90',
			]
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '9.90', $result['data']['sale_price'] );
	}

	/**
	 * Une chaîne vide retire le prix promo : c'est une intention valide.
	 */
	public function test_empty_sale_price_clears_it(): void {
		$result = McpWooVariations::validate( [ 'sale_price' => '' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '', $result['data']['sale_price'] );
	}

	/**
	 * Vider le prix normal est refusé.
	 *
	 * Une variation sans prix normal n'est plus achetable, et un prix normal vide
	 * neutralisait au passage la comparaison promo/normal : un prix promo
	 * quelconque serait alors passé sans contrôle.
	 */
	public function test_emptying_regular_price_is_refused(): void {
		$result = McpWooVariations::validate( [ 'regular_price' => '' ] );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not purchasable', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Le contournement de la vérification promo est bien fermé.
	 */
	public function test_emptying_regular_does_not_bypass_the_sale_check(): void {
		$result = McpWooVariations::validate(
			[
				'regular_price' => '',
				'sale_price'    => '999.00',
			]
		);

		$this->assertFalse( $result['ok'] );
	}

	// ── Mise à jour partielle ─────────────────────────────────────────────────

	/**
	 * Seuls les champs fournis apparaissent dans la charge validée.
	 *
	 * C'est ce qui garantit qu'une mise à jour du seul stock laisse prix, SKU et
	 * image intacts : ce qui n'est pas transmis n'est jamais écrit.
	 */
	public function test_only_supplied_fields_are_returned(): void {
		$result = McpWooVariations::validate( [ 'stock_quantity' => 5 ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( [ 'stock_quantity' ], \array_keys( $result['data'] ) );
		$this->assertArrayNotHasKey( 'regular_price', $result['data'] );
		$this->assertArrayNotHasKey( 'sku', $result['data'] );
		$this->assertArrayNotHasKey( 'image_id', $result['data'] );
	}

	/**
	 * Désactiver une variation est un champ à part entière.
	 */
	public function test_enabled_flag_is_carried(): void {
		$result = McpWooVariations::validate( [ 'enabled' => false ] );

		$this->assertTrue( $result['ok'] );
		$this->assertFalse( $result['data']['enabled'] );
	}

	// ── Énumérations ──────────────────────────────────────────────────────────

	/**
	 * Un état de stock invalide est refusé avec la liste des valeurs acceptées.
	 */
	public function test_invalid_stock_status_lists_accepted_values(): void {
		$result = McpWooVariations::validate( [ 'stock_status' => 'peut-etre' ] );

		$this->assertFalse( $result['ok'] );
		$joined = \implode( ' ', $result['errors'] );
		$this->assertStringContainsString( 'stock_status', $joined );
		$this->assertStringContainsString( 'instock', $joined );
	}

	// ── Garde WooCommerce ─────────────────────────────────────────────────────

	/**
	 * Sans WooCommerce, les cinq opérations refusent proprement.
	 */
	public function test_operations_refuse_cleanly_without_woocommerce(): void {
		$this->assertFalse( McpWooVariations::is_available() );

		$results = [
			McpWooVariations::list_variations( [ 'product_id' => 1 ] ),
			McpWooVariations::get_variation( 1 ),
			McpWooVariations::create_variation( [ 'product_id' => 1 ] ),
			McpWooVariations::update_variation( [ 'variation_id' => 1 ] ),
			McpWooVariations::delete_variation( [ 'variation_id' => 1 ] ),
		];

		foreach ( $results as $result ) {
			$this->assertFalse( $result['ok'] );
			$this->assertStringContainsString( 'WooCommerce is not active', $result['error'] );
		}
	}
}
