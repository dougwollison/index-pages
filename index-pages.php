<?php
/*
Plugin Name: Index Pages
Plugin URI: https://github.com/dougwollison/index-pages
Description: Assign pages as the index page for WordPress custom post types, similar to the Posts Page.
Version: 1.4.0
Author: Doug Wollison
Author URI: http://dougw.me
Tags: index page, custom post type, custom index, page for posts
License: GPL2
Text Domain: index-pages
Domain Path: /languages
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
define( 'INDEXPAGES_PLUGIN_DIR', dirname( INDEXPAGES_PLUGIN_FILE ) );

// =========================
// ! Includes
// =========================

require( INDEXPAGES_PLUGIN_DIR . '/includes/autoloader.php' );
require( INDEXPAGES_PLUGIN_DIR . '/includes/functions-indexpages.php' );
require( INDEXPAGES_PLUGIN_DIR . '/includes/functions-template.php' );

// =========================
// ! Setup
// =========================

IndexPages\System::setup();
