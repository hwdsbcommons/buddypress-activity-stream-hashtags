<?php

/** OPTIONS PAGE ********************************************************/

/**
 * Register menu page in admin area.
 */
function etivite_bp_activity_hashtags_admin_add_admin_menu() {
	global $bp;

	if ( ! is_super_admin() ) {
		return;
	}

	add_submenu_page( $bp->admin->settings_page, __( 'Activity Hashtags Admin', 'bp-activity-hashtags' ), __( 'BP Activity Hashtags', 'bp-activity-hashtags' ), 'manage_options', 'bp-activity-hashtags-settings', 'etivite_bp_activity_hashtags_admin' );

	//set up defaults
	$new = Array();
	$new['slug'] = 'tag';
	$new['install_version'] = etivite_plugin_get_version();
	add_option( 'etivite_bp_activity_stream_hashtags', $new );
}
add_action( bp_core_admin_hook(), 'etivite_bp_activity_hashtags_admin_add_admin_menu' );

/**
 * Admin page screen.
 */
function etivite_bp_activity_hashtags_admin() {
	global $bp;

	if ( isset( $_POST['submit'] ) && check_admin_referer('etivite_bp_activity_stream_hashtags_admin') ) {

		$new = Array();

		if( isset( $_POST['ah_tag_slug'] ) && !empty( $_POST['ah_tag_slug'] ) ) {
	        $new['slug'] = $_POST['ah_tag_slug'];
		} else {
			$new['slug'] = false;
		}

		if( isset( $_POST['ah_activity'] ) && !empty( $_POST['ah_activity'] ) && $_POST['ah_activity'] == 1) {
	        $new['blogactivity']['enabled'] = true;
		} else {
			$new['blogactivity']['enabled'] = false;
		}

		if( isset( $_POST['ah_blog'] ) && !empty( $_POST['ah_blog'] ) && $_POST['ah_blog'] == 1) {
	        $new['blogposts']['enabled'] = true;
		} else {
			$new['blogposts']['enabled'] = false;
		}

		bp_update_option( 'etivite_bp_activity_stream_hashtags', $new );

		$updated = true;

	}
?>

	<div class="wrap">
		<h2><?php _e( 'Activity Stream Hastags Admin', 'bp-activity-hashtags' ); ?></h2>

		<?php if ( isset($updated) ) : echo "<div id='message' class='updated fade'><p>" . __( 'Settings updated.', 'bp-activity-hashtags' ) . "</p></div>"; endif;

		$data = bp_get_option( 'etivite_bp_activity_stream_hashtags' );
		?>

		<form action="<?php echo network_admin_url('/admin.php?page=bp-activity-hashtags-settings') ?>" name="groups-autojoin-form" id="groups-autojoin-form" method="post">

			<h4><?php _e( 'Hashtag Base Slug', 'bp-activity-hashtags' ); ?></h4>
			<table class="form-table">
				<tr>
					<th><label for="ah_tag_slug"><?php _e('Slug','bp-activity-hashtags') ?></label></th>
					<td><input type="text" name="ah_tag_slug" id="ah_tag_slug" value="<?php echo $data['slug']; ?>" /></td>
				</tr>
			</table>

			<h4><?php _e( 'Blog Posts/Comments - in Activity Stream', 'bp-restrictgroups' ); ?></h4>

			<table class="form-table">
				<tr>
					<th><label for="ah_activity"><?php _e('Enable hashtags in blog activity stream','bp-restrictgroups') ?></label></th>
					<td><input type="checkbox" name="ah_activity" id="ah_activity" value="1" <?php if ( $data['blogactivity']['enabled'] ) { echo 'checked'; } ?>/></td>
				</tr>
			</table>

			<?php if ( !is_multisite() ) { ?>
				<h4><?php _e( 'Blog Posts/Comments - in Main Blog', 'bp-restrictgroups' ); ?></h4>

				<table class="form-table">
					<tr>
						<th><label for="ah_blog"><?php _e('Enable hashtags on main blog','bp-restrictgroups') ?></label></th>
						<td><input type="checkbox" name="ah_blog" id="ah_blog" value="1" <?php if ( $data['blogposts']['enabled'] ) { echo 'checked'; } ?>/></td>
					</tr>
				</table>
			<?php } ?>

			<?php wp_nonce_field( 'etivite_bp_activity_stream_hashtags_admin' ); ?>

			<?php printf( __( 'You can manage activity hashtags <a href="%s">here</a>.', 'bp-activity-hashtags' ), get_admin_url( bp_get_root_blog_id(), bp_activity_hashtags_get_admin_path() ) ); ?>

			<p class="submit"><input type="submit" name="submit" value="Save Settings"/></p>

		</form>

		<h3>About:</h3>
		<div id="plugin-about" style="margin-left:15px;">

			<p>
			<a href="http://etivite.com/wordpress-plugins/buddypress-activity-stream-hashtags/">Activity Stream Hashtags - About Page</a><br/>
			</p>

			<div class="plugin-author">
				<strong>Author:</strong> <a href="http://profiles.wordpress.org/users/etivite/"><img style="height: 24px; width: 24px;" class="photo avatar avatar-24" src="http://www.gravatar.com/avatar/9411db5fee0d772ddb8c5d16a92e44e0?s=24&amp;d=monsterid&amp;r=g" alt=""> rich @etivite</a><br/>
				<a href="http://twitter.com/etivite">@etivite</a>
			</div>

			<p>
			<a href="http://etivite.com">Author's site</a><br/>
			<a href="http://etivite.com/api-hooks/">Developer Hook and Filter API Reference</a><br/>
			<a href="http://etivite.com/wordpress-plugins/">WordPress Plugins</a><br/>
			</p>
		</div>

	</div>
<?php
}

/**
 * Add "Settings" action link on plugins list table.
 *
 * Stolen from Welcome Pack - thanks, Paul! then stolen from boone.
 */
function etivite_bp_activity_hashtags_admin_add_action_link( $links, $file ) {
	if ( 'buddypress-activity-stream-hashtags/bp-activity-hashtags-loader.php' != $file )
		return $links;

	if ( function_exists( 'bp_core_do_network_admin' ) ) {
		$settings_url = add_query_arg( 'page', 'bp-activity-hashtags-settings', bp_core_do_network_admin() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) );
	} else {
		$settings_url = add_query_arg( 'page', 'bp-activity-hashtags-settings', is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) );
	}

	$settings_link = '<a href="' . $settings_url . '">' . __( 'Settings', 'bp-activity-hashtags' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links', 'etivite_bp_activity_hashtags_admin_add_action_link', 10, 2 );

/** HASHTAGS ************************************************************/

/**
 * Registers "Activity > Hashtags" menu item in the admin area.
 *
 * If on multisite, we need to register the main "Activity" admin page for
 * use on the root blog's admin dashboard as well.  This is done because
 * the main "Activity" admin page is only viewable in the network admin
 * area and in order to register our "Activity > Hashtags" menu item,
 * we'll need the main "Activity" admin page available on the root blog.
 *
 * @todo Investigate multiblog mode some more.
 */
function bp_activity_hashtags_register_menu() {
	// on multisite, register the top level "Activity" admin page in the root blog
	// admin dashboard as well
	if ( is_multisite() && ! bp_is_multiblog_mode() && bp_is_root_blog() ) {
		if ( function_exists( 'bp_activity_add_admin_menu' ) && ! is_network_admin() ) {
			bp_activity_add_admin_menu();
		}
	}

	// @see bp_activity_hashtags_network_admin_redirect()
	if ( is_network_admin() ) {
		$admin_url = 'admin.php?page=bp-activity&amp;bp-hashtags-redirect=1';
	} else {
		$admin_url = bp_activity_hashtags_get_admin_path();
	}

	// register our "Activity > Hashtags" menu item
	add_submenu_page(
		'bp-activity',
		__( 'Activity Hashtags', 'bp-activity-hashtags' ),
		'<span id="bp-activity-hashtags">' . __( 'Hashtags', 'bp-activity-hashtags' ) .'</span>',
		'bp_moderate',
		$admin_url
	);
}
add_action( 'admin_menu',         'bp_activity_hashtags_register_menu' );
add_action( 'network_admin_menu', 'bp_activity_hashtags_register_menu' );

/**
 * Redirect to root blog version of "Activity > Hashtags" from network admin.
 *
 * The network admin area does not have a taxonomy page, so when a super admin
 * clicks on the "Activity > Hashtags" item in the network admin area, this
 * function redirects this request to the root blog's version of the "Activity
 * > Hashtags" page.
 *
 * @see bp_activity_hashtags_tax_menu()
 */
function bp_activity_hashtags_network_admin_redirect() {
	if ( ! is_network_admin() ) {
		return;
	}

	if ( empty( $_GET['bp-hashtags-redirect'] ) ) {
		return;
	}

	wp_redirect( get_admin_url( bp_get_root_blog_id(), bp_activity_hashtags_get_admin_path() ) );
	exit();
}
add_action( 'load-toplevel_page_bp-activity', 'bp_activity_hashtags_network_admin_redirect' );

/**
 * Utility function to return the admin path for the hashtags menu page.
 *
 * @return string
 */
function bp_activity_hashtags_get_admin_path() {
	return 'edit-tags.php?taxonomy=' . bp_activity_hashtags_get_data( 'taxonomy' ) . '&post_type=' . apply_filters( 'bp_activity_hashtags_object_type', 'bp_activity' );
}

/**
 * Highlight the proper top level menu when on the "Activity > Hashtags" page.
 *
 * Since we want to add our hashtags page under the "Activity" menu page, to
 * highlight the "Activity" page, we need to manually filter the
 * "parent_file" so WP knows about this.
 *
 * @param string $parent_file The current parent file
 * @return string
 */
function bp_activity_hashtags_highlight_menu( $parent_file ) {
	global $current_screen, $submenu_file;

	// if taxonomy is our hashtag, set parent file to the "Activity" page
	if ( $current_screen->taxonomy == bp_activity_hashtags_get_data( 'taxonomy' ) ) {
		$parent_file = 'bp-activity';

		// highlight the 'Activity > Hashtags' menu item
		$submenu_file = bp_activity_hashtags_get_admin_path();
	}

	return $parent_file;
}
add_action( 'parent_file', 'bp_activity_hashtags_highlight_menu' );

/**
 * Inject some code into the <head> when on the "Activity > Hashtags" page.
 *
 * We need to do a bit more customization when on our "Hashtags" page.
 *  1) To relabel a column in the taxonomy list table
 *  2) To hide some UI elements that we don't want visible.
 *
 * @see http://wordpress.stackexchange.com/questions/71865/nuance-in-adding-cpt-and-tax-to-a-submenu
 */
function bp_activity_hashtags_admin_head() {
	global $current_screen, $wp_post_types;

	// Not our taxonomy? stop now!
	if( bp_activity_hashtags_get_data( 'taxonomy' ) != $current_screen->taxonomy ) {
        	return;
	}

	// Check if we're on the edit tags page
	if ( 'edit-tags' != $current_screen->base ) {
		return;
	}

	// Since our post type doesn't really exist, we need to fool WP into thinking
	// it really exists to avoid notices. So the following is a little tomfoolery!
	$faux_post_type = apply_filters( 'bp_activity_hashtags_object_type', 'activity' );
	$current_screen->post_type = $faux_post_type;

	$wp_post_types[$faux_post_type] = new stdClass;
	$wp_post_types[$faux_post_type]->show_ui = true;
	$wp_post_types[$faux_post_type]->labels  = new stdClass;
	$wp_post_types[$faux_post_type]->labels->name = __( 'Items', 'bp-activity-hashtags' );

	// hide various elements on the hashtags taxonomy page
?>

	<style type="text/css">
		#wpbody-content .form-wrap, label[for=description-hide], #description-hide, .column-description {display:none;}
	</style>

<?php
}
add_action( 'admin_head-edit-tags.php', 'bp_activity_hashtags_admin_head' );

/**
 * Remove some row actions on the "Activity > Hashtags" admin page.
 *
 * Hashtags do not need to be edited since these items do not require
 * much data.  So we remove the "Edit" and "Quick Edit" items here.
 */
function bp_activity_hashtags_remove_row_actions( $actions ) {
	// remove ability to edit
	unset( $actions['edit'] );

	// remove ability to quick edit
	unset( $actions['inline hide-if-no-js'] );

	return $actions;
}
add_filter( bp_activity_hashtags_get_data( 'taxonomy' ) . '_row_actions', 'bp_activity_hashtags_remove_row_actions' );

/**
 * Add a description to the "Activity > Hashtags" admin page.
 */
function bp_activity_hashtags_admin_description() {
?>
	<p class="description"><?php _e('<strong>Note:</strong><br />Deleting a hashtag does not remove the corresponding activity item, only the hashtag term is removed from the database.  You should only delete hashtags when there are no posts attached to it.' ) ?></p>

<?php
}
add_action( 'after-' . bp_activity_hashtags_get_data( 'taxonomy' ) . '-table', 'bp_activity_hashtags_admin_description' );
