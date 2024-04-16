<?php
/**
 * Expiry "Groups" Groups template.
 *
 * Shows the Expiry "Groups" Groups on the "Add Rule" page.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.3.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/groups-add-expiry.php -->
<tr>
	<th scope="row">
		<label class="expiry_label" for="cwms_groups_select_expiry"><?php esc_html_e( 'Expiry Group(s)', 'civicrm-wp-member-sync' ); ?></label>
	</th>
	<td>
		<select class="cwms_groups_select" id="cwms_groups_select_expiry" name="cwms_groups_select_expiry[]" multiple="multiple" placeholder="<?php esc_attr_e( 'Find a group', 'civicrm-wp-member-sync' ); ?>" style="min-width: 240px;"></select>
	</td>
</tr>
