<?php
/**
 * IndexPages Internal Functions
 *
 * @package IndexPages
 * @subpackage Utilities
 *
 * @internal
 *
 * @since 1.0.0
 */

namespace IndexPages;

// =========================
// ! Conditional Tags
// =========================

/**
 * Check if we're in the backend of the site (excluding frontend AJAX requests)
 *
 * @internal
 *
 * @since 1.0.0
 *
 * @global string $pagenow The current page slug.
 */
function is_backend() {
	global $pagenow;

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// AJAX request, check if the referrer is from wp-admin
		return strpos( $_SERVER['HTTP_REFERER'] ?? '', admin_url() ) === 0;
	} else {
		// Check if in the admin or otherwise the login/register page
		return is_admin() || in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ) );
	}
}

// =========================
// ! RegEx Tools
// =========================

/**
 * Compile a list of RegEx groups into a pattern.
 *
 * @since 1.4.0
 *
 * @param array[] $groups {
 *     An array of groups.
 *
 *     @type string $name      The name of the group.
 *     @type string $pattern   The pattern for the group.
 *     @type array  $subgroups Optional. An array of subgroups, same structure as $groups.
 *     @type string $wrapper   Optional. A pattern to match containing the group (e.g. '/%s').
 *     @type bool   $optional  Optional. Wether or not the overall pattern is optional.
 * }
 *
 * @return string The compiled pattern.
 */
function compile_regex_groups( $groups ) {
	$compiled = '';

	foreach ( $groups as $group ) {
		$pattern = sprintf( '(?<%s>%s)', $group['name'], $group['pattern'] );

		if ( isset( $group['subgroups'] ) ) {
			$pattern .= compile_regex_groups( $group['subgroups'] );
		}

		if ( isset( $group['wrapper'] ) ) {
			$pattern = sprintf( "(?:{$group['wrapper']})", $pattern );
		}

		if ( isset( $group['optional'] ) && $group['optional'] ) {
			$pattern .= '?';
		}

		$compiled .= $pattern;
	}

	return $compiled;
}
