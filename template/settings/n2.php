<?php
/**
 * N2設定
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'商品タイプ' => array( '食品', 'やきもの', 'eチケット' ),
);
?>
<table class="form-table">
	<tr>
		<th>稼働状況</th>
		<td>
			<label>
				<input type="checkbox" name="n2_settings[n2][active]" value="1" <?php checked( $n2->n2_active_flag ?? '' ); ?>> N2稼働中
			</label>
		</td>
	</tr>
	<tr>
		<th>出品する特殊な商品タイプ</th>
		<td>
			<?php foreach ( $settings['商品タイプ'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[n2][item_types][]" value="<?php echo $v; ?>" <?php checked( in_array( $v, $n2->custom_field['事業者用']['商品タイプ']['option'], true ) ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[n2][item_types][]">
		</td>
	</tr>
	<tr>
		<th>出品ポータル</th>
		<td>
			<?php foreach ( $args->portal_sites as $portal ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[n2][portal_sites][]" value="<?php echo esc_attr( $portal ); ?>" <?php checked( in_array( $portal, $n2->portal_sites ?? array(), true ) ); ?>> <?php echo $portal; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>ポータル共通説明文</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<textarea name="n2_settings[n2][portal_common_description]" rows="5" style="width: 100%;"><?php echo $n2->portal_common_description; ?></textarea>
			</label>
		</td>
	</tr>
	<tr>
		<th>役場確認</th>
		<td>
			<label>
				<input type="checkbox" name="n2_settings[n2][town_check]" value="1" <?php checked( $n2->town_check ?? '' ); ?>> 役場確認する
			</label>
		</td>
	</tr>
</table>
