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

// WordPress function stubs for MCP unit tests (no WP core loaded).
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int) $maybeint);
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim((string) $str);
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return strtolower(preg_replace('/[^a-z0-9_\-\/]/', '', strtolower((string) $key)));
    }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}
