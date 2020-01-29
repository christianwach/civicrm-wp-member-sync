<!-- assets/templates/settings.php -->
<div class="wrap">

	<h1 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h1>

	<?php

	// If we've updated, show message.
	if ( $this->is_network_activated() AND isset( $_GET['updated'] ) AND $_GET['updated'] == 'true' ) {
		echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">' .
				'<p><strong>' . esc_html__( 'Settings saved.', 'civicrm-wp-member-sync' ) . '</strong></p>' .
				'<button type="button" class="notice-dismiss">' .
					'<span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'civicrm-wp-member-sync' ) . '</span>' .
				'</button>' .
			 '</div>';
	}

	?>

	<form method="post" id="civi_wp_member_sync_settings_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' ); ?>

		<h3><?php esc_html_e( 'Synchronization Method', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php esc_html_e( 'Select whether you want CiviCRM WordPress Member Sync to synchronize CiviCRM Memberships to WordPress Roles or WordPress Capabilities. If, for example, you need your WordPress user roles to be independent of membership status, then choose Capabilities.', 'civicrm-wp-member-sync' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_method"><?php esc_html_e( 'Choose Method', 'civicrm-wp-member-sync' ); ?></label></th>
				<td>
					<select class="settings-select" name="civi_wp_member_sync_settings_method" id ="civi_wp_member_sync_settings_method">
						<?php

						$selected = '';
						if ( ! isset( $method ) OR $method == 'capabilities' ) {
							$selected = ' selected="selected"';
						}

						?>
						<option value="capabilities"<?php echo $selected; ?>><?php esc_html_e( 'Capabilities', 'civicrm-wp-member-sync' ); ?></option>
						<?php

						$selected = '';
						if ( isset( $method ) AND $method == 'roles' ) {
							$selected = ' selected="selected"';
						}

						?>
						<option value="roles"<?php echo $selected; ?>><?php esc_html_e( 'Roles', 'civicrm-wp-member-sync' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row"><label class="wp_role_label" for="civi_wp_member_sync_settings_default_wp_role"><?php esc_html_e( 'Select a WordPress Default Role', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<select name="civi_wp_member_sync_settings_default_wp_role" id="civi_wp_member_sync_settings_default_wp_role" class="required required-role">
						<option value=""></option>
						<?php foreach( $roles AS $key => $value ) { ?>
							<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
					<p class="description"><?php esc_html_e( 'When the Roles method is selected, this default role will be applied to any membership that does not match the configured or expired rules.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

		</table>

		<hr />

		<h3><?php esc_html_e( 'Synchronization Events', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php esc_html_e( 'Select which events CiviCRM WordPress Member Sync will use to trigger synchronization of CiviCRM Memberships and WordPress Users. If you choose user login/logout, you will have to run "Manual Synchronize" after you create a new rule for it to be applied to all users and contacts. Leave the default settings if you are unsure which methods to use.', 'civicrm-wp-member-sync' ); ?></p>

		<?php if ( $cau_present === false ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p><?php _e( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to install <a href="https://wordpress.org/plugins/civicrm-admin-utilities/">CiviCRM Admin Utilities</a> version 0.6.8 or greater. Make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ); ?></p>
				<p><?php echo sprintf( __( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ), $cau_link ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $cau_present === true AND $cau_version_ok === false ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p><?php _e( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to upgrade <a href="https://wordpress.org/plugins/civicrm-admin-utilities/">CiviCRM Admin Utilities</a> to version 0.6.8 or higher. When you have done this, make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ); ?></p>
				<p><?php echo sprintf( __( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ), $cau_link ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $cau_present === true AND $cau_version_ok === true AND $cau_configured === false ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p><?php echo sprintf( __( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to visit the CiviCRM Admin Utilities <a href="%s">Settings page</a> and make sure the checkbox labelled <em>&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;</em> is checked so that Contacts which have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ), $cau_link ); ?></p>
				<p><?php echo sprintf( __( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; <em>after</em> CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ), $cau_link ); ?></p>
			</div>
		<?php endif; ?>

		<table class="form-table">

			<tr>
				<th scope="row"><?php esc_html_e( 'Login and Logout', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $login ) AND $login === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_login" id="civi_wp_member_sync_settings_login" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_login"><?php esc_html_e( 'Synchronize whenever a user logs in or logs out. This action is performed only on the user logging in or out.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'CiviCRM Admin', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $civicrm ) AND $civicrm === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_civicrm" id="civi_wp_member_sync_settings_civicrm" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_civicrm"><?php esc_html_e( 'Synchronize when membership is updated in CiviCRM admin pages.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Scheduled Events', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Checked by default.
					$checked = ' checked="checked"';
					if ( isset( $schedule ) AND $schedule === 0 ) {
						$checked = '';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_schedule" id="civi_wp_member_sync_settings_schedule" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_schedule"><?php esc_html_e( 'Synchronize using a recurring schedule. This action is performed on all users and contacts.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_interval"><?php esc_html_e( 'Schedule Interval', 'civicrm-wp-member-sync' ); ?></label></th>
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

		<h3><?php esc_html_e( 'Other Settings', 'civicrm-wp-member-sync' ); ?></h3>

		<table class="form-table">

			<tr>
				<th scope="row"><?php esc_html_e( 'Synced Contact Types', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<?php

					// Unchecked by default.
					$checked = '';
					if ( isset( $types ) AND $types === 1 ) {
						$checked = ' checked="checked"';
					}

					?><input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_types" id="civi_wp_member_sync_settings_types" value="1"<?php echo $checked; ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_types"><?php esc_html_e( 'Synchronize Individuals only.', 'civicrm-wp-member-sync' ); ?></label>
					<p class="description"><?php esc_html_e( 'In versions of CiviCRM WordPress Member Sync prior to 0.3.5, all CiviCRM Memberships were synchronized to WordPress Users. This meant that Organisations and Households also had corresponding WordPress Users. If you want to restrict syncing to Individuals only, then check the box below.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

		</table>

		<hr />

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_settings_submit" name="civi_wp_member_sync_settings_submit" value="<?php esc_attr_e( 'Save Changes', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->



