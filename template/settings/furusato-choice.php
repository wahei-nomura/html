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
	<tr>
		<th>配送業者</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][配送業者]">
			<label style="margin: 0 2em 0 0; line-height:2rem;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="0" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '0', '0' ); ?>> 指定なし
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="1" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '1' ); ?>> ヤマト運輸
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="2" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '2' ); ?>> 佐川急便
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="3" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '3' ); ?>> 日本郵便
			</label>
			<br>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="4" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '4' ); ?>> 西濃運輸
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="5" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '5' ); ?>> 福山通運
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="6" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '6' ); ?>> 日本通運
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="7" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '7' ); ?>> 佐川急便（6時間帯）
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="8" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '8' ); ?>> 佐川急便（5時間帯）
			</label>
		</td>
	</tr>

</table>
