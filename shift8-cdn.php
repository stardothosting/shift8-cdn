<?php
/**
 * Plugin Name: Shift8 CDN 
 * Plugin URI: https://github.com/stardothosting/shift8-cdn
 * Description: Plugin that integrates a fully functional CDN service
 * Version: 2.0.1
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Text Domain: shift8-cdn
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// PHP version check
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Shift8 CDN:</strong> This plugin requires PHP 7.4 or higher. ';
        echo 'You are running PHP ' . esc_html(PHP_VERSION) . '. ';
        echo 'Please contact your hosting provider to upgrade PHP.</p></div>';
    });
    return;
}

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load plugin files
require_once(plugin_dir_path(__FILE__).'shift8-cdn-rules.php' );
require_once(plugin_dir_path(__FILE__).'components/enqueuing.php' );
require_once(plugin_dir_path(__FILE__).'components/settings.php' );
require_once(plugin_dir_path(__FILE__).'components/functions.php' );
require_once(plugin_dir_path(__FILE__).'inc/shift8_cdn_rewrite.class.php' );
require_once(plugin_dir_path(__FILE__).'components/wp-cli.php' );

// Admin welcome page
if (!function_exists('shift8_main_page')) {
	function shift8_main_page() {
	?>
	<div class="wrap">
	<h2>Shift8 Plugins</h2>
	Shift8 is a Toronto based web development and design company. We specialize in Wordpress development and love to contribute back to the Wordpress community whenever we can! You can see more about us by visiting <a href="https://www.shift8web.ca" target="_new">our website</a>.
	</div>
	<?php
	}
}

// Admin settings page
function shift8_cdn_settings_page() {
?>
<div class="wrap">
<h2>Shift8 CDN Settings</h2>

<?php
// Display success message after settings save
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>Settings saved successfully.</strong></p>
    </div>
    <?php
}
?>

<?php if (is_admin()) { 
$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'core_settings';
$cdn_subtab = isset($_GET['cdn_subtab']) ? sanitize_text_field(wp_unslash($_GET['cdn_subtab'])) : 'general';
$plugin_data = get_plugin_data( __FILE__ );
$plugin_name = $plugin_data['TextDomain'];
    ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=core_settings" class="nav-tab <?php echo $active_tab == 'core_settings' ? 'nav-tab-active' : ''; ?>">Core Settings</a>
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_purge" class="nav-tab <?php echo $active_tab == 'cdn_purge' ? 'nav-tab-active' : ''; ?>">Purge Cache</a>
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_options" class="nav-tab <?php echo $active_tab == 'cdn_options' ? 'nav-tab-active' : ''; ?>">CDN Settings</a>
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=support_options" class="nav-tab <?php echo $active_tab == 'support_options' ? 'nav-tab-active' : ''; ?>">Support</a>
</h2>

<?php if ($active_tab == 'cdn_options'): ?>
<h2 class="nav-tab-wrapper shift8-subtab-wrapper">
    <a href="?page=<?php echo esc_attr($plugin_name); ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_options&cdn_subtab=general" class="nav-tab <?php echo $cdn_subtab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
    <a href="?page=<?php echo esc_attr($plugin_name); ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_options&cdn_subtab=minification" class="nav-tab <?php echo $cdn_subtab == 'minification' ? 'nav-tab-active' : ''; ?>">Minification</a>
    <a href="?page=<?php echo esc_attr($plugin_name); ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_options&cdn_subtab=advanced" class="nav-tab <?php echo $cdn_subtab == 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
    <a href="?page=<?php echo esc_attr($plugin_name); ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=cdn_options&cdn_subtab=optimization" class="nav-tab <?php echo $cdn_subtab == 'optimization' ? 'nav-tab-active' : ''; ?>">Resource Optimization</a>
</h2>
<?php endif; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'shift8-cdn-settings-group' ); ?>
    <?php do_settings_sections( 'shift8-cdn-settings-group' ); ?>
    <?php
	$locations = get_theme_mod( 'nav_menu_locations' );
	if (!empty($locations)) {
		foreach ($locations as $locationId => $menuValue) {
			if (has_nav_menu($locationId)) {
				$shift8_cdn_menu = $locationId;
			}
		}
	}
	?>
    <table class="form-table shift8-cdn-table">
    <tbody class="<?php echo $active_tab == 'core_settings' ? 'shift8-cdn-admin-tab-active' : 'shift8-cdn-admin-tab-inactive'; ?>">
	<tr valign="top">
    <th scope="row">Core Settings</th>
    <td><span id="shift8-cdn-notice">
    <?php 
    settings_errors('shift8_cdn_url');
    settings_errors('shift8_cdn_api');
    settings_errors('shift8_cdn_prefix');
    settings_errors('shift8_cdn_css');
    settings_errors('shift8_cdn_js');
    settings_errors('shift8_cdn_media');
    ?>
    </span></td>
	</tr>
    <tr valign="top">
    <th scope="row">Enable Shift8 CDN : </th>
    <td>
    <?php
    if (esc_attr( get_option('shift8_cdn_enabled') ) == 'on') {
        $enabled_checked = "checked";
    } else {
        $enabled_checked = "";
    }
    ?>
    <label class="switch">
    <input type="checkbox" name="shift8_cdn_enabled" <?php echo esc_attr($enabled_checked); ?>>
    <div class="slider round"></div>
    </label>
    </td>
    </tr>
    <tr valign="top">
    <th scope="row">Shift8 CDN Account Status : </th>
    <td>
    <?php
    if (shift8_cdn_check_paid_transient() === S8CDN_SUFFIX_PAID) {
        $account_status = "Paid Plan";
    } else {
        $account_status = "Free Plan";
    }
    ?>
    <strong><?php echo esc_html($account_status); ?></strong>
    <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-cdn-tooltiptext">Note : If you have upgraded your account and dont see this status change, click the "Check" button to manually synchronize.</span>
    </div>
    </td>
</tr>
	<tr valign="top">
    <th scope="row">Site URL : </th>
    <td><input type="text" name="shift8_cdn_url" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_url'))) ? get_site_url() : esc_attr(get_option('shift8_cdn_url'))); ?>">
    <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-cdn-tooltiptext">Note : Only enter site, no URI : https://www.domain.com . Also make sure the site url matches whats in our dashboard exactly.</span>
    </div>
    </td>
	</tr>
	<tr valign="top">
    <th scope="row">Shift8 CDN API Key : </th>
    <td><input type="text" id="shift8_cdn_api_field" name="shift8_cdn_api" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_api'))) ? '' : esc_attr(get_option('shift8_cdn_api'))); ?>">
    <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-cdn-tooltiptext">Keep this safe!</span>
    </div>
    </td>
	</tr>
	<tr valign="top">
    <th scope="row">Shift8 CDN Prefix : </th>
    <td><input type="text" id="shift8_cdn_prefix_field" name="shift8_cdn_prefix" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_prefix'))) ? '' : esc_attr(get_option('shift8_cdn_prefix'))); ?>"></td>
	</tr>
    <?php if (!empty(esc_attr(get_option('shift8_cdn_prefix')))) { ?>
    <tr valign="top">
    <th scope="row">Shift8 CDN Hostname : </th>
    <td><input type="text" id="shift8_cdn_hostname_field" name="shift8_cdn_hostname_display" size="34" value="<?php echo esc_attr(shift8_cdn_get_hostname()); ?>" readonly style="background-color: #f0f0f1; cursor: text;" onclick="this.select();">
    <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-cdn-tooltiptext">Copy this hostname to use in other caching plugins that support custom CDN configuration. Click the field to select all text.</span>
    </div>
    </td>
	</tr>
    <tr valign="top">
    <th scope="row">Test URL before enabling : </th>
    <?php
        if (!empty(esc_attr(get_option('shift8_cdn_prefix'))) && !empty(esc_attr(get_option('shift8_cdn_url')))) {
            // Parse url properly to catch if path exists or not
            $shift8_test_path = (array_key_exists('path', wp_parse_url(esc_attr(get_option('shift8_cdn_url'), PHP_URL_PATH))) ? rtrim(wp_parse_url(esc_attr(get_option('shift8_cdn_url'), PHP_URL_PATH))['path'], '/') : null );
            
            // Use helper function to get CDN hostname
            $shift8_cdn_hostname = shift8_cdn_get_hostname();
            $shift8_test_url = 'https://' . $shift8_cdn_hostname . $shift8_test_path . '/wp-content/plugins/shift8-cdn/test/test.png';
        } else { 
            $shift8_test_url = null;
        }
    ?>
    <td><a href="<?php echo esc_url($shift8_test_url); ?>" target="_new" >Click to open test URL in new tab</a>
    <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
        <span class="shift8-cdn-tooltiptext">Note : this will load a test image from the CDN. If it loads correctly then it should be working!</span>
    </div>
    </td>
    </tr>
    <?php } ?>
    <tr valign="top">
    <td width="226px"><div class="shift8-cdn-spinner"></div></td>
    <td>
    <?php if (empty(esc_attr(get_option('shift8_cdn_api')))) { ?>
    <div class="shift8-cdn-prereg-note">Note : You need to register in our dashboard first. Click "Register" below and add your site via our dashboard. Then copy the information back here and hit save.</div>
    <?php } ?>
    <ul class="shift8-cdn-controls">
    <li>
    <div class="shift8-cdn-button-container">
    <button onclick="window.open('<?php echo S8CDN_API . "/register"; ?>','_blank')" class="shift8-cdn-button shift8-cdn-button-register">Register</button>
    </div>
    </li>
    <?php if (!empty(esc_attr(get_option('shift8_cdn_api')))) { ?>
    <li>
    <div class="shift8-cdn-button-container">
    <a id="shift8-cdn-check" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-check">Check</button></a>
    </div>
    </li>
    <?php } ?>
    </ul>
    <div class="shift8-cdn-response">
    </div>
    </td>
    </tr>
    </tbody>
    <!-- CDN PURGE TAB -->
    <tbody class="<?php echo $active_tab == 'cdn_purge' ? 'shift8-cdn-admin-tab-active' : 'shift8-cdn-admin-tab-inactive'; ?>">
    <tr valign="top">
    <th scope="row">Purge Cache</th>
    </tr>
    <tr valign="top">
    <td>This will submit a purge request across the entire network of endpoints. This can only be done once every few minutes. If you are troubleshooting, it is better to switch the CDN off in the Core Settings tab.</td>
    </tr>
    <tr valign="top">
    <td>
        <div class="shift8-cdn-button-container">
            <a id="shift8-cdn-purge" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-register">Purge</button></a>
        </div>
    </td>
    </tr>
    <tr valign="top">
    <td width="226px"><div class="shift8-cdn-purge-spinner"></div></td>
    </tr>
    <tr>
    <td>
        <div class="shift8-cdn-purge-response"></div>
    </td>
    </tr>
    </tbody>
    <!-- CDN SETTINGS TAB -->
    <tbody class="<?php echo $active_tab == 'cdn_options' ? 'shift8-cdn-admin-tab-active' : 'shift8-cdn-admin-tab-inactive'; ?>">
    <tr valign="top">
    <td colspan="2">
        
        <?php
        // Hidden inputs to preserve settings from other sub-tabs
        // Only include hidden input if option is currently checked
        if ($cdn_subtab !== 'general') {
            // Preserve General tab settings
            if (!empty(get_option('shift8_cdn_css'))) {
                echo '<input type="hidden" name="shift8_cdn_css" value="on">';
            }
            if (!empty(get_option('shift8_cdn_js'))) {
                echo '<input type="hidden" name="shift8_cdn_js" value="on">';
            }
            if (!empty(get_option('shift8_cdn_media'))) {
                echo '<input type="hidden" name="shift8_cdn_media" value="on">';
            }
        }
        if ($cdn_subtab !== 'minification') {
            // Preserve Minification tab settings
            if (!empty(get_option('shift8_cdn_minify_css'))) {
                echo '<input type="hidden" name="shift8_cdn_minify_css" value="on">';
            }
            if (!empty(get_option('shift8_cdn_minify_js'))) {
                echo '<input type="hidden" name="shift8_cdn_minify_js" value="on">';
            }
            if (!empty(get_option('shift8_cdn_minify_html'))) {
                echo '<input type="hidden" name="shift8_cdn_minify_html" value="on">';
            }
            if (!empty(get_option('shift8_cdn_minify_html_skip_logged_in'))) {
                echo '<input type="hidden" name="shift8_cdn_minify_html_skip_logged_in" value="on">';
            }
            if (!empty(get_option('shift8_cdn_minify_html_preserve_comments'))) {
                echo '<input type="hidden" name="shift8_cdn_minify_html_preserve_comments" value="on">';
            }
        }
        ?>
        
        <?php if ($cdn_subtab == 'general'): ?>
        <!-- GENERAL SUB-TAB -->
        
        <div class="shift8-card">
            <h3 class="shift8-card-header">CDN Hostname</h3>
            <div class="shift8-card-body">
                <?php if (!empty(esc_attr(get_option('shift8_cdn_prefix')))): ?>
                <div class="shift8-setting-row">
                    <div class="shift8-setting-label">
                        <strong>Your CDN Hostname</strong>
                        <div class="shift8-info">Copy this to use in other caching plugins that support custom CDN configuration</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="text" class="shift8-readonly-input" value="<?php echo esc_attr(shift8_cdn_get_hostname()); ?>" readonly onclick="this.select();" style="width: 300px;">
                    </div>
                </div>
                <?php else: ?>
                <div class="shift8-info">Configure your API key in Core Settings to see your CDN hostname</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="shift8-card">
            <h3 class="shift8-card-header">Enable CDN by File Type</h3>
            <div class="shift8-card-body">
                <div class="shift8-setting-row">
                    <label for="shift8_cdn_css" class="shift8-setting-label">CSS Files</label>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_css" name="shift8_cdn_css" <?php echo (empty(esc_attr(get_option('shift8_cdn_css'))) ? '' : 'checked'); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_css" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                    </div>
                </div>
                <div class="shift8-setting-row">
                    <label for="shift8_cdn_js" class="shift8-setting-label">JavaScript Files</label>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_js" name="shift8_cdn_js" <?php echo (empty(esc_attr(get_option('shift8_cdn_js'))) ? '' : 'checked'); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_js" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                    </div>
                </div>
                <div class="shift8-setting-row">
                    <label for="shift8_cdn_media" class="shift8-setting-label">Image & Media Files</label>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_media" name="shift8_cdn_media" <?php echo (empty(esc_attr(get_option('shift8_cdn_media'))) ? '' : 'checked'); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_media" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($cdn_subtab == 'minification'): ?>
        <!-- MINIFICATION SUB-TAB -->
        
        <div class="shift8-card">
            <h3 class="shift8-card-header">Automatic Minification</h3>
            <div class="shift8-card-body">
                <div class="shift8-setting-row">
                    <div class="shift8-setting-label">
                        <label for="shift8_cdn_minify_css">Minify CSS Files</label>
                        <div class="shift8-info">Requires "Enable CDN for CSS Files" to be active</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_minify_css" name="shift8_cdn_minify_css" <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_css'))) ? '' : 'checked'); ?> <?php echo (empty(esc_attr(get_option('shift8_cdn_css'))) ? 'disabled' : ''); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_minify_css" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                        <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
                            <span class="shift8-cdn-tooltiptext">Automatically minifies CSS files before serving through CDN. Already minified files (.min.css) are skipped.</span>
                        </div>
                    </div>
                </div>
                <div class="shift8-setting-row">
                    <div class="shift8-setting-label">
                        <label for="shift8_cdn_minify_js">Minify JavaScript Files</label>
                        <div class="shift8-info">Requires "Enable CDN for JavaScript Files" to be active</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_minify_js" name="shift8_cdn_minify_js" <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_js'))) ? '' : 'checked'); ?> <?php echo (empty(esc_attr(get_option('shift8_cdn_js'))) ? 'disabled' : ''); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_minify_js" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                        <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
                            <span class="shift8-cdn-tooltiptext">Automatically minifies JavaScript files before serving through CDN. Already minified files (.min.js) are skipped.</span>
                        </div>
                    </div>
                </div>
                <div class="shift8-setting-row">
                    <div class="shift8-setting-label">
                        <label for="shift8_cdn_minify_html">Minify HTML Output</label>
                        <div class="shift8-info">Reduces page size by 20-30%, improving transfer speeds</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_minify_html" name="shift8_cdn_minify_html" <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_html'))) ? '' : 'checked'); ?> class="shift8-toggle-checkbox">
                        <label for="shift8_cdn_minify_html" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                        <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
                            <span class="shift8-cdn-tooltiptext">Removes unnecessary whitespace and comments from HTML output. Automatically skips page builder edit modes.</span>
                        </div>
                    </div>
                </div>
                <div class="shift8-setting-row" style="padding-left: 30px;">
                    <div class="shift8-setting-label">
                        <label for="shift8_cdn_minify_html_skip_logged_in">Skip for Logged-In Users</label>
                        <div class="shift8-info">Useful for compatibility with page builders</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_minify_html_skip_logged_in" name="shift8_cdn_minify_html_skip_logged_in" <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_html_skip_logged_in'))) ? '' : 'checked'); ?> <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_html'))) ? 'disabled' : ''); ?> class="shift8-toggle-checkbox shift8-html-option">
                        <label for="shift8_cdn_minify_html_skip_logged_in" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                        <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
                            <span class="shift8-cdn-tooltiptext">When enabled, HTML minification will not run for logged-in users.</span>
                        </div>
                    </div>
                </div>
                <div class="shift8-setting-row" style="padding-left: 30px;">
                    <div class="shift8-setting-label">
                        <label for="shift8_cdn_minify_html_preserve_comments">Preserve HTML Comments</label>
                        <div class="shift8-info">Keep all HTML comments (useful for debugging)</div>
                    </div>
                    <div class="shift8-setting-control">
                        <input type="checkbox" id="shift8_cdn_minify_html_preserve_comments" name="shift8_cdn_minify_html_preserve_comments" <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_html_preserve_comments'))) ? '' : 'checked'); ?> <?php echo (empty(esc_attr(get_option('shift8_cdn_minify_html'))) ? 'disabled' : ''); ?> class="shift8-toggle-checkbox shift8-html-option">
                        <label for="shift8_cdn_minify_html_preserve_comments" class="shift8-toggle-label">
                            <span class="shift8-toggle-switch"></span>
                        </label>
                        <div class="shift8-cdn-tooltip"><span class="dashicons dashicons-editor-help"></span>
                            <span class="shift8-cdn-tooltiptext">When enabled, HTML comments will not be removed during minification.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="shift8-card">
            <h3 class="shift8-card-header">Minified File Cache</h3>
            <div class="shift8-card-body">
                <div class="shift8-cache-stats">
                    <?php
                    $cache_stats = shift8_cdn_get_cache_stats();
                    $size_mb = round($cache_stats['total_size'] / (1024 * 1024), 2);
                    echo esc_html($cache_stats['css_count']) . ' CSS files, ' . esc_html($cache_stats['js_count']) . ' JS files cached (' . esc_html($size_mb) . ' MB)';
                    ?>
                </div>
                <div class="shift8-setting-row">
                    <div class="shift8-setting-label">
                        <div class="shift8-info">Cache is automatically invalidated when source files change</div>
                    </div>
                    <div class="shift8-setting-control">
                        <a id="shift8-cdn-clear-cache" href="<?php echo esc_url(wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_clear_cache'), 'shift8_cdn_clear_cache')); ?>" class="button button-secondary">Clear Minified Cache</a>
                        <span class="shift8-cdn-cache-response" style="margin-left: 10px;"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($cdn_subtab == 'advanced'): ?>
        <!-- ADVANCED SUB-TAB -->
        
        <div class="shift8-card">
            <h3 class="shift8-card-header">URL Exclusion Patterns</h3>
            <div class="shift8-card-body">
                <div class="shift8-setting-label">
                    <strong>Files to exclude from CDN (one per line)</strong>
                    <div class="shift8-info">Example: /wp-content/themes/mytheme/custom.js or /wp-content/plugins/myplugin/</div>
                    <div class="shift8-info" style="color: #d63638; margin-top: 8px;">Note: Wildcards (*) are supported. Example: /wp-content/uploads/2023/12/*</div>
                </div>
                <textarea id="shift8-cdn-reject-files" rows="10" style="width: 100%; margin-top: 12px;" name="shift8_cdn_reject_files" placeholder="/wp-content/uploads/file.jpg&#10;/wp-content/plugins/myplugin/"><?php echo esc_textarea(get_option('shift8_cdn_reject_files')); ?></textarea>
            </div>
        </div>
        
        <?php elseif ($cdn_subtab == 'optimization'): ?>
        <!-- RESOURCE OPTIMIZATION SUB-TAB (Coming Soon) -->
        
        <div class="shift8-coming-soon">
            <h3>Coming Soon in Version 2.1.0</h3>
            <ul>
                <li>HTML Minification</li>
                <li>Resource Hints (preconnect, preload)</li>
                <li>Enhanced Lazy Loading</li>
                <li>JavaScript Deferring</li>
                <li>Font Optimization</li>
            </ul>
            <p style="color: #646970; margin-top: 20px;">These features are currently in development and will be available in a future release.</p>
        </div>
        
        <?php endif; ?>
        
    </td>
    </tr>
    </tbody>
    <!-- SUPPORT TAB -->
    <tbody class="<?php echo $active_tab == 'support_options' ? 'shift8-cdn-admin-tab-active' : 'shift8-cdn-admin-tab-inactive'; ?>">
    <tr valign="top">
    <th scope="row">Support</th>
    </tr>
    <tr valign="top">
    <td style="width:500px;">If you are experiencing difficulties, you can receive support if you Visit the <a href="https://wordpress.org/support/plugin/shift8-cdn/" target="_new">Shift8 CDN Wordpress support page</a> and post your question there.<Br /><Br />
    <strong>Debug Info</strong><br /><br />
    Providing the debug information below to the Shift8 CDN support team may be helpful in them assisting in diagnosing any issues you may be having. <br /><br />
    <div class="shift8-cdn-button-container">
    </div><button class="shift8-cdn-button shift8-cdn-button-copyclipboard" id="button1" onclick="Shift8CDNCopyToClipboard('shift8cdn-debug')">Copy info below to clipboard</button>
    <br /><br />
    <script type="text/javascript">
        function showDetails(id) {
            document.getElementById(id).style.display = 'block';
        }
        function hideDetails(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
    <div class="wrap">
        <div class="postbox" id="shift8cdn-debug">
            <h2><?php _e('Shift8 CDN Debug Info'); ?></h2>
            <p><?php echo shift8_cdn_debug_version_check(); ?></p>
        </div>
        <!--<div class="postbox" id="shift8cdn-debugphp">
            <h2><?php _e('Shift8 CDN PHP Debug Info'); ?></h2>
            <p><?php _e('For more detailed PHP server related information, click the Show Details link below.'); ?></p>
            <a href="#" onclick="showDetails('details'); return false;"><?php _e('Show Details'); ?></a>
            <a href="#" onclick="hideDetails('details'); return false;"><?php _e('Hide Details'); ?></a>
            <span id="details" style="display: none;"><?php echo shift8_cdn_debug_get_php_info(); ?></span>
        </div>-->
    </div>
    </td>
    </tr>
    </tbody>
    </table>
    <?php 
    if ($active_tab !== 'support_options' && $active_tab !== 'cdn_purge') {
        submit_button(); 
    }
    ?>
    </form>
</div>
<?php 
	} // is_admin
}
