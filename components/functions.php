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
    if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'register') {
        shift8_cdn_poll('register');
        die();
    } else {
        die();
    }
}

// Handle the actual GET
function shift8_cdn_poll($shift8_action) {
    if (current_user_can('administrator')) {
        global $wpdb;
        global $shift8_cdn_table_name;
        $current_user = wp_get_current_user();

        $cdn_url = esc_attr(get_option('shift8_cdn_url'));
        $cdn_email = esc_attr(get_option('shift8_cdn_email'));
        $cdn_api = esc_attr(get_option('shift8_cdn_api'));

        // Set headers for WP Remote post
        $headers = array(
            'Content-type: application/json',
            //'Authorization' => 'Basic ' . base64_encode($cdn_user . ':' . $cdn_api),
        );

        // REGISTER
        if ($shift8_action == 'register') {
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
        } else if ($shift8_action == 'delete') {
            // Use WP Remote Get to poll the cdn api 
            $response = wp_remote_post( S8CDN_API . '/api/delete',
                array(
                    'method' => 'POST',
                    'headers' => $headers,
                    'httpversion' => '1.1',
                    'timeout' => '45',
                    'blocking' => true,
                    'body' => array(
                        'url' => $cdn_url,
                        'email' => $cdn_email,
                        'api' => $cdn_api
                    ),
                )
            );
        } else if ($shift8_action == 'check') {
            // Use WP Remote Get to poll the cdn api 
            $response = wp_remote_get( S8CDN_API . '/api/check',
                array(
                    'method' => 'POST',
                    'headers' => $headers,
                    'httpversion' => '1.1',
                    'timeout' => '45',
                    'blocking' => true,
                    'body' => array(
                        'url' => $cdn_url,
                        'email' => $cdn_email,
                        'api' => $cdn_api
                    ),
                )
            );
        }

        // Deal with the response
        if (is_array($response) && $response['response']['code'] == '200' && !json_decode($response['body'])->error) {
            echo $response['body'];
            update_option('shift8_cdn_api', esc_attr(json_decode($response['body'])->apikey));
            update_option('shift8_cdn_prefix', esc_attr(json_decode($response['body'])->cdnprefix));
        } else {
            echo 'Error Detected : ';
            if (is_array($response['response'])) {
                echo esc_attr(json_decode($response['body'])->error);

            } else {
                echo 'unknown';
            }
        } 
    } 
}

// Rewrite static URLs with CDN
function shift8_cdn_rewrites( $rewrites ) {
        if (shift8_cdn_check_enabled()) {
            // Get all options configured as array
            $shift8_options = shift8_cdn_check_options();
            $shift8_site_url = get_site_url();

            $urls = array(
                home_url( '/wp-content/uploads/' ),
                home_url( '/wp-content/themes/' ),
                home_url( '/wp-content/plugins/' ),
                home_url( '/wp-includes/' ),
            );

            foreach( $urls as $in => $out ) {
                $rewrites[$out] = str_replace( $shift8_site_url, 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX, $urls[$in] );
            }

            return $rewrites;
        }
}

add_filter( 'shift8_cdn_rewrites', 'shift8_cdn_rewrites' );

// Add DNS prefetch for faster resolution and loading
function shift8_cdn_prefetch() {
    if (shift8_cdn_check_enabled()) {
        // Get all options configured as array
        $shift8_options = shift8_cdn_check_options();
        echo '<meta http-equiv="x-dns-prefetch-control" content="on">
        <link rel="dns-prefetch" href="//' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX . '" />';
    }
}

add_action('wp_head', 'shift8_cdn_prefetch', 0);
