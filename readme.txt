=== Plugin Name ===
Contributors: nuprn1, etivite, r-a-y
Donate link: http://etivite.com/donate/
Tags: buddypress, activity stream, activity, hashtag, hashtags
Requires at least: WordPress 3.4.1, BuddyPress 1.6
Tested up to: WordPress 3.9.1, BuddyPress 2.0.1
Stable tag: 0.5.1

This plugin will convert #hashtags references to a link (activity search page) posted within the activity stream

== Description ==

** IMPORTANT **

This plugin will convert #hashtags references to a link (activity search page) posted to the activity stream

Works on the same filters as the @atusername mention filter (see Extra Configuration if you want to enable this on blog/comments activity) - this will convert anything with a leading #

Warning: This plugin converts #hashtags prior to database insert/update. Uninstalling this plugin will not remove #hashtags links from the activity content.

= Also works with =
* BuddyPress Edit Activity Stream plugin 0.3.0 or greater
* BuddyPress Activity Stream Ajax Notifier plugin


= Related Links: = 

* <a href="http://etivite.com" title="Plugin Demo Site">Author's Site</a>
* <a href="http://etivite.com/wordpress-plugins/buddypress-activity-stream-hashtags/">BuddyPress Activity Stream Hashtags - About Page</a>
* <a href="http://etivite.com/api-hooks/">BuddyPress and bbPress Developer Hook and Filter API Reference</a>


== Installation ==

1. Upload the full directory into your wp-content/plugins directory
2. Activate the plugin at the plugin administration page

== Frequently Asked Questions ==

= What pattern is matched? =

The regex looks for /[#]([_0-9a-zA-Z-]+)/ within the content and will proceed to replace anything matching /(^|\s|\b)#myhashtag/

= Can this be enabled with other content? =

Possible - try applying the filter `bp_activity_hashtags_filter`

See extra configuration

= Why convert #hashtags into links before the database save? =

The trick with activity search_terms (which is used for @atmentions) is the ending </a> since BuddyPress's sql for searching is %%term%% so #child would match #children

= What url is used? =

you may define a slug for hashtags via the admin settings page

= My question isn't answered here =

Please contact me on http://etivite.com


== Changelog ==

= 0.6.0 =
* Feature: Unicode support for hashtags (uses Twitter's algorithm)
* Feature: Taxonomy support
* Feature: Tag cloud widget support - use WP's "Tag Cloud" widget and for "Taxonomy", select "Activity Hashtags"
* Enhancement: Better multisite support
* Enhancement: phpDoc throughout the plugin
* Enhancement: Removed global references with native BP functions

= 0.5.1 =

* BUG: fix network admin settings page on multisite
* FEATURE: support for locale mo files

= 0.5.0 =

* BUG: updated for BuddyPress 1.5.1
* FEATURE: added admin options - no more functions.php config line items

= 0.4.0 =

* BuddyPress 1.2.6 and higher
* Bug: if html is allowed and color: #fff was used, was converting the attribute
* Bug: if #test was used, other #test1 was linked to #test

= 0.3.1 =

* Bug: Added display_comments=true to activity loop to display all instances of a hashtag search (thanks r-a-y!)

= 0.3.0 =

* Feature: RSS feed for a hashtag (adds head rel and replaces activity rss link)
* Feature: Added filter for hashtag activity title

= 0.2.0 =

* Bug: Filtering hashtags (thanks r-a-y!)

= 0.1.0 =

* First [BETA] version


== Upgrade Notice ==

= 0.5.0 =
* BuddyPress 1.5.1 and higher - required.


== Extra Configuration ==

`
