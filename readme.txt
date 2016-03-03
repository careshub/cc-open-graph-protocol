=== CC Open Graph Headers  ===
Contributors: dcavins
Tags: buddypress
Requires at least: WordPress 4.4, BuddyPress 2.4
Tested up to: WordPress 4.4, BuddyPress 2.5
Stable tag: 1.0.0

Add Open Graph meta tags to page headers.

== Description ==

The Open Graph protocol is used by Facebook, Twitter and G+ to determine what info to show when a user shares a link to a page. By setting these meta tags in the <head>, you can suggest what those titles, images, description, etc those services should use, with the end goal of making shared links from your site look better and work better.

See http://ogp.me for information about Open Graph.

== Changelog ==

= 1.0.0 =
First pass adds fallbacks and support for basic cases: 
* on a single page (blog post, page, bp-doc)
* on a member’s profile
* in a group
* basic filters so that other plugins, like group pages, can hook in to provide correct info about the content of a group tab, for instance.