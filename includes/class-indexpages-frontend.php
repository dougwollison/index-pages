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
 * @internal Used by the System.
 *
 * @since 1.0.0
 */
final class Frontend extends Handler {
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
	 * @since 1.3.0 Added wp_nav_menu_objects hook.
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
		self::add_action( 'parse_request', 'handle_request', 10, 1 );

		// Title/link rewriting
		self::add_filter( 'wp_title_parts', 'rewrite_title_parts', 10, 1 );
		self::add_filter( 'post_type_archive_link', 'rewrite_archive_link', 10, 2 );
		self::add_filter( 'term_link', 'rewrite_term_link', 10, 2 );

		// Nav menu rewriting
		self::add_filter( 'wp_nav_menu_objects', 'handle_index_page_links', 10, 1 );

		// Admin bar additions
		self::add_action( 'admin_bar_menu', 'add_edit_button', 85, 1 );
	}

	// =========================
	// ! Request Handling
	// =========================

	/**
	 * Check if the path requested matches an assigned index page.
	 *
	 * Also checks for date and pagination parameters.
	 *
	 * @since 1.4.0 Added support for term pages.
	 * @since 1.2.0 Added check to make sure post type currently exists.
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

			// Get the post type, and validate that it exists
			if ( $post_type = Registry::is_index_page( $page->ID ) ) {
				// Modify the request into a post type archive instead
				$qv['post_type'] = $post_type;
				list( , , $qv['year'], $qv['monthnum'], $qv['day'], $qv['paged'] ) = array_pad( $matches, 6, null );

				// Make sure these are unset
				unset( $qv['pagename'] );
				unset( $qv['page'] );
				unset( $qv['name'] );
			} else
			// Alternatively, get the term, and validate that it exists
			if ( $term = Registry::is_term_page( $page->ID ) ) {
				// Modify the request into a post type archive instead
				switch ( $term->taxonomy ) {
					case 'category':
						$qv['cat'] = $term->term_id;
						break;

					case 'post_tag':
						$qv['tag_id'] = $term->term_id;
						break;

					default:
						$qv['tax_query'] = array(
							array(
								'taxonomy' => $term->taxonomy,
								'field' => 'term_id',
								'terms' => $term->term_id,
							),
						);
				}
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
	 * @since 1.4.0 Added support for term pages.
	 * @since 1.0.0
	 *
	 * @param array $title The title parts to filter.
	 *
	 * @return array The filtered title parts.
	 */
	public static function rewrite_title_parts( array $title_parts ) {
		// Handle post type index if applicable
		if ( is_post_type_archive() ) {
			// Get the queried post type
			$post_type = get_query_var( 'post_type', 'post' );

			// Get the index for this post type, update the title if found
			if ( $index_page = Registry::get_index_page( $post_type ) ) {
				$title_parts[0] = get_the_title( $index_page );
			}
		} else
		// Alternatively, handle term index if applicable
		if ( is_category() || is_tag() || is_tax() ) {
			// Get the queried term
			$term = get_queried_object();

			// Get the index for this post type, update the title if found
			if ( $index_page = Registry::get_term_page( $term ) ) {
				$title_parts[0] = get_the_title( $index_page );
			}
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

	// =========================
	// ! Nav Menu Rewriting
	// =========================

	/**
	 * Filter the menu items and handle current menu item/page for index pages.
	 *
	 * @since 1.3.0
	 *
	 * @param array $menu_items The menu items to filter.
	 *
	 * @return array The filtered menu items.
	 */
	public static function handle_index_page_links( $menu_items ) {
		// Get the current index page
		$index_page = get_term_index_page() ?: get_index_page();

		if ( $index_page ) {
			foreach ( $menu_items as $menu_item ) {
				if ( $menu_item->object === 'page' && $menu_item->object_id == $index_page ) {
					if ( is_singular() ) {
						$menu_item->classes[] = 'current_page_parent';
					} else {
						$menu_item->classes[] = 'current-menu-item';
					}
				}
			}
		}

		return $menu_items;
	}

	// =========================
	// ! Admin Bar Additions
	// =========================

	/**
	 * Add an Edit Index Page button to the admin bar if applicable.
	 *
	 * @since 1.4.0 Added support for term pages.
	 * @since 1.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public static function add_edit_button( \WP_Admin_Bar $wp_admin_bar ) {
		// Abort if not an archive for the supported post types
		if ( is_post_type_archive() ) {
			$index_page = Registry::get_index_page( get_query_var( 'post_type' ) );
		} else
		if ( is_category() || is_tag() || is_tax() ) {
			$index_page = Registry::get_term_page( get_queried_object() );
		} else {
			return;
		}

		// Abort if an edit node already exists
		if ( $wp_admin_bar->get_node( 'edit' ) ) {
			return;
		}

		// Get the page post type object
		$post_type_object = get_post_type_object( 'page' );

		// If an index is found, is editable, and has an edit link, add the edit button.
		if ( $index_page
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
