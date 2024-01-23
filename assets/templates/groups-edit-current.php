<?php
/**
 * Current "Groups" Groups template.
 *
 * Shows the Current "Groups" Groups on the "Edit Rule" page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.3.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/groups-edit-current.php -->
<tr>
	<th scope="row"><label class="current_label" for="cwms_groups_select_current"><?php esc_html_e( 'Current Group(s)', 'civicrm-wp-member-sync' ); ?></label></th>
	<td>
	<select class="cwms_groups_select" id="cwms_groups_select_current" name="cwms_groups_select_current[]" multiple="multiple" placeholder="<?php esc_attr_e( 'Find a group', 'civicrm-wp-member-sync' ); ?>" style="min-width: 240px;">
		<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		<?php echo $options_html; ?>
	</select>
	</td>
</tr>
