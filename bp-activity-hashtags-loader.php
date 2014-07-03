<?php
/*
Plugin Name: BuddyPress Activity Stream Hashtags
Plugin URI: http://wordpress.org/extend/plugins/buddypress-activity-stream-hashtags/
Description: Enable #hashtags linking within activity stream content - converts before database.
Author: rich @etiviti, r-a-y
Author URI: http://etivite.com
License: GNU GENERAL PUBLIC LICENSE 3.0 http://www.gnu.org/licenses/gpl.txt
Version: 0.6-bleeding
Text Domain: bp-activity-hashtags
Network: true
*/

//TODO - We really need unicode support =) For example �#tag� works ok, but �#?????� � nope.
//TODO - support post db content filter rewrite on #tag

function etivite_bp_activity_hashtags_init() {

	if ( !bp_is_active( 'activity' ) )
		return;

	if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
		load_textdomain( 'bp-activity-hashtags', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );

	$data = bp_get_option( 'etivite_bp_activity_stream_hashtags' );

	if ( empty( $data['slug'] ) ) {
		$data['slug'] = 'hashtag';
	}

	//if you want to change up the /activity/tag/myhashtag
	if ( !defined( 'BP_ACTIVITY_HASHTAGS_SLUG' ) )
		define( 'BP_ACTIVITY_HASHTAGS_SLUG', $data['slug'] );

	require( dirname( __FILE__ ) . '/bp-activity-hashtags.php' );

	//same set used for atme mentions
	add_filter( 'bp_activity_comment_content', 'etivite_bp_activity_hashtags_filter' );
	add_filter( 'bp_activity_new_update_content', 'etivite_bp_activity_hashtags_filter' );
	add_filter( 'group_forum_topic_text_before_save', 'etivite_bp_activity_hashtags_filter' );
	add_filter( 'group_forum_post_text_before_save', 'etivite_bp_activity_hashtags_filter' );
	add_filter( 'groups_activity_new_update_content', 'etivite_bp_activity_hashtags_filter' );

	//what about blog posts in the activity stream
	if ( ! empty( $data['blogactivity']['enabled'] ) ) {
		add_filter( 'bp_blogs_activity_new_post_content', 'etivite_bp_activity_hashtags_filter' );
		add_filter( 'bp_blogs_activity_new_comment_content', 'etivite_bp_activity_hashtags_filter' );
	}

	//what about general blog posts/comments?
	if ( ! empty( $data['blogposts']['enabled'] ) ) {
		add_filter( 'get_comment_text' , 'etivite_bp_activity_hashtags_filter', 9999 );
		add_filter( 'the_content', 'etivite_bp_activity_hashtags_filter', 9999 );
	}

	//support edit activity stream plugin
	add_filter( 'bp_edit_activity_action_edit_content', 'etivite_bp_activity_hashtags_filter' );

	//ignore this - if we wanted to filter after - this would be it
	//but then we can't search by the #hashtag via search_terms (since the trick is the ending </a>)
	//as the search_term uses LIKE %%term%% so we would match #child #children
	//add_filter( 'bp_get_activity_content_body', 'etivite_bp_activity_hashtags_filter' );

	// Add the component's administration tab under the "BuddyPress" menu for site administrators
	if ( is_admin() ) {
		add_action( 'init', create_function( '', "
			require ( dirname( __FILE__ ) . '/admin/bp-activity-hashtags-admin.php' );
		" ) );
	}

}
add_action( 'bp_include', 'etivite_bp_activity_hashtags_init', 88 );
//add_action( 'bp_init', 'etivite_bp_activity_hashtags_init', 88 );

/**
 * Get version number of the plugin.
 */
function etivite_plugin_get_version() {
	$plugin_data = get_plugin_data( __FILE__ );
	return $plugin_data['Version'];
}
