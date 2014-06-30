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

	// taxonomy name for our hashtags taxonomy
	$bp->activity->hashtags->taxonomy = 'hashtag';

	// save our regex pattern for later reference
	$bp->activity->hashtags->pattern  = bp_activity_hashtags_get_regex();
}
add_action( 'bp_setup_globals', 'bp_activity_hashtags_setup_globals' );

/**
 * Register taxonomy for hashtags.
 */
function bp_activity_hashtags_register_taxonomy() {
	// Setup our taxonomy args
	$args = array(
		'labels' => array(
			'name'          => __( 'Activity Hashtags', 'bp-activity-hashtags' ),
			'singular_name' => __( 'Hashtag',  'bp-activity-hashtags' ),
			'menu_name'     => __( 'Hashtags', 'bp-activity-hashtags' ),
			'search_items'  => __( 'Search Hashtags', 'bp-activity-hashtags' )
		),
		'capabilities' => array(
			'manage_terms' => 'edit_users',
			'edit_terms'   => 'edit_users',
			'delete_terms' => 'edit_users',
			'assign_terms' => 'read',
		),
		'query_var'         => false,
		'rewrite'           => false,
		'show_in_nav_menus' => bp_is_root_blog()
	);

	// register the 'hashtag' taxonomy
	//
	// issues to be aware of:
	// (1) we're attaching this to the 'bp_activity' object type, which doesn't exist
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
		bp_activity_hashtags_get_data( 'taxonomy' ),
		apply_filters( 'bp_activity_hashtags_object_type', 'bp_activity' ),
		apply_filters( 'bp_activity_hashtags_taxonomy_args', $args )
	);
}

add_action( 'bp_setup_globals', 'bp_activity_hashtags_register_taxonomy' );

/** SCREENS *************************************************************/

/**
 * Screen router for activity hashtags.
 *
 * Determines if we're on a hashtag page. If so, sends things along their
 * merry way!
 */
function etivite_bp_activity_hashtags_screen_router() {
	if ( ! bp_is_activity_component() || ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return false;

	if ( ! bp_action_variables() )
		return false;

	if ( bp_is_action_variable( 'feed', 1 ) ) {
		global $wp_query;

		$link      = bp_get_activity_hashtags_permalink( esc_attr( bp_action_variable( 0 ) ) );
		$link_self = $link . '/feed/';

		$wp_query->is_404 = false;
		status_header( 200 );

		include_once( dirname( __FILE__ ) . '/feeds/bp-activity-hashtags-feed.php' );
		die;

	} else {
		// BP 1.7 - add theme compat
		if ( class_exists( 'BP_Theme_Compat' ) ) {
			new BP_Activity_Hashtags_Theme_Compat();
		}

		bp_core_load_template( 'activity/index' );

	}

}
add_action( 'bp_screens', 'etivite_bp_activity_hashtags_screen_router' );

/**
 * The main theme compat class for BP Activity Hashtags
 *
 * This class sets up the necessary theme compatability actions to safely output
 * the template part to the_title and the_content areas of a theme.
 */
class BP_Activity_HashTags_Theme_Compat {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'setup_theme_compat' ) );
	}

	/**
	 * Setup theme compatibility for BP Activity Hashtags
	 */
	public function setup_theme_compat() {

		add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'dummy_post' ) );
		add_filter( 'bp_replace_the_content',                    array( $this, 'content'    ) );
	}

	/**
	 * Update the global $post with dummy data
	 */
	public function dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'Sitewide Activity', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_activity',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the activity index template part
	 */
	public function content() {
		bp_buffer_template_part( 'activity/index' );
	}

}

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

	// save hashtags for later reference so we don't have to parse again
	$bp->activity->hashtags->temp = array();

	return preg_replace_callback(
		bp_activity_hashtags_get_data( 'pattern' ),
		'bp_activity_hashtags_autolink',
		$content
	);
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
	wp_set_object_terms( $activity->id, (array) $bp->activity->hashtags->temp, bp_activity_hashtags_get_data( 'taxonomy' ) );

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
		wp_delete_object_term_relationships( $id, bp_activity_hashtags_get_data( 'taxonomy' ) );
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
	if ( ! bp_is_activity_component() )
		return $query_string;

	if ( ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return $query_string;

	if ( ! bp_action_variables() )
		return $query_string;

	if ( strpos( $query_string, 'scope=tag' ) === false )
		return $query_string;

	if ( bp_is_action_variable( 'feed', 1 ) )
		return $query_string;

	if ( strlen( $query_string ) < 1 )
		return 'display_comments=true&search_terms=#'. bp_action_variable( 0 ) . '<';

	/* Now pass the querystring to override default values. */
	$query_string .= '&display_comments=true&search_terms=#'. bp_action_variable( 0 ) . '<';

	return $query_string;
}
add_filter( 'bp_ajax_querystring', 'etivite_bp_activity_hashtags_querystring', 11, 2 );

/**
 * Set scope on 'Hashtags' tab when on activity directory.
 */
function bp_activity_hashtags_set_scope() {
	// activity directory
	if ( ! bp_displayed_user_id() && bp_is_activity_component() && !bp_current_action() ) {

		$scope = ! empty( $_COOKIE['bp-activity-scope'] ) ? $_COOKIE['bp-activity-scope'] : '';

		// if we're on the activity directory and we last-visited a hashtag page,
		// let's reset the scope to 'all'
		if ( $scope == 'tag' ) {
			// reset the scope to 'all'
			@setcookie( 'bp-activity-scope', 'all', 0, '/' );
		}

	// activity hashtag page
	} elseif ( bp_is_activity_component() && bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) ) {
		$_POST['cookie'] = 'bp-activity-scope%3Dtag%3B%20bp-activity-filter%3D-1';
	
		// reset the scope to 'tag' so our 'Hashtags' tab is highlighted
		@setcookie( 'bp-activity-scope', 'tag', 0, '/' );		

		// reset the dropdown menu to 'Everything'
		@setcookie( 'bp-activity-filter', '-1', 0, '/' );
	}
}
add_action( 'bp_screens', 'bp_activity_hashtags_set_scope', 9 );

/**
 * Modifies the page title if we're on a hashtag page.
 *
 * @param str $title The unmodified page title
 * @return str If we're on our specialized hashtag screen,
 *  modify the page title to include our hashtag.
 */
function etivite_bp_activity_hashtags_page_title( $title) {
	if ( ! bp_is_activity_component() || ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return $title;

	if ( ! bp_action_variables() )
		return $title;

	$title = strip_tags( sprintf( __( '<h3>Activity results for #%s</h3>', 'bp-activity-hashtags' ), urldecode( esc_attr( bp_action_variable( 0 ) ) ) ) . $title );

	return apply_filters( 'bp_activity_page_title', $title, esc_attr( bp_action_variable( 0 ) ) );

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
	if ( ! bp_is_activity_component() || ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return $feedurl;

	if ( ! bp_action_variables() )
		return $feedurl;

	return bp_get_activity_hashtags_permalink( esc_attr( bp_action_variable( 0 ) ) ) . 'feed/';

}
add_filter( 'bp_get_sitewide_activity_feed_link', 'etivite_bp_activity_hashtags_activity_feed_link', 1, 1 );

/**
 * Inject a header if we're on a hashtag page.
 */
function etivite_bp_activity_hashtags_header() {
	if ( ! bp_is_activity_component() )
		return;

	if ( ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return;

	// grab the AJAX querystring
	$qs = ! empty( $_POST['cookie'] ) ? urldecode( $_POST['cookie'] ) : '';

	// not a hashtag page? stop now!
	if ( strpos( $qs, 'bp-activity-scope=tag' ) === false )
		return;

	printf( __( '<h3>Activity results for #%s</h3>', 'bp-activity-hashtags' ), urldecode( esc_attr( bp_action_variable( 0 ) ) ) );

}
add_action( 'bp_before_activity_loop', 'etivite_bp_activity_hashtags_header' );

/**
 * Add 'Hashtags' tab to activity directory.
 */
function bp_activity_hashtags_add_tab() {
	if ( ! bp_is_activity_component() )
		return;

	if ( ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return;
?>

	<li id="activity-tag"><a href="<?php echo bp_get_activity_hashtags_permalink( urldecode( esc_attr( bp_action_variable( 0 ) ) ) ); ?>" ><?php _e( 'Hashtags', 'bp-activity-hashtags' ); ?></a></li>
<?php
}
add_action( 'bp_activity_type_tabs', 'bp_activity_hashtags_add_tab', 0 );

/**
 * Inject a hashtag feed into the <head> if we're on a hashtag page.
 */
function etivite_bp_activity_hashtags_insert_rel_head() {
	if ( ! bp_is_activity_component() || ! bp_is_current_action( BP_ACTIVITY_HASHTAGS_SLUG ) )
		return false;

	if ( ! bp_action_variables() )
		return false;

	$link = bp_get_activity_hashtags_permalink( esc_attr( bp_action_variable( 0 ) ) ) . 'feed/';

	echo '<link rel="alternate" type="application/rss+xml" title="'. get_blog_option( BP_ROOT_BLOG, 'blogname' ) .' | '. esc_attr( bp_action_variable( 0 ) ) .' | Hashtag" href="'. $link .'" />';
}
add_action( 'bp_head', 'etivite_bp_activity_hashtags_insert_rel_head' );

/**
 * Filter the default "Tag Cloud" widget.
 *
 * If using the 'Hashtags' taxonomy for the built-in tag cloud widget, filter
 * the tag permalink to use our activity hashtag permalink.
 *
 * @param mixed $retval Either an array or string of the generated tag cloud.
 * @param mixed $tags If tags exist, an array of tags, else boolean false.
 * @param array $args Tag cloud arguments
 * @return mixed Either an array or string of the generated tag cloud.
 */
function bp_activity_hashtags_tag_cloud_filter( $retval, $tags, $args ) {
	if ( $args['taxonomy'] != bp_activity_hashtags_get_data( 'taxonomy' ) )
		return $retval;

	if ( empty( $tags ) )
		return $retval;

	/** the following is mostly a duplicate of wp_generate_tag_cloud() *****/

	extract( $args );

	$counts = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag->count;
		$counts[ $key ] = $topic_count_scale_callback($tag->count);
	}

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = $largest - $smallest;
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	$a = array();

	foreach ( $tags as $key => $tag ) {
		$count = $counts[ $key ];
		$real_count = $real_counts[ $key ];

		// changed this to use the activity hashtag permalink
		$tag_link = bp_get_activity_hashtags_permalink( $tag->slug );

		// changed tag title to use custom localization
		$tag_title = sprintf( _n( '1 mention', '%s mentions', $real_count, 'bp-activity-hashtags' ), $real_count );

		$tag_id = isset($tags[ $key ]->id) ? $tags[ $key ]->id : $key;
		$tag_name = $tags[ $key ]->name;
		
		$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr__( $tag_title ) . "' style='font-size: " .
			str_replace( ',', '.', ( $smallest + ( ( $count - $min_count ) * $font_step ) ) )
			. "$unit;'>#$tag_name</a>";
	}

	switch ( $format ) :
		case 'array' :
			$return =& $a;
			break;
		case 'list' :
			$return = "<ul class='wp-tag-cloud'>\n\t<li>";
			$return .= join( "</li>\n\t<li>", $a );
			$return .= "</li>\n</ul>\n";
			break;
		default :
			$return = join( $separator, $a );
		break;
	endswitch;

	return $return;
}
add_filter( 'wp_generate_tag_cloud', 'bp_activity_hashtags_tag_cloud_filter', 10, 3 );

/**
 * Filters the term link to use our our activity hashtag permalink.
 *
 * @param string $termlink The term link
 * @param obj $term The WP term object
 * @param string $taxonomy The current taxonomy for the term link.
 */
function bp_activity_hashtags_filter_tag_link( $termlink, $term, $taxonomy ) {
	// we're not on our hashtag taxonomy, so stop!
	if ( $taxonomy != bp_activity_hashtags_get_data( 'taxonomy' ) ) {
		return $termlink;
	}

	return bp_get_activity_hashtags_permalink( $term->slug );
}
add_filter( 'term_link', 'bp_activity_hashtags_filter_tag_link', 10, 3 );

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
 * Returns a variable from our activity hashtags data object.
 *
 * @param str Name of variable we want to grab
 * @return mixed
 */
function bp_activity_hashtags_get_data( $variable = false ) {
	if ( empty( $variable ) )
		return false;

	global $bp;

	return ! empty( $bp->activity->hashtags->$variable ) ? $bp->activity->hashtags->$variable : false;
}

/**
 * Callback to auto-link hashtags from activity content.  Unicode-compatible.
 * See {@link etivite_bp_activity_hashtags_filter()}.
 *
 * Derived from the algorithm from the Twitter Autolink::_addLinksToHashtags()
 * method:
 * {@link https://github.com/nojimage/twitter-text-php/blob/master/lib/Twitter/Autolink.php#L460}
 *
 * With small tweaks to prevent having to query the hashtags again.
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
function bp_activity_hashtags_autolink( $matches ) {
	// support both hashtag symbols
	$hash_signs = '#＃';

	// end hashtag pattern
	$end_hashtag_match = '/\A(?:['.$hash_signs.']|:\/\/)/u';

	list( $all, $before, $hash, $tag, $after ) = array_pad( $matches, 5, '' );

	if ( preg_match( $end_hashtag_match, $after )
		|| ( ! preg_match( '!\A["\']!', $before ) && preg_match( '!\A["\']!', $after ) )
		|| preg_match( '!\A</!', $after )
	) {
		return $all;
	}

	global $bp;

	// save hashtag so we don't have to parse them again
	$bp->activity->hashtags->temp[] = $tag;

	$replacement = $before;
	$replacement .= '<a href="' .  bp_get_activity_hashtags_permalink( htmlspecialchars( $tag ) ) . '" rel="nofollow" class="hashtag">' . htmlspecialchars( $hash . $tag ) . '</a>';

	return $replacement;
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
