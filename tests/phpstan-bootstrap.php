<?php
/**
 * PHPStan Bootstrap
 * Define WordPress stubs to prevent errors
 *
 * @package FPNewspaper\Tests
 */

// WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('FP_NEWSPAPER_VERSION')) {
    define('FP_NEWSPAPER_VERSION', '1.0.0');
}

// WordPress function stubs
if (!function_exists('add_action')) {
    function add_action(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1): bool { return true; }
}
if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1): bool { return true; }
}
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string { return htmlspecialchars($text); }
}
if (!function_exists('esc_html')) {
    function esc_html(string $text): string { return htmlspecialchars($text); }
}
if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES); }
}
if (!function_exists('esc_url')) {
    function esc_url(string $url): string { return filter_var($url, FILTER_SANITIZE_URL) ?: ''; }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $options = 0, int $depth = 512) { return json_encode($data, $options, $depth); }
}
if (!function_exists('get_transient')) {
    function get_transient(string $transient) { return false; }
}
if (!function_exists('set_transient')) {
    function set_transient(string $transient, $value, int $expiration = 0): bool { return true; }
}
if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool { return true; }
}
if (!function_exists('do_action')) {
    function do_action(string $hook_name, ...$arg): void {}
}
if (!function_exists('apply_filters')) {
    function apply_filters(string $hook_name, $value, ...$args) { return $value; }
}


