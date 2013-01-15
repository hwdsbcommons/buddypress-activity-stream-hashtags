<?php
if ( !defined( 'ABSPATH' ) ) exit;

function etivite_bp_activity_hashtags_filter( $content ) {
	global $bp;
	
	//what are we doing here? - same at atme mentions
	//$pattern = '/[#]([_0-9a-zA-Z-]+)/';
	$pattern = '/(?(?<!color: )(?<!color: )[#]([_0-9a-zA-Z-]+)|(^|\s|\b)[#]([_0-9a-zA-Z-]+))/';

	//unicode support???
	//$pattern = '/(#|\\uFF03)([a-z0-9_\\u00c0-\\u00d6\\u00d8-\\u00f6\\u00f8-\\u00ff]+)/i';
	//$pattern = '/(^|[^0-9A-Z&/]+)(#|\uFF03)([0-9A-Z_]*[A-Z_]+[a-z0-9_\\u00c0-\\u00d6\\u00d8-\\u00f6\\u00f8-\\u00ff]*)/i';
	//the twitter pattern
	//"(^|[^0-9A-Z&/]+)(#|\uFF03)([0-9A-Z_]*[A-Z_]+[a-z0-9_\\u00c0-\\u00d6\\u00d8-\\u00f6\\u00f8-\\u00ff]*)"
	
	preg_match_all( $pattern, $content, $hashtags );
	if ( $hashtags ) {
		/* Make sure there's only one instance of each tag */
		if ( !$hashtags = array_unique( $hashtags[1] ) )
			return $content;

		//but we need to watch for edits and if something was already wrapped in html link - thus check for space or word boundary prior
		foreach( (array)$hashtags as $hashtag ) {
			$pattern = "/(^|\s|\b)#". $hashtag ."($|\b)/";
			$content = preg_replace( $pattern, ' <a href="' . $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . htmlspecialchars( $hashtag ) . '" rel="nofollow" class="hashtag">#'. htmlspecialchars( $hashtag ) .'</a>', $content );
		}
	}
	
	return $content;
}

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

//thanks r-a-y for the snippet
function etivite_bp_activity_hashtags_header() {
	global $bp, $bp_unfiltered_uri;
	
	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return;
	
	printf( __( '<h3>Activity results for #%s</h3>', 'bp-activity-hashtags' ), $bp->action_variables[0] );
	
}
add_action( 'bp_before_activity_loop', 'etivite_bp_activity_hashtags_header' );

function etivite_bp_activity_hashtags_page_title( $title) {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return $title;

	if ( empty( $bp->action_variables[0] ) )
		return $title;

	return apply_filters( 'bp_activity_page_title', 'Activity results for #'. esc_attr( $bp->action_variables[0] ) . $title, esc_attr( $bp->action_variables[0] ) );

}
add_filter( 'wp_title', 'etivite_bp_activity_hashtags_page_title', 99 );

function etivite_bp_activity_hashtags_insert_rel_head() {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return false;

	if ( empty( $bp->action_variables[0] ) )
		return false;
		
	$link = $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . esc_attr( $bp->action_variables[0] ) . '/feed/';

	echo '<link rel="alternate" type="application/rss+xml" title="'. get_blog_option( BP_ROOT_BLOG, 'blogname' ) .' | '. esc_attr( $bp->action_variables[0] ) .' | Hashtag" href="'. $link .'" />';
}
add_action('bp_head','etivite_bp_activity_hashtags_insert_rel_head');


function etivite_bp_activity_hashtags_activity_feed_link( $feedurl ) {
	global $bp;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return $feedurl;

	if ( empty( $bp->action_variables[0] ) )
		return $feedurl;

	return $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . esc_attr( $bp->action_variables[0] ) . '/feed/';

}
add_filter( 'bp_get_sitewide_activity_feed_link', 'etivite_bp_activity_hashtags_activity_feed_link', 1, 1 );

function etivite_bp_activity_hashtags_action_router() {
	global $bp, $wp_query;

	if ( !bp_is_activity_component() || $bp->current_action != BP_ACTIVITY_HASHTAGS_SLUG )
		return false;

	if ( empty( $bp->action_variables[0] ) )
		return false;

	if ( 'feed' == $bp->action_variables[1] ) {
	
		$link = $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . esc_attr( $bp->action_variables[0] );
		$link_self = $bp->root_domain . "/" . $bp->activity->slug . "/". BP_ACTIVITY_HASHTAGS_SLUG ."/" . esc_attr( $bp->action_variables[0] ) . '/feed/';

		$wp_query->is_404 = false;
		status_header( 200 );

		include_once( dirname( __FILE__ ) . '/feeds/bp-activity-hashtags-feed.php' );
		die;
	
	} else {
	
		bp_core_load_template( 'activity/index' );
	
	}
	
}
add_action( 'wp', 'etivite_bp_activity_hashtags_action_router', 3 );


function etivite_bp_activity_hashtags_current_activity() {
	global $activities_template;
	return $activities_template->current_activity;
}
?>
