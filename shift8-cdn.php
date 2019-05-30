<?php
/**
 * Plugin Name: Shift8 CDN 
 * Plugin URI: https://github.com/stardothosting/shift8-cdn
 * Description: Plugin that integrates a fully functional CDN service
 * Version: 1.15
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
<?php if (is_admin()) { 
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'core_settings';
$plugin_data = get_plugin_data( __FILE__ );
$plugin_name = $plugin_data['TextDomain'];
    ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=core_settings" class="nav-tab <?php echo $active_tab == 'core_settings' ? 'nav-tab-active' : ''; ?>">Core Settings</a>
    <a href="?page=<?php echo $plugin_name; ?>%2Fcomponents%2Fsettings.php%2Fcustom&tab=support_options" class="nav-tab <?php echo $active_tab == 'support_options' ? 'nav-tab-active' : ''; ?>">Support</a>
</h2>

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
    <td><input type="text" id="shift8_cdn_api_field" name="shift8_cdn_api" size="34" value="<?php echo (empty(esc_attr(get_option('shift8_cdn_api'))) ? '' : esc_attr(get_option('shift8_cdn_api'))); ?>"> <small>Keep this safe!</small></td>
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
    <tr valign="top">
    <td></td>
    <td>
    <center>Note : make sure you set your URL and email properly, then save the settings before registering.</center>
    <ul class="shift8-cdn-controls">
    <li>
    <div class="shift8-cdn-button-container">
    <a id="shift8-cdn-register" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-register">Register</button></a>
    </div>
    </li>
    <?php if (!empty(esc_attr(get_option('shift8_cdn_api')))) { ?>
    <li>
    <div class="shift8-cdn-button-container">
    <a id="shift8-cdn-check" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-check">Check</button></a>
    </div>
    </li>
    <li>
    <div class="shift8-cdn-button-container">
    <a id="shift8-cdn-unregister" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=shift8_cdn_push'), 'process'); ?>"><button class="shift8-cdn-button shift8-cdn-button-unregister">Unregister</button></a>
    </div>
    </li>
    <?php } ?>
    </ul>
    <div class="shift8-cdn-response">
    </div>
    </td>
    </tr>
</tbody>
    <!-- SUPPORT TAB -->
    <tbody class="<?php echo $active_tab == 'support_options' ? 'shift8-cdn-admin-tab-active' : 'shift8-cdn-admin-tab-inactive'; ?>">
    <tr valign="top">
    <th scope="row">Support</th>
    </tr>
    <tr valign="top">
    <td style="width:500px;">If you are experiencing difficulties, you can receive support if you Visit the <a href="https://wordpress.org/support/plugin/shift8-cdn/" target="_new">Shift8 CDN Wordpress support page</a> and post your question there.
    </td>
    </tr>
</tbody>
    <?php } ?>
    </table>
    <?php 
    if ($active_tab != 'support_options') {
        submit_button(); 
    }
    ?>
    </form>
</div>
<?php 
	} // is_admin
}


