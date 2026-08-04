<?php
/**
 * Tests — McpWooProducts (WooCommerce product layer)
 *
 * WooCommerce is not installed in the unit test environment, so these tests
 * cover what does not depend on it: payload validation, the decimal price
 * contract, and the guard that keeps the tools from fataling when WooCommerce
 * is absent. The CRUD path itself is covered by the manual protocol in
 * docs/internal/mcp-woocommerce-products.md.
 *
 * @package    G2RD\Tests
 * @since      1.29.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpWooProducts;
use PHPUnit\Framework\TestCase;

/**
 * Verifies validation, the price contract and error reporting.
 */
final class McpWooProductsTest extends TestCase {

	/**
	 * Builds a minimal valid payload.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 * @return array<string, mixed>
	 */
	private function payload( array $overrides = [] ): array {
		return \array_merge(
			[
				'name'          => 'TEST MCP WOO',
				'status'        => 'publish',
				'type'          => 'simple',
				'regular_price' => '200.00',
			],
			$overrides
		);
	}

	// ── Cas nominal ───────────────────────────────────────────────────────────

	/**
	 * Un produit simple à 200,00 € valide.
	 */
	public function test_simple_product_payload_is_valid(): void {
		$result = McpWooProducts::validate( $this->payload() );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '200.00', $result['data']['regular_price'] );
		$this->assertSame( 'simple', $result['data']['type'] );
	}

	/**
	 * La virgule décimale française est tolérée : un agent reprend souvent le
	 * prix tel qu'affiché sur le site.
	 */
	public function test_french_decimal_comma_is_accepted(): void {
		$result = McpWooProducts::validate( $this->payload( [ 'regular_price' => '19,99' ] ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '19.99', $result['data']['regular_price'] );
	}

	/**
	 * Un prix entier sans décimale reste valide.
	 */
	public function test_integer_price_string_is_accepted(): void {
		$result = McpWooProducts::validate( $this->payload( [ 'regular_price' => '20' ] ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '20', $result['data']['regular_price'] );
	}

	// ── Le contrat de prix ────────────────────────────────────────────────────

	/**
	 * Un prix inexploitable est refusé plutôt que coercé silencieusement.
	 */
	public function test_malformed_price_is_refused(): void {
		foreach ( [ '19.999', 'gratuit', '-5', '1 000', [] ] as $bad ) {
			$result = McpWooProducts::validate( $this->payload( [ 'regular_price' => $bad ] ) );

			$this->assertFalse( $result['ok'], 'Un prix invalide doit être refusé : ' . \wp_json_encode( $bad ) );
			$this->assertStringContainsString( 'decimal amount', \implode( ' ', $result['errors'] ) );
		}
	}

	/**
	 * Le message d'erreur nomme explicitement le piège des centimes.
	 *
	 * WooCommerce attend des décimales là où les outils FluentCart attendent des
	 * centimes : un agent qui reporte l'habitude créerait un produit à 20 000 €.
	 */
	public function test_price_error_warns_about_the_cents_trap(): void {
		$result = McpWooProducts::validate( $this->payload( [ 'regular_price' => 'abc' ] ) );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'NOT a number of cents', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Un prix promo supérieur ou égal au prix normal est refusé : WooCommerce
	 * l'ignorerait, donc l'opération semblerait réussir sans effet.
	 */
	public function test_sale_price_not_below_regular_is_refused(): void {
		$result = McpWooProducts::validate(
			$this->payload(
				[
					'regular_price' => '100.00',
					'sale_price'    => '100.00',
				]
			)
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'must be lower', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Un prix promo valide passe.
	 */
	public function test_valid_sale_price_is_accepted(): void {
		$result = McpWooProducts::validate(
			$this->payload(
				[
					'regular_price' => '100.00',
					'sale_price'    => '79.90',
				]
			)
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '79.90', $result['data']['sale_price'] );
	}

	/**
	 * Un produit simple sans prix est refusé : il ne pourrait pas être ajouté
	 * au panier.
	 */
	public function test_product_without_price_is_refused_on_create(): void {
		$payload = $this->payload();
		unset( $payload['regular_price'] );

		$result = McpWooProducts::validate( $payload, true );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'regular_price is required', \implode( ' ', $result['errors'] ) );
	}

	// ── Refus explicites ──────────────────────────────────────────────────────

	/**
	 * Toutes les énumérations sont vérifiées et annoncent leurs valeurs.
	 */
	public function test_invalid_enums_are_reported_with_accepted_values(): void {
		$result = McpWooProducts::validate(
			$this->payload(
				[
					'type'               => 'abonnement',
					'status'             => 'nonsense',
					'stock_status'       => 'peut-etre',
					'catalog_visibility' => 'partout',
					'tax_status'         => 'exonere',
				]
			)
		);

		$this->assertFalse( $result['ok'] );
		$joined = \implode( ' | ', $result['errors'] );

		foreach ( [ 'type', 'status', 'stock_status', 'catalog_visibility', 'tax_status' ] as $field ) {
			$this->assertStringContainsString( $field, $joined );
		}

		// Chaque message doit nommer ce qui est accepté.
		$this->assertStringContainsString( 'simple', $joined );
		$this->assertStringContainsString( 'instock', $joined );
	}

	/**
	 * Le nom est requis à la création, facultatif à la mise à jour.
	 */
	public function test_name_is_required_on_create_only(): void {
		$payload = $this->payload( [ 'name' => '' ] );

		$this->assertFalse( McpWooProducts::validate( $payload, true )['ok'] );
		$this->assertTrue( McpWooProducts::validate( [ 'product_id' => 12 ], false )['ok'] );
	}

	/**
	 * Un champ non fourni reste null, pour ne jamais écraser une valeur
	 * existante lors d'une mise à jour partielle.
	 */
	public function test_absent_fields_stay_null_for_partial_update(): void {
		$result = McpWooProducts::validate( [ 'product_id' => 12, 'regular_price' => '10.00' ], false );

		$this->assertTrue( $result['ok'] );
		$this->assertNull( $result['data']['description'] );
		$this->assertNull( $result['data']['sku'] );
		$this->assertNull( $result['data']['categories'] );
	}

	// ── Libellé de prix (e-mail de confirmation) ──────────────────────────────

	/**
	 * Le prix est écrit en toutes lettres dans l'e-mail : c'est le garde-fou
	 * qui permet à l'administrateur de repérer une confusion centimes/décimales.
	 */
	public function test_summary_states_the_formatted_price(): void {
		$summary = McpWooProducts::summarize( $this->payload() );

		$this->assertStringContainsString( '200,00 €', $summary );
		$this->assertStringContainsString( 'TEST MCP WOO', $summary );
	}

	/**
	 * Une erreur de centimes saute aux yeux dans le résumé.
	 */
	public function test_summary_makes_a_cents_mistake_obvious(): void {
		$summary = McpWooProducts::summarize( $this->payload( [ 'regular_price' => '20000' ] ) );

		$this->assertStringContainsString( '20 000,00 €', $summary );
	}

	/**
	 * Le résumé remonte les erreurs plutôt qu'une fausse promesse.
	 */
	public function test_summary_reports_errors(): void {
		$summary = McpWooProducts::summarize( $this->payload( [ 'regular_price' => 'abc' ] ) );

		$this->assertStringContainsString( 'decimal amount', $summary );
	}

	// ── Garde WooCommerce ─────────────────────────────────────────────────────

	/**
	 * Sans WooCommerce, les outils refusent proprement au lieu de provoquer une
	 * erreur fatale sur une classe absente.
	 */
	public function test_operations_refuse_cleanly_without_woocommerce(): void {
		$this->assertFalse( McpWooProducts::is_available(), 'WooCommerce doit être absent en test unitaire.' );

		$results = [
			McpWooProducts::create( $this->payload() ),
			McpWooProducts::update( [ 'product_id' => 1 ] ),
			McpWooProducts::trash( 1 ),
			McpWooProducts::get( 1 ),
			McpWooProducts::list_products( [] ),
		];

		foreach ( $results as $result ) {
			$this->assertFalse( $result['ok'] );
			$this->assertStringContainsString( 'WooCommerce is not active', $result['error'] );
		}
	}
}
