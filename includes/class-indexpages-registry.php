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

	/**
	 * The list of assigned term pages,
	 * indexed by term ID.
	 *
	 * @internal
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	protected static $term_pages = array();

	/**
	 * The list of supported taxonomies.
	 *
	 * @internal
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	protected static $supported_taxonomies = array();

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
	 * Get the list of supported taxonomies.
	 *
	 * @since 1.4.0
	 *
	 * @return array The list of supported taxonomies.
	 */
	public static function get_supported_taxonomies() {
		return self::$supported_taxonomies;
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
		// Assume true if it's the Post post type
		if ( $post_type == 'post' ) {
			return true;
		}

		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		if ( post_type_supports( $post_type, 'index-page' ) ) {
			return true;
		}

		return get_post_type_object( $post_type )->has_archive;
	}

	/**
	 * Test if a taxonomy is supported.
	 *
	 * Will check if it exists and has index-page support or has_archive set.
	 *
	 * @since 1.4.0
	 *
	 * @param string $taxonomy The taxonomy to check.
	 */
	public static function is_taxonomy_supported( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		if ( ! in_array( $taxonomy, self::$supported_taxonomies ) ) {
			return false;
		}

		return get_taxonomy( $taxonomy )->public;
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
	 * Add taxonomies to the support list.
	 *
	 * @since 1.4.0
	 *
	 * @param string|array $taxonomies A post type or array of post types to add.
	 */
	public static function add_taxonomies( $taxonomies ) {
		$taxonomies = (array) $taxonomies;

		foreach ( $taxonomies as $taxonomy ) {
			self::$supported_taxonomies[] = $taxonomy;
		}
	}

	/**
	 * Remove post types from the support list.
	 *
	 * @since 1.4.0
	 *
	 * @param string|array $taxonomies A post type or array of post types to remove.
	 */
	public static function remove_taxonomies( $taxonomies ) {
		$taxonomies = (array) $taxonomies;

		self::$supported_taxonomies = array_diff( self::$supported_taxonomies, $taxonomies );
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
	 * Get the assigned index page for a term.
	 *
	 * @since 1.4.0
	 *
	 * @param object|int|string $term     The term or ID/slug.
	 * @param string            $taxonomy The taxonomy if going by slug.
	 *
	 * @return int|bool The index page ID, or false if not found.
	 */
	public static function get_term_page( $term, $taxonomy = null ) {
		if ( is_string( $term ) ) {
			if ( is_null( $taxonomy ) ) {
				// Can't find by slug without taxonomy.
				return false;
			}

			$term = get_term_by( 'slug', $term, $taxonomy );
		}

		if ( ! is_object( $term ) ) {
			$term = get_term( $term, $taxonomy );
		}

		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		// Bail if not a supported taxonomy
		if ( ! self::is_taxonomy_supported( $term->taxonomy ) ) {
			return false;
		}

		$page_id = false;
		if ( isset( self::$term_pages[ $term->term_id ] ) ) {
			$page_id = self::$term_pages[ $term->term_id ];
		}

		/**
		 * Filter the ID of the index page retrieved.
		 *
		 * @since 1.8.0
		 *
		 * @param int    $page_id  The ID of the page determined.
		 * @param object $term     The term it's meant for.
		 * @param string $taxonomy The taxonomy the term belongs to.
		 */
		$page_id = apply_filters( 'indexpages_get_term_page', $page_id, $term, $taxonomy );

		return $page_id;
	}

	/**
	 * Get the post type for an assigned index page.
	 *
	 * Can also be used to check if a page is a post-type index page.
	 *
	 * @since 1.3.1 Handle multiple post type matches, return first supported one.
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
		 * @since 1.0.0
		 *
		 * @param int $post_id The ID of the page determined.
		 */
		$page_id = apply_filters( 'indexpages_is_index_page', $page_id );

		// If $page_id is somehow 0, return false
		if ( ! $page_id ) {
			return false;
		}

		// Find all uses of this page as an index page
		$matched_pages = array_filter( self::$index_pages, function( $index_page_id ) use ( $page_id ) {
			return $index_page_id == $page_id;
		} );

		// Filter out any that are for not-currently supported post types
		$post_types = array_filter( array_keys( $matched_pages ), function( $post_type ) {
			return Registry::is_post_type_supported( $post_type );
		} );

		// Return first match
		return reset( $post_types ) ?: false;
	}

	/**
	 * Get the term for an assigned index page.
	 *
	 * Can also be used to check if a page is a term index page.
	 *
	 * @since 1.4.1 Fix check for existing term.
	 * @since 1.4.0
	 *
	 * @param int $page_id The ID of the page to check.
	 *
	 * @return object|bool The term it is for, or false if not found.
	 */
	public static function is_term_page( $page_id ) {
		/**
		 * Filter the ID of the index page to check.
		 *
		 * @since 1.4.0
		 *
		 * @param int $post_id The ID of the page determined.
		 */
		$page_id = apply_filters( 'indexpages_is_term_page', $page_id );

		// If $page_id is somehow 0, return false
		if ( ! $page_id ) {
			return false;
		}

		// Find a post type using that page ID
		$term_id = array_search( intval( $page_id ), self::$term_pages, true );

		// If not found or term no longer exists, bail
		if ( ! $term_id || ! ( $term = get_term( $term_id ) ) ) {
			return false;
		}

		// If it doesn't belong to a currently supported taxonomy, bail
		if ( ! self::is_taxonomy_supported( $term->taxonomy ) ) {
			return false;
		}

		return $term;
	}

	// =========================
	// ! Setup Method
	// =========================

	/**
	 * Load the relevant options.
	 *
	 * @internal
	 *
	 * @since 1.3.0 Convert page_for_posts to integer.
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
		self::$index_pages[ 'post' ] = intval( get_option( 'page_for_posts' ) );

		// Get all page_for_*_posts options found.
		$pages = $wpdb->get_results( "SELECT SUBSTRING(option_name, 10, CHAR_LENGTH(option_name) - 15) AS post_type, option_value AS page_id FROM $wpdb->options WHERE option_name LIKE 'page\_for\_%\_posts'" );
		foreach ( $pages as $page ) {
			self::$index_pages[ $page->post_type ] = intval( $page->page_id );
		}

		// Get all page_for_term_* options found.
		$pages = $wpdb->get_results( "SELECT SUBSTRING(option_name, 15) AS term_id, option_value AS page_id FROM $wpdb->options WHERE option_name LIKE 'page\_for\_term\_%'" );
		foreach ( $pages as $page ) {
			self::$term_pages[ intval( $page->term_id ) ] = intval( $page->page_id );
		}

		// Get the list of supported taxonomies
		self::$supported_taxonomies = (array) get_option( 'index_pages_taxonomies', array() );

		// Flag that we've loaded everything
		self::$__loaded = true;
	}
}
