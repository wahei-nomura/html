<?php
/**
 * ふるさとチョイス
 *
 * @package neoneng
 */

global $n2;
?>
<table class="form-table">
	<tr>
		<th>ポイント導入</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][ポイント導入]">
			<label>
				<input type="checkbox" name="n2_settings[ふるさとチョイス][ポイント導入]" value="導入する" <?php checked( $n2->settings['ふるさとチョイス']['ポイント導入'], '導入する' ); ?>> 導入する
			</label>
		</td>
	</tr>
	<tr>
		<th>オンライン決済限定</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][オンライン決済限定]">
			<label>
				<input type="checkbox" name="n2_settings[ふるさとチョイス][オンライン決済限定]" value="利用する" <?php checked( $n2->settings['ふるさとチョイス']['オンライン決済限定'], '利用する' ); ?>> 利用する
			</label>
		</td>
	</tr>
	<tr>
		<th>配達時間指定</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][配達時間指定]">
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配達時間指定]" value="0" <?php checked( $n2->settings['ふるさとチョイス']['配達時間指定'] ?? '0', '0' ); ?>> 指定できない
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配達時間指定]" value="1" <?php checked( $n2->settings['ふるさとチョイス']['配達時間指定'] ?? '', '1' ); ?>> 指定できる
			</label>
		</td>
	</tr>

</table>
