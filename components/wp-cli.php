<?php
/**
 * Shift8 CDN WP-Cli Commands
 *
 * Definition of WP-CLI commands to interact with the Shift8 CDN plugin
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

class WDS_CLI {

	/**
	 * Enable the plugin
	 *
	 * @since  1.47
	 * @author Shift8 Web
	 */
	public function enable() {
		// Enable
		if ( get_option('shift8_cdn_enabled') && get_option('shift8_cdn_enabled') === 'off') {
			update_option('shift8_cdn_enabled', 'on');
			WP_CLI::line( 'Shift8 CDN has been enabled' );
		} else {
			WP_CLI::line( 'Shift8 CDN is either not installed or not disabled - doing nothing.' );
		}
	}

	/**
	 * Disable the plugin
	 *
	 * @since  1.47
	 * @author Shift8 Web
	 */
	public function disable() {
		// Enable
		if ( get_option('shift8_cdn_enabled') && get_option('shift8_cdn_enabled') === 'on') {
			update_option('shift8_cdn_enabled', 'off');
			WP_CLI::line( 'Shift8 CDN has been disabled' );
		} else {
			WP_CLI::line( 'Shift8 CDN is either not installed or not enabled - doing nothing.' );
		}
	}

}

/**
 * Registers our command when cli get's initialized.
 *
 * @since  1.47
 * @author Shift8 Web
 */
function shift8_cdn_cli_register_commands() {
	WP_CLI::add_command( 'shift8cdn', 'WDS_CLI' );
}

add_action( 'cli_init', 'shift8_cdn_cli_register_commands' );