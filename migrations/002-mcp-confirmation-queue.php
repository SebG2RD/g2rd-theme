<?php
/**
 * MCP Migration 002 — Create confirmation queue table
 *
 * Creates g2rd_mcp_confirmation_queue.
 * Uses dbDelta() — safe to call multiple times (idempotent).
 * Hooked to after_switch_theme from functions.php.
 *
 * Schema:
 *   - confirm_token / reject_token : 64-char hex (32 random bytes), single-use
 *   - arguments_enc                : AES-256-GCM encrypted JSON payload (RGPD)
 *   - status                       : pending → confirmed | rejected | expired
 *   - expires_at                   : 15-minute TTL from creation
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/** @var string Current migration version for migration 002. */
define( 'G2RD_MCP_DB_VERSION_002', '1.0.0' );

/**
 * Runs migration 002: creates confirmation queue table if not at current version.
 *
 * @return void
 */
function g2rd_mcp_run_migration_002(): void {
	$installed = \get_option( 'g2rd_mcp_db_version_002', '' );

	if ( $installed === \G2RD_MCP_DB_VERSION_002 ) {
		return;
	}

	g2rd_mcp_create_confirmation_queue_table();

	\update_option( 'g2rd_mcp_db_version_002', \G2RD_MCP_DB_VERSION_002, false );
}

/**
 * Creates or updates the g2rd_mcp_confirmation_queue table.
 *
 * @return void
 */
function g2rd_mcp_create_confirmation_queue_table(): void {
	global $wpdb;

	$table           = $wpdb->prefix . 'g2rd_mcp_confirmation_queue';
	$charset_collate = $wpdb->get_charset_collate();

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- Migration code, intentional DDL.
	$sql = "CREATE TABLE {$table} (
  id            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  confirm_token VARCHAR(64)       NOT NULL,
  reject_token  VARCHAR(64)       NOT NULL,
  audit_log_id  BIGINT UNSIGNED   NOT NULL DEFAULT 0,
  user_id       BIGINT UNSIGNED   NOT NULL,
  token_id      BIGINT UNSIGNED   NOT NULL,
  ip_address    VARCHAR(45)       NOT NULL,
  ability_name  VARCHAR(100)      NOT NULL,
  arguments_enc TEXT              NOT NULL,
  status        ENUM('pending','confirmed','rejected','expired') NOT NULL DEFAULT 'pending',
  created_at    DATETIME          NOT NULL,
  expires_at    DATETIME          NOT NULL,
  resolved_at   DATETIME          NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY confirm_token (confirm_token),
  UNIQUE KEY reject_token (reject_token),
  KEY user_id (user_id),
  KEY status (status),
  KEY expires_at (expires_at)
) ENGINE=InnoDB {$charset_collate};";
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange

	require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
	\dbDelta( $sql );
}
