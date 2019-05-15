<?php

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
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_email', 'shift8_cdn_email_validate' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_api' );
    register_setting( 'shift8-cdn-settings-group', 'shift8_cdn_prefix' );
}

// Validate Input for Admin options
function shift8_cdn_url_validate($data){
	if(filter_var($data, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
   		return $data;
   	} else {
   		add_settings_error(
            'shift8_cdn_url',
            'shift8-cdn-notice',
            'You did not enter a valid URL for your site URL',
            'error');
   	}
}

function shift8_cdn_email_validate($data){
	if(filter_var($data, FILTER_SANITIZE_EMAIL)) {
   		return $data;
   	} else {
   		add_settings_error(
            'shift8_cdn_email',
            'shift8-cdn-notice',
            'You did not enter a valid string for the email field',
            'error');
   	}
}

// Validate admin options
function shift8_cdn_check_options() {
    // If enabled is not set
    if(empty(esc_attr(get_option('shift8_cdn_url') ))) return false;
    if(empty(esc_attr(get_option('shift8_cdn_email') ))) return false;

    return true;

}
