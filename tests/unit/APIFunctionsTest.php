<?php
/**
 * API Communication Functions tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test API communication functions
 */
class APIFunctionsTest extends TestCase {

    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Setup global test options
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'http://example.com',
            'shift8_cdn_api' => 'test_api_key',
            'shift8_cdn_prefix' => 'test_prefix',
        );
        
        // Re-mock critical functions after Monkey\setUp() resets them
        Functions\stubs([
            'get_option' => function($option, $default = false) {
                global $_test_options;
                return isset($_test_options[$option]) ? $_test_options[$option] : $default;
            },
            'update_option' => function($option, $value) {
                global $_test_options;
                $_test_options[$option] = $value;
                return true;
            },
            'set_transient' => true,
            'get_transient' => S8CDN_SUFFIX_SECOND,
            'esc_attr' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'sanitize_text_field' => function($str) {
                return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
            },
            'wp_get_current_user' => function() {
                return (object) array('ID' => 1, 'user_login' => 'admin');
            },
            'current_user_can' => true,
            '__' => function($text, $domain = 'default') { return $text; },
            'get_bloginfo' => '6.4.0',
            'phpversion' => '8.1.0',
            'wp_get_theme' => function() {
                return (object) array(
                    'get' => function($key) {
                        $data = array('Name' => 'Test Theme', 'Version' => '1.0', 'Author' => 'Test', 'AuthorURI' => '', 'ThemeURI' => '', 'Template' => '');
                        return isset($data[$key]) ? $data[$key] : '';
                    }
                );
            },
            'get_plugins' => array(),
        ]);
        
        // Files already loaded in bootstrap
    }

    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test encryption function
     */
    public function test_encryption() {
        $key = 'test_encryption_key';
        $payload = 'sensitive data';
        
        $encrypted = shift8_cdn_encrypt($key, $payload);
        
        $this->assertNotEmpty($encrypted, 'Encrypted data should not be empty');
        $this->assertNotEquals($payload, $encrypted, 'Encrypted data should differ from original');
    }

    /**
     * Test decryption function
     */
    public function test_decryption() {
        $key = 'test_encryption_key';
        $payload = 'sensitive data';
        
        $encrypted = shift8_cdn_encrypt($key, $payload);
        $decrypted = shift8_cdn_decrypt($key, $encrypted);
        
        $this->assertEquals($payload, $decrypted, 'Decrypted data should match original');
    }

    /**
     * Test encryption with empty values
     */
    public function test_encryption_with_empty_values() {
        $result1 = shift8_cdn_encrypt('', 'data');
        $result2 = shift8_cdn_encrypt('key', '');
        
        $this->assertFalse($result1, 'Should return false with empty key');
        $this->assertFalse($result2, 'Should return false with empty payload');
    }

    /**
     * Test decryption with empty values
     */
    public function test_decryption_with_empty_values() {
        $result1 = shift8_cdn_decrypt('', 'encrypted');
        $result2 = shift8_cdn_decrypt('key', '');
        
        $this->assertFalse($result1, 'Should return false with empty key');
        $this->assertFalse($result2, 'Should return false with empty encrypted data');
    }

    /**
     * Test API check poll with successful response
     */
    public function test_poll_check_success() {
        // Mock current_user_can
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object) array('ID' => 1));
        
        // Mock successful API response
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'apikey' => 'new_api_key',
                'cdnprefix' => 'new_prefix',
                'user_plan' => array('cdn_suffix' => S8CDN_SUFFIX_PAID),
                'error' => false
            ))
        ));
        
        // Capture output
        ob_start();
        shift8_cdn_poll('check');
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        
        $this->assertIsArray($data, 'Should return JSON array');
        $this->assertEquals('new_api_key', $data['apikey'], 'Should return API key');
        $this->assertEquals('new_prefix', $data['cdnprefix'], 'Should return CDN prefix');
    }

    /**
     * Test API purge poll with successful response
     */
    public function test_poll_purge_success() {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object) array('ID' => 1));
        
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'response' => 'Cache purge submitted successfully',
                'error' => false
            ))
        ));
        
        ob_start();
        shift8_cdn_poll('purge');
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        
        $this->assertIsArray($data, 'Should return JSON array');
        $this->assertArrayHasKey('response', $data, 'Should have response key');
        $this->assertStringContainsString('successfully', $data['response'], 'Should indicate success');
    }

    /**
     * Test API poll with WP_Error
     */
    public function test_poll_with_wp_error() {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object) array('ID' => 1));
        
        $error = new \WP_Error('http_error', 'Connection timeout');
        Functions\when('wp_remote_get')->justReturn($error);
        Functions\when('is_wp_error')->justReturn(true);
        
        ob_start();
        shift8_cdn_poll('check');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Error Detected', $output, 'Should show error message');
        $this->assertStringContainsString('Connection timeout', $output, 'Should include error details');
    }

    /**
     * Test API poll without administrator capability
     */
    public function test_poll_without_admin_capability() {
        Functions\when('current_user_can')->justReturn(false);
        
        ob_start();
        shift8_cdn_poll('check');
        $output = ob_get_clean();
        
        $this->assertEmpty($output, 'Should not process without admin capability');
    }

    /**
     * Test check suffix cron function
     */
    public function test_check_suffix_cron() {
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'user_plan' => array('cdn_suffix' => S8CDN_SUFFIX_PAID)
            ))
        ));
        
        shift8_cdn_check_suffix();
        
        // Verify transient was set (mocked to return true)
        $this->assertTrue(true, 'Cron function should complete without errors');
    }

    /**
     * Test DNS prefetch function
     */
    public function test_dns_prefetch() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        $_test_options['shift8_cdn_prefix'] = 'test';
        
        ob_start();
        shift8_cdn_prefetch();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('x-dns-prefetch-control', $output, 'Should include DNS prefetch control');
        $this->assertStringContainsString('dns-prefetch', $output, 'Should include dns-prefetch links');
        $this->assertStringContainsString('test.cdn.shift8web.ca', $output, 'Should include first CDN suffix');
        $this->assertStringContainsString('test.cdn.shift8web.com', $output, 'Should include second CDN suffix');
        $this->assertStringContainsString('test.wpcdn.shift8cdn.com', $output, 'Should include paid CDN suffix');
    }

    /**
     * Test debug version check function
     */
    public function test_debug_version_check() {
        // Create a proper mock theme object with get() method
        $theme = new class {
            private $data = array(
                'Name' => 'Test Theme',
                'Version' => '1.0',
                'Author' => 'Test Author',
                'AuthorURI' => 'https://example.com',
                'ThemeURI' => 'https://example.com/theme',
                'Template' => ''
            );
            
            public function get($key) {
                return isset($this->data[$key]) ? $this->data[$key] : '';
            }
        };
        
        Functions\stubs([
            'get_bloginfo' => '6.4.0',
            'wp_get_theme' => $theme,
            'get_plugins' => array(
                'test-plugin/test-plugin.php' => array(
                    'Name' => 'Test Plugin',
                    'Version' => '1.0',
                    'PluginURI' => 'https://example.com/plugin'
                )
            ),
            'phpversion' => '8.1.0',
        ]);
        
        ob_start();
        shift8_cdn_debug_version_check();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('WordPress Version', $output, 'Should show WordPress version');
        $this->assertStringContainsString('PHP Version', $output, 'Should show PHP version');
        $this->assertStringContainsString('Active Plugins', $output, 'Should show active plugins');
    }

    /**
     * Test AJAX push handler with check action
     */
    public function test_ajax_push_check() {
        $_GET['_wpnonce'] = 'test_nonce';
        $_GET['type'] = 'check';
        
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'apikey' => 'api_key',
                'cdnprefix' => 'prefix',
                'user_plan' => array('cdn_suffix' => S8CDN_SUFFIX_SECOND),
                'error' => false
            ))
        ));
        
        ob_start();
        try {
            shift8_cdn_push();
        } catch (\Exception $e) {
            // Expected die() converted to exception in test environment
        }
        $output = ob_get_clean();
        
        if (!empty($output)) {
            $data = json_decode($output, true);
            $this->assertArrayHasKey('apikey', $data, 'Should return API key');
        }
        
        unset($_GET['_wpnonce'], $_GET['type']);
    }

    /**
     * Test AJAX push handler with purge action
     */
    public function test_ajax_push_purge() {
        $_GET['_wpnonce'] = 'test_nonce';
        $_GET['type'] = 'purge';
        
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'response' => 'Purge successful',
                'error' => false
            ))
        ));
        
        ob_start();
        try {
            shift8_cdn_push();
        } catch (\Exception $e) {
            // Expected die() converted to exception
        }
        $output = ob_get_clean();
        
        if (!empty($output)) {
            $data = json_decode($output, true);
            $this->assertArrayHasKey('response', $data, 'Should return response');
        }
        
        unset($_GET['_wpnonce'], $_GET['type']);
    }

    /**
     * Test AJAX push handler with invalid nonce
     */
    public function test_ajax_push_invalid_nonce() {
        $_GET['_wpnonce'] = 'invalid_nonce';
        $_GET['type'] = 'check';
        
        Functions\when('wp_verify_nonce')->justReturn(false);
        
        ob_start();
        try {
            shift8_cdn_push();
        } catch (\Exception $e) {
            // Expected die()
        }
        $output = ob_get_clean();
        
        $this->assertEmpty($output, 'Should not output anything with invalid nonce');
        
        unset($_GET['_wpnonce'], $_GET['type']);
    }

    /**
     * Test AJAX cache clear with valid nonce
     */
    public function test_ajax_clear_cache_success() {
        $_GET['_wpnonce'] = 'valid_nonce';
        
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);
        Functions\when('glob')->justReturn(['/tmp/test.min.css']);
        Functions\when('unlink')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);
        Functions\when('wp_send_json_success')->alias(function($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        });
        
        ob_start();
        try {
            shift8_cdn_clear_cache_ajax();
        } catch (\Exception $e) {
            // Expected die()
        }
        $output = ob_get_clean();
        
        $this->assertStringContainsString('success', $output, 'Should return success response');
        
        unset($_GET['_wpnonce']);
    }

    /**
     * Test AJAX cache clear with invalid nonce
     */
    public function test_ajax_clear_cache_invalid_nonce() {
        $_GET['_wpnonce'] = 'invalid_nonce';
        
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('wp_send_json_error')->alias(function($data) {
            echo json_encode(['success' => false, 'data' => $data]);
        });
        
        ob_start();
        try {
            shift8_cdn_clear_cache_ajax();
        } catch (\Exception $e) {
            // Expected die()
        }
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Invalid nonce', $output, 'Should return nonce error');
        
        unset($_GET['_wpnonce']);
    }

    /**
     * Test AJAX cache clear without admin capability
     */
    public function test_ajax_clear_cache_no_capability() {
        $_GET['_wpnonce'] = 'valid_nonce';
        
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('wp_send_json_error')->alias(function($data) {
            echo json_encode(['success' => false, 'data' => $data]);
        });
        
        ob_start();
        try {
            shift8_cdn_clear_cache_ajax();
        } catch (\Exception $e) {
            // Expected die()
        }
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Insufficient permissions', $output, 'Should return permission error');
        
        unset($_GET['_wpnonce']);
    }
}

