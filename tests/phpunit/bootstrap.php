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
if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = false) {
        return ($type === 'timestamp') ? time() : gmdate('Y-m-d H:i:s');
    }
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// In-memory transient store for rate-limiter tests.
global $g2rd_transient_store;
$g2rd_transient_store = [];

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        global $g2rd_transient_store;
        return $g2rd_transient_store[$transient] ?? false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        global $g2rd_transient_store;
        $g2rd_transient_store[$transient] = $value;
        return true;
    }
}
if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        global $g2rd_transient_store;
        unset($g2rd_transient_store[$transient]);
        return true;
    }
}
