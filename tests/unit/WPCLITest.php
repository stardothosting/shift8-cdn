<?php
/**
 * WP-CLI Commands tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test WP-CLI commands
 */
class WPCLITest extends TestCase {

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
            'update_option' => function($option, $value) {
                global $_test_options;
                $_test_options[$option] = $value;
                return true;
            },
            'esc_attr' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'wp_remote_get' => array(
                'response' => array('code' => 200),
                'body' => json_encode(array('response' => 'Success'))
            ),
            'is_wp_error' => false,
        ]);
        
        // Setup global test options
        global $_test_options;
        $_test_options = array();
        
        // Mock WP_CLI class
        if (!class_exists('WP_CLI')) {
            eval('class WP_CLI {
                public static function line($message) {
                    echo $message . "\n";
                }
                public static function add_command($name, $callable) {
                    return true;
                }
            }');
        }
        
        // WP-CLI file already loaded in bootstrap
    }

    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test enable command when CDN is disabled
     */
    public function test_enable_command_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'off';
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->enable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('enabled', $output, 'Should show enabled message');
    }

    /**
     * Test enable command when already enabled
     */
    public function test_enable_command_when_already_enabled() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->enable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('doing nothing', $output, 'Should show already enabled message');
    }

    /**
     * Test enable command when not installed
     */
    public function test_enable_command_when_not_installed() {
        global $_test_options;
        $_test_options = array(); // No options set
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->enable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('not installed', $output, 'Should show not installed message');
    }

    /**
     * Test disable command when CDN is enabled
     */
    public function test_disable_command_when_enabled() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->disable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('disabled', $output, 'Should show disabled message');
    }

    /**
     * Test disable command when already disabled
     */
    public function test_disable_command_when_already_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'off';
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->disable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('doing nothing', $output, 'Should show already disabled message');
    }

    /**
     * Test disable command when not installed
     */
    public function test_disable_command_when_not_installed() {
        global $_test_options;
        $_test_options = array();
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->disable();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('not installed', $output, 'Should show not installed message');
    }

    /**
     * Test flush command with successful response
     */
    public function test_flush_command_success() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
        );
        
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '200'),
            'body' => json_encode(array(
                'response' => 'Cache purge submitted successfully',
                'error' => false
            ))
        ));
        Functions\when('is_wp_error')->justReturn(false);
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->flush();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('submitted', $output, 'Should show success message');
        $this->assertStringContainsString('successfully', $output, 'Should indicate success');
    }

    /**
     * Test flush command with WP_Error
     */
    public function test_flush_command_with_error() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
        );
        
        $error = new \WP_Error('http_error', 'Connection failed');
        Functions\when('wp_remote_get')->justReturn($error);
        Functions\when('is_wp_error')->justReturn(true);
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->flush();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Error', $output, 'Should show error message');
        $this->assertStringContainsString('Connection failed', $output, 'Should show error details');
    }

    /**
     * Test flush command with unknown error
     */
    public function test_flush_command_with_unknown_error() {
        global $_test_options;
        $_test_options = array(
            'shift8_cdn_enabled' => 'on',
            'shift8_cdn_url' => 'https://example.com',
            'shift8_cdn_api' => 'test_api_key',
        );
        
        Functions\when('wp_remote_get')->justReturn(array(
            'response' => array('code' => '500'),
            'body' => json_encode(array('error' => true))
        ));
        Functions\when('is_wp_error')->justReturn(false);
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->flush();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('error', strtolower($output), 'Should show error message');
    }

    /**
     * Test flush command when CDN not enabled
     */
    public function test_flush_command_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'off';
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->flush();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('not enabled', $output, 'Should show not enabled message');
    }

    /**
     * Test flush command when not installed
     */
    public function test_flush_command_when_not_installed() {
        global $_test_options;
        $_test_options = array();
        
        $cli = new \WDS_CLI();
        
        ob_start();
        $cli->flush();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('not installed', $output, 'Should show not installed message');
    }
}

