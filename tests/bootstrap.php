<?php
/**
 * PHPUnit Bootstrap
 *
 * @package FPNewspaper\Tests
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Brain Monkey setup
if (!function_exists('tests_add_filter')) {
    function tests_add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // Mock per testing
    }
}

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
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
if (!defined('FP_NEWSPAPER_DIR')) {
    define('FP_NEWSPAPER_DIR', dirname(__DIR__) . '/');
}
if (!defined('FP_NEWSPAPER_URL')) {
    define('FP_NEWSPAPER_URL', 'http://example.com/wp-content/plugins/FP-Newspaper/');
}


