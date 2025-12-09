<?php
/**
 * Exclusion Pattern Matching tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test the exclusion pattern matching functionality
 */
class ExclusionPatternTest extends TestCase {

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
            'shift8_cdn_prefix' => 'test',
            'shift8_cdn_css' => 'on',
            'shift8_cdn_js' => 'on',
            'shift8_cdn_media' => 'on',
            'shift8_cdn_reject_files' => ''
        );
        
        // Re-mock critical functions after Monkey\setUp() resets them
        Functions\stubs([
            'get_option' => function($option, $default = false) {
                global $_test_options;
                return isset($_test_options[$option]) ? $_test_options[$option] : $default;
            },
            'get_transient' => S8CDN_SUFFIX_SECOND,
            'esc_attr' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'esc_textarea' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'wp_parse_url' => function($url, $component = -1) {
                return parse_url($url, $component);
            },
            'content_url' => function($path = '') {
                return 'http://example.com/wp-content' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'includes_url' => function($path = '') {
                return 'http://example.com/wp-includes' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'home_url' => function($path = '') {
                return 'http://example.com' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'wp_upload_dir' => array(
                'path' => '/var/www/wp-content/uploads',
                'url' => 'http://example.com/wp-content/uploads',
                'subdir' => '',
                'basedir' => '/var/www/wp-content/uploads',
                'baseurl' => 'http://example.com/wp-content/uploads',
                'error' => false
            ),
            'trailingslashit' => function($string) {
                return rtrim($string, '/') . '/';
            },
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
     * Test single file exclusion
     */
    public function test_exclude_single_file() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/exclude-me.jpg';
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/uploads/exclude-me.jpg';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'Specified file should be excluded');
    }

    /**
     * Test wildcard exclusion with asterisk
     */
    public function test_wildcard_exclusion() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/2024/01/*';
        
        $cdn = new \Shift8_CDN();
        
        $url1 = 'http://example.com/wp-content/uploads/2024/01/image1.jpg';
        $url2 = 'http://example.com/wp-content/uploads/2024/01/image2.jpg';
        $url3 = 'http://example.com/wp-content/uploads/2024/02/image3.jpg';
        
        $this->assertTrue($cdn->is_excluded($url1), 'File matching wildcard pattern should be excluded');
        $this->assertTrue($cdn->is_excluded($url2), 'Another file matching wildcard should be excluded');
        $this->assertFalse($cdn->is_excluded($url3), 'File not matching wildcard should not be excluded');
    }

    /**
     * Test multiple exclusion patterns
     */
    public function test_multiple_exclusion_patterns() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = "/wp-content/uploads/exclude1.jpg\n/wp-content/uploads/exclude2.jpg";
        
        $cdn = new \Shift8_CDN();
        
        $url1 = 'http://example.com/wp-content/uploads/exclude1.jpg';
        $url2 = 'http://example.com/wp-content/uploads/exclude2.jpg';
        $url3 = 'http://example.com/wp-content/uploads/include.jpg';
        
        $this->assertTrue($cdn->is_excluded($url1), 'First excluded file should be excluded');
        $this->assertTrue($cdn->is_excluded($url2), 'Second excluded file should be excluded');
        $this->assertFalse($cdn->is_excluded($url3), 'Non-excluded file should not be excluded');
    }

    /**
     * Test wildcard in filename
     */
    public function test_wildcard_in_filename() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/long-filename-*';
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/uploads/long-filename-2024-01-15-12-30-45.jpg';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'File with wildcard in name should be excluded');
    }

    /**
     * Test matchURL helper function
     */
    public function test_matchurl_function() {
        $cdn = new \Shift8_CDN();
        
        $patterns = array(
            '/wp-content/uploads/test*',
            '/wp-content/uploads/exclude.jpg'
        );
        
        $url1 = '/wp-content/uploads/test-file.jpg';
        $url2 = '/wp-content/uploads/exclude.jpg';
        $url3 = '/wp-content/uploads/include.jpg';
        
        $this->assertTrue($cdn->matchURL($patterns, $url1), 'Should match wildcard pattern');
        $this->assertTrue($cdn->matchURL($patterns, $url2), 'Should match exact pattern');
        $this->assertFalse($cdn->matchURL($patterns, $url3), 'Should not match non-matching pattern');
    }

    /**
     * Test exclusion with directory path
     */
    public function test_exclude_directory() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/private/*';
        
        $cdn = new \Shift8_CDN();
        
        $url1 = 'http://example.com/wp-content/uploads/private/file1.jpg';
        $url2 = 'http://example.com/wp-content/uploads/private/subfolder/file2.jpg';
        $url3 = 'http://example.com/wp-content/uploads/public/file3.jpg';
        
        $this->assertTrue($cdn->is_excluded($url1), 'File in private directory should be excluded');
        $this->assertTrue($cdn->is_excluded($url2), 'File in subdirectory should be excluded');
        $this->assertFalse($cdn->is_excluded($url3), 'File outside private directory should not be excluded');
    }

    /**
     * Test empty exclusion list
     */
    public function test_empty_exclusion_list() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '';
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/uploads/image.jpg';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertFalse($result, 'With empty exclusion list, normal files should not be excluded');
    }

    /**
     * Test exclusion pattern with special regex characters
     */
    public function test_exclusion_with_special_chars() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/test[1].jpg';
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/uploads/test[1].jpg';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'Should handle special regex characters in exclusion pattern');
    }

    /**
     * Test exclusion with leading/trailing whitespace
     */
    public function test_exclusion_with_whitespace() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = "  /wp-content/uploads/test.jpg  \n  /wp-content/uploads/test2.jpg  ";
        
        $cdn = new \Shift8_CDN();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('get_excluded_files');
        $method->setAccessible(true);
        
        $excluded = $method->invoke($cdn, '#');
        
        $this->assertCount(2, $excluded, 'Should parse two patterns despite whitespace');
        $this->assertEquals('/wp-content/uploads/test.jpg', trim($excluded[0]), 'Should trim whitespace');
        $this->assertEquals('/wp-content/uploads/test2.jpg', trim($excluded[1]), 'Should trim whitespace');
    }

    /**
     * Test case-sensitive matching
     */
    public function test_case_sensitive_matching() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/Image.jpg';
        
        $cdn = new \Shift8_CDN();
        
        $url_upper = 'http://example.com/wp-content/uploads/Image.jpg';
        $url_lower = 'http://example.com/wp-content/uploads/image.jpg';
        
        $this->assertTrue($cdn->is_excluded($url_upper), 'Should exclude exact case match');
        $this->assertFalse($cdn->is_excluded($url_lower), 'Should not exclude different case (case-sensitive)');
    }

    /**
     * Test complex wildcard pattern
     */
    public function test_complex_wildcard_pattern() {
        global $_test_options;
        $_test_options['shift8_cdn_reject_files'] = '/wp-content/uploads/*/thumbnails/*.jpg';
        
        $cdn = new \Shift8_CDN();
        
        $url1 = 'http://example.com/wp-content/uploads/2024/thumbnails/image.jpg';
        $url2 = 'http://example.com/wp-content/uploads/2024/full/image.jpg';
        
        $this->assertTrue($cdn->is_excluded($url1), 'Should match complex pattern with thumbnails');
        $this->assertFalse($cdn->is_excluded($url2), 'Should not match pattern without thumbnails directory');
    }
}

