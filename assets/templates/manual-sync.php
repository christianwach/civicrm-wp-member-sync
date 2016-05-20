<!-- assets/templates/manual_sync.php -->
<div class="wrap">

	<h1 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php _e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php _e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab nav-tab-active"><?php _e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h1>

	<?php

	// if we've updated, show message...
	if ( isset( $_GET['updated'] ) ) {
		echo '<div id="message" class="updated"><p>'.__( 'Sync completed.', 'civicrm-wp-member-sync' ).'</p></div>';
	}

	?>

	<p><?php _e( 'Synchronize CiviMember Memberships with WordPress Users using the available rules.<br> <em>Note:</em> if no association rules exist then no synchronization will take place.', 'civicrm-wp-member-sync' ); ?></p>

	<form method="post" id="civi_wp_member_sync_manual_sync_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Create WordPress Users', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_manual_sync_create" id="civi_wp_member_sync_manual_sync_create" value="1" />
					<label class="civi_wp_member_sync_manual_sync_label" for="civi_wp_member_sync_manual_sync_create"><?php _e( 'Create a WordPress User for each Membership when one does not already exist.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

		</table>

		<div id="progress-bar"><div class="progress-label"></div></div>

		<p><input type="submit" id="civi_wp_member_sync_manual_sync_submit" name="civi_wp_member_sync_manual_sync_submit" value="<?php if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) { _e( 'Synchronize Now', 'civicrm-wp-member-sync' ); } else { _e( 'Continue Sync', 'civicrm-wp-member-sync' ); } ?>" class="button-primary" /><?php if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) {} else { ?> <input type="submit" id="civi_wp_member_sync_manual_sync_stop" name="civi_wp_member_sync_manual_sync_stop" value="<?php _e( 'Stop Sync', 'civicrm-wp-member-sync' ); ?>" class="button-secondary" /><?php } ?></p>

	</form>

</div><!-- /.wrap -->



