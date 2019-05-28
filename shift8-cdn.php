<?php
/**
 * Plugin Name: Shift8 CDN 
 * Plugin URI: https://github.com/stardothosting/shift8-cdn
 * Description: Plugin that integrates a fully functional CDN service
 * Version: 1.12
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 */

require_once(plugin_dir_path(__FILE__).'shift8-cdn-rules.php' );
require_once(plugin_dir_path(__FILE__).'inc/shift8_cdn_rewrite.class.php' );
require_once(plugin_dir_path(__FILE__).'components/enqueuing.php' );
require_once(plugin_dir_path(__FILE__).'components/settings.php' );
require_once(plugin_dir_path(__FILE__).'components/functions.php' );

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
<?php if (is_admin()) { ?>
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
	<tr valign="top">
    <td><span id="shift8-cdn-notice">
    <?php 
    settings_errors('shift8_cdn_url');
    settings_errors('shift8_cdn_email');
    settings_errors('shift8_cdn_api');
    settings_errors('shift8_cdn_prefix');
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
    <input type="checkbox" name="shift8_cdn_enabled" <?php echo $enabled_checked; ?>>
    <div class="slider round"></div>
    </label>
    </td>
    </tr>
	<tr valign="top">
    <th scope="row">Site URL : </th>
    <td><input type="text" name="shift8_cdn_url" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_url'))) ? get_site_url() : esc_attr(get_option('shift8_cdn_url'))); ?>"></td>
	</tr>
	<tr valign="top">
    <th scope="row">Your Email : </th>
    <td><input type="text" name="shift8_cdn_email" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_email'))) ? '' : esc_attr(get_option('shift8_cdn_email'))); ?>"></td>
	</tr>
	<tr valign="top">
    <th scope="row">Shift8 CDN API Key : </th>
    <td><input type="text" id="shift8_cdn_api_field" name="shift8_cdn_api" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_api'))) ? '' : esc_attr(get_option('shift8_cdn_api'))); ?>"></td>
	</tr>
	<tr valign="top">
    <th scope="row">Shift8 CDN Prefix : </th>
    <td><input type="text" id="shift8_cdn_prefix_field" name="shift8_cdn_prefix" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_prefix'))) ? '' : esc_attr(get_option('shift8_cdn_prefix'))); ?>"></td>
	</tr>
    <?php if (!empty(esc_attr(get_option('shift8_cdn_prefix')))) { ?>
    <tr valign="top">
    <th scope="row">Test URL before enabling : </th>
    <td><a href="<?php echo (empty(esc_attr(get_option('shift8_cdn_prefix'))) ? '' : 'https://' . esc_attr(get_option('shift8_cdn_prefix'))) . '.wpcdn.shift8cdn.com/wp-content/plugins/shift8-cdn/test/test.png'; ?>" target="_new" >Click to open test URL in new tab</a></td>
    </tr>
    <?php } ?>
	</table>
    <?php submit_button(); ?>
	</form>
</div>
	<div class="shift8-cdn-button-container">
    Note : make sure you set your URL and email properly, then save the settings before registering.<br /><br />
	<a id="shift8-cdn-register" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-register">Register</button></a>
	<div class="shift8-cdn-response">
	</div>
<?php 
	} // is_admin
}


