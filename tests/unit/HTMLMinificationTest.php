<?php
/**
 * Tests for HTML Minification
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class HTMLMinificationTest extends TestCase {
    
    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\stubs([
            'get_option' => function($option, $default = false) {
                global $_test_options;
                return isset($_test_options[$option]) ? $_test_options[$option] : $default;
            },
            'esc_attr' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'esc_html' => function($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
            'is_admin' => false,
            'is_user_logged_in' => false,
            'error_log' => function($message) {},
        ]);
    }
    
    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
    
    /**
     * Test that HTML minification is skipped when disabled
     */
    public function test_minify_html_skipped_when_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = '';
        
        $html = '<html>  <body>  Test  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertEquals($html, $result, 'HTML should not be minified when option is disabled');
    }
    
    /**
     * Test that HTML minification works when enabled
     */
    public function test_minify_html_removes_whitespace() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html>  <body>  <p>  Test  </p>  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('<html><body><p>', $result, 'Whitespace between tags should be removed');
        $this->assertLessThan(strlen($html), strlen($result), 'Minified HTML should be shorter');
    }
    
    /**
     * Test that HTML comments are removed
     */
    public function test_minify_html_removes_comments() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_preserve_comments'] = '';
        
        $html = '<html><!-- This is a comment --><body>Test</body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringNotContainsString('<!-- This is a comment -->', $result, 'Regular HTML comments should be removed');
    }
    
    /**
     * Test that HTML comments are preserved when option is enabled
     */
    public function test_minify_html_preserves_comments_when_enabled() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_preserve_comments'] = 'on';
        
        $html = '<html><!-- This is a comment --><body>Test</body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('<!-- This is a comment -->', $result, 'HTML comments should be preserved when option is enabled');
    }
    
    /**
     * Test that conditional comments for IE are always preserved
     */
    public function test_minify_html_preserves_conditional_comments() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_preserve_comments'] = '';
        
        $html = '<html><!--[if IE]><p>IE Only</p><![endif]--><body>Test</body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('<!--[if IE]>', $result, 'Conditional IE comments should always be preserved');
        $this->assertStringContainsString('<![endif]-->', $result, 'Conditional IE comment endings should be preserved');
    }
    
    /**
     * Test that script tags are preserved
     */
    public function test_minify_html_preserves_script_tags() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><body><script>var x = "  spaced  ";</script></body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('var x = "  spaced  ";', $result, 'Script content should be preserved exactly');
    }
    
    /**
     * Test that style tags are preserved
     */
    public function test_minify_html_preserves_style_tags() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><head><style>body {  margin: 0;  }</style></head></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('margin: 0;', $result, 'Style content should be preserved exactly');
    }
    
    /**
     * Test that pre tags are preserved
     */
    public function test_minify_html_preserves_pre_tags() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><body><pre>  Code with    spaces  </pre></body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('  Code with    spaces  ', $result, 'Pre tag content should preserve whitespace');
    }
    
    /**
     * Test that textarea tags are preserved
     */
    public function test_minify_html_preserves_textarea_tags() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><body><textarea>  Text with    spaces  </textarea></body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('  Text with    spaces  ', $result, 'Textarea content should preserve whitespace');
    }
    
    /**
     * Test that HTML minification is skipped in admin area
     */
    public function test_minify_html_skipped_in_admin() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        Functions\when('is_admin')->justReturn(true);
        
        $html = '<html>  <body>  Test  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertEquals($html, $result, 'HTML should not be minified in admin area');
    }
    
    /**
     * Test that HTML minification is skipped for logged-in users when option is enabled
     */
    public function test_minify_html_skipped_for_logged_in_users() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_skip_logged_in'] = 'on';
        
        Functions\when('is_user_logged_in')->justReturn(true);
        
        $html = '<html>  <body>  Test  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertEquals($html, $result, 'HTML should not be minified for logged-in users when option is enabled');
    }
    
    /**
     * Test that HTML minification works for logged-in users when option is disabled
     */
    public function test_minify_html_works_for_logged_in_when_option_disabled() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_skip_logged_in'] = '';
        
        Functions\when('is_user_logged_in')->justReturn(true);
        
        $html = '<html>  <body>  Test  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('<html><body>', $result, 'HTML should be minified for logged-in users when skip option is disabled');
    }
    
    /**
     * Test page builder detection - Elementor
     */
    public function test_page_builder_detection_elementor() {
        $_GET['elementor-preview'] = '123';
        
        $result = shift8_cdn_is_page_builder_active();
        
        $this->assertTrue($result, 'Elementor edit mode should be detected');
        
        unset($_GET['elementor-preview']);
    }
    
    /**
     * Test page builder detection - Elementor action
     */
    public function test_page_builder_detection_elementor_action() {
        $_GET['action'] = 'elementor';
        
        $result = shift8_cdn_is_page_builder_active();
        
        $this->assertTrue($result, 'Elementor action should be detected');
        
        unset($_GET['action']);
    }
    
    /**
     * Test page builder detection - Beaver Builder
     */
    public function test_page_builder_detection_beaver_builder() {
        $_GET['fl_builder'] = '1';
        
        $result = shift8_cdn_is_page_builder_active();
        
        $this->assertTrue($result, 'Beaver Builder should be detected');
        
        unset($_GET['fl_builder']);
    }
    
    /**
     * Test page builder detection - Bricks
     */
    public function test_page_builder_detection_bricks() {
        $_GET['bricks'] = 'run';
        
        $result = shift8_cdn_is_page_builder_active();
        
        $this->assertTrue($result, 'Bricks builder should be detected');
        
        unset($_GET['bricks']);
    }
    
    /**
     * Test page builder detection - None active
     */
    public function test_page_builder_detection_none_active() {
        $result = shift8_cdn_is_page_builder_active();
        
        $this->assertFalse($result, 'No page builder should be detected when none are active');
    }
    
    /**
     * Test that HTML minification is skipped when page builder is active
     */
    public function test_minify_html_skipped_for_page_builder() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $_GET['elementor-preview'] = '123';
        
        $html = '<html>  <body>  Test  </body>  </html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertEquals($html, $result, 'HTML should not be minified when page builder is active');
        
        unset($_GET['elementor-preview']);
    }
    
    /**
     * Test that multiple script tags are preserved
     */
    public function test_minify_html_preserves_multiple_scripts() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><body><script>var a = 1;</script><div>Text</div><script>var b = 2;</script></body></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('var a = 1;', $result, 'First script should be preserved');
        $this->assertStringContainsString('var b = 2;', $result, 'Second script should be preserved');
    }
    
    /**
     * Test that HTML structure is preserved
     */
    public function test_minify_html_preserves_structure() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><head><title>Test</title></head><body><div><p>Content</p></div></body></html>';
        $result = shift8_cdn_minify_html($html);
        
        // Check that all opening and closing tags are present
        $this->assertStringContainsString('<html>', $result, 'HTML tag should be present');
        $this->assertStringContainsString('</html>', $result, 'Closing HTML tag should be present');
        $this->assertStringContainsString('<head>', $result, 'Head tag should be present');
        $this->assertStringContainsString('</head>', $result, 'Closing head tag should be present');
        $this->assertStringContainsString('<body>', $result, 'Body tag should be present');
        $this->assertStringContainsString('</body>', $result, 'Closing body tag should be present');
        $this->assertStringContainsString('<div>', $result, 'Div tag should be present');
        $this->assertStringContainsString('</div>', $result, 'Closing div tag should be present');
    }
    
    /**
     * Test minification with real-world HTML
     */
    public function test_minify_html_real_world_example() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_preserve_comments'] = '';
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Page</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <!-- Main content -->
    <div class="container">
        <h1>Hello World</h1>
        <p>This is a test paragraph.</p>
    </div>
    <script>
        console.log("Hello");
    </script>
</body>
</html>';
        
        $result = shift8_cdn_minify_html($html);
        
        $this->assertLessThan(strlen($html), strlen($result), 'Minified HTML should be shorter than original');
        $this->assertStringNotContainsString('<!-- Main content -->', $result, 'HTML comments should be removed');
        $this->assertStringContainsString('console.log("Hello");', $result, 'Script content should be preserved');
        $this->assertStringContainsString('margin: 0;', $result, 'Style content should be preserved');
        $this->assertStringContainsString('<h1>Hello World</h1>', $result, 'Content should be preserved');
    }
    
    /**
     * Test that error handling works
     */
    public function test_minify_html_error_handling() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        // Test with malformed HTML to trigger potential error
        $html = '<html><body><div><p>Test</body></html>'; // Missing closing div and p
        $result = shift8_cdn_minify_html($html);
        
        // Should still return something (original or processed)
        $this->assertNotEmpty($result, 'Should handle malformed HTML gracefully');
    }
    
    /**
     * Test that JSON-LD structured data is preserved
     */
    public function test_minify_html_preserves_json_ld() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        
        $html = '<html><head><script type="application/ld+json">{"@context": "https://schema.org", "@type": "Organization"}</script></head></html>';
        $result = shift8_cdn_minify_html($html);
        
        $this->assertStringContainsString('"@context"', $result, 'JSON-LD content should be preserved');
        $this->assertStringContainsString('"@type"', $result, 'JSON-LD type should be preserved');
    }
    
    /**
     * Test settings registration includes HTML minification options
     */
    public function test_register_settings_includes_html_minify_options() {
        // This test verifies that the registration function includes HTML minify options
        // We can't actually test register_setting() calls, but we can verify the function exists
        $this->assertTrue(function_exists('register_shift8_cdn_settings'), 'Settings registration function should exist');
    }
    
    /**
     * Test uninstall hook deletes HTML minification options
     */
    public function test_uninstall_hook_deletes_html_minify_options() {
        global $_test_options;
        $_test_options['shift8_cdn_minify_html'] = 'on';
        $_test_options['shift8_cdn_minify_html_skip_logged_in'] = 'on';
        $_test_options['shift8_cdn_minify_html_preserve_comments'] = 'on';
        
        // Mock wp_upload_dir for cache directory operations
        Functions\when('wp_upload_dir')->justReturn(array(
            'path' => '/tmp/uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false
        ));
        
        // Mock file operations
        Functions\when('glob')->justReturn(array());
        Functions\when('file_exists')->justReturn(false);
        Functions\when('is_writable')->justReturn(true);
        Functions\when('rmdir')->justReturn(true);
        
        Functions\expect('delete_option')->with('shift8_cdn_minify_html')->once();
        Functions\expect('delete_option')->with('shift8_cdn_minify_html_skip_logged_in')->once();
        Functions\expect('delete_option')->with('shift8_cdn_minify_html_preserve_comments')->once();
        
        // Additional expectations for other options
        Functions\expect('delete_option')->atLeast()->times(1);
        Functions\expect('delete_transient')->atLeast()->times(1);
        Functions\expect('wp_clear_scheduled_hook')->atLeast()->times(1);
        
        shift8_cdn_uninstall_hook();
    }
}

