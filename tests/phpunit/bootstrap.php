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
global $g2rd_option_store;
$g2rd_option_store = ['admin_email' => 'admin@example.com'];

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $g2rd_option_store;
        return $g2rd_option_store[$option] ?? $default;
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
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

// WordPress hook stubs (no-ops for unit testing).
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
}
if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = [], $override = false) { return true; }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return is_array($value) ? array_map('wp_unslash', $value) : stripslashes((string) $value);
    }
}

// WordPress REST API stubs.
if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server {
        const CREATABLE  = 'POST';
        const READABLE   = 'GET';
        const EDITABLE   = 'PUT, PATCH';
        const DELETABLE  = 'DELETE';
        const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private array $headers = [];
        private ?array $json_params = null;

        public function get_header(string $key): ?string {
            return $this->headers[strtolower($key)] ?? null;
        }
        public function get_json_params(): ?array {
            return $this->json_params;
        }
        public function set_header(string $key, string $value): void {
            $this->headers[strtolower($key)] = $value;
        }
        public function set_json_params(array $params): void {
            $this->json_params = $params;
        }
    }
}
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public mixed $data;
        public int $status;
        public function __construct(mixed $data = null, int $status = 200) {
            $this->data   = $data;
            $this->status = $status;
        }
    }
}

// WordPress post stubs for McpAbilities tests.
if (!class_exists('WP_Post')) {
    class WP_Post {
        public int    $ID               = 0;
        public string $post_title       = '';
        public string $post_name        = '';
        public string $post_status      = 'publish';
        public string $post_date_gmt    = '';
        public string $post_modified_gmt = '';
        public string $post_content     = '';
        public string $post_excerpt     = '';
        public string $post_type        = 'post';
        public int    $post_author      = 1;
    }
}

// In-memory post store.
global $g2rd_post_store;
$g2rd_post_store = [];
if (!function_exists('get_post')) {
    function get_post($post_id, $output = OBJECT, $filter = 'raw') {
        global $g2rd_post_store;
        return $g2rd_post_store[(int) $post_id] ?? null;
    }
}

// In-memory WP_Query store (controlled per test via global).
global $g2rd_query_store;
$g2rd_query_store = ['posts' => [], 'found_posts' => 0, 'max_num_pages' => 1];
if (!class_exists('WP_Query')) {
    class WP_Query {
        public array $posts        = [];
        public int   $found_posts  = 0;
        public int   $max_num_pages = 0;

        public function __construct(array $args = []) {
            global $g2rd_query_store;
            $this->posts         = $g2rd_query_store['posts']         ?? [];
            $this->found_posts   = $g2rd_query_store['found_posts']   ?? 0;
            $this->max_num_pages = $g2rd_query_store['max_num_pages'] ?? 1;
        }
    }
}

// Post type object store — pre-populated for standard types.
global $g2rd_post_type_store;
$post_stub = (object) ['publicly_queryable' => true, 'public' => true];
$g2rd_post_type_store = ['post' => $post_stub, 'page' => $post_stub];
if (!function_exists('get_post_type_object')) {
    function get_post_type_object($post_type) {
        global $g2rd_post_type_store;
        return $g2rd_post_type_store[$post_type] ?? null;
    }
}

// WordPress template tag stubs.
if (!function_exists('get_the_title')) {
    function get_the_title($post = null) {
        if (is_object($post) && isset($post->post_title)) return $post->post_title;
        $p = get_post((int) $post);
        return $p ? $p->post_title : '';
    }
}
if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt($post = null) {
        if (is_object($post) && isset($post->post_excerpt)) return $post->post_excerpt;
        return '';
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink($post = 0) {
        $id = is_object($post) ? $post->ID : (int) $post;
        return "https://example.com/?p={$id}";
    }
}
if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        $u               = new stdClass();
        $u->display_name = "User {$user_id}";
        $u->user_login   = "user{$user_id}";
        $u->user_email   = "user{$user_id}@example.com";
        return $u;
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        $data = [
            'name'        => 'Test Site',
            'description' => 'Just another WordPress site',
            'url'         => 'https://example.com',
            'language'    => 'en-US',
        ];
        return $data[$show] ?? '';
    }
}
if (!function_exists('wp_timezone_string')) {
    function wp_timezone_string() { return 'UTC'; }
}
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false) {
        return strip_tags((string) $string);
    }
}

// $wp_version global for exec_get_site_info.
global $wp_version;
$wp_version = '6.5.0-test';

// ── SP-3 stubs ────────────────────────────────────────────────────────────────

// WP_Error class + is_wp_error().
if (!class_exists('WP_Error')) {
    class WP_Error {
        public string $code;
        public string $message;
        public function __construct(string $code = '', string $message = '', $data = '') {
            $this->code    = $code;
            $this->message = $message;
        }
        public function get_error_message(string $code = ''): string {
            return $this->message;
        }
        public function get_error_code(): string {
            return $this->code;
        }
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool {
        return ($thing instanceof WP_Error);
    }
}

// Current-user simulation (controlled per test via $g2rd_current_user_id).
global $g2rd_current_user_id;
$g2rd_current_user_id = 0;

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int {
        global $g2rd_current_user_id;
        return (int) $g2rd_current_user_id;
    }
}
if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($id) {
        global $g2rd_current_user_id;
        $g2rd_current_user_id = (int) $id;
        return new stdClass();
    }
}

// Capability check (controlled per test via $g2rd_user_can).
global $g2rd_user_can;
$g2rd_user_can = true;

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args): bool {
        global $g2rd_user_can;
        return (bool) $g2rd_user_can;
    }
}

// In-memory wp_insert_post / wp_update_post (use $g2rd_post_store).
global $g2rd_wp_insert_post_result;
$g2rd_wp_insert_post_result = null; // null = auto-generate ID

if (!function_exists('wp_insert_post')) {
    function wp_insert_post(array $postarr, bool $wp_error = false) {
        global $g2rd_wp_insert_post_result, $g2rd_post_store;
        if (null !== $g2rd_wp_insert_post_result) {
            return $g2rd_wp_insert_post_result;
        }
        $id            = count($g2rd_post_store) + 100;
        $post          = new WP_Post();
        $post->ID      = $id;
        $post->post_title   = $postarr['post_title']   ?? '';
        $post->post_content = $postarr['post_content'] ?? '';
        $post->post_status  = $postarr['post_status']  ?? 'draft';
        $post->post_type    = $postarr['post_type']    ?? 'post';
        $post->post_excerpt = $postarr['post_excerpt'] ?? '';
        $g2rd_post_store[$id] = $post;
        return $id;
    }
}

global $g2rd_wp_update_post_result;
$g2rd_wp_update_post_result = null; // null = auto (return ID from store)

if (!function_exists('wp_update_post')) {
    function wp_update_post(array $postarr, bool $wp_error = false) {
        global $g2rd_wp_update_post_result, $g2rd_post_store;
        if (null !== $g2rd_wp_update_post_result) {
            return $g2rd_wp_update_post_result;
        }
        $id = (int) ($postarr['ID'] ?? 0);
        if (!isset($g2rd_post_store[$id])) {
            return $wp_error ? new WP_Error('invalid_post', 'Post not found') : 0;
        }
        $post = $g2rd_post_store[$id];
        if (isset($postarr['post_title']))   $post->post_title   = $postarr['post_title'];
        if (isset($postarr['post_content'])) $post->post_content = $postarr['post_content'];
        if (isset($postarr['post_excerpt'])) $post->post_excerpt = $postarr['post_excerpt'];
        $g2rd_post_store[$id] = $post;
        return $id;
    }
}

// wp_mail spy (records calls, controlled per test via $g2rd_wp_mail_log).
global $g2rd_wp_mail_log;
$g2rd_wp_mail_log = [];

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []): bool {
        global $g2rd_wp_mail_log;
        $g2rd_wp_mail_log[] = compact('to', 'subject', 'message');
        return true;
    }
}

// URL helpers.
if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, string $url = ''): string {
        if (is_array($args) && '' !== $url) {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            return $url . $sep . http_build_query($args);
        }
        return $url;
    }
}

// Sanitization helpers missing from SP-2 stubs.
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str): string {
        return trim((string) $str);
    }
}
if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data): string {
        return (string) $data;
    }
}

// i18n stub.
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

// $wpdb stub — controllable per test via globals.
global $g2rd_wpdb_get_row_return, $g2rd_wpdb_insert_return, $g2rd_wpdb_update_return, $g2rd_wpdb_query_return;
$g2rd_wpdb_get_row_return  = null;
$g2rd_wpdb_insert_return   = true;  // truthy = success
$g2rd_wpdb_update_return   = 1;     // rows affected
$g2rd_wpdb_query_return    = 0;     // rows affected

class G2rdWpdbStub {
    public int    $insert_id = 0;
    public array  $inserts   = [];
    public array  $updates   = [];
    public string $prefix    = 'wp_';
    private int   $auto_id   = 1;

    public function insert(string $table, array $data, $format = null) {
        global $g2rd_wpdb_insert_return;
        if ($g2rd_wpdb_insert_return) {
            $this->insert_id = $this->auto_id++;
            $this->inserts[] = ['table' => $table, 'data' => $data];
            return 1;
        }
        return false;
    }

    public function get_row($query, $output = OBJECT) {
        global $g2rd_wpdb_get_row_return;
        return $g2rd_wpdb_get_row_return;
    }

    public function update(string $table, array $data, array $where, $format = null, $where_format = null) {
        global $g2rd_wpdb_update_return;
        $this->updates[] = ['table' => $table, 'data' => $data, 'where' => $where];
        return $g2rd_wpdb_update_return;
    }

    public function query(string $query) {
        global $g2rd_wpdb_query_return;
        return $g2rd_wpdb_query_return;
    }

    // Simple prepare: replaces %s/%d with quoted placeholders.
    public function prepare(string $query, ...$args): string {
        $i = 0;
        return preg_replace_callback(
            '/%[sd]/',
            static function($m) use ($args, &$i) { return "'" . addslashes((string) ($args[$i++] ?? '')) . "'"; },
            $query
        );
    }

    public function get_charset_collate(): string {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }
}

global $wpdb;
$wpdb = new G2rdWpdbStub();

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

// ── Stubs licensing (LicenseManager + GitHubUpdater) ─────────────────────────

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'https://example.test' . $path;
    }
}
if (!function_exists('delete_option')) {
    function delete_option(string $option): bool {
        global $g2rd_option_store;
        unset($g2rd_option_store[$option]);
        return true;
    }
}
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled(string $hook, array $args = []): int|false {
        return false;
    }
}
if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = []): bool {
        return true;
    }
}
if (!function_exists('get_template_directory')) {
    function get_template_directory(): string {
        return '/var/www/html/wp-content/themes/g2rd-theme';
    }
}
if (!function_exists('wp_get_theme')) {
    function wp_get_theme(string $slug = ''): object {
        return new class {
            public function get(string $field): string { return '1.14.0'; }
        };
    }
}
if (!function_exists('self_admin_url')) {
    function self_admin_url(string $path = ''): string {
        return 'https://example.test/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('get_site_transient')) {
    function get_site_transient(string $transient): mixed {
        return false;
    }
}
if (!function_exists('esc_html')) {
    function esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string {
        return rtrim($string, '/\\') . '/';
    }
}
if (!function_exists('untrailingslashit')) {
    function untrailingslashit(string $string): string {
        return rtrim($string, '/\\');
    }
}
if (!function_exists('esc_url')) {
    function esc_url(string $url): string {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

// HTTP spies — contrôlés par globals réinitialisés dans chaque setUp().
global $g2rd_wp_remote_post_return, $g2rd_wp_remote_get_return;
$g2rd_wp_remote_post_return = null;
$g2rd_wp_remote_get_return  = null;

if (!function_exists('wp_remote_post')) {
    function wp_remote_post(string $url, array $args = []): mixed {
        global $g2rd_wp_remote_post_return;
        return $g2rd_wp_remote_post_return ?? new WP_Error('no_stub', 'wp_remote_post non configuré');
    }
}
if (!function_exists('wp_remote_get')) {
    function wp_remote_get(string $url, array $args = []): mixed {
        global $g2rd_wp_remote_get_return;
        return $g2rd_wp_remote_get_return ?? new WP_Error('no_stub', 'wp_remote_get non configuré');
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code(mixed $response): int {
        if (is_wp_error($response)) { return 0; }
        return (int) ($response['response']['code'] ?? 0);
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body(mixed $response): string {
        if (is_wp_error($response)) { return ''; }
        return (string) ($response['body'] ?? '');
    }
}
