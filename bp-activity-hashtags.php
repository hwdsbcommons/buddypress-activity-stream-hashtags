<?php
if ( !defined( 'ABSPATH' ) ) exit;

/** REGISTRATION ********************************************************/

/**
 * Setup globals.
 */
function bp_activity_hashtags_setup_globals() {
	global $bp;

	// create a new object and stuff under the current 'activity' component object
	$bp->activity->hashtags = new stdClass;

	// save our regex pattern for later reference
	$bp->activity->hashtags->pattern = bp_activity_hashtags_get_regex();
}
add_action( 'bp_setup_globals', 'bp_activity_hashtags_setup_globals' );

/**
 * Register taxonomy for hashtags.
 */
function bp_activity_hashtags_register_taxonomy() {
	// Setup our taxonomy args
	$args = array(
		'labels' => array(
			'name'          => __( 'Hashtags', 'bp-activity-hashtags' ),
			'singular_name' => __( 'Hashtag',  'bp-activity-hashtags' ),
			'menu_name'     => __( 'Hashtags', 'bp-activity-hashtags' ),
			'search_items'  => __( 'Search Hashtags', 'bp-activity-hashtags' )
		),
		'capabilities' => array(
			'manage_terms' => 'edit_users', // Using 'edit_users' cap to keep this simple.
			'edit_terms'   => 'edit_users',
			'delete_terms' => 'edit_users',
			'assign_terms' => 'read',
		),
		//'update_count_callback' => 'my_update_profession_count', // Use a custom function to update the count.
		'query_var' => false,
		'rewrite'   => false
	);

	// register the 'hashtag' taxonomy
	//
	// issues to be aware of:
	// (1) we're attaching this to the 'activity' object type, which doesn't exist
	//     and is more like a pseudo-type. but WP doesn't block this functionality
	//     at the moment. however WP could "fix" this in the future and break this
	//
	//     FWIW, Justin Tadlock does the same thing in his tutorial:
	//     http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	//
	//     The 'user' object type doesn't exist.
	//
	// (2) Conflicting object IDs noted by Boone Gorges
	//     http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress#comment-503194
	register_taxonomy(
		'hashtag',
		apply_filters( 'bp_activity_hashtags_object_type', 'activity' ),
		apply_filters( 'bp_activity_hashtags_taxonomy_args', $args )
	);

	// testing this...
	//register_taxonomy_for_object_type( 'hashtag', 'activity' );
}

add_action( 'init', 'bp_activity_hashtags_register_taxonomy', 0 );

/** SCREENS *************************************************************/

/**
 * Screen router for activity hashtags.
 *
 * Determines if we're on a hashtag page. If so, sends things along their
 * merry way!
 */
function etivite_bp_activity_hashtags_screen_router() {
	global $bp, $wp_query;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return false;

	if ( empty( $bp->action_variables[0] ) )
		return false;

	if ( 'feed' == $bp->action_variables[1] ) {

		$link      = bp_get_activity_hashtags_permalink( esc_attr( $bp->action_variables[0] ) );
		$link_self = $link . '/feed/';

		$wp_query->is_404 = false;
		status_header( 200 );

		include_once( dirname( __FILE__ ) . '/feeds/bp-activity-hashtags-feed.php' );
		die;

	} else {

		bp_core_load_template( 'activity/index' );

	}

}
add_action( 'bp_screens', 'etivite_bp_activity_hashtags_screen_router' );

/** HOOKS ***************************************************************/

/**
 * Finds hashtags for a given piece of content and auto-links it.
 *
 * @param str $content The content we want to find hashtags for
 * @return str If hashtags are found, they are replaced with a linked
 *  version of that hashtag.
 */
function etivite_bp_activity_hashtags_filter( $content ) {
	global $bp;

	// do our hashtag matching
	preg_match_all( $bp->activity->hashtags->pattern, $content, $hashtags );

	if ( $hashtags ) {
		// Make sure there's only one instance of each tag
		if ( ! $hashtags = array_unique( $hashtags[3] ) )
			return $content;

		// save hashtags for later reference so we don't have to parse again
		$bp->activity->hashtags->temp = $hashtags;

		// watch for edits and if something was already wrapped in html link - thus check for space or word boundary prior
		foreach( (array)$hashtags as $hashtag ) {
			$pattern = "/(^|\s|\b)#". $hashtag ."($|\b)/";
			$content = preg_replace( $pattern, ' <a href="' .  bp_get_activity_hashtags_permalink( htmlspecialchars( $hashtag ) ). '" rel="nofollow" class="hashtag">#'. htmlspecialchars( $hashtag ) .'</a>', $content );
		}
	}

	return $content;
}

/**
 * If hashtags exist in an activity entry, save each tag as a taxonomy term.
 *
 * @param obj The BP activity object after saving the entry
 */
function bp_activity_hashtags_save_terms( $activity ) {
	global $bp;

	// see if hashtags were made
	if ( empty( $bp->activity->hashtags->temp ) )
		return;

	// save the terms
	foreach ( (array) $bp->activity->hashtags->temp as $hashtag ) {
		wp_set_object_terms( $activity->id, $hashtag, 'hashtag' );
	}

	// unset our temp variable
	unset( $bp->activity->hashtags->temp );
}
add_action( 'bp_activity_after_save', 'bp_activity_hashtags_save_terms' );

/**
 * Removes hashtags from the taxonomy term relationship tables after an
 * activity entry is deleted.
 *
 * @param array The activity IDs that were deleted
 */
function bp_activity_hashtags_delete_terms( $ids ) {
	// sanity check
	if ( empty( $ids ) )
		return;

	// remove the terms
	foreach ( (array) $ids as $id ) {
		wp_delete_object_term_relationships( $id, 'hashtag' );
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_activity_hashtags_delete_terms' );

/**
 * Modifies the activity querystring to find our hashtags.
 *
 * @param str $query_string The unmodified querystring
 * @return str If we're on our specialized hashtag screen,
 *  modify the querystring to find our hashtags.
 */
function etivite_bp_activity_hashtags_querystring( $query_string, $object ) {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return $query_string;

	if ( empty( $bp->action_variables[0] ) )
		return $query_string;

	if ( 'feed' == $bp->action_variables[1] )
		return $query_string;

	if ( strlen( $query_string ) < 1 )
		return 'display_comments=true&search_terms=#'. $bp->action_variables[0] . '<';

	/* Now pass the querystring to override default values. */
	$query_string .= '&display_comments=true&search_terms=#'. $bp->action_variables[0] . '<';

	return $query_string;
}
add_filter( 'bp_ajax_querystring', 'etivite_bp_activity_hashtags_querystring', 11, 2 );

/**
 * Modifies the page title if we're on a hashtag page.
 *
 * @param str $title The unmodified page title
 * @return str If we're on our specialized hashtag screen,
 *  modify the page title to include our hashtag.
 */
function etivite_bp_activity_hashtags_page_title( $title) {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return $title;

	if ( empty( $bp->action_variables[0] ) )
		return $title;

	return apply_filters( 'bp_activity_page_title', 'Activity results for #'. esc_attr( $bp->action_variables[0] ) . $title, esc_attr( $bp->action_variables[0] ) );

}
add_filter( 'wp_title', 'etivite_bp_activity_hashtags_page_title', 99 );

/**
 * Modifies the sitewide activity feed link if we're on a hashtag page.
 *
 * @param str $title The unmodified feed URL
 * @return str If we're on our specialized hashtag screen,
 *  modify the feed URL to use our hashtag feed instead.
 */
function etivite_bp_activity_hashtags_activity_feed_link( $feedurl ) {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return $feedurl;

	if ( empty( $bp->action_variables[0] ) )
		return $feedurl;

	return $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . esc_attr( $bp->action_variables[0] ) . '/feed/';

}
add_filter( 'bp_get_sitewide_activity_feed_link', 'etivite_bp_activity_hashtags_activity_feed_link', 1, 1 );

/**
 * Inject a header if we're on a hashtag page.
 */
function etivite_bp_activity_hashtags_header() {
	global $bp, $bp_unfiltered_uri;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return;

	printf( __( '<h3>Activity results for #%s</h3>', 'bp-activity-hashtags' ), urldecode( $bp->action_variables[0] ) );

}
add_action( 'bp_before_activity_loop', 'etivite_bp_activity_hashtags_header' );

/**
 * Inject a hashtag feed into the <head> if we're on a hashtag page.
 */
function etivite_bp_activity_hashtags_insert_rel_head() {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return false;

	if ( empty( $bp->action_variables[0] ) )
		return false;

	$link = bp_get_activity_hashtags_permalink( esc_attr( $bp->action_variables[0] ) ) . '/feed/';

	echo '<link rel="alternate" type="application/rss+xml" title="'. get_blog_option( BP_ROOT_BLOG, 'blogname' ) .' | '. esc_attr( $bp->action_variables[0] ) .' | Hashtag" href="'. $link .'" />';
}
add_action('bp_head','etivite_bp_activity_hashtags_insert_rel_head');

/** FUNCTIONS ***********************************************************/

/**
 * Returns current activity in the activity loop.
 *
 * Used in the custom hashtag feed.
 * @see etivite_bp_activity_hashtags_action_router()
 */
function etivite_bp_activity_hashtags_current_activity() {
	global $activities_template;
	return $activities_template->current_activity;
}

/**
 * Template tag to return the activity hashtag permalink.
 *
 * @param str $hashtag The hashtag to append to the hashtag permalink.
 * @return str The full activity hashtag permalink
 */
function bp_get_activity_hashtags_permalink( $hashtag = false ) {
	$hashtag = ! empty( $hashtag ) && is_string( $hashtag ) ? trailingslashit( $hashtag ) : '';

	return bp_get_activity_directory_permalink() . constant( "BP_ACTIVITY_HASHTAGS_SLUG" ) . "/" . $hashtag;
}

/**
 * Regex pattern to find hashtags.  Unicode-compatible.
 *
 * Uses the algorithm from the Twitter Regex Abstract Class:
 * {@link https://raw.github.com/nojimage/twitter-text-php/master/lib/Twitter/Regex.php}
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, based
 * on code by {@link http://github.com/mzsanford Matt Sanford} and heavily
 * modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author    Mike Cochrane <mikec@mikenz.geek.nz>
 * @author    Nick Pope <nick@nickpope.me.uk>
 * @copyright Copyright © 2010, Mike Cochrane, Nick Pope
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 */
function bp_activity_hashtags_get_regex() {
	$tmp = array();

	# Expression to match latin accented characters.
	#
	#   0x00C0-0x00D6
	#   0x00D8-0x00F6
	#   0x00F8-0x00FF
	#   0x0100-0x024f
	#   0x0253-0x0254
	#   0x0256-0x0257
	#   0x0259
	#   0x025b
	#   0x0263
	#   0x0268
	#   0x026f
	#   0x0272
	#   0x0289
	#   0x028b
	#   0x02bb
	#   0x0300-0x036f
	#   0x1e00-0x1eff
	#
	# Excludes 0x00D7 - multiplication sign (confusable with 'x').
	# Excludes 0x00F7 - division sign.
	$tmp['latin_accents'] = '\x{00c0}-\x{00d6}\x{00d8}-\x{00f6}\x{00f8}-\x{00ff}';
	$tmp['latin_accents'] .= '\x{0100}-\x{024f}\x{0253}-\x{0254}\x{0256}-\x{0257}';
	$tmp['latin_accents'] .= '\x{0259}\x{025b}\x{0263}\x{0268}\x{026f}\x{0272}\x{0289}\x{028b}\x{02bb}\x{0300}-\x{036f}\x{1e00}-\x{1eff}';

	# Expression to match non-latin characters.
	#
	# Cyrillic (Russian, Ukranian, ...):
	#
	#   0x0400-0x04FF Cyrillic
	#   0x0500-0x0527 Cyrillic Supplement
	#   0x2DE0-0x2DFF Cyrillic Extended A
	#   0xA640-0xA69F Cyrillic Extended B
	$tmp['non_latin_hashtag_chars'] = '\x{0400}-\x{04ff}\x{0500}-\x{0527}\x{2de0}-\x{2dff}\x{a640}-\x{a69f}';
	# Hebrew:
	#
	#   0x0591-0x05bf Hebrew
	#   0x05c1-0x05c2
	#   0x05c4-0x05c5
	#   0x05c7
	#   0x05d0-0x05ea
	#   0x05f0-0x05f4
	#   0xfb12-0xfb28 Hebrew Presentation Forms
	#   0xfb2a-0xfb36
	#   0xfb38-0xfb3c
	#   0xfb3e
	#   0xfb40-0xfb41
	#   0xfb43-0xfb44
	#   0xfb46-0xfb4f
	$tmp['non_latin_hashtag_chars'] .= '\x{0591}-\x{05bf}\x{05c1}-\x{05c2}\x{05c4}-\x{05c5}\x{05c7}\x{05d0}-\x{05ea}\x{05f0}-\x{05f4}';
	$tmp['non_latin_hashtag_chars'] .= '\x{fb12}-\x{fb28}\x{fb2a}-\x{fb36}\x{fb38}-\x{fb3c}\x{fb3e}\x{fb40}-\x{fb41}\x{fb43}-\x{fb44}\x{fb46}-\x{fb4f}';
	# Arabic:
	#
	#   0x0610-0x061a Arabic
	#   0x0620-0x065f
	#   0x066e-0x06d3
	#   0x06d5-0x06dc
	#   0x06de-0x06e8
	#   0x06ea-0x06ef
	#   0x06fa-0x06fc
	#   0x06ff
	#   0x0750-0x077f Arabic Supplement
	#   0x08a0        Arabic Extended A
	#   0x08a2-0x08ac
	#   0x08e4-0x08fe
	#   0xfb50-0xfbb1 Arabic Pres. Forms A
	#   0xfbd3-0xfd3d
	#   0xfd50-0xfd8f
	#   0xfd92-0xfdc7
	#   0xfdf0-0xfdfb
	#   0xfe70-0xfe74 Arabic Pres. Forms B
	#   0xfe76-0xfefc
	$tmp['non_latin_hashtag_chars'] .= '\x{0610}-\x{061a}\x{0620}-\x{065f}\x{066e}-\x{06d3}\x{06d5}-\x{06dc}\x{06de}-\x{06e8}\x{06ea}-\x{06ef}\x{06fa}-\x{06fc}\x{06ff}';
	$tmp['non_latin_hashtag_chars'] .= '\x{0750}-\x{077f}\x{08a0}\x{08a2}-\x{08ac}\x{08e4}-\x{08fe}';
	$tmp['non_latin_hashtag_chars'] .= '\x{fb50}-\x{fbb1}\x{fbd3}-\x{fd3d}\x{fd50}-\x{fd8f}\x{fd92}-\x{fdc7}\x{fdf0}-\x{fdfb}\x{fe70}-\x{fe74}\x{fe76}-\x{fefc}';
	#
	#   0x200c-0x200c Zero-Width Non-Joiner
	#   0x0e01-0x0e3a Thai
	$tmp['non_latin_hashtag_chars'] .= '\x{200c}\x{0e01}-\x{0e3a}';
	# Hangul (Korean):
	#
	#   0x0e40-0x0e4e Hangul (Korean)
	#   0x1100-0x11FF Hangul Jamo
	#   0x3130-0x3185 Hangul Compatibility Jamo
	#   0xA960-0xA97F Hangul Jamo Extended A
	#   0xAC00-0xD7AF Hangul Syllables
	#   0xD7B0-0xD7FF Hangul Jamo Extended B
	#   0xFFA1-0xFFDC Half-Width Hangul
	$tmp['non_latin_hashtag_chars'] .= '\x{0e40}-\x{0e4e}\x{1100}-\x{11ff}\x{3130}-\x{3185}\x{a960}-\x{a97f}\x{ac00}-\x{d7af}\x{d7b0}-\x{d7ff}\x{ffa1}-\x{ffdc}';

	# Expression to match other characters.
	#
	#   0x30A1-0x30FA   Katakana (Full-Width)
	#   0x30FC-0x30FE   Katakana (Full-Width)
	#   0xFF66-0xFF9F   Katakana (Half-Width)
	#   0xFF10-0xFF19   Latin (Full-Width)
	#   0xFF21-0xFF3A   Latin (Full-Width)
	#   0xFF41-0xFF5A   Latin (Full-Width)
	#   0x3041-0x3096   Hiragana
	#   0x3099-0x309E   Hiragana
	#   0x3400-0x4DBF   Kanji (CJK Extension A)
	#   0x4E00-0x9FFF   Kanji (Unified)
	#   0x20000-0x2A6DF Kanji (CJK Extension B)
	#   0x2A700-0x2B73F Kanji (CJK Extension C)
	#   0x2B740-0x2B81F Kanji (CJK Extension D)
	#   0x2F800-0x2FA1F Kanji (CJK supplement)
	#   0x3003          Kanji (CJK supplement)
	#   0x3005          Kanji (CJK supplement)
	#   0x303B          Kanji (CJK supplement)
	$tmp['cj_hashtag_characters'] = '\x{30A1}-\x{30FA}\x{30FC}-\x{30FE}\x{FF66}-\x{FF9F}\x{FF10}-\x{FF19}\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{3041}-\x{3096}\x{3099}-\x{309E}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{3003}\x{3005}\x{303B}\x{020000}-\x{02a6df}\x{02a700}-\x{02b73f}\x{02b740}-\x{02b81f}\x{02f800}-\x{02fa1f}';

	$tmp['hashtag_alpha']        = '[a-z_'.$tmp['latin_accents'].$tmp['non_latin_hashtag_chars'].$tmp['cj_hashtag_characters'].']';
	$tmp['hashtag_alphanumeric'] = '[a-z0-9_'.$tmp['latin_accents'].$tmp['non_latin_hashtag_chars'].$tmp['cj_hashtag_characters'].']';
	$tmp['hashtag_boundary']     = '(?:\A|\z|[^&a-z0-9_'.$tmp['latin_accents'].$tmp['non_latin_hashtag_chars'].$tmp['cj_hashtag_characters'].'])';

	$tmp['hashtag'] = '('.$tmp['hashtag_boundary'].')(#|ï¼ƒ)('.$tmp['hashtag_alphanumeric'].'*'.$tmp['hashtag_alpha'].$tmp['hashtag_alphanumeric'].'*)';

	return '/'.$tmp['hashtag'].'(?=(.*|$))/iu';

}
