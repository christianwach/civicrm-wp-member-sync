<?php
/**
 * Manual Sync template.
 *
 * Main template for the Manual Sync page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.2.8
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/manual-sync.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<?php

	// Get updated query var.
	$updated     = '';
	$updated_raw = filter_input( INPUT_GET, 'updated' );
	if ( ! empty( $updated_raw ) ) {
		$updated = trim( wp_unslash( $updated_raw ) );
	}

	// If we've updated, show message.
	if ( ! empty( $updated ) ) {
		echo '<div id="message" class="updated"><p>' . esc_html__( 'Sync completed.', 'civicrm-wp-member-sync' ) . '</p></div>';
	}

	?>

	<p><?php esc_html_e( 'Synchronize CiviMember Memberships with WordPress Users using the available rules.', 'civicrm-wp-member-sync' ); ?></p>

	<p><?php esc_html_e( 'Because of the way in which Memberships are stored in CiviCRM, you may not see 100% accurate feedback during the sync process. Examples of situations that can affect feedback are: whether or not the Contact associated with a Membership has an email address; whether or not there are multiple Memberships per Contact. Rules will, however, be fully applied by the end of the process.', 'civicrm-wp-member-sync' ); ?></p>

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

			<tr>
				<th scope="row"><?php esc_html_e( 'Selected Memberships', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<p><label class="civi_wp_member_sync_manual_sync_label" for="civi_wp_member_sync_manual_sync_from"><?php esc_html_e( 'From:', 'civicrm-wp-member-sync' ); ?></label> <input type="number" class="settings-text-field small-text" name="civi_wp_member_sync_manual_sync_from" id="civi_wp_member_sync_manual_sync_from" value="" /> <label class="civi_wp_member_sync_manual_sync_label" for="civi_wp_member_sync_manual_sync_to"><?php esc_html_e( '&rarr; To:', 'civicrm-wp-member-sync' ); ?></label> <input type="number" class="settings-text-field small-text" name="civi_wp_member_sync_manual_sync_to" id="civi_wp_member_sync_manual_sync_to" value="" /></p>
					<p class="description"><?php esc_html_e( 'Leave these fields empty to sync all Memberships. In some situations (e.g. to avoid external API rate limits) you may need the sync process to be limited to a certain "block" of Memberships. If so, enter the starting and ending Membership IDs to restrict the sync process.', 'civicrm-wp-member-sync' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Dry Run', 'civicrm-wp-member-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civi_wp_member_sync_manual_sync_dry_run" id="civi_wp_member_sync_manual_sync_dry_run" value="1" checked="checked" />
					<label class="civi_wp_member_sync_manual_sync_label" for="civi_wp_member_sync_manual_sync_dry_run"><?php esc_html_e( 'When this box is checked, no changes will be made and you will get feedback on what would happen.', 'civicrm-wp-member-sync' ); ?></label>
				</td>
			</tr>

		</table>

		<?php if ( 'fgffgs' === get_option( '_civi_wpms_memberships_offset', 'fgffgs' ) ) : ?>
			<?php $button = __( 'Synchronize Now', 'civicrm-wp-member-sync' ); ?>
			<?php $stop = ''; ?>
		<?php else : ?>
			<?php $button = __( 'Continue Sync', 'civicrm-wp-member-sync' ); ?>
			<?php $stop = 'show'; ?>
		<?php endif; ?>

		<p>
			<input type="submit" id="civi_wp_member_sync_manual_sync_submit" name="civi_wp_member_sync_manual_sync_submit" value="<?php echo esc_attr( $button ); ?>" class="button-primary" />
			<?php if ( 'show' === $stop ) : ?>
				<input type="submit" id="civi_wp_member_sync_manual_sync_stop" name="civi_wp_member_sync_manual_sync_stop" value="<?php esc_attr_e( 'Stop Sync', 'civicrm-wp-member-sync' ); ?>" class="button-secondary" />
			<?php endif; ?>
		</p>

		<div id="feedback">

			<hr>

			<div id="progress-bar"><div class="progress-label"></div></div>

			<div id="feedback-results">

				<table cellspacing="0" class="wp-list-table widefat fixed striped">

					<thead>
						<tr>
							<th class="manage-column column-is-new" id="cwms-is-new" scope="col"><?php esc_html_e( 'New', 'civicrm-wp-member-sync' ); ?></th>
							<th class="manage-column column-contact-name" id="cwms-contact-name" scope="col"><?php esc_html_e( 'Contact Name', 'civicrm-wp-member-sync' ); ?></th>
							<th class="manage-column column-username" id="cwms-user-name" scope="col"><?php esc_html_e( 'Username', 'civicrm-wp-member-sync' ); ?></th>
							<th class="manage-column column-member-type" id="cwms-member-type" scope="col"><?php esc_html_e( 'Membership Type', 'civicrm-wp-member-sync' ); ?></th>
							<th class="manage-column column-member-status" id="cwms-member-status" scope="col"><?php esc_html_e( 'Status', 'civicrm-wp-member-sync' ); ?></th>
							<?php

							/**
							 * Allows extra columns to be added after "Status".
							 *
							 * @since 0.5
							 */
							do_action( 'cwms/manual_sync/feedback/th' );

							?>
						</tr>
					</thead>

					<tbody class="cwmp-feedback-list" id="the-comment-list">
					</tbody>

				</table>

			</div>

		</div>

	</form>

</div><!-- /.wrap -->
