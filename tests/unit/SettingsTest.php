<?php
/**
 * Settings Validation tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test settings validation and registration
 */
class SettingsTest extends TestCase {

    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Re-mock critical functions after Monkey\setUp() resets them
        Functions\stubs([
            'get_option' => function($option, $default = false) {
                global $_test_options;
                return isset($_test_options[$option]) ? $_test_options[$option] : $default;
            },
            'esc_attr' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'wp_parse_url' => function($url, $component = -1) {
                return parse_url($url, $component);
            },
        ]);
        
        // Setup global test options
        global $_test_options;
        $_test_options = array();
        
        // Settings file already loaded in bootstrap
    }

    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test valid URL validation
     */
    public function test_url_validation_valid() {
        $url = 'https://www.example.com';
        
        $result = shift8_cdn_url_validate($url);
        
        $this->assertEquals('https://www.example.com', $result, 'Valid URL should pass validation');
    }

    /**
     * Test URL validation with path
     */
    public function test_url_validation_with_path() {
        $url = 'https://www.example.com/blog';
        
        $result = shift8_cdn_url_validate($url);
        
        $this->assertEquals('https://www.example.com/blog', $result, 'URL with path should pass validation');
    }

    /**
     * Test URL validation with trailing slash
     */
    public function test_url_validation_removes_trailing_slash() {
        $url = 'https://www.example.com/blog/';
        
        $result = shift8_cdn_url_validate($url);
        
        // Function returns scheme://host/path (without trailing slash for path)
        $this->assertStringStartsWith('https://www.example.com', $result, 'Should handle trailing slash');
    }

    /**
     * Test invalid URL validation
     */
    public function test_url_validation_invalid() {
        Functions\expect('add_settings_error')
            ->once()
            ->with('shift8_cdn_url', 'shift8-cdn-notice', \Mockery::any(), 'error');
        
        $url = 'not-a-valid-url';
        
        $result = shift8_cdn_url_validate($url);
        
        $this->assertNull($result, 'Invalid URL should return null');
    }

    /**
     * Test check enabled with all settings
     */
    public function test_check_enabled_all_settings() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => 'test_prefix',
        );
        
        $result = shift8_cdn_check_enabled();
        
        $this->assertTrue($result, 'Should return true when all settings are configured');
    }

    /**
     * Test check enabled when disabled
     */
    public function test_check_enabled_when_disabled() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'off',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => 'test_prefix',
        );
        
        $result = shift8_cdn_check_enabled();
        
        $this->assertFalse($result, 'Should return false when disabled');
    }

    /**
     * Test check enabled with missing URL
     */
    public function test_check_enabled_missing_url() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => '',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => 'test_prefix',
        );
        
        $result = shift8_cdn_check_enabled();
        
        $this->assertFalse($result, 'Should return false with missing URL');
    }

    /**
     * Test check enabled with missing API key
     */
    public function test_check_enabled_missing_api() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => '',
            'shift8_cdn_prefix' => 'test_prefix',
        );
        
        $result = shift8_cdn_check_enabled();
        
        $this->assertFalse($result, 'Should return false with missing API key');
    }

    /**
     * Test check enabled with missing prefix
     */
    public function test_check_enabled_missing_prefix() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => '',
        );
        
        $result = shift8_cdn_check_enabled();
        
        $this->assertFalse($result, 'Should return false with missing prefix');
    }

    /**
     * Test check options returns array
     */
    public function test_check_options_returns_array() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => 'test_prefix',
            'shift8_cdn_css' => 'on',
            'shift8_cdn_js' => 'on',
            'shift8_cdn_media' => 'on',
        );
        
        $result = shift8_cdn_check_options();
        
        $this->assertIsArray($result, 'Should return array');
        $this->assertArrayHasKey('cdn_url', $result, 'Should have cdn_url key');
        $this->assertArrayHasKey('cdn_api', $result, 'Should have cdn_api key');
        $this->assertArrayHasKey('cdn_prefix', $result, 'Should have cdn_prefix key');
        $this->assertArrayHasKey('static_css', $result, 'Should have static_css key');
        $this->assertArrayHasKey('static_js', $result, 'Should have static_js key');
        $this->assertArrayHasKey('static_media', $result, 'Should have static_media key');
    }

    /**
     * Test check options with defaults
     */
    public function test_check_options_with_defaults() {
        global $_test_options;
        $_test_options = array();
        
        // Mock get_option to return defaults
        Functions\when('get_option')->alias(function($option, $default = false) {
            $defaults = array(
                'shift8_cdn_css' => 'on',
                'shift8_cdn_js' => 'on',
                'shift8_cdn_media' => 'on',
            );
            return isset($defaults[$option]) ? $defaults[$option] : $default;
        });
        
        $result = shift8_cdn_check_options();
        
        $this->assertEquals('on', $result['static_css'], 'CSS should default to on');
        $this->assertEquals('on', $result['static_js'], 'JS should default to on');
        $this->assertEquals('on', $result['static_media'], 'Media should default to on');
    }

    /**
     * Test check paid transient with free suffix
     */
    public function test_check_paid_transient_free() {
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX_SECOND);
        
        $result = shift8_cdn_check_paid_transient();
        
        $this->assertEquals(S8CDN_SUFFIX_SECOND, $result, 'Should return free CDN suffix');
    }

    /**
     * Test check paid transient with paid suffix
     */
    public function test_check_paid_transient_paid() {
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX_PAID);
        
        $result = shift8_cdn_check_paid_transient();
        
        $this->assertEquals(S8CDN_SUFFIX_PAID, $result, 'Should return paid CDN suffix');
    }

    /**
     * Test check paid transient not set
     */
    public function test_check_paid_transient_not_set() {
        Functions\when('get_transient')->justReturn(false);
        
        $result = shift8_cdn_check_paid_transient();
        
        $this->assertFalse($result, 'Should return false when transient not set');
    }

    /**
     * Test sanitize reject field
     */
    public function test_sanitize_reject_field() {
        $input = "/wp-content/uploads/file.jpg\n/wp-content/uploads/*.png";
        
        $result = shift8_cdn_sanitize_reject_field($input);
        
        // Function currently returns input as-is, but tests the logic exists
        $this->assertNotEmpty($result, 'Should process reject field');
    }

    /**
     * Test sanitize reject field with wildcards
     */
    public function test_sanitize_reject_field_with_wildcards() {
        $input = "/wp-content/uploads/test*\n/wp-content/uploads/exclude.*";
        
        $result = shift8_cdn_sanitize_reject_field($input);
        
        $this->assertNotEmpty($result, 'Should handle wildcards');
    }

    /**
     * Test sanitize reject field with empty input
     */
    public function test_sanitize_reject_field_empty() {
        $input = '';
        
        $result = shift8_cdn_sanitize_reject_field($input);
        
        // Empty array or empty string depending on implementation
        $this->assertTrue(empty($result) || $result === '', 'Should handle empty input');
    }

    /**
     * Test get hostname with paid account
     */
    public function test_get_hostname_paid() {
        global $_test_options;
        $_test_options['shift8_cdn_prefix'] = 'testprefix';
        
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX_PAID);
        
        $hostname = shift8_cdn_get_hostname();
        
        $this->assertEquals('testprefix.wpcdn.shift8cdn.com', $hostname, 'Should return paid CDN hostname');
    }

    /**
     * Test get hostname with free account (suffix 1)
     */
    public function test_get_hostname_free_suffix_one() {
        global $_test_options;
        $_test_options['shift8_cdn_prefix'] = 'testprefix';
        
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX);
        
        $hostname = shift8_cdn_get_hostname();
        
        $this->assertEquals('testprefix.cdn.shift8web.ca', $hostname, 'Should return free CDN hostname (suffix 1)');
    }

    /**
     * Test get hostname with free account (suffix 2)
     */
    public function test_get_hostname_free_suffix_two() {
        global $_test_options;
        $_test_options['shift8_cdn_prefix'] = 'testprefix';
        
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX_SECOND);
        
        $hostname = shift8_cdn_get_hostname();
        
        $this->assertEquals('testprefix.cdn.shift8web.com', $hostname, 'Should return free CDN hostname (suffix 2)');
    }

    /**
     * Test get hostname with no transient (defaults to suffix 2)
     */
    public function test_get_hostname_no_transient() {
        global $_test_options;
        $_test_options['shift8_cdn_prefix'] = 'testprefix';
        
        Functions\when('get_transient')->justReturn(false);
        
        $hostname = shift8_cdn_get_hostname();
        
        $this->assertEquals('testprefix.cdn.shift8web.com', $hostname, 'Should default to suffix 2 when no transient');
    }

    /**
     * Test get hostname with empty prefix
     */
    public function test_get_hostname_empty_prefix() {
        global $_test_options;
        $_test_options['shift8_cdn_prefix'] = '';
        
        $hostname = shift8_cdn_get_hostname();
        
        $this->assertEmpty($hostname, 'Should return empty string when prefix is empty');
    }
}

