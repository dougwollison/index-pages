<?php
/**
 * IndexPages Options Registry
 *
 * @package IndexPages
 * @subpackage Helpers
 *
 * @since 1.0.0
 */

namespace IndexPages;

/**
 * The Options Registry
 *
 * Stores all the configuration options for the system.
 *
 * @package IndexPages
 * @subpackage Helpers
 *
 * @api
 *
 * @since 1.0.0
 */

class Registry {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The loaded status flag.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected static $__loaded = false;

	/**
	 * A list of post types that should
	 * have index pages assigned.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $post_types = array();

	/**
	 * The list of assigned index pages,
	 * indexed by post type.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected static $index_pages = array();

	// =========================
	// ! Property Accessing
	// =========================

	/**
	 * Retrieve a property value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The property name.
	 * @param mixed  $default  Optional. The default value to return.
	 *
	 * @return mixed The property value.
	 */
	public static function get( $property, $default = null ) {
		if ( property_exists( get_called_class(), $property ) ) {
			return static::$$property;
		}
		return $default;
	}

	/**
	 * Override a property value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The property name.
	 * @param mixed  $value    The value to assign.
	 */
	public static function set( $property, $value = null ) {
		if ( property_exists( get_called_class(), $property ) ) {
			static::$$property = $value;
		}
	}

	/**
	 * Get the list of supported post types.
	 *
	 * If none are registered, will default to all custom post types that support archives.
	 *
	 * @since 1.0.0
	 *
	 * @return array The supported post types.
	 */
	public static function get_post_types() {
		if ( empty( static::$post_types ) ) {
			return get_post_types( array(
				'has_archive' => true,
				'_builtin' => false,
			) );
		}

		return static::$post_types;
	}

	/**
	 * Add post types to the support list.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $post_types     A post type or array of post types to add.
	 * @param string       $post_types,... Additional post types to add.
	 */
	public static function add_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = func_get_args();
		}

		foreach ( $post_types as $post_type ) {
			static::$post_types[] = $post_type;
		}
	}

	/**
	 * Remove post types from the support list.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $post_types     A post type or array of post types to remove.
	 * @param string       $post_types,... Additional post types to remove.
	 */
	public static function remove_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = func_get_args();
		}

		$remaining_post_types = array();
		foreach ( static::$post_types as $post_type ) {
			if ( ! in_array( $post_type, $post_types ) ) {
				$remaining_post_types[] = $post_type;
			}
		}

		static::$post_types = $remaining_post_types;
	}

	/**
	 * Get the assigned index page for a post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type to look for.
	 *
	 * @return int|bool The index page ID, or false if not found.
	 */
	public static function get_index_page( $post_type ) {
		if ( isset( static::$index_pages[ $post_type ] ) ) {
			return static::$index_pages[ $post_type ];
		}
		return false;
	}

	/**
	 * Get the post type for an assigned index page.
	 *
	 * Can also be used to check if a page is an index page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id The ID of the page to check.
	 *
	 * @return string|bool The post type it is for, or false if not found.
	 */
	public static function is_index_page( $page_id ) {
		return array_search( intval( $page_id ), static::$index_pages, true );
	}

	// =========================
	// ! Setup Method
	// =========================

	/**
	 * Load the relevant options.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @see Registry::$__loaded
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param bool $reload Should we reload the options?
	 */
	public static function load( $reload = false ) {
		global $wpdb;

		if ( static::$__loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Automatically log the index page for posts
		static::$index_pages[ 'post' ] = get_option( 'page_for_posts' );

		// Get all page_for_*_posts options found.
		$pages = $wpdb->get_results( "SELECT SUBSTRING(option_name, 10, CHAR_LENGTH(option_name) - 15) AS post_type, option_value AS page_id FROM $wpdb->options WHERE option_name LIKE 'page\_for\_%\_posts'" );
		foreach ( $pages as $page ) {
			static::$index_pages[ $page->post_type ] = intval( $page->page_id );
		}

		// Flag that we've loaded everything
		static::$__loaded = true;
	}
}
