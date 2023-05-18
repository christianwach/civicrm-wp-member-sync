<?php
/**
 * Capabilities Edit Rule template.
 *
 * Main template for for the "Edit Rule" page when syncing Capabilities.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/rule-cap-edit.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<h3><?php esc_html_e( 'Edit Association Rule', 'civicrm-wp-member-sync' ); ?> <a class="add-new-h2" href="<?php echo $urls['list']; ?>"><?php esc_html_e( 'Cancel', 'civicrm-wp-member-sync' ); ?></a></h3>

	<?php

	// If we've updated, show message (note that this will only display if we have JS turned off).
	if ( isset( $this->errors ) && is_array( $this->errors ) ) {

		// Init messages.
		$error_messages = [];

		// Construct array of messages based on error code.
		foreach ( $this->errors as $error_code ) {
			$error_messages[] = esc_html( $this->error_strings[ $error_code ] );
		}

		// Show them.
		echo '<div id="message" class="error"><p>' . implode( '<br>', $error_messages ) . '</p></div>';

	}

	?>

	<p><?php esc_html_e( 'Edit the CiviMember Membership Type and select the Current and Expired Statuses for it. All statuses must be allocated as either Current or Expired.', 'civicrm-wp-member-sync' ); ?></p>

	<p><?php esc_html_e( 'Current Status adds a Membership Capability to the WordPress User, while Expired Status removes the Membership Capability from the WordPress User. This Capability will be of the form "civimember_ID", where "ID" is the numeric ID of the Membership Type. So, for Membership Type 2, the Capability will be "civimember_2". If you have the "Members" plugin active, then the "restrict_content" Capability will also be added.', 'civicrm-wp-member-sync' ); ?></p>

	<p><?php esc_html_e( 'An additional Membership Status Capability will also be added to the WordPress User that is tied to the status of their Membership. This Capability will be of the form "civimember_ID_NUM", where "ID" is the numeric ID of the Membership Type and "NUM" is the numeric ID of the Membership Status. So, for Membership Type 2 with Membership Status 4, the Capability will be "civimember_2_4".', 'civicrm-wp-member-sync' ); ?></p>

	<form method="post" id="civi_wp_member_sync_rules_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr class="form-field form-required">
				<th scope="row"><label class="civi_member_type_id_label" for="civi_member_type_id"><?php esc_html_e( 'CiviMember Membership Type', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<?php
					/*
					// Round we go...
					foreach ( $membership_types as $key => $value ) {

						if ( isset( $civi_member_type_id ) && $key == $civi_member_type_id ) {
							echo '<p>' . $value . '</p>';
							break;
						}

					}

					} else {
					*/
					?>
						<select name="civi_member_type_id" id="civi_member_type_id" class ="required required-type">
							<?php

							// Round we go...
							foreach ( $membership_types as $key => $value ) {

								$selected = '';
								if ( isset( $civi_member_type_id ) && $key == $civi_member_type_id ) {
									$selected = ' selected="selected"';
								}

								?><option value="<?php echo esc_attr( $key ); ?>"<?php echo $selected; ?>><?php echo esc_html( $value ); ?></option><?php

							}

							?>
						</select>
						<?php

					//}

						?>
				</td>
			</tr>

			<tr>
				<th scope="row"><label class="current_label" for="current"><?php esc_html_e( 'Current Status', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
				<?php

				foreach ( $status_rules as $key => $value ) {

					$checked = '';
					if ( isset( $current_rule ) && ! empty( $current_rule ) ) {
						if ( array_search( $key, $current_rule ) ) {
							$checked = ' checked="checked"';
						}
					}

					?><input type="checkbox" class="required-current current-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>"<?php echo $checked; ?> />
					<label for="<?php echo esc_attr( 'current[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
					<?php

				}

				?>

				</td>
			</tr>

			<?php

			/**
			 * Allows extra rows to be added.
			 *
			 * @since 0.3.9
			 *
			 * @param array $status_rules The status rules.
			 * @param array $selected_rule The rule being edited.
			 */
			do_action( 'civi_wp_member_sync_cap_edit_after_current', $status_rules, $selected_rule );

			?>

			<tr>
				<th scope="row"><label class="expire_label" for="expire"><?php esc_html_e( 'Expire Status', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
				<?php

				foreach ( $status_rules as $key => $value ) {

					$checked = '';
					if ( isset( $expiry_rule ) && ! empty( $expiry_rule ) ) {
						if ( array_search( $key, $expiry_rule ) ) {
							$checked = ' checked="checked"';
						}
					}

					?><input type="checkbox" class="required-expire expire-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>"<?php echo $checked; ?> />
					<label for="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
					<?php

				}

				?>
				</td>
			</tr>

			<?php

			/**
			 * Allows extra rows to be added.
			 *
			 * @since 0.3.9
			 *
			 * @param array $status_rules The status rules.
			 * @param array $selected_rule The rule being edited.
			 */
			do_action( 'civi_wp_member_sync_cap_edit_after_expiry', $status_rules, $selected_rule );

			?>

		</table>

		<input type="hidden" id="civi_wp_member_sync_rules_mode" name="civi_wp_member_sync_rules_mode" value="edit" />

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_rules_submit" name="civi_wp_member_sync_rules_submit" value="<?php esc_attr_e( 'Save Association Rule', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->
