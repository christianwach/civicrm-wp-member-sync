<?php
/**
 * Roles Edit Rule template.
 *
 * Main template for for the "Edit Rule" page when syncing Roles.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/rule-role-edit.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Member Sync', 'civicrm-wp-member-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['list']; ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Association Rules', 'civicrm-wp-member-sync' ); ?></a>
		<a href="<?php echo $urls['manual_sync']; ?>" class="nav-tab"><?php esc_html_e( 'Manual Synchronize', 'civicrm-wp-member-sync' ); ?></a>
	</h2>

	<h3><?php esc_html_e( 'Edit Association Rule', 'civicrm-wp-member-sync' ); ?> <a class="add-new-h2" href="<?php echo $urls['list']; ?>"><?php esc_html_e( 'Cancel', 'civicrm-wp-member-sync' ); ?></a></h3>

	<?php /* If we've updated, show message - note that this will only display if we have JS turned off. */ ?>
	<?php if ( ! empty( $error_messages ) ) : ?>
		<div id="message" class="error">
			<p><?php echo $error_messages; ?></p>
		</div>
	<?php endif; ?>

	<p><?php esc_html_e( 'Choose a CiviMember Membership Type and a WordPress Role below. This will associate that Membership Type with the WordPress Role.', 'civicrm-wp-member-sync' ); ?></p>

	<form method="post" id="civi_wp_member_sync_rules_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civi_wp_member_sync_rule_action', 'civi_wp_member_sync_nonce' ); ?>

		<table class="form-table">

			<tr class="form-field form-required">
				<th scope="row">
					<label class="civi_member_type_id_label" for="civi_member_type_id"><?php esc_html_e( 'CiviMember Membership Type', 'civicrm-wp-member-sync' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<select name="civi_member_type_id" id="civi_member_type_id" class ="required required-type">
						<?php foreach ( $membership_types as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $civi_member_type_id, $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Current Status', 'civicrm-wp-member-sync' ); ?> <span class="required">*</span>
				</th>
				<td>
					<?php foreach ( $status_rules as $key => $value ) : ?>
						<input type="checkbox" class="required-current current-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'current[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>"<?php checked( array_search( $key, $current_rule ), $key ); ?> />
						<label for="<?php echo esc_attr( 'current[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
					<?php endforeach; ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row">
					<label class="current_wp_role_label" for="current_wp_role"><?php esc_html_e( 'WordPress Current Role', 'civicrm-wp-member-sync' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<select name="current_wp_role" id="current_wp_role" class="required required-role">
						<?php foreach ( $roles as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $current_wp_role, $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
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
			 * @param array $selected_rule The rule being edited.
			 */
			do_action( 'civi_wp_member_sync_role_edit_after_current', $status_rules, $selected_rule );

			?>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Expire Status', 'civicrm-wp-member-sync' ); ?> <span class="required">*</span>
				</th>
				<td>
					<?php foreach ( $status_rules as $key => $value ) : ?>
						<input type="checkbox" class="required-expire expire-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" id="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>"<?php checked( array_search( $key, $expiry_rule ), $key ); ?> />
						<label for="<?php echo esc_attr( 'expire[' . $key . ']' ); ?>"><?php echo esc_html( $value ); ?></label><br />
					<?php endforeach; ?>
				</td>
			</tr>

			<tr class="form-field form-required">
				<th scope="row">
					<label class="expire_assign_wp_role_label" for="expire_assign_wp_role"><?php esc_html_e( 'WordPress Expiry Role', 'civicrm-wp-member-sync' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<select name="expire_assign_wp_role" id ="expire_assign_wp_role" class ="required required-role">
						<?php foreach ( $roles as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $expired_wp_role, $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
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
			 * @param array $selected_rule The rule being edited.
			 */
			do_action( 'civi_wp_member_sync_role_edit_after_expiry', $status_rules, $selected_rule );

			?>

		</table>

		<input type="hidden" id="civi_wp_member_sync_rules_mode" name="civi_wp_member_sync_rules_mode" value="edit" />

		<p class="submit">
			<input class="button-primary" type="submit" id="civi_wp_member_sync_rules_submit" name="civi_wp_member_sync_rules_submit" value="<?php esc_attr_e( 'Save Association Rule', 'civicrm-wp-member-sync' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->
