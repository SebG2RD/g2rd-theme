<?php
/**
 * Tests unitaires — GitHubUpdater
 *
 * Couvre : checkForUpdates() (chemins sans HTTP), formatChangelog() et
 * get_download_url() via Reflection, ainsi que la signature du constructeur.
 *
 * @package    G2RD\Tests
 * @since      1.14.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\GitHubUpdater;
use G2RD\LicenseManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

// Tous les stubs sont définis dans bootstrap.php (namespace global).

/**
 * Tests GitHubUpdater sans appels HTTP ni système de fichiers.
 */
final class GithubUpdaterTest extends TestCase {

	private LicenseManager $lm;
	private GitHubUpdater  $updater;

	protected function setUp(): void {
		global $g2rd_option_store, $g2rd_transient_store, $g2rd_wp_remote_get_return, $g2rd_wp_remote_post_return;
		$g2rd_option_store          = [ 'admin_email' => 'admin@example.com' ];
		$g2rd_transient_store       = [];
		$g2rd_wp_remote_get_return  = null;
		$g2rd_wp_remote_post_return = null;

		$this->lm      = new LicenseManager();
		$this->updater = new GitHubUpdater( $this->lm );
	}

	// ── Constructeur ─────────────────────────────────────────────────────────

	public function test_constructor_accepts_license_manager(): void {
		self::assertInstanceOf( GitHubUpdater::class, $this->updater );
	}

	// ── checkForUpdates() — chemin licence inactive ───────────────────────────

	public function test_check_for_updates_returns_transient_unchanged_when_license_inactive(): void {
		// Pas d'option/transient → licence inactive → retour immédiat.
		$transient          = new \stdClass();
		$transient->checked = [ 'g2rd-theme' => '1.14.0' ];
		$transient->response = [];

		$result = $this->updater->checkForUpdates( $transient );
		self::assertSame( $transient, $result );
	}

	// ── checkForUpdates() — chemin checked vide ───────────────────────────────

	public function test_check_for_updates_returns_unchanged_when_checked_empty(): void {
		global $g2rd_option_store;
		// Rendre la licence active.
		$g2rd_option_store['g2rd_license_status'] = 'active';

		$transient           = new \stdClass();
		$transient->checked  = []; // vide → retour immédiat sans appel API
		$transient->response = [];

		$result = $this->updater->checkForUpdates( $transient );
		self::assertSame( $transient, $result );
	}

	// ── checkForUpdates() — erreur réseau ────────────────────────────────────

	public function test_check_for_updates_returns_unchanged_on_network_error(): void {
		global $g2rd_option_store, $g2rd_wp_remote_get_return;
		$g2rd_option_store['g2rd_license_status'] = 'active';
		$g2rd_wp_remote_get_return = new \WP_Error( 'http_request_failed', 'timeout' );

		$transient           = new \stdClass();
		$transient->checked  = [ 'g2rd-theme' => '1.14.0' ];
		$transient->response = [];

		$result = $this->updater->checkForUpdates( $transient );
		self::assertEmpty( $result->response );
	}

	// ── formatChangelog() — méthode privée via Reflection ────────────────────

	private function callFormatChangelog( string $markdown ): string {
		$ref = new ReflectionMethod( GitHubUpdater::class, 'formatChangelog' );
		$ref->setAccessible( true );
		return (string) $ref->invoke( $this->updater, $markdown );
	}

	public function test_format_changelog_converts_h3_heading(): void {
		$html = $this->callFormatChangelog( "### Titre de section\n" );
		self::assertStringContainsString( '<h4>', $html );
		self::assertStringContainsString( 'Titre de section', $html );
	}

	public function test_format_changelog_converts_h2_heading(): void {
		$html = $this->callFormatChangelog( "## Version 1.14.0\n" );
		self::assertStringContainsString( '<h3>', $html );
		self::assertStringContainsString( 'Version 1.14.0', $html );
	}

	public function test_format_changelog_converts_list_items(): void {
		$html = $this->callFormatChangelog( "- Premier élément\n- Deuxième élément\n" );
		self::assertStringContainsString( '<ul>', $html );
		self::assertStringContainsString( '<li>', $html );
		self::assertStringContainsString( 'Premier', $html );
	}

	public function test_format_changelog_converts_bold_text(): void {
		$html = $this->callFormatChangelog( "- **Nouveau** : fonctionnalité IA\n" );
		self::assertStringContainsString( '<strong>', $html );
		self::assertStringContainsString( 'Nouveau', $html );
	}

	public function test_format_changelog_closes_list_on_empty_line(): void {
		$md   = "- Item A\n- Item B\n\nParagraphe\n";
		$html = $this->callFormatChangelog( $md );
		self::assertStringContainsString( '</ul>', $html );
		self::assertStringContainsString( '<p>', $html );
	}

	// ── get_download_url() — méthode privée via Reflection ───────────────────

	private function callGetDownloadUrl( array $releaseData ): string {
		$ref = new ReflectionMethod( GitHubUpdater::class, 'get_download_url' );
		$ref->setAccessible( true );
		return (string) $ref->invoke( $this->updater, $releaseData );
	}

	public function test_get_download_url_prefers_zip_asset(): void {
		$data = [
			'zipball_url' => 'https://api.github.com/repos/SebG2RD/g2rd-theme/zipball/v1.14.0',
			'assets'      => [
				[
					'name'                 => 'g2rd-theme.zip',
					'browser_download_url' => 'https://github.com/SebG2RD/g2rd-theme/releases/download/v1.14.0/g2rd-theme.zip',
				],
			],
		];
		$url = $this->callGetDownloadUrl( $data );
		self::assertStringContainsString( 'releases/download', $url );
		self::assertStringEndsWith( '.zip', $url );
	}

	public function test_get_download_url_falls_back_to_zipball(): void {
		$data = [
			'zipball_url' => 'https://api.github.com/repos/SebG2RD/g2rd-theme/zipball/v1.14.0',
			'assets'      => [],
		];
		$url = $this->callGetDownloadUrl( $data );
		self::assertStringContainsString( 'zipball', $url );
	}

	public function test_get_download_url_returns_empty_string_when_no_data(): void {
		$url = $this->callGetDownloadUrl( [] );
		self::assertSame( '', $url );
	}
}
