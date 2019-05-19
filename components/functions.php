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
    if (current_user_can('administrator')) {
        global $wpdb;
        global $shift8_cdn_table_name;
        $current_user = wp_get_current_user();

        $cdn_url = esc_attr(get_option('shift8_cdn_url'));
        $cdn_email = esc_attr(get_option('shift8_cdn_email'));
        // Set headers for WP Remote get
        $headers = array(
            'Content-type: application/json',
            //'Authorization' => 'Basic ' . base64_encode($cdn_user . ':' . $cdn_api),
        );

        // Use WP Remote Get to poll the cdn api 
        $response = wp_remote_post( S8CDN_API . '/api/create',
            array(
                'method' => 'POST',
                'headers' => $headers,
                'httpversion' => '1.1',
                'timeout' => '45',
                'blocking' => true,
                'body' => array(
                    'url' => $cdn_url,
                    'email' => $cdn_email
                ),
            )
        );
        if (is_array($response) && $response['response']['code'] == '200' && !json_decode($response['body'])->error) {
            echo $response['body'];
            update_option('shift8_cdn_api', esc_attr(json_decode($response['body'])->apikey));
            update_option('shift8_cdn_prefix', esc_attr(json_decode($response['body'])->cdnprefix));
        } else {
            echo 'error_detected : ';
            if (is_array($response['response'])) {
                echo esc_attr(json_decode($response['body'])->error);

            } else {
                echo 'unknown';
            }
        } 
    } 
}

// Function to initialize & check for session
function shift8_cdn_init() {
    // Initialize only if enabled
    if (shift8_ipintel_check_options()) {
        global $shift8_options;
        $shift8_options = shift8_security_check_options();

        $excludes = array_map('trim', explode(',', '.php'));

        new CDN_Enabler_Rewriter(
            get_option('home'),
            'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX,
            'wp-content,wp-includes',
            $excludes
        );

    }
}
add_action('init', 'shift8_cdn_init', 1);

