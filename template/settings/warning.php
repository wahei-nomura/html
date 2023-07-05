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
				<textarea name="n2_settings[注意書き][共通]" rows="5" style="width: 100%;"><?php echo $n2->settings['注意書き']['共通']; ?></textarea>
			</label>
		</td>
	</tr>
	<?php foreach ( array_filter( $n2->settings['N2']['商品タイプ'] ) as $type ) : ?>
	<tr>
		<th><?php echo $type; ?></th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<textarea name="n2_settings[注意書き][<?php echo $type; ?>]" rows="5" style="width: 100%;"><?php echo $n2->settings['注意書き'][ $type ]; ?></textarea>
			</label>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
