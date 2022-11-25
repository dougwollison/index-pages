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
final class System extends Handler {
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

		// Link rewriting
		self::add_filter( 'post_type_archive_link', 'rewrite_archive_link', 10, 2 );
		self::add_filter( 'term_link', 'rewrite_term_link', 10, 2 );

		// Register the hooks of the subsystems
		Frontend::register_hooks();
		Backend::register_hooks();
		Liaison::register_hooks();
	}

	// =========================
	// ! Link Rewriting
	// =========================

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
		if ( $index = Registry::get_index_page( $post_type ) ) {
			$link = get_permalink( $index );
		}

		return $link;
	}

	/**
	 * Filter the term link to use the assigned index page's permalink.
	 *
	 * @since 1.4.0
	 *
	 * @param string $link The permalink to filter.
	 * @param object $term The term this is for.
	 *
	 * @return string The filtered term link.
	 */
	public static function rewrite_term_link( $link, $term ) {
		if ( $index = Registry::get_term_page( $term ) ) {
			$link = get_permalink( $index );
		}

		return $link;
	}
}
