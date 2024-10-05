<?php
/**
 * IndexPages Template Functions
 *
 * @package IndexPages
 * @subpackage Utilities
 *
 * @api
 *
 * @since 1.0.0
 */

use IndexPages\Registry as Registry;

/**
 * Get the ID or full post object of the index page.
 *
 * @since 1.4.0 Restrucuted to avoid else > return style.
 * @since 1.0.0
 *
 * @param string $post_type Optional The post type to get the index page for.
 * @param string $return    Optional What to return ('id' or 'object').
 *
 * @return bool|int|object The desired return value or NULL on failure.
 */
function get_index_page( $post_type = null, $return = 'id' ) {
	// If no post type specified, determine it.
	if ( is_null( $post_type ) ) {
		// Get the queried object
		$object = get_queried_object();

		// If it's an archive or the home page, and the queried object is a post, use that
		if ( ( is_post_type_archive() || is_home() ) && is_a( $object, 'WP_Post' ) ) {
			// Return the desired value
			return $return == 'id' ? $object->ID : $object;
		}

		// Otherwise, attempt to determine it
		if ( is_home() ) {
			// Must be post
			$post_type = 'post';
		} elseif ( is_post_type_archive() ) {
			// If it's a post type archive, use the query var
			$post_type = get_query_var( 'post_type' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
		} elseif ( is_a( $object, 'WP_Term' ) ) {
			// If it's a taxonomy page, assume first object type for the taxonomy
			$tax = $object->taxonomy;
			$tax = get_taxonomy( $tax );
			$post_type = $tax->object_type[0];
		} elseif ( is_singular() ) {
			// If single post, use the queried object's post type
			$post_type = $object->post_type;
		} else {
			// No idea
			return null;
		}

		// Recall this function with the determined post type
		return get_index_page( $post_type, $return );
	}

	// Return null if it does not exist, or is not a supported post type (unless it's post)
	if ( ! post_type_exists( $post_type ) || ( ! Registry::is_post_type_supported( $post_type ) && $post_type != 'post' ) ) {
		return null;
	}

	// Get the index page for this post type
	$page_id = Registry::get_index_page( $post_type );

	// Check if a page ID was retrieved
	if ( ! $page_id ) {
		return null;
	}

	// Return the desired value
	return $return == 'id' ? $page_id : get_post( $page_id );
}

/**
 * Get the ID or full post object of the term index page.
 *
 * @since 1.4.0
 *
 * @param int|string|object $term     Optional The term ID/slug/object to get the index page for.
 * @param string            $return   Optional What to return ('id' or 'object').
 * @param string            $taxonomy Optional The taxonomy if finding by slug.
 *
 * @return bool|int|object The desired return value or NULL on failure.
 */
function get_term_index_page( $term = null, $return = 'id', $taxonomy = null ) {
	// If no term is specified, determine it.
	if ( is_null( $term ) ) {
		// Get the queried object
		$object = get_queried_object();

		// If it's a term page, and the queried object is the term, use that
		if ( is_category() || is_tag() || is_tax() && is_a( $object, 'WP_Term' ) ) {
			return get_term_index_page( $object, $return );
		}

		return null;
	}

	// Find by slug if needed
	if ( is_string( $term ) ) {
		if ( is_null( $taxonomy ) ) {
			// Can't find by slug without taxonomy.
			return null;
		}

		$term = get_term_by( 'slug', $term, $taxonomy );
	}

	// Get the object if not already an object
	if ( ! is_object( $term ) ) {
		$term = get_term( $term, $taxonomy );
	}

	// Fail if the term/taxonomy doesn't exist or is not supported
	if ( ! $term || is_wp_error( $term ) || ! taxonomy_exists( $term->taxonomy ) || ! Registry::is_taxonomy_supported( $term->taxonomy ) ) {
		return null;
	}

	$page_id = Registry::get_term_page( $term->term_id );

	// Check if a page ID was retrieved
	if ( ! $page_id ) {
		return null;
	}

	// Return the desired value
	return $return == 'id' ? $page_id : get_post( $page_id );
}

/**
 * Setup the postdata for the page for the current post-type index
 *
 * @since 1.4.0 Added return value and check if get_index_page returned false.
 * @since 1.0.0
 *
 * @see get_index_page()
 */
function the_index_page() {
	global $post;

	$post = get_index_page( null, 'object' );

	if ( is_null( $post ) ) {
		return false;
	}

	setup_postdata( $post );
	return true;
}

/**
 * Setup the postdata for the page for the current term index
 *
 * @since 1.4.0
 *
 * @see get_term_index_page()
 */
function the_term_index_page() {
	global $post;

	$post = get_term_index_page( null, 'object' );

	if ( is_null( $post ) ) {
		return false;
	}

	setup_postdata( $post );
	return true;
}

/**
 * Check if the a post is an index page.
 *
 * If you don't pass a post type, it'll return the slug of the post
 * type it's the index page for. Otherwise, it'll return true/false.
 *
 * @since 1.0.0
 *
 * @param int|object $post_id         Optional The ID of the post to check.
 * @param string     $match_post_type Optional The post type to match.
 *
 * @return string|bool The result of the test.
 */
function is_index_page( $post_id = null, $match_post_type = null ) {
	global $wpdb;

	// Handle no post or post object, also get the post type
	if ( is_null( $post_id ) ) {
		global $post;
		$post_type = $post->post_type;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_type = $post_id->post_type;
		$post_id = $post_id->ID;
	} else {
		$post_type = get_post_type( $post_id );
	}

	// Automatically return false if not a page
	if ( $post_type != 'page' ) {
		return false;
	}

	// Pass the ID to Registry::is_index_page() to get the post type
	$for_post_type = Registry::is_index_page( $post_id );

	if ( is_null( $match_post_type ) ) {
		// No match requested, return the post type
		return $for_post_type;
	} else {
		// Match test requested, return result
		return $match_post_type == $for_post_type;
	}
}

/**
 * Check if the a post is an term page.
 *
 * If you don't pass a term, it'll return the object of the term
 * it's the index page for. Otherwise, it'll return true/false.
 *
 * @since 1.4.0
 *
 * @param int|object $post_id    Optional The ID of the post to check.
 * @param int|object $match_term Optional The term ID/object to match.
 *
 * @return string|bool The result of the test.
 */
function is_term_index_page( $post_id = null, $match_term = null ) {
	global $wpdb;

	// Handle no post or post object, also get the post type
	if ( is_null( $post_id ) ) {
		global $post;
		$post_type = $post->post_type;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_type = $post_id->post_type;
		$post_id = $post_id->ID;
	} else {
		$post_type = get_post_type( $post_id );
	}

	// Automatically return false if not a page
	if ( $post_type != 'page' ) {
		return false;
	}

	// Pass the ID to Registry::is_index_page() to get the post type
	$for_term = Registry::is_term_page( $post_id );

	if ( is_null( $match_term ) ) {
		// No match requested, return the post type
		return $for_term;
	} else {
		$match_term = get_term( $match_term );
		// Match test requested, return result
		return $match_term->term_id == $for_term->term_id;
	}
}

/**
 * Return the date archive URL fo the post type.
 *
 * @since 1.0.0
 *
 * @globla WP_Rewrite $wp_rewrite The rewrite rules system.
 *
 * @param string $post_type The post type for the link.
 * @param int    $year      The year for the link. Pass '' for current year.
 * @param int    $month     Optional The month for the link. Pass '' for current month.
 * @param int    $day       Optional The day for the link. Pass '' for current day.
 *
 * @return string The daily archive URL.
 */
function get_post_type_date_link( $post_type, $year = '', $month = false, $day = false ) {
	global $wp_rewrite;

	$base = untrailingslashit( get_post_type_archive_link( $post_type ) );

	if ( $year === '' ) {
		$year = gmdate( 'Y', current_time( 'timestamp' ) );
	}
	if ( $month === '' ) {
		$month = gmdate( 'm', current_time( 'timestamp' ) );
	}
	if ( $day === '' ) {
		$day = gmdate( 'j', current_time( 'timestamp' ) );
	}

	if ( $day ) {
		$datelink = $wp_rewrite->get_day_permastruct();
		if ( !empty($datelink) ) {
			$datelink = str_replace( '%year%', $year, $datelink );
			$datelink = str_replace( '%monthnum%', zeroise( intval( $month ), 2 ), $datelink );
			$datelink = str_replace( '%day%', zeroise( intval( $day ), 2 ), $datelink );
			$datelink = user_trailingslashit( $datelink, 'day' );
		} else {
			$datelink = '?m=' . $year . zeroise( $month, 2 ) . zeroise( $day, 2 );
		}
	} elseif ( $month ) {
		$datelink = $wp_rewrite->get_month_permastruct();
		if ( !empty($datelink) ) {
			$datelink = str_replace( '%year%', $year, $datelink );
			$datelink = str_replace( '%monthnum%', zeroise( intval( $month ), 2 ), $datelink );
			$datelink = user_trailingslashit( $datelink, 'month' );
		} else {
			$datelink = '?m=' . $year . zeroise( $month, 2 );
		}
	} else {
		$datelink = $wp_rewrite->get_year_permastruct();
		if ( ! empty( $datelink ) ) {
			$datelink = str_replace( '%year%', $year, $datelink );
			$datelink = user_trailingslashit( $datelink, 'year' );
		} else {
			$datelink = '?m=' . $year;
		}
	}

	return $base . $datelink;
}

/**
 * Return the yearly archive URL for the post type.
 *
 * @see get_post_type_date_link()
 */
function get_post_type_year_link( $post_type, $year = '' ) {
	return get_post_type_date_link( $post_type, $year );
}

/**
 * Return the monthly archive URL for the post type.
 *
 * @see get_post_type_date_link()
 */
function get_post_type_month_link( $post_type, $year = '', $month = '' ) {
	return get_post_type_date_link( $post_type, $year, $month );
}

/**
 * Return the daily archive URL for the post type.
 *
 * @see get_post_type_date_link()
 */
function get_post_type_day_link( $post_type, $year = '', $month = '', $day = '' ) {
	return get_post_type_date_link( $post_type, $year, $month, $day );
}
