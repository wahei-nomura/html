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
			<input type="hidden" name="n2_settings[ふるさとチョイス][ポイント導入]" <?php wp_readonly( ! $n2->settings_access ); ?>>
			<label <?php echo ! $n2->settings_access ? 'style="pointer-events:none;"' : ''; ?>>
				<input type="checkbox" name="n2_settings[ふるさとチョイス][ポイント導入]" value="導入する" <?php checked( $n2->settings['ふるさとチョイス']['ポイント導入'], '導入する' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 導入する
			</label>
		</td>
	</tr>
	<tr>
		<th>オンライン決済限定</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][オンライン決済限定]" <?php wp_readonly( ! $n2->settings_access ); ?>>
			<label <?php echo ! $n2->settings_access ? 'style="pointer-events:none;"' : ''; ?>>
				<input type="checkbox" name="n2_settings[ふるさとチョイス][オンライン決済限定]" value="利用する" <?php checked( $n2->settings['ふるさとチョイス']['オンライン決済限定'], '利用する' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 利用する
			</label>
		</td>
	</tr>
	<tr>
		<th>配達時間指定</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][配達時間指定]" <?php wp_readonly( ! $n2->settings_access ); ?>>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配達時間指定]" value="0" <?php checked( $n2->settings['ふるさとチョイス']['配達時間指定'] ?? '0', '0' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 指定できない
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配達時間指定]" value="1" <?php checked( $n2->settings['ふるさとチョイス']['配達時間指定'] ?? '', '1' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 指定できる
			</label>
		</td>
	</tr>
	<tr>
		<th>配送業者</th>
		<td>
			<input type="hidden" name="n2_settings[ふるさとチョイス][配送業者]">
			<label style="margin: 0 2em 0 0; line-height:2rem; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="0" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '0', '0' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 指定なし
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="1" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '1' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> ヤマト運輸
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="2" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '2' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 佐川急便
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="3" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '3' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 日本郵便
			</label>
			<br>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="4" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '4' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 西濃運輸
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="5" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '5' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 福山通運
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="6" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '6' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 日本通運
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="7" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '7' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 佐川急便（6時間帯）
			</label>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[ふるさとチョイス][配送業者]" value="8" <?php checked( $n2->settings['ふるさとチョイス']['配送業者'] ?? '', '8' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 佐川急便（5時間帯）
			</label>
		</td>
	</tr>

</table>
