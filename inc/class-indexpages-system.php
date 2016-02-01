<?php
/**
 * IndexPages System
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @since 1.0.0
 */

namespace IndexPages;

/**
 * Main System Class
 *
 * Sets up the Registry and all the Handler classes.
 *
 * @package IndexPages
 * @subpackage Helpers
 *
 * @api
 *
 * @since 1.0.0
 */

class System {

	// =========================
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
	 * @since 1.0.0
	 */
	public static function setup() {
		// Register the uninstall hook
		register_uninstall_hook( INDEXPAGES_PLUGIN_FILE, array( static::$name, 'uninstall' ) );

		// Setup the registry
		Registry::load();

		// Register the hooks of the subsystems
		Frontend::register_hooks();
		Backend::register_hooks();
	}

	// =========================
	// ! Uninstallation
	// =========================

	/**
	 * Delete database tables and any options.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function uninstall() {
		global $wpdb;

		// Make sure they have permisson
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( $check_referer ) {
			$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			check_admin_referer( "{$check_referer}-plugin_{$plugin}" );
		} else {
			// Check if this is the intended file for uninstalling
			if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
				return;
			}
		}

		// And delete the options
		$wpdb->query( "DELETE FORM $wpdb->options WHERE option_name LIKE 'page\_for\_%\_posts'" );
	}
}

