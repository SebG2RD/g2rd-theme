<?php
/**
 * MCP Migration 001 — Create core MCP tables
 *
 * Creates g2rd_mcp_tokens and g2rd_mcp_audit_log.
 * Uses dbDelta() — safe to call multiple times (idempotent).
 * Hooked to after_switch_theme from functions.php.
 *
 * Note: g2rd_mcp_confirmation_queue is created in migration 002 (SP-3).
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/** @var string Current migration version. */
define( 'G2RD_MCP_DB_VERSION', '1.0.0' );

/**
 * Runs migration 001: create MCP tables if not already at current version.
 *
 * Safe to call on every theme activation — skips if already installed.
 *
 * @return void
 */
function g2rd_mcp_run_migration_001(): void {
	$installed = \get_option( 'g2rd_mcp_db_version', '' );

	if ( $installed === \G2RD_MCP_DB_VERSION ) {
		return;
	}

	g2rd_mcp_create_tokens_table();
	g2rd_mcp_create_audit_log_table();

	\update_option( 'g2rd_mcp_db_version', \G2RD_MCP_DB_VERSION, false );
}

/**
 * Creates or updates the g2rd_mcp_tokens table.
 *
 * @return void
 */
function g2rd_mcp_create_tokens_table(): void {
	global $wpdb;

	$table      = $wpdb->prefix . 'g2rd_mcp_tokens';
	$charset_collate = $wpdb->get_charset_collate();

	// dbDelta requires two spaces before column definitions and specific formatting.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- Migration code, intentional DDL.
	$sql = "CREATE TABLE {$table} (
  id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED  NOT NULL,
  token_name    VARCHAR(100)     NOT NULL,
  token_hash    VARCHAR(255)     NOT NULL,
  token_prefix  VARCHAR(21)      NOT NULL,
  scope         ENUM('read_only','editor','admin','full') NOT NULL DEFAULT 'read_only',
  allowed_ips   TEXT             NULL,
  last_used_at  DATETIME         NULL,
  last_used_ip  VARCHAR(45)      NULL,
  expires_at    DATETIME         NOT NULL,
  created_at    DATETIME         NOT NULL,
  revoked_at    DATETIME         NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY token_hash (token_hash),
  KEY user_id (user_id),
  KEY expires_at (expires_at),
  KEY token_prefix (token_prefix)
) ENGINE=InnoDB {$charset_collate};";
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange

	require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
	\dbDelta( $sql );
}

/**
 * Creates or updates the g2rd_mcp_audit_log table.
 *
 * The chain_hash column enables tamper detection: each row's hash
 * includes the previous row's hash, making any modification detectable.
 *
 * @return void
 */
function g2rd_mcp_create_audit_log_table(): void {
	global $wpdb;

	$table           = $wpdb->prefix . 'g2rd_mcp_audit_log';
	$charset_collate = $wpdb->get_charset_collate();

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- Migration code, intentional DDL.
	$sql = "CREATE TABLE {$table} (
  id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  created_at       DATETIME(3)       NOT NULL,
  user_id          BIGINT UNSIGNED   NOT NULL,
  token_id         BIGINT UNSIGNED   NOT NULL,
  ip_address       VARCHAR(45)       NOT NULL,
  user_agent       VARCHAR(255)      NOT NULL DEFAULT '',
  ability_name     VARCHAR(100)      NOT NULL,
  input_hash       VARCHAR(64)       NOT NULL,
  decision         ENUM('allowed','denied','pending','rolled_back') NOT NULL,
  denial_reason    VARCHAR(255)      NULL,
  execution_ms     SMALLINT UNSIGNED NULL,
  screen_context   VARCHAR(500)      NULL,
  chain_hash       VARCHAR(64)       NOT NULL,
  PRIMARY KEY  (id),
  KEY user_id (user_id),
  KEY token_id (token_id),
  KEY created_at (created_at),
  KEY decision (decision)
) ENGINE=InnoDB {$charset_collate};";
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange

	require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
	\dbDelta( $sql );
}
