<?php
/**
 * Tests unitaires — LicenseManager
 *
 * Couvre : is_active(), get_display_data(), get_license_data(),
 * isLicenseValid(), activate(), deactivate(), detect_domain_change().
 * Aucun appel HTTP réel — wp_remote_post est contrôlé via global.
 *
 * @package    G2RD\Tests
 * @since      1.14.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\LicenseManager;
use PHPUnit\Framework\TestCase;

// Tous les stubs sont définis dans bootstrap.php (namespace global).

/**
 * Tests LicenseManager sans base de données ni appels HTTP réels.
 */
final class LicenseManagerTest extends TestCase {

	private LicenseManager $lm;

	protected function setUp(): void {
		global $g2rd_option_store, $g2rd_transient_store, $g2rd_wp_remote_post_return;
		$g2rd_option_store          = [ 'admin_email' => 'admin@example.com' ];
		$g2rd_transient_store       = [];
		$g2rd_wp_remote_post_return = null;

		$this->lm = new LicenseManager();
	}

	// ── is_active() ──────────────────────────────────────────────────────────

	public function test_is_active_returns_false_when_nothing_set(): void {
		self::assertFalse( LicenseManager::is_active() );
	}

	public function test_is_active_returns_true_from_transient(): void {
		global $g2rd_transient_store;
		$g2rd_transient_store['g2rd_license_valid'] = true;
		self::assertTrue( LicenseManager::is_active() );
	}

	public function test_is_active_returns_false_for_expired_status(): void {
		global $g2rd_option_store;
		$g2rd_option_store['g2rd_license_status'] = 'expired';
		self::assertFalse( LicenseManager::is_active() );
	}

	public function test_is_active_reads_option_when_no_transient(): void {
		global $g2rd_option_store;
		$g2rd_option_store['g2rd_license_status'] = 'active';
		self::assertTrue( LicenseManager::is_active() );
	}

	// ── get_display_data() ───────────────────────────────────────────────────

	public function test_get_display_data_defaults(): void {
		$data = LicenseManager::get_display_data();
		self::assertSame( 'inactive', $data['status'] );
		self::assertSame( '', $data['masked_key'] );
		self::assertSame( [], $data['data'] );
	}

	public function test_get_display_data_masks_license_key(): void {
		global $g2rd_option_store;
		$g2rd_option_store['g2rd_license_key']    = 'ABCD1234EFGH5678';
		$g2rd_option_store['g2rd_license_status'] = 'active';
		$data = LicenseManager::get_display_data();
		self::assertSame( 'active', $data['status'] );
		self::assertStringStartsWith( 'ABCD1234', $data['masked_key'] );
		self::assertStringContainsString( '•', $data['masked_key'] );
	}

	// ── get_license_data() ───────────────────────────────────────────────────

	public function test_get_license_data_returns_empty_when_not_set(): void {
		self::assertSame( [], $this->lm->get_license_data() );
	}

	public function test_get_license_data_decodes_stored_json(): void {
		global $g2rd_option_store;
		$g2rd_option_store['g2rd_license_data'] = '{"expires_at":"2026-12-31","max_activations":3}';
		$data = $this->lm->get_license_data();
		self::assertSame( '2026-12-31', $data['expires_at'] );
		self::assertSame( 3, $data['max_activations'] );
	}

	// ── isLicenseValid() ─────────────────────────────────────────────────────

	public function test_is_license_valid_delegates_to_is_active(): void {
		global $g2rd_option_store;
		$g2rd_option_store['g2rd_license_status'] = 'active';
		self::assertTrue( $this->lm->isLicenseValid() );
	}

	// ── activate() ───────────────────────────────────────────────────────────

	public function test_activate_returns_error_for_empty_key(): void {
		$result = $this->lm->activate( '' );
		self::assertFalse( $result['success'] );
		self::assertNotEmpty( $result['message'] );
	}

	public function test_activate_returns_error_on_network_failure(): void {
		global $g2rd_wp_remote_post_return;
		$g2rd_wp_remote_post_return = new \WP_Error( 'http_request_failed', 'cURL error 28' );
		$result = $this->lm->activate( 'VALID-KEY-1234' );
		self::assertFalse( $result['success'] );
	}

	public function test_activate_returns_error_when_api_rejects_key(): void {
		global $g2rd_wp_remote_post_return;
		$g2rd_wp_remote_post_return = [
			'response' => [ 'code' => 400 ],
			'body'     => json_encode( [ 'success' => false, 'message' => 'Clé invalide.' ] ),
		];
		$result = $this->lm->activate( 'BAD-KEY-0000' );
		self::assertFalse( $result['success'] );
	}

	public function test_activate_returns_success_and_caches_transient(): void {
		global $g2rd_wp_remote_post_return, $g2rd_transient_store;
		$g2rd_wp_remote_post_return = [
			'response' => [ 'code' => 200 ],
			'body'     => json_encode( [
				'success' => true,
				'license' => [ 'expires_at' => '2027-01-01' ],
			] ),
		];
		$result = $this->lm->activate( 'GOOD-KEY-9999' );
		self::assertTrue( $result['success'] );
		// store_license() appelle set_transient → vérifié via le store mémoire.
		self::assertTrue( (bool) ( $g2rd_transient_store['g2rd_license_valid'] ?? false ) );
	}

	// ── deactivate() ─────────────────────────────────────────────────────────

	public function test_deactivate_always_returns_success(): void {
		$result = $this->lm->deactivate();
		self::assertTrue( $result['success'] );
		self::assertNotEmpty( $result['message'] );
	}

	public function test_deactivate_clears_license_transient(): void {
		global $g2rd_transient_store;
		$g2rd_transient_store['g2rd_license_valid'] = true;
		$this->lm->deactivate();
		self::assertFalse( isset( $g2rd_transient_store['g2rd_license_valid'] ) );
	}

	// ── detect_domain_change() ───────────────────────────────────────────────

	public function test_detect_domain_change_noops_when_no_stored_domain(): void {
		// Aucune exception → pass.
		$this->lm->detect_domain_change();
		self::assertTrue( true );
	}

	public function test_detect_domain_change_invalidates_transient_on_migration(): void {
		global $g2rd_option_store, $g2rd_transient_store;
		// Domain stocké ≠ home_url() → migration détectée.
		$g2rd_option_store['g2rd_license_domain']  = 'https://old-site.test';
		$g2rd_transient_store['g2rd_license_valid'] = true;
		$this->lm->detect_domain_change();
		// delete_transient() doit avoir supprimé le transient.
		self::assertFalse( isset( $g2rd_transient_store['g2rd_license_valid'] ) );
	}
}
