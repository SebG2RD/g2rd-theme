<?php
/**
 * Tests — McpProducts (FluentCart product layer)
 *
 * FluentCart is not installed in the unit test environment, so these tests cover
 * the layer that does NOT depend on it: payload validation, the cents contract,
 * subscription mapping into other_info, and the price wording used in the
 * confirmation email. The FluentCart write path itself is covered by the manual
 * protocol in docs/internal/mcp-fluentcart-products.md.
 *
 * @package    G2RD\Tests
 * @since      1.28.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpProducts;
use PHPUnit\Framework\TestCase;

/**
 * Verifies validation, price handling and error reporting.
 */
final class McpProductsTest extends TestCase {

	/**
	 * Builds a minimal valid payload.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 * @return array<string, mixed>
	 */
	private function payload( array $overrides = [] ): array {
		return \array_merge(
			[
				'title'            => 'TEST MCP',
				'status'           => 'publish',
				'fulfillment_type' => 'service',
				'variations'       => [
					[
						'payment_type'     => 'subscription',
						'price'            => 20000,
						'billing_interval' => 'month',
					],
				],
			],
			$overrides
		);
	}

	// ── Cas nominal ───────────────────────────────────────────────────────────

	/**
	 * The reference case from the brief: 200 EUR per month, renewing forever.
	 */
	public function test_subscription_payload_is_valid(): void {
		$result = McpProducts::validate( $this->payload() );

		$this->assertTrue( $result['ok'], 'A 200 EUR/month subscription must validate.' );

		$variation = $result['data']['variations'][0];
		$this->assertSame( 20000, $variation['price'], 'Price must stay in cents, untouched.' );
		$this->assertSame( 'subscription', $variation['payment_type'] );
		$this->assertSame( 'month', $variation['billing_interval'] );
		$this->assertSame( 0, $variation['cycles'], '0 cycles means unlimited renewal.' );
	}

	/**
	 * The first variation becomes the default when none is flagged, so
	 * default_variation_id can always be resolved.
	 */
	public function test_first_variation_becomes_default(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[ 'price' => 1000 ],
						[ 'price' => 2000 ],
					],
				]
			)
		);

		$this->assertTrue( $result['ok'] );
		$this->assertTrue( $result['data']['variations'][0]['is_default'] );
		$this->assertFalse( $result['data']['variations'][1]['is_default'] );
	}

	// ── Le contrat « centimes » ───────────────────────────────────────────────

	/**
	 * A decimal price is refused rather than silently truncated.
	 *
	 * This is the trap the cents contract exists for: 199.99 cast to int is 199,
	 * i.e. 1.99 EUR instead of 199.99 EUR, with no error anywhere.
	 */
	public function test_decimal_price_is_refused(): void {
		$result = McpProducts::validate(
			$this->payload( [ 'variations' => [ [ 'price' => 199.99 ] ] ] )
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'CENTS', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * A numeric string of cents is accepted: JSON transports integers as strings
	 * often enough that refusing them would be needless friction.
	 */
	public function test_numeric_string_price_is_accepted(): void {
		$result = McpProducts::validate(
			$this->payload( [ 'variations' => [ [ 'price' => '20000' ] ] ] )
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 20000, $result['data']['variations'][0]['price'] );
	}

	// ── Refus explicites ──────────────────────────────────────────────────────

	/**
	 * A product with no variation is refused: it would not be purchasable, which
	 * is precisely the production defect this tool was written to prevent.
	 */
	public function test_product_without_variation_is_refused(): void {
		$result = McpProducts::validate( $this->payload( [ 'variations' => [] ] ) );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not purchasable', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Every error is returned at once, with the accepted values, so an agent can
	 * fix all of them in a single retry instead of one per round trip.
	 */
	public function test_all_errors_are_reported_together_with_accepted_values(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'status'           => 'nonsense',
					'fulfillment_type' => 'teleportation',
					'variations'       => [
						[
							'price'            => 1000,
							'payment_type'     => 'barter',
							'billing_interval' => 'fortnight',
						],
					],
				]
			)
		);

		$this->assertFalse( $result['ok'] );
		$joined = \implode( ' | ', $result['errors'] );

		$this->assertStringContainsString( 'status', $joined );
		$this->assertStringContainsString( 'fulfillment_type', $joined );
		$this->assertStringContainsString( 'payment_type', $joined );
		// Each message must name what is accepted.
		$this->assertStringContainsString( 'digital', $joined );
		$this->assertStringContainsString( 'onetime', $joined );
	}

	/**
	 * A missing title is refused on create.
	 */
	public function test_title_is_required_on_create(): void {
		$result = McpProducts::validate( $this->payload( [ 'title' => '' ] ), true );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'title is required', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * A missing title is tolerated on update, where fields are optional.
	 */
	public function test_title_is_optional_on_update(): void {
		$result = McpProducts::validate( [ 'variations' => [ [ 'price' => 500 ] ] ], false );

		$this->assertTrue( $result['ok'] );
	}

	/**
	 * Two default variations are refused: default_variation_id holds one row.
	 */
	public function test_two_default_variations_are_refused(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[
							'price'      => 1000,
							'is_default' => true,
						],
						[
							'price'      => 2000,
							'is_default' => true,
						],
					],
				]
			)
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'Only one variation', \implode( ' ', $result['errors'] ) );
	}

	// ── Libellé de prix (e-mail de confirmation) ──────────────────────────────

	/**
	 * The confirmation email must state the price in human terms.
	 */
	public function test_subscription_price_is_described_for_the_email(): void {
		$result = McpProducts::validate( $this->payload() );
		$text   = McpProducts::describe_price( $result['data']['variations'][0] );

		$this->assertStringContainsString( '200,00', $text );
		$this->assertStringContainsString( 'par month', $text );
		$this->assertStringContainsString( 'illimité', $text );
	}

	/**
	 * A one-off payment is described as such, never as a subscription.
	 */
	public function test_onetime_price_is_described_as_one_off(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[
							'payment_type' => 'onetime',
							'price'        => 4990,
						],
					],
				]
			)
		);

		$text = McpProducts::describe_price( $result['data']['variations'][0] );

		$this->assertStringContainsString( '49,90', $text );
		$this->assertStringContainsString( 'unique', $text );
	}

	/**
	 * A limited-cycle subscription with a trial states both.
	 */
	public function test_limited_cycles_and_trial_are_described(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[
							'payment_type'     => 'subscription',
							'price'            => 10000,
							'billing_interval' => 'year',
							'cycles'           => 3,
							'trial_days'       => 14,
						],
					],
				]
			)
		);

		$text = McpProducts::describe_price( $result['data']['variations'][0] );

		$this->assertStringContainsString( '3 cycles', $text );
		$this->assertStringContainsString( '14 jours', $text );
	}

	/**
	 * The email summary surfaces validation errors rather than a bare title, so
	 * an administrator never approves an operation that will be refused.
	 */
	public function test_summary_reports_errors_instead_of_a_false_promise(): void {
		$summary = McpProducts::summarize( $this->payload( [ 'variations' => [] ] ) );

		$this->assertStringContainsString( 'not purchasable', $summary );
	}

	// ── Régressions relevées en revue ─────────────────────────────────────────

	/**
	 * Un tableau variations explicitement vide est refusé en mise à jour.
	 *
	 * Il passait la validation, supprimait tous les tarifs sans en écrire aucun,
	 * et l'opération réussissait — produit devenu non achetable en silence.
	 */
	public function test_explicit_empty_variations_is_refused_on_update(): void {
		$result = McpProducts::validate(
			[
				'product_id' => 42,
				'variations' => [],
			],
			false
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not purchasable', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Omettre la clé variations reste licite : les tarifs sont laissés intacts.
	 */
	public function test_omitting_variations_key_is_allowed_on_update(): void {
		$result = McpProducts::validate( [ 'product_id' => 42, 'title' => 'Nouveau nom' ], false );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( [], $result['data']['variations'] );
	}

	/**
	 * compare_at_price respecte le même contrat centimes que price.
	 *
	 * Il passait par absint() seul, donc une décimale était tronquée en silence
	 * alors que price la refusait — contrat annoncé mais pas tenu.
	 */
	public function test_decimal_compare_at_price_is_refused(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[
							'price'            => 20000,
							'compare_at_price' => 249.99,
						],
					],
				]
			)
		);

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'compare_at_price', \implode( ' ', $result['errors'] ) );
		$this->assertStringContainsString( 'CENTS', \implode( ' ', $result['errors'] ) );
	}

	/**
	 * Un compare_at_price entier reste accepté.
	 */
	public function test_integer_compare_at_price_is_accepted(): void {
		$result = McpProducts::validate(
			$this->payload(
				[
					'variations' => [
						[
							'price'            => 20000,
							'compare_at_price' => 24999,
						],
					],
				]
			)
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 24999, $result['data']['variations'][0]['compare_at_price'] );
	}

	// ── Garde FluentCart ──────────────────────────────────────────────────────

	/**
	 * Without FluentCart the tools refuse with a readable message instead of a
	 * PHP fatal error on a missing class.
	 */
	public function test_operations_refuse_cleanly_without_fluentcart(): void {
		$this->assertFalse( McpProducts::is_available(), 'FluentCart must be absent in unit tests.' );

		foreach ( [ McpProducts::create( $this->payload() ), McpProducts::update( [ 'product_id' => 1 ] ), McpProducts::trash( 1 ), McpProducts::get( 1 ), McpProducts::list_products( [] ) ] as $result ) {
			$this->assertFalse( $result['ok'] );
			$this->assertStringContainsString( 'FluentCart is not active', $result['error'] );
		}
	}
}
