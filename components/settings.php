<?php

// Create activity log table
add_action( 'init', 'shift8_cdn_register_activity_log_table', 1 );
add_action( 'switch_blog', 'shift8_cdn_register_activity_log_table' );
 
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
    //register our settings
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_url', 'shift8_cdn_url_validate' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_user', 'shift8_cdn_user_validate' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_api', 'shift8_cdn_api_validate' );
}

// Validate Input for Admin options
function shift8_cdn_url_validate($data){
	if(filter_var($data, FILTER_VALIDATE_URL,FILTER_FLAG_QUERY_REQUIRED)) {
   		return $data;
   	} else {
   		add_settings_error(
            'shift8_cdn_url',
            'shift8-cdn-notice',
            'You did not enter a valid URL for the CDN push',
            'error');
   	}
}

function shift8_cdn_user_validate($data){
	if(filter_var($data, FILTER_SANITIZE_STRING)) {
   		return $data;
   	} else {
   		add_settings_error(
            'shift8_cdn_user',
            'shift8-cdn-notice',
            'You did not enter a valid string for the username field',
            'error');
   	}
}

function shift8_cdn_api_validate($data){
	if(filter_var($data, FILTER_SANITIZE_STRING)) {
   		return $data;
   	} else {
   		add_settings_error(
            'shift8_cdn_api',
            'shift8-cdn-notice',
            'You did not enter a valid string for the API field',
            'error');
   	}
}

// Validate admin options
function shift8_cdn_check_options() {
    // If enabled is not set
    if(empty(esc_attr(get_option('shift8_cdn_url') ))) return false;
    if(empty(esc_attr(get_option('shift8_cdn_api') ))) return false;
    if(empty(esc_attr(get_option('shift8_cdn_user') ))) return false;

    return true;

}
