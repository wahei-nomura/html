<?php
/**
 * 注意書き
 *
 * @package neoneng
 */

global $n2;
?>
<table class="form-table">
	<tr>
		<th>共通</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<textarea name="n2_settings[注意書き][共通]" rows="5" style="width: 100%;" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>><?php echo $n2->settings['注意書き']['共通']; ?></textarea>
			</label>
		</td>
	</tr>
	<?php foreach ( $args->data['商品タイプ'] as $type ) : ?>
	<tr <?php echo ! in_array( $type, $n2->settings['N2']['商品タイプ'], true ) ? 'style="display: none;"' : ''; ?>>
		<th><?php echo $type; ?></th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<textarea name="n2_settings[注意書き][<?php echo $type; ?>]" rows="5" style="width: 100%;" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>><?php echo $n2->settings['注意書き'][ $type ]; ?></textarea>
			</label>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
