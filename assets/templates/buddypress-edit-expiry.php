<?php
/**
 * Expiry BuddyPress Groups template.
 *
 * Shows the Expiry BuddyPress Groups on the "Edit Rule" page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.4.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/buddypress-edit-expiry.php -->
<tr>
	<th scope="row">
		<label class="expiry_label" for="cwms_buddypress_select_expiry"><?php esc_html_e( 'Expiry BuddyPress Group(s)', 'civicrm-wp-member-sync' ); ?></label>
	</th>
	<td>
		<select class="cwms_buddypress_select" id="cwms_buddypress_select_expiry" name="cwms_buddypress_select_expiry[]" multiple="multiple" placeholder="<?php esc_attr_e( 'Find a group', 'civicrm-wp-member-sync' ); ?>" style="min-width: 240px;">
			<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
			<?php echo $options_html; ?>
		</select>
	</td>
</tr>
