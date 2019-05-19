<?php

if ( !defined( 'ABSPATH' ) ) {
    die();
}

define( 'S8CDN_FILE', 'shift8-cdn/shift8-cdn.php' );

if ( !defined( 'S8CDN_DIR' ) )
    define( 'S8CDN_DIR', realpath( dirname( __FILE__ ) ) );

if ( !defined( 'S8CDN_TEST_README_URL' ) )
	define( 'S8CDN_TEST_README_URL', WP_PLUGIN_URL . '/' . dirname( S8SEC_FILE ) . '/test/test.png');

define( 'S8CDN_API' , 'https://shift8cdn.com');
define( 'S8CDN_SUFFIX', '.wpcdn.shift8cdn.com');