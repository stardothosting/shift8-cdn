<?php
/**
 * Brain/Monkey PHPUnit bootstrap for Shift8 CDN
 *
 * @package Shift8\CDN\Tests
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize Brain/Monkey
Brain\Monkey\setUp();

use Brain\Monkey\Functions;

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

if (!defined('WP_PLUGIN_URL')) {
    define('WP_PLUGIN_URL', 'http://example.com/wp-content/plugins');
}

if (!defined('PHP_INT_MAX')) {
    define('PHP_INT_MAX', 9223372036854775807);
}

// Define Shift8 CDN constants
define('S8CDN_FILE', 'shift8-cdn/shift8-cdn.php');
define('S8CDN_DIR', dirname(__DIR__));
define('S8CDN_TEST_README_URL', WP_PLUGIN_URL . '/shift8-cdn/test/test.png');
define('S8CDN_API', 'https://shift8cdn.com');
define('S8CDN_SUFFIX_PAID', '.wpcdn.shift8cdn.com');
define('S8CDN_SUFFIX', '.cdn.shift8web.ca');
define('S8CDN_SUFFIX_SECOND', '.cdn.shift8web.com');
define('S8CDN_PAID_CHECK', 'shift8_cdn_check');

// Global test options storage
global $_test_options;
$_test_options = array();

// Mock essential WordPress functions - use stubs() for flexibility in tests
Functions\stubs([
    'get_option' => function($option, $default = false) {
        global $_test_options;
        return isset($_test_options[$option]) ? $_test_options[$option] : $default;
    },
]);

Functions\stubs([
    'update_option' => function($option, $value) {
        global $_test_options;
        $_test_options[$option] = $value;
        return true;
    },
    'add_option' => function($option, $value) {
        global $_test_options;
        if (!isset($_test_options[$option])) {
            $_test_options[$option] = $value;
            return true;
        }
        return false;
    },
    'delete_option' => function($option) {
        global $_test_options;
        if (isset($_test_options[$option])) {
            unset($_test_options[$option]);
            return true;
        }
        return false;
    },
]);

// Define plugin path functions
if (!function_exists('plugin_dir_path')) {
    Functions\when('plugin_dir_path')->justReturn(dirname(__DIR__) . '/');
}
if (!function_exists('plugin_dir_url')) {
    Functions\when('plugin_dir_url')->justReturn('http://example.com/wp-content/plugins/shift8-cdn/');
}
if (!function_exists('plugin_basename')) {
    Functions\when('plugin_basename')->justReturn('shift8-cdn/shift8-cdn.php');
}

// Mock common WordPress functions
Functions\stubs([
    'current_time' => function() { return date('Y-m-d H:i:s'); },
    'wp_json_encode' => 'json_encode',
    'sanitize_text_field' => function($str) {
        return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
    },
    'sanitize_textarea_field' => function($str) {
        return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
    },
    'esc_attr' => function($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    },
    'esc_html' => function($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    },
    'esc_url' => function($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    },
    'esc_textarea' => function($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    },
]);

// Mock add_action and add_filter
Functions\stubs([
    'add_action' => true,
    'add_filter' => true,
    'apply_filters' => function($tag, $value) { return $value; },
    'register_activation_hook' => true,
    'register_deactivation_hook' => true,
    'register_uninstall_hook' => true,
]);

// Mock WordPress HTTP API functions - NO real network calls
Functions\stubs([
    'wp_remote_get' => array(
        'response' => array('code' => 200),
        'body' => json_encode(array('apikey' => 'test', 'cdnprefix' => 'test', 'user_plan' => array('cdn_suffix' => S8CDN_SUFFIX_SECOND)))
    ),
    'wp_remote_post' => array(
        'response' => array('code' => 200),
        'body' => json_encode(array('success' => true))
    ),
    'wp_remote_retrieve_response_code' => 200,
    'wp_remote_retrieve_body' => json_encode(array('success' => true)),
    'is_wp_error' => false,
]);

// Mock transient functions
Functions\stubs([
    'get_transient' => false,
    'set_transient' => true,
    'delete_transient' => true,
]);

// Mock cron functions
Functions\stubs([
    'wp_schedule_event' => true,
    'wp_next_scheduled' => false,
    'wp_clear_scheduled_hook' => true,
]);

// Mock URL functions
Functions\stubs([
    'get_site_url' => 'http://example.com',
    'home_url' => function($path = '') {
        return 'http://example.com' . ($path ? '/' . ltrim($path, '/') : '');
    },
    'content_url' => function($path = '') {
        return 'http://example.com/wp-content' . ($path ? '/' . ltrim($path, '/') : '');
    },
    'includes_url' => function($path = '') {
        return 'http://example.com/wp-includes' . ($path ? '/' . ltrim($path, '/') : '');
    },
    'admin_url' => function($path = '') {
        return 'http://example.com/wp-admin/' . ltrim($path, '/');
    },
]);

// Mock wp_parse_url (use native parse_url)
Functions\stubs([
    'wp_parse_url' => function($url, $component = -1) {
        return parse_url($url, $component);
    },
]);

// Mock wp_upload_dir
Functions\stubs([
    'wp_upload_dir' => array(
        'path' => '/var/www/wp-content/uploads',
        'url' => 'http://example.com/wp-content/uploads',
        'subdir' => '',
        'basedir' => '/var/www/wp-content/uploads',
        'baseurl' => 'http://example.com/wp-content/uploads',
        'error' => false
    ),
    'wp_mkdir_p' => true,
    'wp_send_json_success' => function($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    },
    'wp_send_json_error' => function($data) {
        echo json_encode(['success' => false, 'data' => $data]);
    },
]);

// Mock user capability checks
Functions\stubs([
    'current_user_can' => true,
    'wp_get_current_user' => function() {
        return (object) array('ID' => 1, 'user_login' => 'admin');
    },
]);

// Mock nonce functions
Functions\stubs([
    'wp_create_nonce' => 'test_nonce_12345',
    'wp_verify_nonce' => true,
    'wp_nonce_url' => function($actionurl, $action = -1) {
        return $actionurl . (strpos($actionurl, '?') !== false ? '&' : '?') . '_wpnonce=test_nonce';
    },
]);

// Mock settings functions
Functions\stubs([
    'register_setting' => true,
    'add_settings_error' => true,
    'settings_errors' => array(),
    'settings_fields' => function($group) {
        echo '<input type="hidden" name="option_page" value="' . esc_attr($group) . '" />';
    },
    'do_settings_sections' => true,
    'submit_button' => function($text = null) {
        echo '<button type="submit">' . ($text ?: 'Save Changes') . '</button>';
    },
]);

// Mock menu functions
Functions\stubs([
    'add_menu_page' => 'menu_slug',
    'add_submenu_page' => 'submenu_slug',
]);

// Mock misc functions
Functions\stubs([
    'is_admin' => false,
    'is_plugin_active' => true,
    'trailingslashit' => function($string) {
        return rtrim($string, '/') . '/';
    },
    'wp_unslash' => function($value) {
        return is_string($value) ? stripslashes($value) : $value;
    },
    'load_plugin_textdomain' => true,
    'get_plugin_data' => array(
        'Name' => 'Shift8 CDN',
        'Version' => '1.71',
        'TextDomain' => 'shift8-cdn'
    ),
]);

// Mock translation functions
Functions\stubs([
    '__' => function($text, $domain = 'default') { return $text; },
    '_e' => function($text, $domain = 'default') { echo $text; },
    'esc_html__' => function($text, $domain = 'default') { return $text; },
    'esc_attr__' => function($text, $domain = 'default') { return $text; },
]);

// Mock WP_Error class
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();
        public $error_data = array();
        
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code] = array($message);
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }
        
        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            if (!isset($this->errors[$code])) {
                return '';
            }
            return $this->errors[$code][0];
        }
        
        public function get_error_code() {
            $codes = array_keys($this->errors);
            return empty($codes) ? '' : $codes[0];
        }
    }
}

// Load plugin files AFTER all mocks are set up
require_once dirname(__DIR__) . '/components/settings.php';
require_once dirname(__DIR__) . '/components/functions.php';
require_once dirname(__DIR__) . '/components/wp-cli.php';
require_once dirname(__DIR__) . '/inc/shift8_cdn_rewrite.class.php';

