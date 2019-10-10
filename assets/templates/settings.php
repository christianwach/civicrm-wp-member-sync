<!-- assets/templates/settings.php -->
<div class="wrap">

	<h1 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab nav-tab-active"><?php _e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php _e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php _e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h1>

	<?php

	// If we've updated, show message.
	if ( $this->is_network_activated() AND isset( $_GET['updated'] ) AND $_GET['updated'] == 'true' ) {
		echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">' .
				'<p><strong>' . __( 'Settings saved.', 'civicrm-wp-member-sync' ) . '</strong></p>' .
				'<button type="button" class="notice-dismiss">' .
					'<span class="screen-reader-text">' . __( 'Dismiss this notice.', 'civicrm-wp-member-sync' ) . '</span>' .
				'</button>' .
			 '</div>';
	}

	?>

	<form method="post" id="civi_wp_member_sync_settings_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' ); ?>

		<h3><?php _e( 'Synchronization Method', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php _e( 'Select whether you want CiviCRM WordPress Member Sync to synchronize CiviCRM Memberships to WordPress Roles or WordPress Capabilities. If, for example, you need your WordPress user roles to be independent of membership status, then choose Capabilities.', 'civicrm-wp-member-sync' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_method"><?php _e( 'Choose Method', 'civicrm-wp-member-sync' ); ?></label></th>
				<td>
					<select class="settings-select" name="civi_wp_member_sync_settings_method" id ="civi_wp_member_sync_settings_method">
						<?php

						$selected = '';
						if ( ! isset( $method ) OR $method == 'capabilities' ) {
							$selected = ' selected="selected"';
						}

						?>
						<option value="capabilities"<?php echo $selected; ?>><?php _e( 'Capabilities', 'civicrm-wp-member-sync' ); ?></option>
						<?php

						$selected = '';
						if ( isset( $method ) AND $method == 'roles' ) {
							$selected = ' selected="selected"';
						}

						?>
						<option value="roles"<?php echo $selected; ?>><?php _e( 'Roles', 'civicrm-wp-member-sync' ); ?></option>
					</select>
				</td>
			</tr>

		</table>

		<hr />

		<h3><?php _e( 'Synchronization Events', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php _e( 'Select which events CiviCRM WordPress Member Sync will use to trigger synchronization of CiviCRM Memberships and WordPress Users. If you choose user login/logout, you will have to run "Manual Synchronize" after you create a new rule for it to be applied to all users and contacts. Leave the default settings if you are unsure which methods to use.', 'civicrm-wp-member-sync' ); ?></p>

		<?php if ( $cau_present === false ) : ?>
			<div class="notice notice-warning inline">
				<p><?php _e( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to install <a href="https://wordpress.org/plugins/civicrm-admin-utilities/">CiviCRM Admin Utilities</a> version 0.6.8 or greater. Make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated. Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been installed and configured.', 'civicrm-wp-member-sync' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $cau_present === true AND $cau_version_ok === false ) : ?>
			<div class="notice notice-warning inline">
				<p><?php _e( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to upgrade <a href="https://wordpress.org/plugins/civicrm-admin-utilities/">CiviCRM Admin Utilities</a> to version 0.6.8 or higher. When you have done this, make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated. Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been upgraded and configured.', 'civicrm-wp-member-sync' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $cau_present === true AND $cau_version_ok === true AND $cau_configured === false ) : ?>
			<div class="notice notice-warning inline">
				<p><?php echo sprintf( __( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to visit the CiviCRM Admin Utilities <a href="%s">Settings page</a> and make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts which have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated. Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ), $cau_link ); ?></p>
			</div>
		<?php endif; ?>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Login and Logout', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $login ) AND $login === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_login" id="civi_wp_member_sync_settings_login" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_login"><?php _e( 'Synchronize whenever a user logs in or logs out. This action is performed only on the user logging in or out.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e( 'CiviCRM Admin', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $civicrm ) AND $civicrm === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_civicrm" id="civi_wp_member_sync_settings_civicrm" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_civicrm"><?php _e( 'Synchronize when membership is updated in CiviCRM admin pages.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php _e( 'Scheduled Events', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $schedule ) AND $schedule === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_schedule" id="civi_wp_member_sync_settings_schedule" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_schedule"><?php _e( 'Synchronize using a recurring schedule. This action is performed on all users and contacts.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_interval"><?php _e( 'Schedule Interval', 'civicrm-wp-member-sync' ); ?></label></th>
				<td>
					<select class="settings-select" name="civi_wp_member_sync_settings_interval" id ="civi_wp_member_sync_settings_interval">
						<?php

						foreach( $schedules AS $key => $value ) {

							$selected = '';
							if ( isset( $interval ) AND $key == $interval ) {
								$selected = ' selected="selected"';
							}

							?><option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $value['display']; ?></option><?php

						}

						?>
					</select>
				</td>
			</tr>

		</table>

		<hr />

		<h3><?php _e( 'Other Settings', 'civicrm-wp-member-sync' ); ?></h3>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Synced Contact Types', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Unchecked by default.
					$checked = '';
					if ( isset( $types ) AND $types === 1 ) {
						$checked = ' checked="checked"';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_types" id="civi_wp_member_sync_settings_types" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_types"><?php _e( 'Synchronize Individuals only.', 'civicrm-wp-member-sync' ); ?></label>
					<p class="description"><?php _e( 'In versions of CiviCRM WordPress Member Sync prior to 0.3.5, all CiviCRM Memberships were synchronized to WordPress Users. This meant that Organisations and Households also had corresponding WordPress Users. If you want to restrict syncing to Individuals only, then check the box below.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

		</table>

		<hr />

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_settings_submit" name="civi_wp_member_sync_settings_submit" value="<?php _e( 'Save Changes', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->



