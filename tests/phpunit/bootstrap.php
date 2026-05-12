<?php
declare(strict_types=1);

/**
 * Bootstrap PHPUnit pour le thème G2RD.
 */

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload; // phpcs:ignore WordPress.Files.IncludingFile.NoFileExtension -- Chemin construit dynamiquement, extension .php garantie.
}

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2) . '/');
}

// WordPress constants required by MCP classes.
if (!defined('AUTH_KEY')) {
    define('AUTH_KEY', 'g2rd_phpunit_auth_key_not_for_production_xYzAbCdEfGhIjKlMnOpQrStUvWxYz01');
}
