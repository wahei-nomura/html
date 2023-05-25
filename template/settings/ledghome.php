<?php
/**
 * レジホーム
 *
 * @package neoneng
 */

global $n2;
?>
<table class="form-table">
	<tr>
		<th>カテゴリー</th>
		<td>
			<p>※改行区切りでカテゴリーを記入</p>
			<textarea name="n2_settings[portal_setting][LedgHOME][カテゴリー]" rows="10" style="width: 100%;"><?php echo esc_attr( implode( "\n", $n2->portal_setting['LedgHOME']['カテゴリー'] ) ); ?></textarea>
		</td>
	</tr>
	<tr>
		<th>レターパック送料反映</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[portal_setting][LedgHOME][レターパック送料反映]" value="反映する" <?php checked( $n2->portal_setting['LedgHOME']['レターパック送料反映'], '反映する' ); ?>> 反映する
			</label>
			<label>
				<input type="radio" name="n2_settings[portal_setting][LedgHOME][レターパック送料反映]" value="反映しない" <?php checked( $n2->portal_setting['LedgHOME']['レターパック送料反映'], '反映しない' ); ?>> 反映しない
			</label>
		</td>
	</tr>
</table>
