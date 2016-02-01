<?php
/**
 * IndexPages Backend Functionality
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @since 1.0.0
 */

namespace IndexPages;

/**
 * The Backend Functionality
 *
 * Hooks into various backend systems to identify
 * assigned index pages, add settings fields, and
 * load the text domain.
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @internal Used by the System.
 *
 * @since 1.0.0
 */

class Backend extends Handler {
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

		// After-setup stuff
		static::add_action( 'plugins_loaded', 'ready' );

		// Settings registration
		static::add_action( 'init', 'register_settings', 10, 0 );

		// Interface additions
		static::add_filter( 'display_post_states', 'add_index_state', 10, 2 );
	}

	// =========================
	// ! After Setup
	// =========================

	/**
	 * Load the text domain.
	 *
	 * @since 1.0.0
	 */
	public static function ready() {
		// Load the textdomain
		load_plugin_textdomain( 'index-pages', false, INDEXPAGES_PLUGIN_DIR . '/lang' );
	}

	// =========================
	// ! Settings Registration
	// =========================

	/**
	 * Add "Page for * posts" dropdowns to the reading settings page.
	 *
	 * @since 1.0.0
	 */
	public static function register_settings() {
		// to be written
	}

	// =========================
	// ! Interface Additions
	// =========================

	/**
	 * Filter the post states list, adding a "*s Page" state flag to if applicable.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $post_states The list of post states for the post.
	 * @param WP_Post $post        The post in question.
	 *
	 * @return array The filtered post states list.
	 */
	public static function add_index_state( array $post_states, \WP_Post $post ) {
		// Only proceed if the post is a page
		if ( $post->post_type == 'page' ) {
			// Check if it's an assigned index page, get the associated post type
			if ( $post_type = Registry::is_index_page( $post->ID ) ) {
				$post_type_obj = get_post_type_object( $post_type );

				// Default label
				$label = sprintf( __( '%s Page', 'page for post type', 'index-pages' ), $post_type_obj->label );

				// Use defined label if present in post type's label list
				if ( property_exists( $post_type_obj->labels, 'index_page' ) ) {
					$label = $post_type_obj->labels->index_page;
				}

				$post_states[] = $label;
			}
		}

		return $post_states;
	}
}

