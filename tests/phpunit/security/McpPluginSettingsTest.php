<?php
/**
 * Tests — McpPluginSettings (allowlisted plugin settings)
 *
 * Covers the five guarantees the feature must hold:
 *
 *   1. Nominal   — enabling the SEOPress Google News sitemap flips only the
 *                  targeted sub-key.
 *   2. Isolation — every sibling key of the serialized option survives.
 *   3. Allowlist — anything outside REGISTRY is refused.
 *   4. Capability — no manage_options, no write.
 *   5. Confirmation — calling the tool only enqueues; nothing is written until
 *                  the administrator confirms.
 *
 * Uses in-memory WP stubs from bootstrap.php:
 *   $g2rd_option_store — backs get_option()/update_option()
 *   $g2rd_user_can     — drives current_user_can() and WP_User::has_cap()
 *
 * @package    G2RD\Tests
 * @since      1.27.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAbilities;
use G2RD\McpPluginSettings;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the allowlist engine and its MCP tool surface.
 */
final class McpPluginSettingsTest extends TestCase {

	/** SEOPress option container, as stored by the plugin. */
	private const SEOPRESS_OPTION = 'seopress_pro_option_name';

	protected function setUp(): void {
		global $g2rd_option_store, $g2rd_user_can;

		// SEOPress must look installed for the engine to serve its settings.
		// The Google News settings live in the PRO option, so the PRO constant
		// is required too — the free plugin also defines SEOPRESS_VERSION.
		if ( ! \defined( 'SEOPRESS_VERSION' ) ) {
			\define( 'SEOPRESS_VERSION', '10.0.2' );
		}
		if ( ! \defined( 'SEOPRESS_PRO_VERSION' ) ) {
			\define( 'SEOPRESS_PRO_VERSION', '10.0.2' );
		}

		$g2rd_user_can = true;

		// A realistic SEOPress option: the News keys sit among many siblings
		// that must never be touched by a targeted write.
		$g2rd_option_store = [
			self::SEOPRESS_OPTION => [
				'seopress_news_name'          => 'Ancien nom',
				'seopress_broken_enable'      => '1',
				'seopress_rich_snippets_type' => 'Article',
				'seopress_404_enable'         => '1',
				'seopress_workflow_enable'    => '1',
			],
		];
	}

	// ── 1 + 2. Nominal write and sibling isolation ────────────────────────────

	/**
	 * Enabling the Google News sitemap sets only its own sub-key.
	 */
	public function test_enabling_news_sitemap_sets_only_target_subkey(): void {
		global $g2rd_option_store;

		$result = McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', true );

		$this->assertTrue( $result['ok'], 'Write should succeed for an allowlisted setting.' );
		$this->assertSame( self::SEOPRESS_OPTION, $result['option'] );
		$this->assertFalse( $result['old'], 'Setting was previously absent, so it reads as false.' );
		$this->assertTrue( $result['new'] );

		$stored = $g2rd_option_store[ self::SEOPRESS_OPTION ];
		$this->assertSame( '1', $stored['seopress_news_enable'], 'SEOPress stores "on" as the string 1.' );
	}

	/**
	 * A targeted write leaves every other key of the serialized option intact.
	 */
	public function test_sibling_keys_survive_the_write(): void {
		global $g2rd_option_store;

		$before = $g2rd_option_store[ self::SEOPRESS_OPTION ];

		McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', true );

		$after = $g2rd_option_store[ self::SEOPRESS_OPTION ];

		foreach ( $before as $key => $value ) {
			$this->assertArrayHasKey( $key, $after, "Sibling key {$key} disappeared." );
			$this->assertSame( $value, $after[ $key ], "Sibling key {$key} was modified." );
		}

		// Exactly one key was added.
		$this->assertSame(
			[ 'seopress_news_enable' ],
			\array_values( \array_diff( \array_keys( $after ), \array_keys( $before ) ) )
		);
	}

	/**
	 * Disabling removes the key entirely — SEOPress reads absence as "off",
	 * so writing an empty string would not disable the sitemap.
	 */
	public function test_disabling_unsets_the_key_rather_than_emptying_it(): void {
		global $g2rd_option_store;

		McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', true );
		$this->assertArrayHasKey( 'seopress_news_enable', $g2rd_option_store[ self::SEOPRESS_OPTION ] );

		McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', false );

		$this->assertArrayNotHasKey(
			'seopress_news_enable',
			$g2rd_option_store[ self::SEOPRESS_OPTION ],
			'SEOPress disables by removing the key, not by storing an empty value.'
		);
	}

	/**
	 * The publication name is sanitized and stored at its own sub-key.
	 */
	public function test_publication_name_is_sanitized(): void {
		global $g2rd_option_store;

		$result = McpPluginSettings::write( 'seopress', 'news_publication_name', '  Le Journal  ' );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'Le Journal', $g2rd_option_store[ self::SEOPRESS_OPTION ]['seopress_news_name'] );
		$this->assertSame( 'Ancien nom', $result['old'] );
	}

	/**
	 * Post types are written as a nested include map and filtered against
	 * the post types actually registered on the site.
	 */
	public function test_post_types_are_filtered_and_written_as_include_map(): void {
		global $g2rd_option_store;

		$result = McpPluginSettings::write(
			'seopress',
			'news_post_types',
			[ 'post', 'portfolio', 'not_a_real_cpt' ]
		);

		$this->assertTrue( $result['ok'] );

		$map = $g2rd_option_store[ self::SEOPRESS_OPTION ]['seopress_news_name_post_types_list'];

		$this->assertSame( '1', $map['post']['include'] );
		$this->assertSame( '1', $map['portfolio']['include'] );
		$this->assertArrayNotHasKey(
			'not_a_real_cpt',
			$map,
			'Unregistered post types must be dropped, not written.'
		);
	}

	/**
	 * Declared side effects reach the caller so sitemap rewrites get flushed.
	 */
	public function test_sitemap_write_declares_rewrite_flush(): void {
		$result = McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', true );

		$this->assertContains( 'flush_rewrite', $result['side_effects'] );
		$this->assertSame( '/news.xml', $result['verify_path'] );
	}

	// ── 3. Allowlist refusals ─────────────────────────────────────────────────

	/**
	 * A setting slug outside the allowlist is refused explicitly.
	 */
	public function test_unknown_setting_is_refused(): void {
		$result = McpPluginSettings::write( 'seopress', 'seopress_license_key', 'abcd-1234' );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not allowlisted', $result['error'] );
	}

	/**
	 * An entire plugin outside the allowlist is refused.
	 */
	public function test_unknown_plugin_is_refused(): void {
		$result = McpPluginSettings::write( 'some_random_plugin', 'anything', 'value' );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not allowlisted', $result['error'] );
	}

	/**
	 * An out-of-range enum value is refused rather than silently coerced.
	 */
	public function test_enum_value_outside_allowed_list_is_refused(): void {
		if ( ! \defined( 'WPSEO_VERSION' ) ) {
			\define( 'WPSEO_VERSION', '24.0' );
		}

		$result = McpPluginSettings::write( 'yoast', 'title_separator', 'sc-not-a-separator' );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not allowed', $result['error'] );
	}

	/**
	 * A setting on an allowlisted but inactive plugin is refused.
	 */
	public function test_inactive_plugin_is_refused(): void {
		// WooCommerce is allowlisted but the WooCommerce class is not loaded here.
		$result = McpPluginSettings::write( 'woocommerce', 'enable_reviews', true );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'not active', $result['error'] );
	}

	/**
	 * The introspection payload never leaks stored values.
	 */
	public function test_describe_never_exposes_values(): void {
		$described = McpPluginSettings::describe( false );
		$flat      = (string) \wp_json_encode( $described );

		$this->assertStringNotContainsString( 'Ancien nom', $flat, 'Introspection must not leak current values.' );
		$this->assertNotEmpty( $described );
	}

	// ── 4. Capability enforcement ─────────────────────────────────────────────

	/**
	 * Without manage_options the read tool refuses, whatever the allowlist says.
	 */
	public function test_read_tool_refuses_without_manage_options(): void {
		global $g2rd_user_can;
		$g2rd_user_can = false;

		$abilities = new McpAbilities();

		$result = $abilities->call(
			'g2rd_get-plugin-setting',
			[
				'plugin'  => 'seopress',
				'setting' => 'news_publication_name',
			],
			$this->gate( 'read_only' )
		);

		$this->assertTrue( $result['isError'] ?? false, 'Missing capability must produce a tool error.' );
		$this->assertStringContainsString( 'Insufficient permissions', (string) $result['content'][0]['text'] );
	}

	// ── 5. Confirmation queue enforcement ─────────────────────────────────────

	/**
	 * Calling the write tool without a queue never touches the option.
	 */
	public function test_write_tool_does_not_apply_without_confirmation(): void {
		global $g2rd_option_store;

		$before = $g2rd_option_store[ self::SEOPRESS_OPTION ];

		// No queue injected — the tool cannot execute, only report unavailability.
		$abilities = new McpAbilities();

		$result = $abilities->call(
			'g2rd_update-plugin-setting',
			[
				'plugin'  => 'seopress',
				'setting' => 'news_sitemap_enabled',
				'value'   => true,
			],
			$this->gate( 'editor' )
		);

		$this->assertTrue( $result['isError'] ?? false );
		$this->assertSame(
			$before,
			$g2rd_option_store[ self::SEOPRESS_OPTION ],
			'No write may happen before administrator confirmation.'
		);
		$this->assertArrayNotHasKey( 'seopress_news_enable', $g2rd_option_store[ self::SEOPRESS_OPTION ] );
	}

	/**
	 * A read_only token cannot reach the write tool at all.
	 */
	public function test_write_tool_requires_editor_scope(): void {
		$abilities = new McpAbilities();

		$result = $abilities->call(
			'g2rd_update-plugin-setting',
			[
				'plugin'  => 'seopress',
				'setting' => 'news_sitemap_enabled',
				'value'   => true,
			],
			$this->gate( 'read_only' )
		);

		$this->assertTrue( $result['isError'] ?? false );
		$this->assertStringContainsString( 'editor scope', (string) $result['content'][0]['text'] );
	}

	// ── Rollback ──────────────────────────────────────────────────────────────

	/**
	 * The captured previous value restores the setting exactly.
	 */
	public function test_previous_value_can_be_restored(): void {
		global $g2rd_option_store;

		$result = McpPluginSettings::write( 'seopress', 'news_publication_name', 'Nouveau nom' );
		$this->assertSame( 'Nouveau nom', $g2rd_option_store[ self::SEOPRESS_OPTION ]['seopress_news_name'] );

		$restored = McpPluginSettings::restore( 'seopress', 'news_publication_name', $result['old'] );

		$this->assertTrue( $restored['ok'] );
		$this->assertSame( 'Ancien nom', $g2rd_option_store[ self::SEOPRESS_OPTION ]['seopress_news_name'] );
	}

	/**
	 * Rollback returns the side effects it needs, so the caller can flush.
	 *
	 * Reverting a sitemap toggle without rebuilding rewrite rules leaves
	 * /news.xml answering for a sitemap that no longer exists.
	 */
	public function test_restore_reports_side_effects_for_the_caller(): void {
		$result   = McpPluginSettings::write( 'seopress', 'news_sitemap_enabled', true );
		$restored = McpPluginSettings::restore( 'seopress', 'news_sitemap_enabled', $result['old'] );

		$this->assertTrue( $restored['ok'] );
		$this->assertContains( 'flush_rewrite', $restored['side_effects'] );
	}

	/**
	 * An empty post type list clears the setting instead of being refused —
	 * otherwise a list can never be emptied, nor rolled back to empty.
	 */
	public function test_empty_post_type_list_clears_the_setting(): void {
		$seed = McpPluginSettings::write( 'seopress', 'news_post_types', [ 'post', 'page' ] );
		$this->assertSame( [ 'page', 'post' ], $seed['new'] );

		$cleared = McpPluginSettings::write( 'seopress', 'news_post_types', [] );

		$this->assertTrue( $cleared['ok'], 'An empty list must clear the setting, not fail.' );
		$this->assertSame( [], $cleared['new'] );
	}

	/**
	 * A non-empty list of entirely invalid slugs is still refused.
	 */
	public function test_all_invalid_post_types_is_still_refused(): void {
		$result = McpPluginSettings::write( 'seopress', 'news_post_types', [ 'nope', 'also_nope' ] );

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'No valid public post type', $result['error'] );
	}

	/**
	 * Submission order must not matter: the stored list is rebuilt in map-key
	 * order, so a strict comparison would report a spurious failure.
	 */
	public function test_post_type_order_does_not_break_idempotency(): void {
		McpPluginSettings::write( 'seopress', 'news_post_types', [ 'post', 'page' ] );

		$again = McpPluginSettings::write( 'seopress', 'news_post_types', [ 'page', 'post' ] );

		$this->assertTrue( $again['ok'], 'Same set in another order must be a no-op, not an error.' );
		$this->assertSame( $again['old'], $again['new'] );
	}

	/**
	 * Internal post types are refused: describe() only advertises public ones,
	 * and sanitize() must not accept more than what was advertised.
	 */
	public function test_internal_post_types_are_not_accepted(): void {
		$result = McpPluginSettings::write( 'seopress', 'news_post_types', [ 'post', 'revision' ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( [ 'post' ], $result['new'], 'Only public post types may be written.' );
	}

	/**
	 * The Google News settings declare the PRO requirement.
	 *
	 * Asserted declaratively: the free SEOPress plugin also defines
	 * SEOPRESS_VERSION, and a PHP constant cannot be undefined mid-process to
	 * exercise the refusal directly. The refusal path itself is covered by
	 * test_inactive_plugin_is_refused(), which shares the same guard shape.
	 */
	public function test_google_news_settings_require_seopress_pro(): void {
		foreach ( [ 'news_sitemap_enabled', 'news_publication_name', 'news_post_types' ] as $setting ) {
			$definition = McpPluginSettings::get_definition( 'seopress', $setting );

			$this->assertSame(
				[ 'constant' => 'SEOPRESS_PRO_VERSION' ],
				$definition['requires'] ?? null,
				"{$setting} writes a PRO option and must require the PRO constant."
			);
		}
	}

	/**
	 * Introspection reports whether a setting can actually take effect.
	 */
	public function test_describe_reports_availability(): void {
		$described = McpPluginSettings::describe( false );
		$by_plugin = \array_column( $described, null, 'plugin' );

		$news = \array_column( $by_plugin['seopress']['settings'], null, 'setting' );
		$this->assertTrue( $news['news_sitemap_enabled']['available'] );

		// WooCommerce is allowlisted but its class is not loaded here.
		$woo = \array_column( $by_plugin['woocommerce']['settings'], null, 'setting' );
		$this->assertFalse( $woo['enable_reviews']['available'] );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Builds a minimal authorized gate result.
	 *
	 * @param string $scope Token scope.
	 * @return array<string, mixed>
	 */
	private function gate( string $scope ): array {
		return [
			'allowed'   => true,
			'user_id'   => 1,
			'token_id'  => 1,
			'scope'     => $scope,
			'client_ip' => '127.0.0.1',
		];
	}
}
