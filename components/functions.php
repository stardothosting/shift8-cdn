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
    // Register
    if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'register') {
        shift8_cdn_poll('register');
        die();
    // Check
    } else if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'check') {
        shift8_cdn_poll('check');
        die();
    // Delete
    } else if ( wp_verify_nonce($_GET['_wpnonce'], 'process') && $_GET['type'] == 'delete') {
        shift8_cdn_poll('delete');
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
        $cdn_api = esc_attr(get_option('shift8_cdn_api'));

        // Set headers for WP Remote post
        $headers = array(
            'Content-type: application/json',
            //'Authorization' => 'Basic ' . base64_encode($cdn_user . ':' . $cdn_api),
        );

        // Check values with dashboard
        if ($shift8_action == 'check') {
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
                        'api' => $cdn_api
                    ),
                )
            );
        }

        // Deal with the response
        if (is_array($response) && $response['response']['code'] == '200' && !json_decode($response['body'])->error) {
            update_option('shift8_cdn_api', esc_attr(json_decode($response['body'])->apikey));
            update_option('shift8_cdn_prefix', esc_attr(json_decode($response['body'])->cdnprefix));
            //echo esc_attr(json_decode($response['body'])->apikey);
            echo json_encode(array(
                'apikey' => esc_attr(json_decode($response['body'])->apikey),
                'cdnprefix' => esc_attr(json_decode($response['body'])->cdnprefix),
                ));
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
            $shift8_site_url = parse_url(get_site_url());

            $urls = array(
                home_url( 'wp-content' ),
                home_url( 'wp-includes' ),
            );

            foreach( $urls as $in => $out ) {
                $url = parse_url($urls[$in]);
                $rewrites[$out] = str_replace( $shift8_site_url['scheme'] . '://' . $shift8_site_url['host'], 'https://' . $shift8_options['cdn_prefix'] . S8CDN_SUFFIX, $urls[$in] );
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


// Functions to produce debugging information

function shift8_cdn_debug_get_php_info() {
    //retrieve php info for current server
    if (!function_exists('ob_start') || !function_exists('phpinfo') || !function_exists('ob_get_contents') || !function_exists('ob_end_clean') || !function_exists('preg_replace')) {
        echo 'This information is not available.';
    } else {
        ob_start();
        phpinfo();
        $pinfo = ob_get_contents();
        ob_end_clean();

        $pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
        echo $pinfo;
    }
}

function shift8_cdn_debug_get_mysql_version() {
        global $wpdb;
        $rows = $wpdb->get_results('select version() as mysqlversion');
        if (!empty($rows)) {
             return $rows[0]->mysqlversion;
        }
        return false;
    }

function shift8_cdn_debug_version_check() {
    //outputs basic information
    $notavailable = __('This information is not available.');
    if ( !function_exists( 'get_bloginfo' ) ) {
        $wp = $notavailable;
    } else {
        $wp = get_bloginfo( 'version' );
    }

    if ( !function_exists( 'wp_get_theme' ) ) {
        $theme = $notavailable;
    } else {
        $theme = wp_get_theme();
    }

    if ( !function_exists( 'get_plugins' ) ) {
        $plugins = $notavailable;
    } else {
        $plugins_list = get_plugins();
        if( is_array( $plugins_list ) ){
            $active_plugins = '';
            $plugins = '<ul>';
            foreach ( $plugins_list as $plugin ) {
                $version = '' != $plugin['Version'] ? $plugin['Version'] : __( 'Unversioned', 'debug-info' );
                if( !empty( $plugin['PluginURI'] ) ){
                    $plugins .= '<li><a href="' . $plugin['PluginURI'] . '">' . $plugin['Name'] . '</a> (' . $version . ')</li>';
                } else {
                    $plugins .= '<li>' . $plugin['Name'] . ' (' . $version . ')</li>';
                }
            }
            $plugins .= '</ul>';
        }
    }

    if ( !function_exists( 'phpversion' ) ) {
        $php = $notavailable;
    } else {
        $php = phpversion();
    }

    if ( !function_exists( 'debug_info_get_mysql_version' ) ) {
        $mysql = $notavailable;
    } else {
        $mysql = debug_info_get_mysql_version();
    }

    if ( !function_exists( 'apache_get_version' ) ) {
        $apache = $notavailable;
    } else {
        $apache = apache_get_version();
    }

    $themeversion   = $theme->get( 'Name' ) . __( ' version ', 'debug-info' ) . $theme->get( 'Version' ) . $theme->get( 'Template' );
    $themeauth      = $theme->get( 'Author' ) . ' - ' . $theme->get( 'AuthorURI' );
    $uri            = $theme->get( 'ThemeURI' );

    echo '<strong>' . __( 'WordPress Version: ' ) . '</strong>' . $wp . '<br />';
    echo '<strong>' . __( 'Current WordPress Theme: ' ) . '</strong>' . $themeversion . '<br />';
    echo '<strong>' . __( 'Theme Author: ' ) . '</strong>' . $themeauth . '<br />';
    echo '<strong>' . __( 'Theme URI: ' ) . '</strong>' . $uri . '<br />';
    echo '<strong>' . __( 'PHP Version: ' ) . '</strong>' . $php . '<br />';
    echo '<strong>' . __( 'MySQL Version: ' ) . '</strong>' . $mysql . '<br />';
    echo '<strong>' . __( 'Apache Version: ' ) . '</strong>' . $apache . '<br />';
    echo '<strong>' . __( 'Active Plugins: ' ) . '</strong>' . $plugins . '<br />';

}
