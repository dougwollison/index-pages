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

		$taxonomies = Registry::get_supported_taxonomies();

		// After-setup stuff
		self::add_action( 'plugins_loaded', 'load_textdomain' );

		// Settings registration/saving
		self::add_action( 'admin_init', 'register_settings', 10, 0 );
		foreach ( $taxonomies as $taxonomy ) {
			self::add_action( "edited_{$taxonomy}", 'save_index_page', 10, 1 );
		}

		// Interface additions
		self::add_filter( 'display_post_states', 'add_index_state', 10, 2 );
		self::add_action( 'edit_form_after_title', 'add_index_notice', 10, 1 );
		foreach ( $taxonomies as $taxonomy ) {
			self::add_action( "{$taxonomy}_edit_form_fields", 'add_index_selector', 10, 1 );
		}
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
	// ! Settings Registration/Saving
	// =========================

	/**
	 * Add "Page for * posts" dropdowns to the reading settings page.
	 *
	 * @since 1.4.0 Added settings selection for supported taxonomies.
	 * @since 1.3.0 Now loops through all custom post types and checks for support.
	 * @since 1.0.0
	 */
	public static function register_settings() {
		add_settings_section(
			'index_pages',
			__( 'Index Pages', 'index-pages' ),
			array( __CLASS__, 'do_settings_section' ),
			'reading'
		);

		foreach ( get_post_types( array( '_builtin' => false ) ) as $post_type ) {
			// Skip if post type is not supported
			if ( ! Registry::is_post_type_supported( $post_type ) ) {
				continue;
			}

			$option_name = "page_for_{$post_type}_posts";

			register_setting( 'reading', $option_name, 'intval' );

			add_settings_field(
				$option_name,
				self::get_index_page_label( $post_type ),
				array( __CLASS__, 'do_page_selector_field' ),
				'reading',
				'index_pages',
				array(
					'label_for' => $option_name,
					'post_type' => $post_type,
				)
			);
		}

		// Add setting to enable index pages for taxonomy terms
		register_setting( 'reading', 'index_pages_taxonomies' );

		add_settings_field(
			'index_pages_taxonomies',
			__( 'Supported Taxonomies', 'index-pages' ),
			array( __CLASS__, 'do_taxonomies_dropdown' ),
			'reading',
			'index_pages'
		);
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
	 * Print an page selector dropdown.
	 *
	 * @since 1.4.0 Renamed, filterable wp_dropdown_pages() args.
	 * @since 1.0.0
	 *
	 * @param array $config The arguments passed in add_settings_field().
	 */
	public static function do_page_selector_field( $config ) {
		$args = array(
			'selected'          => Registry::get_index_page( $config['post_type'] ),
			'name'              => $config['label_for'],
			'id'                => $config['label_for'],
			'show_option_none'  => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			// Include this context flag for use by 3rd party plugins
			'plugin-context'    => 'index-pages',
		);

		/**
		 * Filter the arguments for wp_dropdown_pages() for term index page selecting.
		 *
		 * @param array         $args   The arguments for wp_dropdown_pages().
		 * @param string        $type   The type of object this will be for (term vs post_type).
		 * @param string|object $object The object this will be for.
		 */
		$args = apply_filters( 'indexpages_dropdown_pages_args', $args, 'post_type', $config['post_type'] );

		wp_dropdown_pages( $args );
	}

	/**
	 * Print the taxonomies checklist.
	 *
	 * @since 1.4.1 Write taxonomy slug to label title for added context.
	 * @since 1.4.0
	 */
	public static function do_taxonomies_dropdown() {
		$selected = Registry::get_supported_taxonomies();

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		echo '<p>';
		foreach ( $taxonomies as $taxonomy ) {
			printf(
				'<label title="%2$s"><input name="%1$s[]" type="checkbox" value="%2$s" %4$s /> %3$s</label><br /> ',
				'index_pages_taxonomies',
				$taxonomy->name,
				$taxonomy->label,
				in_array( $taxonomy->name, $selected ) ? 'checked' : ''
			);
		}
		echo '</p>';
	}

	/**
	 * Save the term's index page setting.
	 *
	 * @since 1.4.0
	 *
	 * @param int $term_id The ID of the term being edited.
	 */
	public static function save_index_page( $term_id ) {
		if ( isset( $_POST['term_index_page'] ) ) {
			update_option( "page_for_term_{$term_id}", $_POST['term_index_page'] );
		}
	}

	// =========================
	// ! Interface Additions
	// =========================

	/**
	 * Filter the post states list, adding a "*s Page" state flag to if applicable.
	 *
	 * @since 1.3.0 Add check to prevent duplicate printing of "Posts Page".
	 * @since 1.2.0 Added check to make sure post type currently exists.
	 * @since 1.1.0 Store the post state in an explicit key.
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
			// Check if it's an assigned index page (other than for posts),
			// get the associated post type (and ensure it exists)
			if ( ( $post_type = Registry::is_index_page( $post->ID ) ) && $post_type !== 'post' && post_type_exists( $post_type ) ) {
				// Get the label to use
				$label = self::get_index_page_label( $post_type );

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
	 * @since 1.3.0 Modified notice to only mention lack of content display when index-pages support is absent.
	 * @since 1.2.1 Rejigged check to handle deprecated index pages.
	 * @since 1.1.0 Added missing static keyword
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The post in question.
	 */
	public static function add_index_notice( \WP_Post $post ) {
		// Abort if not a page or not an index page
		if ( $post->post_type != 'page' || ! ( $post_type = Registry::is_index_page( $post->ID ) ) || ! post_type_exists( $post_type ) ) {
			return;
		}

		// Get the plural labe to use
		$label = strtolower( get_post_type_object( $post_type )->label );

		// Default notice type/message
		$notice_type = 'info';
		$notice_text = sprintf( __( 'You are currently editing the page that shows your latest %s.', 'index-pages' ), $label );

		// If the post type doesn't explicitly support index-pages, mention content may not be displayed.
		if ( ! post_type_supports( $post_type, 'index-page' ) ) {
			$notice_type = 'warning';
			$notice_text .= ' <em>' . __( 'Your current theme may not display the content you write here.', 'index-pages' ) . '</em>';
		}

		printf( '<div class="notice notice-%s inline"><p>%s</p></div>', $notice_type, $notice_text );
	}

	/**
	 * Print a page dropdown to select the index page for this term.
	 *
	 * @since 1.4.0
	 *
	 * @param object The term object.
	 */
	public static function add_index_selector( $term ) {
		$args = array(
			'selected'          => Registry::get_term_page( $term ),
			'name'              => 'term_index_page',
			'id'                => 'term_index_page',
			'show_option_none'  => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			// Include this context flag for use by 3rd party plugins
			'plugin-context'    => 'index-pages',
		);

		 /** This filter is documented in IndexPages\Backend::do_page_selector_field() */
		$args = apply_filters( 'indexpages_dropdown_pages_args', $args, 'term', $term );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="term_index_page"><?php _e( 'Index Page', 'index-pages' ); ?></label>
			</th>
			<td>
				<?php wp_dropdown_pages( $args ); ?>
			</td>
		</tr>
		<?php
	}
}
