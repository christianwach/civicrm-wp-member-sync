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

		<input class="button-primary" type="submit" id="civi_wp_member_sync_manual_sync_submit" name="civi_wp_member_sync_manual_sync_submit" value="<?php _e( 'Synchronize Now', 'civicrm-wp-member-sync' ); ?>" />

	</form>

</div><!-- /.wrap -->



