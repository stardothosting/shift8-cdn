<?php
/**
 * Plugin Name: Shift8 CDN 
 * Plugin URI: https://github.com/stardothosting/shift8-cdn
 * Description: Plugin that integrates a fully functional CDN service
 * Version: 1.01
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 */

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
    settings_errors('shift8_cdn_user');
    settings_errors('shift8_cdn_api'); 
    ?>
    </span></td>
	</tr>
	<tr valign="top">
    <th scope="row">Jenkins Build Trigger URL : </th>
    <td><input type="text" name="shift8_cdn_url" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_url'))) ? '' : esc_attr(get_option('shift8_cdn_url'))); ?>"></td>
	</tr>
	<tr valign="top">
    <th scope="row">Jenkins Build Username : </th>
    <td><input type="text" name="shift8_cdn_user" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_user'))) ? '' : esc_attr(get_option('shift8_cdn_user'))); ?>"></td>
	</tr>
	<tr valign="top">
    <th scope="row">Jenkins Build API Token : </th>
    <td><input type="text" name="shift8_cdn_api" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_api'))) ? '' : esc_attr(get_option('shift8_cdn_api'))); ?>"></td>
	</tr>
	</table>
    <?php submit_button(); ?>
	</form>
</div>
	<div class="shift8-cdn-button-container">
	<a id="shift8-cdn-push" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button">Push to Production</button></a>
	<div class="shift8-cdn-push-container">
	<div class="shift8-cdn-push-progress"></div>
	</div>
	</div>
<?php 
	} // is_admin
}


