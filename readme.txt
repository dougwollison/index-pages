=== Index Pages ===
Contributors: dougwollison
Tags: index page, custom post type, custom index, page for posts
Requires at least: 4.0.0
Tested up to: 4.6.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Assign pages as the index page for WordPress custom post types, similar to the Posts Page.

== Description ==

The Index Pages system allows you to designate pages as the index page for a particular post type.

This allows you to have a custom title, text content, and other information displayed on your
post type archives, should your current theme support it.

For theme developers, the plugin offers some template functions for loading the post object for the
current index page (including the posts page), in a similar fashion to `the_post();`, to create things
like a customizable introductory banner that appears above the listing, using the index page's data.

Designated index pages are flagged as such in the Pages manager, for easy recognition.

== Installation ==

1. Upload the contents of `index-pages.tar.gz` to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Usage Documentation ==

The Index Page assignment interface can be found under Settings > Reading. By default, all post types
registered with the `has_archive` argument set to true will be available for assignment.

For theme and plugin developers, you can register support for your custom post types like so:

`
IndexPages\Registry::add_post_types( $post_types ); // a single post type or array of post types
`

When an index page is assigned, it's permalink will point to the associated post type's archive, with
the page title updated appropriately.

For theme and plugin developers, you can access the index page's post object with the following.

`
the_index_page();
`

This works exactly like `the_post();`, populating the `$post` variable with the index page's data.

== Changelog ==

**Details on each release can be found [on the GitHub releases page](https://github.com/dougwollison/index-pages/releases) for this project.**

= 1.2.0 =
Added checks to make sure an index page's associated post type exists.

= 1.1.0 =
Updated file and code structure, added missing static keyword to Backend::add_index_notice().

= 1.0.0 =
Initial public release.
