<?php
/**
 * MCP Assistant — SP-5 Gutenberg editor sidebar plugin
 *
 * Enqueues the compiled `g2rd-mcp-assistant` registerPlugin script in the
 * Block Editor for administrators. The plugin renders a PluginDocumentSettingPanel
 * showing the MCP server status, pending confirmation count, and anomaly summary.
 *
 * Only loaded for users with `manage_options` capability to avoid unnecessary
 * REST calls from non-admin editors.
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Enqueues the MCP Assistant Gutenberg sidebar plugin.
 */
class McpAssistant {

	// ── WordPress hooks ───────────────────────────────────────────────────────

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	// ── Assets ────────────────────────────────────────────────────────────────

	/**
	 * Enqueues the compiled sidebar plugin script in the Block Editor.
	 *
	 * Skipped silently if the build artifact does not exist (e.g. in a ZIP
	 * distribution that did not include the MCP assistant build).
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$dir_path = \get_template_directory();
		$dir_uri  = \get_template_directory_uri();
		$js_path  = $dir_path . '/blocks/g2rd-mcp-assistant/build/index.js';

		if ( ! \file_exists( $js_path ) ) {
			return;
		}

		$asset_file = $dir_path . '/blocks/g2rd-mcp-assistant/build/index.asset.php';
		$asset      = \file_exists( $asset_file )
			? require $asset_file // phpcs:ignore PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt -- chemin server-controlled, extension .php garantie par la concaténation
			: [
				'dependencies' => [ 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-api-fetch' ],
				'version'      => '1.0.0',
			];

		\wp_enqueue_script(
			'g2rd-mcp-assistant',
			$dir_uri . '/blocks/g2rd-mcp-assistant/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		\wp_localize_script(
			'g2rd-mcp-assistant',
			'G2RDMcpAssistantData',
			[
				'adminUrl' => \admin_url( '' ),
				'nonce'    => \wp_create_nonce( 'wp_rest' ),
			]
		);
	}
}
