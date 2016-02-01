<?php
/**
 * IndexPages Frontend Functionality
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @since 1.0.0
 */

namespace IndexPages;

/**
 * The Frontend Functionality
 *
 * Hooks into various frontend systems to handle
 * implementation of the assigned index pages, including
 * title rewriting, archive permalinks, and admin bar tweaks.
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @internal Used by the System.
 *
 * @since 1.0.0
 */

class Frontend extends Handler {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The name of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected static $name;

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 *
	 * @uses Registry::get() to retrieve enabled post types.
	 */
	public static function register_hooks() {
		// Don't do anything if not in the backend
		if ( ! is_backend() ) {
			return;
		}

		// Request handling
		static::add_action( 'parse_request', 'handle_request', 10, 1 );

		// Title/link rewriting
		static::add_filter( 'wp_title_parts', 'rewrite_title_parts', 10, 1 );
		static::add_filter( 'post_type_archive_link', 'rewrite_archive_link', 10, 2 );

		// Admin bar additions
		static::add_action( 'admin_bar_menu', 'add_edit_button', 85, 1 );
	}

	// =========================
	// ! Request Handling
	// =========================

	/**
	 * Check if the path requested matches an assigned index page.
	 *
	 * Also checks for date and pagination parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param WP $wp The WP request object.
	 */
	public static function handle_request( \WP $wp ) {
		// to be written
	}

	// =========================
	// ! Title/Link Rewriting
	// =========================

	/**
	 * Filter the title parts to use the assigned index page's title.
	 *
	 * @since 1.0.0
	 *
	 * @param array $title The title parts to filter.
	 *
	 * @return array The filtered title parts.
	 */
	public static function rewrite_title_parts( array $title_parts ) {
		// to be written

		return $title_parts;
	}

	/**
	 * Filter the post type archive link to use the assigned index page's permalink.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link      The permalink to filter.
	 * @param string $post_type The post type this is for.
	 *
	 * @return string The filtered archive link.
	 */
	public static function rewrite_archive_link( $link, $post_type ) {
		// to be written

		return $link;
	}

	// =========================
	// ! Admin Bar Additions
	// =========================

	/**
	 * Add an Edit Index Page button to the admin bar if applicable.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public static function add_edit_button( \WP_Admin_Bar $wp_admin_bar ) {
		// to be written
	}
}

