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
 * Hooks into various backend systems to load
 * custom assets and add the editor interface.
 *
 * @internal Used by the System.
 *
 * @since 1.0.0
 */
final class Backend extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
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
		static::add_action( 'edit_form_after_title', 'add_index_notice', 10, 1 );
	}

	// =========================
	// ! Setup Stuff
	// =========================

	/**
	 * Load the text domain.
	 *
	 * @since 1.0.0
	 */
	public static function load_textdomain() {
		// Load the textdomain
		load_plugin_textdomain( 'indexpages', false, dirname( INDEXPAGES_PLUGIN_FILE ) . '/languages' );
	}

	// =========================
	// ! Plugin Information
	// =========================

	/**
	 * In case of update, check for notice about the update.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugin The information about the plugin and the update.
	 */
	public static function update_notice( $plugin ) {
		// Get the version number that the update is for
		$version = $plugin['new_version'];

		// Check if there's a notice about the update
		$transient = "indexpages-update-notice-{$version}";
		$notice = get_transient( $transient );
		if ( $notice === false ) {
			// Hasn't been saved, fetch it from the SVN repo
			$notice = file_get_contents( "http://plugins.svn.wordpress.org/index-pages/assets/notice-{$version}.txt" ) ?: '';

			// Save the notice
			set_transient( $transient, $notice, YEAR_IN_SECONDS );
		}

		// Print out the notice if there is one
		if ( $notice ) {
			echo apply_filters( 'the_content', $notice );
		}
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
		$registered = 0;

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

			$registered++;
		}

		// If any settings were registered, add the settings output
		if ( $registered > 0 ) {
			add_settings_section(
				'index_pages',
				__( 'Index Pages', 'index-pages' ),
				array( static::$name, 'do_settings_section' ),
				'reading'
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
	 * @since 1.0.1 Store the post state in an explicit key.
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

				$post_states[ "page_for_{$post_type}_posts" ] = $label;
			}
		}

		return $post_states;
	}

	/**
	 * Print a notice about the current page being an index page.
	 *
	 * Unlike WordPress for the Posts page, it will not disabled the editor.
	 *
	 * @since 1.1.0 Added missing static keyword
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The post in question.
	 */
	public static function add_index_notice( \WP_Post $post ) {
		// Abort if not a page or not an index page
		if ( $post->post_type != 'page' || ! ( $post_type = Registry::is_index_page( $post->ID ) ) ) {
			return;
		}

		// Get the plural labe to use
		$label = strtolower( get_post_type_object( $post_type )->label );
		echo '<div class="notice notice-warning inline"><p>' .
			sprintf( __( 'You are currently editing the page that shows your latest %s.', 'index-pages' ), $label ) .
			' <em>' . __( 'Your current theme may not display the content you write here.', 'index-pages' ) . '</em>' .
		'</p></div>';
	}
}