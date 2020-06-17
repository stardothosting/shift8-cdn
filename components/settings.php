<?php
/**
 * Shift8 CDN Settings
 *
 * Declaration of plugin settings used throughout
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

// create custom plugin settings menu
add_action('admin_menu', 'shift8_cdn_create_menu');
function shift8_cdn_create_menu() {
        //create new top-level menu
        if ( empty ( $GLOBALS['admin_page_hooks']['shift8-settings'] ) ) {
                add_menu_page('Shift8 Settings', 'Shift8', 'administrator', 'shift8-settings', 'shift8_main_page' , 'dashicons-building' );
        }
        add_submenu_page('shift8-settings', 'CDN Settings', 'CDN Settings', 'manage_options', __FILE__.'/custom', 'shift8_cdn_settings_page');
        //call register settings function
        add_action( 'admin_init', 'register_shift8_cdn_settings' );
}

// Register admin settings
function register_shift8_cdn_settings() {
    //Register our settings
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_enabled' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_url', 'shift8_cdn_url_validate' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_api' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_prefix' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_css', array( 'default' => 'on' ) );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_js', array( 'default' => 'on' ) );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_media', array( 'default' => 'on' ));

    // Cleanup of old settings no longer needed
    if (get_option('shift8_cdn_email')) {
        delete_option('shift8_cdn_email');
    }
}

// Uninstall hook
function shift8_cdn_uninstall_hook() {
  // Delete setting values
  delete_option('shift8_cdn_enabled');
  delete_option('shift8_cdn_url');
  delete_option('shift8_cdn_api');
  delete_option('shift8_cdn_prefix');
  delete_option('shift8_cdn_css');
  delete_option('shift8_cdn_js');
  delete_option('shift8_cdn_media');
  // Clear Cron tasks
  wp_clear_scheduled_hook( 'shift8_cdn_cron_hook' );
  // Delete transient data
  delete_transient(S8CDN_PAID_CHECK);
}
register_uninstall_hook( S8CDN_FILE, 'shift8_cdn_uninstall_hook' );

// Deactivation hook
function shift8_cdn_deactivation() {
  // Clear Cron tasks
  wp_clear_scheduled_hook( 'shift8_cdn_cron_hook' );
  // Delete transient
  delete_transient(S8CDN_PAID_CHECK);
}
register_deactivation_hook( S8CDN_FILE, 'shift8_cdn_deactivation' );

// Validate Input for Admin options
function shift8_cdn_url_validate($data){
	if(filter_var($data, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
      $site_url = parse_url($data);
      return $site_url["scheme"] . '://' . $site_url["host"] . $site_url["path"];
   	} else {
   		add_settings_error(
            'shift8_cdn_url',
            'shift8-cdn-notice',
            'You did not enter a valid URL for your site URL',
            'error');
   	}
}

// Validate admin options
function shift8_cdn_check_enabled() {
  // If enabled is not set
  if(esc_attr( get_option('shift8_cdn_enabled') ) != 'on') return false;
  if(empty(esc_attr(get_option('shift8_cdn_url') ))) return false;
  if(empty(esc_attr(get_option('shift8_cdn_api') ))) return false;
  if(empty(esc_attr(get_option('shift8_cdn_prefix') ))) return false;

  return true;
}

// Process all options and return array
function shift8_cdn_check_options() {
  $shift8_options = array();
  $shift8_options['cdn_url'] = esc_attr( get_option('shift8_cdn_url') );
  $shift8_options['cdn_api'] = esc_attr( get_option('shift8_cdn_api') );
  $shift8_options['cdn_prefix'] = esc_attr( get_option('shift8_cdn_prefix') );
  $shift8_options['static_css'] = esc_attr( get_option('shift8_cdn_css', 'on') );
  $shift8_options['static_js'] = esc_attr( get_option('shift8_cdn_js', 'on') );
  $shift8_options['static_media'] = esc_attr( get_option('shift8_cdn_media','on') );
  
  return $shift8_options;
}

