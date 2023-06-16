<?php
/**
 * ふるさとチョイス
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'ポイント導入' => array( '導入なし', '価格 × 定期便回数', '寄附金額 × 0.3' ),
	'オンライン決済限定' => array( '利用する' ),
);
?>
<table class="form-table">
	<tr>
		<th>ポイント導入</th>
		<td>
			<?php foreach ( $settings['ポイント導入'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[portal_setting][ふるさとチョイス][ポイント導入]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['ふるさとチョイス']['ポイント導入'] ?? false, $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>オンライン決済限定</th>
		<td>
			<?php foreach ( $settings['オンライン決済限定'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[portal_setting][ふるさとチョイス][オンライン決済限定]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['ふるさとチョイス']['オンライン決済限定'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
</table>
