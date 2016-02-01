<?php
/*
Plugin Name: Index Pages
Plugin URI: https://github.com/dougwollison/index-pages
Description: Assign pages as the index page for WordPress custom post types, similar to the Page for Posts.
Version: 1.0.0
Author: Doug Wollison
Author URI: http://dougw.me
Tags: index page, custom post type, custom index, page for posts
License: GPL2
Text Domain: index-pages
*/

// =========================
// ! Constants
// =========================

/**
 * Reference to the plugin file.
 *
 * @since 1.0.0
 *
 * @var string
 */
define( 'INDEXPAGES_PLUGIN_FILE', __FILE__ );

/**
 * Reference to the plugin directory.
 *
 * @since 1.0.0
 *
 * @var string
 */
define( 'INDEXPAGES_PLUGIN_DIR', __DIR__ );

// =========================
// ! Includes
// =========================

require( INDEXPAGES_PLUGIN_DIR . '/inc/autoloader.php' );
require( INDEXPAGES_PLUGIN_DIR . '/inc/functions-indexpages.php' );

// =========================
// ! Setup
// =========================

IndexPages\System::setup();
