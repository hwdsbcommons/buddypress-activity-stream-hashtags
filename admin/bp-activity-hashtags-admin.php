<?php
/**
 * Register menu page in admin area.
 */
function etivite_bp_activity_hashtags_admin_add_admin_menu() {
	if ( ! is_super_admin() ) {
		return;
	}

	add_submenu_page( 'bp-general-settings', __( 'Activity Hashtags Admin', 'bp-activity-hashtags' ), __( 'Activity Hashtags', 'bp-activity-hashtags' ), 'manage_options', 'bp-activity-hashtags-settings', 'etivite_bp_activity_hashtags_admin' );

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

		update_option( 'etivite_bp_activity_stream_hashtags', $new );

		$updated = true;

	}
?>

	<div class="wrap">
		<h2><?php _e( 'Activity Stream Hastags Admin', 'bp-activity-hashtags' ); ?></h2>

		<?php if ( isset($updated) ) : echo "<div id='message' class='updated fade'><p>" . __( 'Settings updated.', 'bp-activity-hashtags' ) . "</p></div>"; endif;

		$data = get_option( 'etivite_bp_activity_stream_hashtags' );
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
