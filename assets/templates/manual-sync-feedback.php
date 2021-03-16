<!-- assets/templates/manual-sync-feedback.php -->
<?php foreach( $simulated AS $item ) : ?>
	<tr>
		<td class="comment column-comment column-primary"><strong><?php echo $item['display_name']; ?></strong></td>
		<td><?php echo $item['username']; ?></td>
		<td><?php echo $item['membership_name']; ?></td>
		<td><?php echo $item['membership_status']; ?></td>
		<?php

		/**
		 * Allow extra fields to be added after "Status".
		 *
		 * @since 0.5
		 */
		do_action( 'cwms/feedback/td', $item['membership_type_id'], $item );

		?>
	</tr>
<?php endforeach; ?>
