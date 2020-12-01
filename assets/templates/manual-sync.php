<!-- assets/templates/manual-sync.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<?php

	// If we've updated, show message.
	if ( isset( $_GET['updated'] ) ) {
		echo '<div id="message" class="updated"><p>' . esc_html__( 'Sync completed.', 'civicrm-wp-member-sync' ) . '</p></div>';
	}

	?>

	<p><?php esc_html_e( 'Synchronize CiviMember Memberships with WordPress Users using the available rules.', 'civicrm-wp-member-sync' ); ?></p>

	<p><?php esc_html_e( 'Note: if no association rules exist then no synchronization will take place.', 'civicrm-wp-member-sync' ); ?></p>

	<form method="post" id="civi_wp_member_sync_manual_sync_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_manual_sync_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr>
				<th scope="row"><?php esc_html_e( 'Create WordPress Users', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_manual_sync_create" id="civi_wp_member_sync_manual_sync_create" value="1" />
					<label class="civi_wp_member_sync_manual_sync_label" for="civi_wp_member_sync_manual_sync_create"><?php esc_html_e( 'Create a WordPress User for each Membership when one does not already exist.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

		</table>

		<div id="progress-bar"><div class="progress-label"></div></div>

		<p><input type="submit" id="civi_wp_member_sync_manual_sync_submit" name="civi_wp_member_sync_manual_sync_submit" value="<?php if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) { esc_attr_e( 'Synchronize Now', 'civicrm-wp-member-sync' ); } else { esc_attr_e( 'Continue Sync', 'civicrm-wp-member-sync' ); } ?>" class="button-primary" /><?php if ( 'fgffgs' == get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) {} else { ?> <input type="submit" id="civi_wp_member_sync_manual_sync_stop" name="civi_wp_member_sync_manual_sync_stop" value="<?php esc_attr_e( 'Stop Sync', 'civicrm-wp-member-sync' ); ?>" class="button-secondary" /><?php } ?></p>

	</form>

</div><!-- /.wrap -->



