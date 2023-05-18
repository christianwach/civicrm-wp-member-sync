<?php
/**
 * Roles Add Rule template.
 *
 * Main template for for the "Add Rule" page when syncing Roles.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/rule-role-add.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<h3><?php esc_html_e( 'Add Association Rule', 'civicrm-wp-member-sync' ); ?> <a class="add-new-h2" href="<?php echo $urls['list']; ?>"><?php esc_html_e( 'Cancel', 'civicrm-wp-member-sync' ); ?></a></h3>

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

	<p><?php esc_html_e( 'Choose one or more CiviMember Membership Types and a WordPress Role below. This will associate those Membership Types with the WordPress Role.', 'civicrm-wp-member-sync' ); ?></p>

	<form method="post" id="civi_wp_member_sync_rules_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr class="form-field form-required">
				<th scope="row">
					<label class="civi_member_type_id_label" for="civi_member_type_id"><?php esc_html_e( 'Select CiviMember Membership Type(s)', 'civicrm-wp-member-sync' ); ?> *</label>
				</th>
				<td>
					<select name="civi_member_type_id[]" id="civi_member_type_id" class ="required required-type" multiple="multiple" style="min-width: 240px;">
						<?php foreach ( $membership_types as $key => $value ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><label class="current_label" for="current"><?php esc_html_e( 'Current Status', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
				<?php foreach ( $status_rules as $key => $value ) { ?>
					<input type="checkbox" class="required-current current-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>" />
					<label for="<?php echo esc_attr( 'current[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
				<?php } ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row"><label class="wp_role_label" for="current_wp_role"><?php esc_html_e( 'Select a WordPress Current Role', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<select name="current_wp_role" id="current_wp_role" class="required required-role">
						<option value="">- <?php esc_html_e( 'Select Role', 'civicrm-wp-member-sync' ); ?> -</option>
						<?php foreach ( $roles as $key => $value ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<?php

			/**
			 * Allows extra rows to be added.
			 *
			 * @since 0.3.9
			 *
			 * @param array $status_rules The status rules.
			 */
			do_action( 'civi_wp_member_sync_role_add_after_current', $status_rules );

			?>

			<tr>
				<th scope="row"><label class="expire_label" for="expire"><?php esc_html_e( 'Expire Status', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
				<?php foreach ( $status_rules as $key => $value ) { ?>
					<input type="checkbox" class="required-expire expire-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>" />
					<label for="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
				<?php } ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row"><label class="expire_assign_wp_role_label" for="expire_assign_wp_role"><?php esc_html_e( 'Select a WordPress Expiry Role', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<select name="expire_assign_wp_role" id ="expire_assign_wp_role" class ="required required-role">
						<option value="">- <?php esc_html_e( 'Select Role', 'civicrm-wp-member-sync' ); ?> -</option>
						<?php foreach ( $roles as $key => $value ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<?php

			/**
			 * Allows extra rows to be added.
			 *
			 * @since 0.3.9
			 *
			 * @param array $status_rules The status rules.
			 */
			do_action( 'civi_wp_member_sync_role_add_after_expiry', $status_rules );

			?>

		</table>

		<input type="hidden" id="civi_wp_member_sync_rules_mode" name="civi_wp_member_sync_rules_mode" value="add" />
		<input type="hidden" id="civi_wp_member_sync_rules_multiple" name="civi_wp_member_sync_rules_multiple" value="yes" />

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_rules_submit" name="civi_wp_member_sync_rules_submit" value="<?php esc_attr_e( 'Add Association Rule', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->
