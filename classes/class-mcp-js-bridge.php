<?php
/**
 * MCP JS Bridge — SP-5 admin bar badge + X-G2RD-Screen context middleware
 *
 * Two responsibilities:
 *
 * 1. Admin bar badge
 *    Adds an MCP node to the WordPress admin bar showing the number of pending
 *    confirmation requests. A small inline JS polls the REST API every 60 s and
 *    updates the count live without a page reload.
 *
 * 2. X-G2RD-Screen header middleware
 *    Injects a `@wordpress/api-fetch` middleware that appends the current admin
 *    page URL as the `X-G2RD-Screen` request header.  The MCP audit log stores
 *    this value in `screen_context` so operators can correlate MCP activity with
 *    the WordPress admin screen the request originated from.
 *
 * Both features are gated behind `manage_options` capability and are only active
 * in the WordPress admin (`is_admin()`).
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Admin bar badge and apiFetch middleware for MCP context propagation.
 */
class McpJsBridge {

	/** @var string Admin bar node ID. */
	private const BAR_NODE_ID = 'g2rd-mcp';

	/** @var string Poll interval in milliseconds (60 s). */
	private const POLL_INTERVAL_MS = 60000;

	// ── WordPress hooks ───────────────────────────────────────────────────────

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'admin_bar_menu',          [ $this, 'add_admin_bar_node' ], 100 );
		\add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_bridge_script' ] );
		\add_action( 'wp_enqueue_scripts',      [ $this, 'enqueue_bridge_script' ] ); // front-end admin bar
	}

	// ── Admin bar ─────────────────────────────────────────────────────────────

	/**
	 * Adds the MCP node to the WordPress admin bar.
	 *
	 * Only rendered for administrators (manage_options). The pending-count badge
	 * starts as an empty span; the inline JS fills it after the first REST poll.
	 *
	 * @param \WP_Admin_Bar $admin_bar WordPress admin bar instance.
	 * @return void
	 */
	public function add_admin_bar_node( \WP_Admin_Bar $admin_bar ): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$queue_url = \admin_url( 'admin.php?page=g2rd-options#mcp-queue' );

		$admin_bar->add_node( [
			'id'    => self::BAR_NODE_ID,
			'title' => '<span class="ab-icon dashicons dashicons-shield" style="top:2px"></span>'
				. '<span class="ab-label">MCP</span>'
				. '<span id="g2rd-mcp-badge" style="'
				. 'display:none;background:#d63638;color:#fff;border-radius:10px;'
				. 'padding:0 6px;font-size:11px;font-weight:700;margin-left:5px;'
				. 'vertical-align:middle;line-height:18px;"></span>',
			'href'  => \esc_url( $queue_url ),
			'meta'  => [
				'title' => \__( 'MCP — Demandes en attente', 'g2rd' ),
			],
		] );
	}

	// ── Inline JS ─────────────────────────────────────────────────────────────

	/**
	 * Outputs the bridge inline script for administrators.
	 *
	 * The script:
	 *   - Polls GET /g2rd/v1/mcp-queue every 60 s and updates the admin bar badge.
	 *   - Registers a @wordpress/api-fetch middleware that appends the current
	 *     admin screen URL as the X-G2RD-Screen header on every REST request.
	 *
	 * @return void
	 */
	public function enqueue_bridge_script(): void {
		if ( ! \is_admin_bar_showing() || ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		// wp-api-fetch must be enqueued so wp_add_inline_script can attach to it.
		\wp_enqueue_script( 'wp-api-fetch' );

		$queue_url = \esc_url_raw( \rest_url( 'g2rd/v1/mcp-queue' ) );
		$nonce     = \wp_create_nonce( 'wp_rest' );
		$interval  = (int) self::POLL_INTERVAL_MS;

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- all values are sanitized above
		\wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'(function(){
					var _queueUrl = %s;
					var _nonce    = %s;
					var _interval = %d;

					/* ── Badge updater ── */
					function g2rdMcpUpdateBadge(count) {
						var el = document.getElementById("g2rd-mcp-badge");
						if (!el) return;
						if (count > 0) {
							el.textContent = count;
							el.style.display = "inline-block";
						} else {
							el.style.display = "none";
						}
					}

					function g2rdMcpPoll() {
						fetch(_queueUrl + "?per_page=1", {
							headers: { "X-WP-Nonce": _nonce }
						})
						.then(function(r){ return r.json(); })
						.then(function(d){ g2rdMcpUpdateBadge(d && d.total ? d.total : 0); })
						.catch(function(){});
					}

					g2rdMcpPoll();
					setInterval(g2rdMcpPoll, _interval);

					/* ── apiFetch X-G2RD-Screen middleware ── */
					if (window.wp && window.wp.apiFetch) {
						wp.apiFetch.use(function(options, next) {
							if (!options.headers) { options.headers = {}; }
							options.headers["X-G2RD-Screen"] = window.location.href.substring(0, 500);
							return next(options);
						});
					}
				}());',
				\wp_json_encode( $queue_url ),
				\wp_json_encode( $nonce ),
				$interval
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
