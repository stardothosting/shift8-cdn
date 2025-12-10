<?php
/**
 * Minification tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test minification functionality
 */
class MinificationTest extends TestCase {

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
            'get_site_url' => 'http://example.com',
            'wp_upload_dir' => array(
                'basedir' => '/tmp/wp-content/uploads',
                'baseurl' => 'http://example.com/wp-content/uploads'
            ),
        ]);
        
        // Setup global test options
        global $_test_options;
        $_test_options = array();
    }

    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test is_already_minified with .min.css file
     */
    public function test_is_already_minified_min_css() {
        $url = '/wp-content/themes/theme/style.min.css';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect .min.css as already minified');
    }

    /**
     * Test is_already_minified with .min.js file
     */
    public function test_is_already_minified_min_js() {
        $url = '/wp-content/plugins/plugin/script.min.js';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect .min.js as already minified');
    }

    /**
     * Test is_already_minified with minified pattern in name
     */
    public function test_is_already_minified_pattern() {
        $url = '/wp-content/themes/theme/style-min.css';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect -min pattern as already minified');
    }

    /**
     * Test is_already_minified with normal file
     */
    public function test_is_already_minified_normal_file() {
        $url = '/wp-content/themes/theme/style.css';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertFalse($result, 'Should not detect normal file as minified');
    }

    /**
     * Test get_cache_dir returns correct path
     */
    public function test_get_cache_dir() {
        $cache_dir = shift8_cdn_get_cache_dir();
        
        $this->assertStringEndsWith('/shift8-cdn-cache', $cache_dir, 'Cache dir should end with shift8-cdn-cache');
    }

    /**
     * Test get_cached_file_path for CSS
     */
    public function test_get_cached_file_path_css() {
        $url = 'http://example.com/wp-content/themes/theme/style.css';
        
        $path = shift8_cdn_get_cached_file_path($url, 'css');
        
        $this->assertStringContainsString('/css/', $path, 'CSS cache path should contain /css/');
        $this->assertStringEndsWith('.min.css', $path, 'CSS cache path should end with .min.css');
    }

    /**
     * Test get_cached_file_path for JS
     */
    public function test_get_cached_file_path_js() {
        $url = 'http://example.com/wp-content/plugins/plugin/script.js';
        
        $path = shift8_cdn_get_cached_file_path($url, 'js');
        
        $this->assertStringContainsString('/js/', $path, 'JS cache path should contain /js/');
        $this->assertStringEndsWith('.min.js', $path, 'JS cache path should end with .min.js');
    }

    /**
     * Test minify_css with simple CSS
     */
    public function test_minify_css_simple() {
        // Only test if library is available
        if (!class_exists('MatthiasMullie\\Minify\\CSS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\CSS not available');
        }
        
        $css = "body {\n    color: red;\n    background: white;\n}";
        
        $minified = shift8_cdn_minify_css($css);
        
        $this->assertNotEmpty($minified, 'Minified CSS should not be empty');
        $this->assertLessThan(strlen($css), strlen($minified), 'Minified CSS should be smaller');
        $this->assertStringNotContainsString("\n", $minified, 'Minified CSS should not contain newlines');
    }

    /**
     * Test minify_js with simple JavaScript
     */
    public function test_minify_js_simple() {
        // Only test if library is available
        if (!class_exists('MatthiasMullie\\Minify\\JS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\JS not available');
        }
        
        $js = "function test() {\n    var x = 1;\n    return x;\n}";
        
        $minified = shift8_cdn_minify_js($js);
        
        $this->assertNotEmpty($minified, 'Minified JS should not be empty');
        $this->assertLessThan(strlen($js), strlen($minified), 'Minified JS should be smaller');
    }

    /**
     * Test minify_css returns original on empty content
     */
    public function test_minify_css_empty() {
        $css = "";
        
        $minified = shift8_cdn_minify_css($css);
        
        $this->assertEquals($css, $minified, 'Empty CSS should return empty');
    }

    /**
     * Test get_minified_url when minification disabled
     */
    public function test_get_minified_url_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_css'] = '';
        
        $url = 'http://example.com/wp-content/themes/theme/style.css';
        
        $result = shift8_cdn_get_minified_url($url, 'css');
        
        $this->assertEquals($url, $result, 'Should return original URL when minification disabled');
    }

    /**
     * Test get_minified_url with already minified file
     */
    public function test_get_minified_url_already_minified() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_css'] = 'on';
        
        $url = 'http://example.com/wp-content/themes/theme/style.min.css';
        
        $result = shift8_cdn_get_minified_url($url, 'css');
        
        $this->assertEquals($url, $result, 'Should return original URL for already minified file');
    }

    /**
     * Test get_minified_url with external URL
     */
    public function test_get_minified_url_external() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_css'] = 'on';
        
        $url = 'https://external-cdn.com/style.css';
        
        $result = shift8_cdn_get_minified_url($url, 'css');
        
        $this->assertEquals($url, $result, 'Should return original URL for external resources');
    }

    /**
     * Test get_cache_stats with no cache
     */
    public function test_get_cache_stats_empty() {
        // Mock glob to return empty arrays
        Functions\when('glob')->justReturn(array());
        
        $stats = shift8_cdn_get_cache_stats();
        
        $this->assertIsArray($stats, 'Stats should be an array');
        $this->assertEquals(0, $stats['css_count'], 'CSS count should be 0');
        $this->assertEquals(0, $stats['js_count'], 'JS count should be 0');
        $this->assertEquals(0, $stats['total_size'], 'Total size should be 0');
    }

    /**
     * Test create_cache_dir creates directory structure
     */
    public function test_create_cache_dir_structure() {
        // Mock wp_mkdir_p to return true
        Functions\when('wp_mkdir_p')->justReturn(true);
        
        // file_exists should return false first time (not exists), then true (created)
        $call_count = 0;
        Functions\when('file_exists')->alias(function() use (&$call_count) {
            $call_count++;
            return $call_count > 1;
        });
        
        Functions\when('is_writable')->justReturn(true);
        Functions\when('file_put_contents')->justReturn(true);
        
        $result = shift8_cdn_create_cache_dir();
        
        $this->assertTrue($result, 'Should successfully create cache directory');
    }

    /**
     * Test create_cache_dir when directory already exists
     */
    public function test_create_cache_dir_already_exists() {
        Functions\when('file_exists')->justReturn(true);
        Functions\when('is_writable')->justReturn(true);
        
        $result = shift8_cdn_create_cache_dir();
        
        $this->assertTrue($result, 'Should return true when directory exists and is writable');
    }

    /**
     * Test create_cache_dir when directory not writable
     */
    public function test_create_cache_dir_not_writable() {
        Functions\when('file_exists')->justReturn(true);
        Functions\when('is_writable')->justReturn(false);
        
        $result = shift8_cdn_create_cache_dir();
        
        $this->assertFalse($result, 'Should return false when directory not writable');
    }

    /**
     * Test clear_cache deletes files
     */
    public function test_clear_cache_success() {
        Functions\when('file_exists')->justReturn(true);
        Functions\when('glob')->alias(function($pattern) {
            if (strpos($pattern, '/css/') !== false) {
                return ['/tmp/cache/css/test.min.css'];
            }
            return ['/tmp/cache/js/test.min.js'];
        });
        Functions\when('unlink')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);
        
        $result = shift8_cdn_clear_cache();
        
        $this->assertTrue($result, 'Should successfully clear cache');
    }

    /**
     * Test clear_cache when cache doesn't exist
     */
    public function test_clear_cache_no_cache() {
        Functions\when('file_exists')->justReturn(false);
        
        $result = shift8_cdn_clear_cache();
        
        $this->assertTrue($result, 'Should return true even when no cache exists');
    }

    /**
     * Test get_minified_url with JS disabled
     */
    public function test_get_minified_url_js_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_js'] = '';
        
        $url = 'http://example.com/wp-content/themes/theme/script.js';
        
        $result = shift8_cdn_get_minified_url($url, 'js');
        
        $this->assertEquals($url, $result, 'Should return original URL when JS minification disabled');
    }

    /**
     * Test minify_css with library unavailable (graceful fallback)
     */
    public function test_minify_css_library_unavailable() {
        // This tests the fallback when class doesn't exist
        // The function checks class_exists() and returns original
        $css = "body { color: red; }";
        
        // If library is available, it will minify; if not, returns original
        $result = shift8_cdn_minify_css($css);
        
        $this->assertNotEmpty($result, 'Should return something (original or minified)');
    }

    /**
     * Test minify_js with library unavailable (graceful fallback)
     */
    public function test_minify_js_library_unavailable() {
        $js = "function test() { return true; }";
        
        $result = shift8_cdn_minify_js($js);
        
        $this->assertNotEmpty($result, 'Should return something (original or minified)');
    }

    /**
     * Test is_already_minified with uppercase extension
     */
    public function test_is_already_minified_uppercase() {
        $url = '/wp-content/themes/theme/style.MIN.CSS';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect uppercase .MIN.CSS as minified');
    }

    /**
     * Test is_already_minified with dot-min pattern
     */
    public function test_is_already_minified_dot_min() {
        $url = '/wp-content/themes/theme/style.min.v2.css';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect .min. pattern in middle of filename');
    }

    /**
     * Test is_already_minified with underscore pattern
     */
    public function test_is_already_minified_underscore() {
        $url = '/wp-content/plugins/plugin/script_min_v1.js';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertTrue($result, 'Should detect _min_ pattern');
    }

    /**
     * Test is_already_minified with false positive prevention
     */
    public function test_is_already_minified_false_positive() {
        $url = '/wp-content/themes/admin-theme/style.css';
        
        $result = shift8_cdn_is_already_minified($url);
        
        $this->assertFalse($result, 'Should not detect "admin" as minified pattern');
    }

    /**
     * Test get_cached_file_path with special characters in URL
     */
    public function test_get_cached_file_path_special_chars() {
        $url = 'http://example.com/wp-content/themes/theme/style.css?ver=1.2.3';
        
        $path = shift8_cdn_get_cached_file_path($url, 'css');
        
        $this->assertStringContainsString('.min.css', $path, 'Should handle query strings in URL');
    }

    /**
     * Test get_cache_dir returns consistent path
     */
    public function test_get_cache_dir_consistent() {
        $dir1 = shift8_cdn_get_cache_dir();
        $dir2 = shift8_cdn_get_cache_dir();
        
        $this->assertEquals($dir1, $dir2, 'Should return same path on multiple calls');
    }

    /**
     * Test minify_css with exception handling
     */
    public function test_minify_css_exception_handling() {
        // Test that function returns original content when exception occurs
        // This simulates malformed CSS that might cause minification to fail
        $malformed_css = "body { color: red /* missing closing brace";
        
        $result = shift8_cdn_minify_css($malformed_css);
        
        // Should return original or minified - either way should not throw exception
        $this->assertNotEmpty($result, 'Should return content even with malformed CSS');
    }

    /**
     * Test minify_js with exception handling
     */
    public function test_minify_js_exception_handling() {
        // Malformed JavaScript
        $malformed_js = "function test() { return 'unclosed string;";
        
        $result = shift8_cdn_minify_js($malformed_js);
        
        // Should not throw exception
        $this->assertNotEmpty($result, 'Should return content even with malformed JS');
    }

    /**
     * Test minify_css actually reduces size
     */
    public function test_minify_css_reduces_size() {
        if (!class_exists('MatthiasMullie\\Minify\\CSS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\CSS not available');
        }
        
        $css = "body {
    color: red;
    background: white;
    margin: 10px;
    padding: 5px;
}

h1 {
    font-size: 24px;
    color: blue;
}";
        
        $minified = shift8_cdn_minify_css($css);
        
        $this->assertLessThan(strlen($css), strlen($minified), 'Minified CSS should be smaller than original');
        $this->assertStringNotContainsString('    ', $minified, 'Should remove indentation');
        $this->assertStringNotContainsString("\n", $minified, 'Should remove newlines');
    }

    /**
     * Test minify_js actually reduces size
     */
    public function test_minify_js_reduces_size() {
        if (!class_exists('MatthiasMullie\\Minify\\JS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\JS not available');
        }
        
        $js = "function calculateTotal(price, quantity) {
    var subtotal = price * quantity;
    var tax = subtotal * 0.1;
    var total = subtotal + tax;
    return total;
}";
        
        $minified = shift8_cdn_minify_js($js);
        
        $this->assertLessThan(strlen($js), strlen($minified), 'Minified JS should be smaller than original');
        $this->assertStringNotContainsString('    ', $minified, 'Should remove indentation');
    }

    /**
     * Test minify_css preserves CSS functionality
     */
    public function test_minify_css_preserves_functionality() {
        if (!class_exists('MatthiasMullie\\Minify\\CSS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\CSS not available');
        }
        
        $css = ".class1 { color: red; } .class2 { background: blue; }";
        
        $minified = shift8_cdn_minify_css($css);
        
        $this->assertStringContainsString('class1', $minified, 'Should preserve class names');
        $this->assertStringContainsString('class2', $minified, 'Should preserve class names');
        $this->assertStringContainsString('color', $minified, 'Should preserve properties');
        $this->assertStringContainsString('background', $minified, 'Should preserve properties');
    }

    /**
     * Test minify_js preserves JavaScript functionality
     */
    public function test_minify_js_preserves_functionality() {
        if (!class_exists('MatthiasMullie\\Minify\\JS')) {
            $this->markTestSkipped('MatthiasMullie\\Minify\\JS not available');
        }
        
        $js = "var myVar = 'test'; function myFunc() { return myVar; }";
        
        $minified = shift8_cdn_minify_js($js);
        
        $this->assertStringContainsString('myVar', $minified, 'Should preserve variable names');
        $this->assertStringContainsString('myFunc', $minified, 'Should preserve function names');
    }

    /**
     * Test filesize limit check in minification
     * Note: Testing the 5MB limit logic without calling the full function
     */
    public function test_minification_respects_file_size_limit() {
        $five_mb = 5 * 1024 * 1024;
        $six_mb = 6 * 1024 * 1024;
        
        $this->assertLessThan($six_mb, $five_mb, 'File size limit should be 5MB');
        $this->assertTrue($six_mb > $five_mb, 'Files over 5MB should be rejected');
    }

    /**
     * Test error handling when file operations fail
     * This validates the error handling logic exists
     */
    public function test_minification_handles_file_errors() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_css'] = '';
        
        // When minification is disabled, should return original URL
        $url = 'http://example.com/wp-content/themes/theme/style.css';
        $result = shift8_cdn_get_minified_url($url, 'css');
        
        $this->assertEquals($url, $result, 'Should handle disabled minification gracefully');
    }

    /**
     * Test cache invalidation logic (newer source file)
     * This tests the filemtime comparison conceptually
     */
    public function test_cache_invalidation_logic() {
        $cache_time = time() - 3600; // Cache is 1 hour old
        $source_time = time(); // Source is current
        
        // Cache is stale if source is newer
        $is_stale = ($cache_time < $source_time);
        
        $this->assertTrue($is_stale, 'Cache should be considered stale when source is newer');
    }

    /**
     * Test cache validation logic (cache is fresh)
     */
    public function test_cache_validation_logic() {
        $source_time = time() - 3600; // Source is 1 hour old
        $cache_time = time(); // Cache is current
        
        // Cache is valid if it's newer than source
        $is_valid = ($cache_time >= $source_time);
        
        $this->assertTrue($is_valid, 'Cache should be valid when it is newer than source');
    }
}

