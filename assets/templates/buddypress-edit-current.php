<?php
/**
 * Current BuddyPress Groups template.
 *
 * Shows the Current BuddyPress Groups on the "Edit Rule" page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.4.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/buddypress-edit-current.php -->
<tr>
	<th scope="row"><label class="current_label" for="cwms_buddypress_select_current"><?php esc_html_e( 'Current BuddyPress Group(s)', 'civicrm-wp-member-sync' ); ?></label></th>
	<td>
	<select class="cwms_buddypress_select" id="cwms_buddypress_select_current" name="cwms_buddypress_select_current[]" multiple="multiple" placeholder="<?php esc_attr_e( 'Find a group', 'civicrm-wp-member-sync' ); ?>" style="min-width: 240px;">
		<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		<?php echo $options_html; ?>
	</select>
	</td>
</tr>
