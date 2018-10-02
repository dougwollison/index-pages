<?php
/**
 * IndexPages Liaison Functionality
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @since 1.3.0
 */

namespace IndexPages;

/**
 * The Liaison System
 *
 * Adds compatibility hooks for 3rd party plugins.
 *
 * @internal Used by the System.
 *
 * @since 1.3.0
 */
final class Liaison extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 1.3.0
	 */
	public static function register_hooks() {
		// Yoast SEO compatibility
		self::add_action( 'plugins_loaded', 'add_wpseo_helpers', 10, 0 );
	}

	// =========================
	// ! Yoast SEO Helpers
	// =========================

	/**
	 * Check if WPSEO is active, setup necessary helpers.
	 *
	 * @since 1.3.0
	 */
	public static function add_wpseo_helpers() {
		// Abort if WPSEO isn't present
		if ( ! function_exists( 'wpseo_init' ) ) {
			return;
		}

		// Add WC endpoint support to localize_here
		self::add_action( 'wpseo_register_extra_replacements', 'wpseo_register_extra_replacements', 10, 0 );
	}

	/**
	 * Add extra replacement variables for use in the Title fields.
	 *
	 * @since 1.3.0
	 */
	public static function wpseo_register_extra_replacements() {
		\WPSEO_Replace_Vars::register_replacement( 'indexpage', array( __CLASS__, 'wpseo_do_indexpage_replacement' ), 'advanced', __( 'Replaced with the title of the applicable Index Page', 'index-pages' ) );
	}

	/**
	 * Return the title of the index page if applicable.
	 *
	 * @since 1.3.0
	 */
	public static function wpseo_do_indexpage_replacement( $key, $args ) {
		// Get the term/post type index page
		$index_page = get_term_index_page() ?: get_index_page();

		// Get the index for this post type, return it's title if found
		if ( $index_page ) {
			return get_the_title( $index_page );
		}

		return false;
	}
}
