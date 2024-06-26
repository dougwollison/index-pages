<?php
/**
 * IndexPages Liaison Functionality
 *
 * @package IndexPages
 * @subpackage Handlers
 *
 * @since 1.3.0
 */

namespace IndexPages;

/**
 * The Liaison System
 *
 * Adds compatibility hooks for 3rd party plugins.
 *
 * @internal Used by the System.
 *
 * @since 1.3.0
 */
final class Liaison extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 1.3.0
	 */
	public static function register_hooks() {
		// Yoast SEO compatibility
		self::add_action( 'plugins_loaded', 'add_wpseo_helpers', 10, 0 );
	}

	// =========================
	// ! Yoast SEO Helpers
	// =========================

	/**
	 * Check if WPSEO is active, setup necessary helpers.
	 *
	 * @since 1.3.0
	 */
	public static function add_wpseo_helpers() {
		// Abort if WPSEO isn't present
		if ( ! function_exists( 'wpseo_init' ) ) {
			return;
		}

		// Add WC endpoint support to localize_here
		self::add_action( 'wpseo_register_extra_replacements', 'wpseo_register_extra_replacements', 10, 0 );
	}

	/**
	 * Add extra replacement variables for use in the Title fields.
	 *
	 * @since 1.3.0
	 */
	public static function wpseo_register_extra_replacements() {
		\WPSEO_Replace_Vars::register_replacement( 'indexpage', array( __CLASS__, 'wpseo_do_indexpage_replacement' ), 'advanced', __( 'Replaced with the title of the applicable Index Page', 'index-pages' ) );
	}

	/**
	 * Return the title of the index page if applicable.
	 *
	 * @since 1.4.1 Attempt to use custom SEO title for page if set.
	 *              Also add support for term page not just post type page.
	 * @since 1.3.0
	 */
	public static function wpseo_do_indexpage_replacement( $key, $args ) {
		$index_page = null;

		// Term requested, get it's page if set
		if ( ! empty( $args->term_id ) ) {
			$index_page = Registry::get_term_page( $args->term_id, $args->taxonomy );
		} else
		// Post type requested, get that index page
		if ( ! empty( $args->post_type ) ) {
			$index_page = Registry::get_index_page( $args->post_type );
		}

		// No page found, abort
		if ( ! $index_page ) {
			return false;
		}

		// Try the custom SEO title if set, strip out placeholders
		$seo_title = get_post_meta( $index_page, '_yoast_wpseo_title', true );
		if ( $seo_title ) {
			$seo_title = preg_replace( '/%%\w+%%/', '', $seo_title );
			$seo_title = preg_replace( '/\s+/', ' ', $seo_title );
			$seo_title = trim( $seo_title );
			return $seo_title;
		}

		return get_the_title( $index_page );
	}
}
