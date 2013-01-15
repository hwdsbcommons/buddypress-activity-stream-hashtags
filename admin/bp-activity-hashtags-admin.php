<?php
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

		$data = maybe_unserialize( get_option( 'etivite_bp_activity_stream_hashtags' ) );
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
?>
