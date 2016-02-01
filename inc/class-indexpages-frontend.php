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
		if ( is_backend() ) {
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
		$qv =& $wp->query_vars;

		// Abort if a pagename wasn't matched at all
		if ( ! isset( $qv['pagename'] ) ) {
			return;
		}

		// Build a RegExp to capture a page with date/paged arguments
		$pattern =
			'(.+?)'. // page name/path
			'(?:/([0-9]{4})'. // optional year...
				'(?:/([0-9]{2})'. // ...with optional month...
					'(?:/([0-9]{2}))?'. // ...and optional day
				')?'.
			')?'.
			'(?:/page/([0-9]+))?'. // and optional page number
		'/?$';

		// Proceed if the pattern checks out
		if ( preg_match( "#$pattern#", $wp->request, $matches ) ) {
			// Get the page using match 1 (pagename)
			$page = get_page_by_path( $matches[1] );

			// Abort if no page is found
			if ( is_null( $page ) ) {
				return;
			}

			if ( $post_type = Registry::is_index_page( $page->ID ) ) {
				// Modify the request into a post type archive instead
				$qv['post_type'] = $post_type;
				list( , , $qv['year'], $qv['monthnum'], $qv['day'], $qv['paged'] ) = array_pad( $matches, 6, null );

				// Make sure these are unset
				unset( $qv['pagename'] );
				unset( $qv['page'] );
				unset( $qv['name'] );
			}
		}
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
		// Skip if not an archive
		if ( ! is_post_type_archive() ) {
			return $title_parts;
		}

		// Get the queried post type
		$post_type = get_query_var( 'post_type' );

		// Get the index for this post type, update the title if found
		if ( $index_page = Registry::get_index_page( $post_type ) ) {
			$title_parts[0] = get_the_title( $index_page );
		}

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
		if ( $index = Registry::get_index_page( $post_type ) ) {
			$link = get_permalink( $index );
		}

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
		// Abort if not an archive for the supported post types
		if ( ! is_post_type_archive() ) {
			return;
		}

		// Abort if an edit node already exists
		if ( $wp_admin_bar->get_node( 'edit' ) ) {
			return;
		}

		// Get the page post type object
		$post_type_object = get_post_type_object( 'page' );

		// If an index is found, is editable, and has an edit link, add the edit button.
		if ( ( $index_page = Registry::get_index_page() )
		&& current_user_can( 'edit_post', $index_page )
		&& $edit_post_link = get_edit_post_link( $index_page ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'edit',
				'title' => $post_type_object->labels->edit_item,
				'href' => $edit_post_link
			) );
		}
	}
}

