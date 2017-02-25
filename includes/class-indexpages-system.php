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
 * @api
 *
 * @since 1.0.0
 */
final class System {
	// =========================
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
	 * @since 1.0.0
	 */
	public static function setup() {
		// Setup the registry
		Registry::load();

		// Register the hooks of the subsystems
		Frontend::register_hooks();
		Backend::register_hooks();
		Liaison::register_hooks();
	}
}
