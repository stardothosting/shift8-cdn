<?php

// Function to encrypt session data
function shift8_cdn_encrypt($key, $payload) {
    if (!empty($key) && !empty($payload)) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    } else {
        return false;
    }
}

// Function to decrypt session data
function shift8_cdn_decrypt($key, $garble) {
    if (!empty($key) && !empty($garble)) {
        list($encrypted_data, $iv) = explode('::', base64_decode($garble), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    } else {
        return false;
    }
}

// Handle the ajax trigger
add_action( 'wp_ajax_shift8_cdn_push', 'shift8_cdn_push' );
function shift8_cdn_push() {
    if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['action'] == 'shift8_cdn_push') {
        shift8_cdn_poll();
        die();
    } else {
        die();
    }
}

// Handle the actual GET
function shift8_cdn_poll() {
    if (current_user_can('administrator') && shift8_cdn_check_options()) {
        global $wpdb;
        global $shift8_cdn_table_name;
        $current_user = wp_get_current_user();

        $cdn_user = esc_attr(get_option('shift8_cdn_user'));
        $cdn_api = esc_attr(get_option('shift8_cdn_api'));
        // Set headers for WP Remote get
        $headers = array(
            'Content-type: application/json',
            'Authorization' => 'Basic ' . base64_encode($cdn_user . ':' . $cdn_api),
        );

        // Use WP Remote Get to poll the cdn api 
        $response = wp_remote_get( esc_attr(get_option('shift8_cdn_url')),
            array(
                'headers' => $headers,
                'httpversion' => '1.1',
                'timeout' => '10',
            )
        );
        if (is_array($response) && $response['response']['code'] == '201') {
            $date = date('Y-m-d H:i:s');
            echo $date . ' / ' . $current_user->user_login . ' : Pushed to production';
            $wpdb->insert( 
                $wpdb->prefix . $shift8_cdn_table_name,
                array( 
                    'user_name' => $current_user->user_login,
                    'activity' => 'pushed to production',
                    'activity_date' => $date,
                )
            );
        } else {
            echo 'error_detected : ';
            if (is_array($response['response'])) {
                echo $response['response']['code'] . ' - ' . $response['response']['message'];
            } else {
                echo 'unknown';
            }
        } 
    } 
}
