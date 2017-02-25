<?php
/**
 * IndexPages Registry API
 *
 * @package IndexPages
 * @subpackage Tools
 *
 * @since 1.0.0
 */

namespace IndexPages;

/**
 * The Registry
 *
 * Stores all the configuration options for the system.
 *
 * @api
 *
 * @since 1.0.0
 */
final class Registry {
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
			return self::$$property;
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
			self::$$property = $value;
		}
	}

	/**
	 * Test if a post type is supported.
	 *
	 * Will check if it exists and has index-page support or has_archive set.
	 *
	 * @since 1.3.0
	 *
	 * @param string $post_type The post type to check.
	 */
	public static function is_post_type_supported( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		if ( post_type_supports( $post_type, 'index-page' ) ) {
			return true;
		}

		return get_post_type_object( $post_type )->has_archive;
	}

	/**
	 * Add post types to the support list.
	 *
	 * @since 1.3.0 Rewrote to use add_post_type_support().
	 * @since 1.0.0
	 *
	 * @param string|array $post_types A post type or array of post types to add.
	 */
	public static function add_post_types( $post_types ) {
		$post_types = (array) $post_types;

		foreach ( $post_types as $post_type ) {
			add_post_type_support( $post_type, 'index-page' );
		}
	}

	/**
	 * Remove post types from the support list.
	 *
	 * @since 1.3.0 Rewrote to use remove_post_type_support().
	 * @since 1.0.0
	 *
	 * @param string|array $post_types A post type or array of post types to remove.
	 */
	public static function remove_post_types( $post_types ) {
		$post_types = (array) $post_types;

		foreach ( $post_types as $post_type ) {
			remove_post_type_support( $post_type, 'index-page' );
		}
	}

	/**
	 * Get the assigned index page for a post type.
	 *
	 * @since 1.3.0 Updated to use is_post_type_supported().
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type to look for.
	 *
	 * @return int|bool The index page ID, or false if not found.
	 */
	public static function get_index_page( $post_type ) {
		// Bail if not a supported post type
		if ( ! self::is_post_type_supported( $post_type ) ) {
			return false;
		}

		$page_id = false;
		if ( isset( self::$index_pages[ $post_type ] ) ) {
			$page_id = self::$index_pages[ $post_type ];
		}

		/**
		 * Filter the ID of the index page retrieved.
		 *
		 * @since 1.8.0
		 *
		 * @param int    $page_id   The ID of the page determined.
		 * @param string $post_type The post type it's meant for.
		 */
		$page_id = apply_filters( 'indexpages_get_index_page', $page_id, $post_type );

		return $page_id;
	}

	/**
	 * Get the post type for an assigned index page.
	 *
	 * Can also be used to check if a page is an index page.
	 *
	 * @since 1.3.0 Add check for $page_id being 0 and if matched post type is supported.
	 * @since 1.0.0
	 *
	 * @param int $page_id The ID of the page to check.
	 *
	 * @return string|bool The post type it is for, or false if not found.
	 */
	public static function is_index_page( $page_id ) {
		/**
		 * Filter the ID of the index page to check.
		 *
		 * @since 1.9.1
		 *
		 * @param int $post_id The ID of the page determined.
		 */
		$page_id = apply_filters( 'indexpages_is_index_page', $page_id );

		// If $page_id is somehow 0, return false
		if ( ! $page_id ) {
			return false;
		}

		// Find a post type using that page ID
		$post_type = array_search( intval( $page_id ), self::$index_pages, true );

		// If found but not a currently supported post type, bail
		if ( $post_type && ! self::is_post_type_supported( $post_type ) ) {
			return false;
		}

		return $post_type;
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

		if ( self::$__loaded && ! $reload ) {
			// Already did this
			return;
		}

		// Automatically log the index page for posts
		self::$index_pages[ 'post' ] = get_option( 'page_for_posts' );

		// Get all page_for_*_posts options found.
		$pages = $wpdb->get_results( "SELECT SUBSTRING(option_name, 10, CHAR_LENGTH(option_name) - 15) AS post_type, option_value AS page_id FROM $wpdb->options WHERE option_name LIKE 'page\_for\_%\_posts'" );
		foreach ( $pages as $page ) {
			self::$index_pages[ $page->post_type ] = intval( $page->page_id );
		}

		// Flag that we've loaded everything
		self::$__loaded = true;
	}
}
