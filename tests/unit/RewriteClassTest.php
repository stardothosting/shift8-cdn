<?php
/**
 * URL Rewriting Class tests using Brain/Monkey
 *
 * @package Shift8\CDN\Tests\Unit
 */

namespace Shift8\CDN\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Test the Shift8_CDN rewriting class
 */
class RewriteClassTest extends TestCase {

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
     * Test basic CSS URL rewriting
     */
    public function test_rewrite_css_url() {
        $cdn = new \Shift8_CDN();
        
        $html = '<link rel="stylesheet" href="http://example.com/wp-content/themes/test/style.css">';
        $expected = '<link rel="stylesheet" href="https://test.cdn.shift8web.com/wp-content/themes/test/style.css">';
        
        // Use reflection to call private rewrite method
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should rewrite CSS URL to CDN');
        $this->assertStringContainsString('/wp-content/themes/test/style.css', $result, 'Should preserve path');
    }

    /**
     * Test JS URL rewriting
     */
    public function test_rewrite_js_url() {
        $cdn = new \Shift8_CDN();
        
        $html = '<script src="http://example.com/wp-content/plugins/test/script.js"></script>';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should rewrite JS URL to CDN');
        $this->assertStringContainsString('/wp-content/plugins/test/script.js', $result, 'Should preserve path');
    }

    /**
     * Test image URL rewriting
     */
    public function test_rewrite_image_url() {
        $cdn = new \Shift8_CDN();
        
        $html = '<img src="http://example.com/wp-content/uploads/2024/01/image.jpg" alt="Test">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should rewrite image URL to CDN');
        $this->assertStringContainsString('/wp-content/uploads/2024/01/image.jpg', $result, 'Should preserve path');
    }

    /**
     * Test URL rewriting with query string
     */
    public function test_rewrite_url_with_query_string() {
        $cdn = new \Shift8_CDN();
        
        $html = '<link rel="stylesheet" href="http://example.com/wp-content/themes/test/style.css?ver=1.0">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should rewrite URL with query string');
        $this->assertStringContainsString('?ver=1.0', $result, 'Should preserve query string');
    }

    /**
     * Test that admin URLs are not rewritten
     */
    public function test_does_not_rewrite_admin_urls() {
        $cdn = new \Shift8_CDN();
        
        $html = '<script src="http://example.com/wp-admin/js/admin.js"></script>';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringNotContainsString('cdn.shift8web.com', $result, 'Should not rewrite admin URLs');
        $this->assertStringContainsString('http://example.com/wp-admin', $result, 'Should keep original admin URL');
    }

    /**
     * Test that root path is excluded
     */
    public function test_excludes_root_path() {
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'Root path should be excluded');
    }

    /**
     * Test that empty path is excluded
     */
    public function test_excludes_empty_path() {
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'Empty path should be excluded');
    }

    /**
     * Test CSS file exclusion when CSS is disabled
     */
    public function test_excludes_css_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_css'] = ''; // Disabled
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/themes/test/style.css';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'CSS files should be excluded when CSS option is disabled');
    }

    /**
     * Test JS file exclusion when JS is disabled
     */
    public function test_excludes_js_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_js'] = ''; // Disabled
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/plugins/test/script.js';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'JS files should be excluded when JS option is disabled');
    }

    /**
     * Test media file exclusion when media is disabled
     */
    public function test_excludes_media_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_media'] = ''; // Disabled
        
        $cdn = new \Shift8_CDN();
        
        $url = 'http://example.com/wp-content/uploads/image.jpg';
        
        $result = $cdn->is_excluded($url);
        
        $this->assertTrue($result, 'Media files should be excluded when media option is disabled');
    }

    /**
     * Test srcset attribute rewriting
     */
    public function test_rewrite_srcset() {
        $cdn = new \Shift8_CDN();
        
        $sources = array(
            array('url' => 'http://example.com/wp-content/uploads/image-300x200.jpg'),
            array('url' => 'http://example.com/wp-content/uploads/image-600x400.jpg'),
            array('url' => 'http://example.com/wp-content/uploads/image-1200x800.jpg'),
        );
        
        $result = $cdn->rewrite_srcset($sources);
        
        $this->assertCount(3, $result, 'Should have same number of sources');
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result[0]['url'], 'First source should use CDN');
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result[1]['url'], 'Second source should use CDN');
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result[2]['url'], 'Third source should use CDN');
    }

    /**
     * Test emoji script rewriting
     */
    public function test_rewrite_emoji_script() {
        $cdn = new \Shift8_CDN();
        
        $source = 'http://example.com/wp-includes/js/wp-emoji-release.min.js';
        
        $result = $cdn->cdn_script_loader_src($source, 'concatemoji');
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Emoji script should use CDN');
        $this->assertStringContainsString('/wp-includes/js/wp-emoji-release.min.js', $result, 'Should preserve path');
    }

    /**
     * Test that non-emoji scripts are not rewritten by script_loader_src
     */
    public function test_does_not_rewrite_non_emoji_scripts() {
        $cdn = new \Shift8_CDN();
        
        $source = 'http://example.com/wp-includes/js/jquery/jquery.min.js';
        
        $result = $cdn->cdn_script_loader_src($source, 'jquery-core');
        
        $this->assertEquals($source, $result, 'Non-emoji scripts should not be modified');
    }

    /**
     * Test paid CDN suffix
     */
    public function test_uses_paid_cdn_suffix() {
        // Mock transient to return paid suffix
        Functions\when('get_transient')->justReturn(S8CDN_SUFFIX_PAID);
        
        $cdn = new \Shift8_CDN();
        
        $html = '<img src="http://example.com/wp-content/uploads/image.jpg">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('.wpcdn.shift8cdn.com', $result, 'Should use paid CDN suffix');
    }

    /**
     * Test subdirectory installation
     */
    public function test_rewrite_with_subdirectory_install() {
        global $_test_options;
        $_test_options['shift8_cdn_url'] = 'http://example.com/blog';
        
        // Re-stub URL functions for subdirectory scenario
        Functions\stubs([
            'get_site_url' => 'http://example.com/blog',
            'home_url' => function($path = '') {
                return 'http://example.com/blog' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'content_url' => function($path = '') {
                return 'http://example.com/blog/wp-content' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'includes_url' => function($path = '') {
                return 'http://example.com/blog/wp-includes' . ($path ? '/' . ltrim($path, '/') : '');
            },
            'wp_upload_dir' => array(
                'baseurl' => 'http://example.com/blog/wp-content/uploads',
            ),
        ]);
        
        $cdn = new \Shift8_CDN();
        
        $html = '<img src="http://example.com/blog/wp-content/uploads/image.jpg">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should rewrite URL in subdirectory install');
        $this->assertStringContainsString('/blog/wp-content/uploads/image.jpg', $result, 'Should preserve correct path with subdirectory');
    }

    /**
     * Test multiple file extensions
     */
    public function test_rewrites_various_media_extensions() {
        $cdn = new \Shift8_CDN();
        
        $extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'pdf');
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        foreach ($extensions as $ext) {
            $html = sprintf('<img src="http://example.com/wp-content/uploads/file.%s">', $ext);
            $result = $method->invoke($cdn, $html);
            
            $this->assertStringContainsString(
                'https://test.cdn.shift8web.com',
                $result,
                "Should rewrite {$ext} files"
            );
        }
    }

    /**
     * Test rewriting preserves HTML structure
     */
    public function test_preserves_html_structure() {
        $cdn = new \Shift8_CDN();
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="http://example.com/wp-content/themes/test/style.css">
</head>
<body>
    <img src="http://example.com/wp-content/uploads/image.jpg" alt="Test">
    <script src="http://example.com/wp-content/plugins/test/script.js"></script>
</body>
</html>';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        $this->assertStringContainsString('<!DOCTYPE html>', $result, 'Should preserve DOCTYPE');
        $this->assertStringContainsString('<html>', $result, 'Should preserve HTML tags');
        $this->assertStringContainsString('<head>', $result, 'Should preserve head tag');
        $this->assertStringContainsString('<body>', $result, 'Should preserve body tag');
        $this->assertStringContainsString('alt="Test"', $result, 'Should preserve attributes');
    }

    /**
     * Test rewrite with minification enabled for CSS
     */
    public function test_rewrite_with_minification_css() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        $_test_options['shift8_cdn_url'] = 'http://example.com';
        $_test_options['shift8_cdn_api'] = 'test_api';
        $_test_options['shift8_cdn_prefix'] = 'test';
        $_test_options['shift8_cdn_css'] = 'on';
        $_test_options['shift8_cdn_minify_css'] = 'on';
        
        // Mock minification to return different URL
        Functions\when('shift8_cdn_get_minified_url')->alias(function($url, $type) {
            if ($type === 'css') {
                return 'http://example.com/wp-content/uploads/shift8-cdn-cache/css/abc123.min.css';
            }
            return $url;
        });
        
        $cdn = new \Shift8_CDN();
        $html = '<link rel="stylesheet" href="http://example.com/wp-content/themes/theme/style.css">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        // Should contain minified cache URL, not original
        $this->assertStringContainsString('shift8-cdn-cache', $result, 'Should use minified cached URL');
        $this->assertStringContainsString('.min.css', $result, 'Should have .min.css extension');
    }

    /**
     * Test rewrite skips already minified CSS files
     */
    public function test_rewrite_skips_already_minified_css() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        $_test_options['shift8_cdn_url'] = 'http://example.com';
        $_test_options['shift8_cdn_api'] = 'test_api';
        $_test_options['shift8_cdn_prefix'] = 'test';
        $_test_options['shift8_cdn_css'] = 'on';
        $_test_options['shift8_cdn_minify_css'] = 'on';
        
        // Mock minification - should skip .min.css files
        Functions\when('shift8_cdn_get_minified_url')->alias(function($url, $type) {
            return $url; // Returns original because already minified
        });
        
        $cdn = new \Shift8_CDN();
        $html = '<link rel="stylesheet" href="http://example.com/wp-content/themes/theme/style.min.css">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        // Should still rewrite to CDN but not change to cache
        $this->assertStringContainsString('https://test.cdn.shift8web.com', $result, 'Should apply CDN rewriting');
        $this->assertStringContainsString('style.min.css', $result, 'Should keep original .min.css filename');
        $this->assertStringNotContainsString('shift8-cdn-cache', $result, 'Should not use cache for already minified');
    }

    /**
     * Test rewrite with minification enabled for JS
     */
    public function test_rewrite_with_minification_js() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        $_test_options['shift8_cdn_url'] = 'http://example.com';
        $_test_options['shift8_cdn_api'] = 'test_api';
        $_test_options['shift8_cdn_prefix'] = 'test';
        $_test_options['shift8_cdn_js'] = 'on';
        $_test_options['shift8_cdn_minify_js'] = 'on';
        
        // Mock minification to return different URL
        Functions\when('shift8_cdn_get_minified_url')->alias(function($url, $type) {
            if ($type === 'js') {
                return 'http://example.com/wp-content/uploads/shift8-cdn-cache/js/def456.min.js';
            }
            return $url;
        });
        
        $cdn = new \Shift8_CDN();
        $html = '<script src="http://example.com/wp-content/plugins/plugin/script.js"></script>';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        // Should contain minified cache URL
        $this->assertStringContainsString('shift8-cdn-cache', $result, 'Should use minified cached URL');
        $this->assertStringContainsString('.min.js', $result, 'Should have .min.js extension');
    }

    /**
     * Test rewrite respects exclusion for minification
     */
    public function test_rewrite_excluded_file_not_minified() {
        global $_test_options;
        $_test_options['shift8_cdn_enabled'] = 'on';
        $_test_options['shift8_cdn_url'] = 'http://example.com';
        $_test_options['shift8_cdn_api'] = 'test_api';
        $_test_options['shift8_cdn_prefix'] = 'test';
        $_test_options['shift8_cdn_css'] = 'on';
        $_test_options['shift8_cdn_minify_css'] = 'on';
        $_test_options['shift8_cdn_reject_files'] = "/wp-content/themes/theme/exclude.css";
        
        $cdn = new \Shift8_CDN();
        $html = '<link rel="stylesheet" href="http://example.com/wp-content/themes/theme/exclude.css">';
        
        $reflection = new \ReflectionClass($cdn);
        $method = $reflection->getMethod('rewrite');
        $method->setAccessible(true);
        
        $result = $method->invoke($cdn, $html);
        
        // Should not be rewritten at all (excluded from both CDN and minification)
        $this->assertStringContainsString('http://example.com/wp-content/themes/theme/exclude.css', $result, 'Should preserve excluded file URL');
        $this->assertStringNotContainsString('shift8-cdn-cache', $result, 'Excluded files should not be minified');
    }
}

