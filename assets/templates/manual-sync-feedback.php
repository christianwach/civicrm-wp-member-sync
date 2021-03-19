<!-- assets/templates/manual-sync-feedback.php -->
<?php foreach( $result AS $item ) : ?>
	<tr>
		<?php if ( $item['is_new'] ) : ?>
			<td><span class="dashicons dashicons-yes-alt"></span></td>
		<?php else : ?>
			<td><span class="dashicons dashicons-no"></span></td>
		<?php endif; ?>
		<td class="comment column-comment column-primary"><strong><?php if ( ! empty( $item['link'] ) ) : ?><a href="<?php echo $item['link']; ?>"><?php endif; ?><?php echo $item['display_name']; ?><?php if ( ! empty( $item['link'] ) ) : ?></a><?php endif; ?></strong></td>
		<td><?php echo $item['username']; ?></td>
		<td><?php echo $item['membership_name']; ?></td>
		<td><?php echo $item['membership_status']; ?></td>
		<?php

		/**
		 * Allow extra fields to be added after "Status".
		 *
		 * @since 0.5
		 */
		do_action( 'cwms/manual_sync/feedback/td', $item['membership_type_id'], $item );

		?>
	</tr>
<?php endforeach; ?>
