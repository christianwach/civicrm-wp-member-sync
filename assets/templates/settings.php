<?php
/**
 * Settings template.
 *
 * Main template for the Settings page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/settings.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>" class="nav-tab"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<?php if ( $this->is_network_activated() && 'true' === $updated ) : ?>
		<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible">
			<p><strong><?php esc_html_e( 'Settings saved.', 'civicrm-wp-member-sync' ); ?></strong></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'civicrm-wp-member-sync' ); ?></span>
			</button>
		</div>
	<?php endif; ?>

	<form method="post" id="civi_wp_member_sync_settings_form" action="<?php echo esc_url( $this->admin_form_url_get() ); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_settings_action', 'civi_wp_member_sync_nonce' ); ?>

		<h3><?php esc_html_e( 'Synchronization Method', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php esc_html_e( 'Select whether you want CiviCRM Member Sync to synchronize CiviCRM Memberships to WordPress Roles or WordPress Capabilities. If, for example, you need your WordPress User Roles to be independent of Membership Status, then choose Capabilities.', 'civicrm-wp-member-sync' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row">
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_method"><?php esc_html_e( 'Choose Method', 'civicrm-wp-member-sync' ); ?></label>
				</th>
				<td>
					<select class="settings-select" name="civi_wp_member_sync_settings_method" id="civi_wp_member_sync_settings_method">
						<option value="capabilities"<?php selected( 'capabilities', $method ); ?>><?php esc_html_e( 'Capabilities', 'civicrm-wp-member-sync' ); ?></option>
						<option value="roles"<?php selected( 'roles', $method ); ?>><?php esc_html_e( 'Roles', 'civicrm-wp-member-sync' ); ?></option>
					</select>
				</td>
			</tr>

		</table>

		<hr />

		<h3><?php esc_html_e( 'Synchronization Events', 'civicrm-wp-member-sync' ); ?></h3>

		<p><?php esc_html_e( 'The most common trigger for synchronization of CiviCRM Memberships and WordPress Users is when CiviCRM cron runs. If you want to enable additional events that CiviCRM Member Sync will use to trigger synchronization, select them below. If you choose User login/logout, you will have to run "Manual Synchronize" after you create a new rule for it to be applied to all Users and Contacts. Leave the default settings if you are unsure which methods to use.', 'civicrm-wp-member-sync' ); ?></p>

		<?php if ( false === $cau_present ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening anchor tag, 2: Closing anchor tag, 3: Opening em tag, 4: Closing em tag */
						esc_html__( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to install %1$sCiviCRM Admin Utilities%2$s version 0.6.8 or greater. Make sure the checkbox labelled %3$s&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;%4$s is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ),
						'<a href="https://wordpress.org/plugins/civicrm-admin-utilities/">',
						'</a>',
						'<em>',
						'</em>'
					);

					?>
				</p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening em tag, 2: Closing em tag */
						esc_html__( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; %1$safter%2$s CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ),
						'<em>',
						'</em>'
					);

					?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( true === $cau_present && false === $cau_version_ok ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening anchor tag, 2: Closing anchor tag, 3: Opening em tag, 4: Closing em tag */
						esc_html__( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to upgrade %1$sCiviCRM Admin Utilities%2$s to version 0.6.8 or higher. When you have done this, make sure the checkbox labelled %3$s&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;%4$s is checked so that Contacts that have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ),
						'<a href="https://wordpress.org/plugins/civicrm-admin-utilities/">',
						'</a>',
						'<em>',
						'</em>'
					);

					?>
				</p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening em tag, 2: Closing em tag */
						esc_html__( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; %1$safter%2$s CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ),
						'<em>',
						'</em>'
					);

					?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( true === $cau_present && true === $cau_version_ok && false === $cau_configured ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Important Fix', 'civicrm-wp-member-sync' ); ?></strong></p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening anchor tag, 2: Closing anchor tag, 3: Opening em tag, 4: Closing em tag */
						esc_html__( 'In order to sync Contacts in CiviCRM that have been &#8220;soft deleted&#8221; (moved to the Trash but not fully deleted) you will need to visit the CiviCRM Admin Utilities %1$sSettings page%2$s and make sure the checkbox labelled %3$s&#8217;Check this to fix the Contact &#8220;soft delete&#8221; process&#8216;%4$s is checked so that Contacts which have been &#8220;soft deleted&#8221; continue to have their matching WordPress User&#8216;s status updated.', 'civicrm-wp-member-sync' ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'<a href="' . $cau_link . '">',
						'</a>',
						'<em>',
						'</em>'
					);

					?>
				</p>
				<p>
					<?php

					echo sprintf(
						/* translators: 1: Opening em tag, 2: Closing em tag */
						esc_html__( 'Note that this fix only applies to Contacts which have been &#8220;soft deleted&#8221; %1$safter%2$s CiviCRM Admin Utilities has been properly configured.', 'civicrm-wp-member-sync' ),
						'<em>',
						'</em>'
					);

					?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table">

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Login and Logout', 'civicrm-wp-member-sync' ); ?>
				</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_login" id="civi_wp_member_sync_settings_login" value="1"<?php checked( 1, $login ); ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_login"><?php esc_html_e( 'Synchronize whenever a User logs in or logs out. This action is performed only on the User logging in or out.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'CiviCRM Admin', 'civicrm-wp-member-sync' ); ?>
				</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_civicrm" id="civi_wp_member_sync_settings_civicrm" value="1"<?php checked( 1, $civicrm ); ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_civicrm"><?php esc_html_e( 'Synchronize when Membership is updated in CiviCRM admin pages.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Scheduled Events', 'civicrm-wp-member-sync' ); ?>
				</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_schedule" id="civi_wp_member_sync_settings_schedule" value="1"<?php checked( 1, $schedule ); ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_schedule"><?php esc_html_e( 'Synchronize using a recurring schedule. This action is performed on all Users and Contacts.', 'civicrm-wp-member-sync' ); ?></label>
					<p class="description"><?php esc_html_e( 'This action can be very processor intensive if you have a lot of Users and Contacts. It is not recommended to have this switched on unless you have a good reason for doing so. Please note that this action is likely to be removed in future versions.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_interval"><?php esc_html_e( 'Schedule Interval', 'civicrm-wp-member-sync' ); ?></label>
				</th>
				<td>
					<select class="settings-select" name="civi_wp_member_sync_settings_interval" id="civi_wp_member_sync_settings_interval">
						<?php foreach ( $schedules as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $interval, $key ); ?>><?php echo esc_html( $value['display'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

		</table>

		<hr />

		<h3><?php esc_html_e( 'Other Settings', 'civicrm-wp-member-sync' ); ?></h3>

		<table class="form-table">

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Synced Contact Types', 'civicrm-wp-member-sync' ); ?>
				</th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_settings_types" id="civi_wp_member_sync_settings_types" value="1"<?php checked( 1, $types ); ?> />
					<label class="civi_wp_member_sync_settings_label" for="civi_wp_member_sync_settings_types"><?php esc_html_e( 'Synchronize Individuals only.', 'civicrm-wp-member-sync' ); ?></label>
					<p class="description"><?php esc_html_e( 'In versions of CiviCRM Member Sync prior to 0.3.5, all CiviCRM Memberships were synchronized to WordPress Users. This meant that Organisations and Households also had corresponding WordPress Users. If you want to restrict syncing to Individuals only, then check the box below.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

		</table>

		<hr />

		<p class="submit">
			<input class="button-primary" type="submit" id="civi_wp_member_sync_settings_submit" name="civi_wp_member_sync_settings_submit" value="<?php esc_attr_e( 'Save Changes', 'civicrm-wp-member-sync' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->
