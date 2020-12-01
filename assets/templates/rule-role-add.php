<!-- assets/templates/rule-role-add.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM WordPress Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<h3><?php esc_html_e( 'Add Association Rule', 'civicrm-wp-member-sync' ); ?> <a class="add-new-h2" href="<?php echo $urls['list']; ?>"><?php esc_html_e( 'Cancel', 'civicrm-wp-member-sync' ); ?></a></h3>

	<?php

	// If we've updated, show message (note that this will only display if we have JS turned off).
	if ( isset( $this->errors ) AND is_array( $this->errors ) ) {

		// Init messages.
		$error_messages = [];

		// Construct array of messages based on error code.
		foreach( $this->errors AS $error_code ) {
			$error_messages[] = $this->error_strings[$error_code];
		}

		// Show them.
		echo '<div id="message" class="error"><p>' . implode( '<br>', $error_messages ) . '</p></div>';

	}

	?>

	<p><?php
	if ( empty( $multiple ) ) {
		esc_html_e( 'Choose a CiviMember Membership Type and a WordPress Role below. This will associate that Membership Type with the WordPress Role.', 'civicrm-wp-member-sync' );
	} else {
		esc_html_e( 'Choose one or more CiviMember Membership Types and a WordPress Role below. This will associate those Membership Types with the WordPress Role.', 'civicrm-wp-member-sync' );
	}
	?></p>

	<form method="post" id="civi_wp_member_sync_rules_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr class="form-field form-required">
				<th scope="row">
					<label class="civi_member_type_id_label" for="civi_member_type_id"><?php
					if ( empty( $multiple ) ) {
						esc_html_e( 'Select a CiviMember Membership Type', 'civicrm-wp-member-sync' );
					} else {
						esc_html_e( 'Select CiviMember Membership Type(s)', 'civicrm-wp-member-sync' );
					}
					?> *</label>
				</th>
				<td>
					<select name="civi_member_type_id<?php if ( ! empty( $multiple ) ) { echo '[]'; } ?>" id="civi_member_type_id" class ="required required-type"<?php echo $multiple; ?>>
						<?php if ( empty( $multiple ) ) { ?>
							<option value=""></option>
						<?php } ?>
						<?php foreach( $membership_types AS $key => $value ) { ?>
							<option value="<?php echo $key;?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><label class="current_label" for="current"><?php esc_html_e( 'Current Status', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
				<?php foreach( $status_rules AS $key => $value ) { ?>
					<input type="checkbox" class="required-current current-<?php echo $key; ?>" name="<?php echo 'current[' . $key . ']'; ?>" id="<?php echo 'current[' . $key . ']'; ?>" value="<?php echo $key; ?>" />
					<label for="<?php echo 'current[' . $key . ']'; ?>"><?php echo $value; ?></label><br />
				<?php } ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row"><label class="wp_role_label" for="current_wp_role"><?php esc_html_e( 'Select a WordPress Current Role', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<select name="current_wp_role" id="current_wp_role" class="required required-role">
						<option value=""></option>
						<?php foreach( $roles AS $key => $value ) { ?>
							<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<?php

			/**
			 * Allow extra rows to be added.
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
				<?php foreach( $status_rules AS $key => $value ) { ?>
					<input type="checkbox" class="required-expire expire-<?php echo $key; ?>" name="<?php echo 'expire[' . $key . ']'; ?>" id="<?php echo 'expire[' . $key . ']'; ?>" value="<?php echo $key; ?>" />
					<label for="<?php echo 'expire[' . $key . ']';?>"><?php echo $value; ?></label><br />
				<?php } ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row"><label class="expire_assign_wp_role_label" for="expire_assign_wp_role"><?php esc_html_e( 'Select a WordPress Expiry Role', 'civicrm-wp-member-sync' ); ?> *</label></th>
				<td>
					<select name="expire_assign_wp_role" id ="expire_assign_wp_role" class ="required required-role">
						<option value=""></option>
						<?php foreach( $roles AS $key => $value ) { ?>
							<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>

			<?php

			/**
			 * Allow extra rows to be added.
			 *
			 * @since 0.3.9
			 *
			 * @param array $status_rules The status rules.
			 */
			do_action( 'civi_wp_member_sync_role_add_after_expiry', $status_rules );

			?>

		</table>

		<input type="hidden" id="civi_wp_member_sync_rules_mode" name="civi_wp_member_sync_rules_mode" value="add" />

		<?php if ( empty( $multiple ) ) { ?>
			<input type="hidden" id="civi_wp_member_sync_rules_multiple" name="civi_wp_member_sync_rules_multiple" value="no" />
		<?php } else { ?>
			<input type="hidden" id="civi_wp_member_sync_rules_multiple" name="civi_wp_member_sync_rules_multiple" value="yes" />
		<?php } ?>

		<p class="submit"><input class="button-primary" type="submit" id="civi_wp_member_sync_rules_submit" name="civi_wp_member_sync_rules_submit" value="<?php esc_attr_e( 'Add Association Rule', 'civicrm-wp-member-sync' ); ?>" /></p>

	</form>

</div><!-- /.wrap -->



