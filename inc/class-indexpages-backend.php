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
		static::add_action( 'admin_init', 'register_settings', 10, 0 );

		// Interface additions
		static::add_filter( 'display_post_states', 'add_index_state', 10, 2 );
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Get the "*s Page" label for a post type.
	 *
	 * Will use the defined label if found, otherwise use template string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type to get the label for.
	 *
	 * @return string The label to use.
	 */
	protected static function get_index_page_label( $post_type ) {
		$post_type_obj = get_post_type_object( $post_type );

		// Default label
		$label = sprintf( __( '%s Page', 'index-pages' ), $post_type_obj->label );

		// Use defined label if present in post type's label list
		if ( property_exists( $post_type_obj->labels, 'index_page' ) ) {
			$label = $post_type_obj->labels->index_page;
		}

		return $label;
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
		// Add the settings section
		add_settings_section(
			'index_pages',
			__( 'Index Pages', 'index-pages' ),
			array( static::$name, 'do_settings_section' ),
			'reading'
		);

		foreach ( Registry::get_post_types() as $post_type ) {
			// Skip if post type does not exist or does not support archives
			if ( ! post_type_exists( $post_type ) || ! get_post_type_object( $post_type )->has_archive ) {
				continue;
			}

			$option_name = "page_for_{$post_type}_posts";

			register_setting( 'reading', $option_name, 'intval' );

			add_settings_field(
				$option_name,
				static::get_index_page_label( $post_type ),
				array( static::$name, 'do_settings_field' ),
				'reading',
				'index_pages',
				array(
					'label_for' => $option_name,
					'post_type' => $post_type,
				)
			);
		}
	}

	/**
	 * Print the Index Pages settings section intro text.
	 *
	 * @since 1.0.0
	 */
	public static function do_settings_section() {
		echo '<p>' . __( 'Assign existing pages as the index page for posts of the following post types.', 'index-pages' ) . '</p>';
	}

	/**
	 * Print an Index Pages settings field.
	 *
	 * @since 1.0.0
	 *
	 * @param
	 */
	public static function do_settings_field( $args ) {
		extract( $args );

		wp_dropdown_pages( array(
			'selected'          => Registry::get_index_page( $post_type ),
			'name'              => $label_for,
			'id'                => $label_for,
			'show_option_none'  => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			// Include this context flag for use by 3rd party plugins
			'plugin-context'    => 'index-pages',
		) );
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
				// Get the label to use
				$label = static::get_index_page_label( $post_type );

				$post_states[] = $label;
			}
		}

		return $post_states;
	}
}

