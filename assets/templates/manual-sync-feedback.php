<?php
/**
 * Manual Sync "feedback" template.
 *
 * Template for each table row in Manual Sync feedback table.
 *
 * @package Civi_WP_Member_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/templates/manual-sync-feedback.php -->
<?php foreach ( $result as $item ) : ?>
	<tr>
		<?php if ( $item['is_new'] ) : ?>
			<td>
				<span class="dashicons dashicons-yes-alt" title="<?php esc_html_e( 'User created', 'civicrm-wp-member-sync' ); ?>"></span>
			</td>
		<?php else : ?>
			<td>
				<span class="dashicons dashicons-no" title="<?php esc_html_e( 'User exists', 'civicrm-wp-member-sync' ); ?>"></span>
			</td>
		<?php endif; ?>
		<td class="comment column-comment column-primary">
			<strong>
				<?php if ( ! empty( $item['link'] ) ) : ?>
					<a href="<?php echo esc_url( $item['link'] ); ?>">
				<?php endif; ?>
				<?php echo esc_html( $item['display_name'] ); ?>
				<?php if ( ! empty( $item['link'] ) ) : ?>
					</a>
				<?php endif; ?>
			</strong>
		</td>
		<td>
			<?php echo esc_html( $item['username'] ); ?>
		</td>
		<td>
			<?php echo esc_html( $item['membership_name'] ); ?>
		</td>
		<td>
			<?php echo esc_html( $item['membership_status'] ); ?>
		</td>
		<?php

		/**
		 * Allows extra fields to be added after "Status".
		 *
		 * @since 0.5
		 */
		do_action( 'cwms/manual_sync/feedback/td', $item['membership_type_id'], $item );

		?>
	</tr>
<?php endforeach; ?>
