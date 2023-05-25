<?php
/**
 * 楽天市場
 *
 * @package neoneng
 */

?>
<table class="form-table">
	<tr>
		<th>FTPユーザー</th>
		<td>
			<input type="text" name="n2_settings[portal_setting][楽天][ftp_user]" value="<?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['ftp_user'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>FTPパスワード</th>
		<td>
			<input type="text" name="n2_settings[portal_setting][楽天][ftp_pass]" value="<?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['ftp_pass'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>楽天SPA</th>
		<td>
			<label>
				<input type="checkbox" name="n2_settings[portal_setting][楽天][spa]" value="1" <?php checked( $args->setting_values['portal_setting']['楽天']['spa'] ?? '' ); ?>> 楽天SPA
			</label>
		</td>
	</tr>
	<tr>
		<th>商品画像ディレクトリ</th>
		<td>
			<input type="text" name="n2_settings[portal_setting][楽天][img_dir]" value="<?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['img_dir'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>タグID</th>
		<td>
			<input type="text" name="n2_settings[portal_setting][楽天][tag_id]" value="<?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['tag_id'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>説明文追加html</th>
		<td>
			<p>※商品説明文の最後に共通で追加される文言を設定できます(タグ使用可能)</p>
			<textarea name="n2_settings[portal_setting][楽天][html]" style="width: 100%;" rows="10"><?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['html'] ); ?></textarea>
		</td>
	</tr>
	<tr>
		<th>項目選択肢</th>
		<td>
			<p>※それぞれの項目選択肢は２つ以上の連続改行をして下さい</p>
			<p>※選択肢は最大16文字で１つの改行で区切って下さい</p>
			<textarea name="n2_settings[portal_setting][楽天][select]" style="width: 100%;" rows="30" placeholder="<?php echo "1.ふるさと納税専用ページです。注文内容確認画面に表示される「注文者情報」を住民票情報とみなします。\n理解した\n\n2.寄附金の用途を選択\n用途１\n用途２\n..."; ?>"><?php echo esc_attr( $args->setting_values['portal_setting']['楽天']['select'] ); ?></textarea>
		</td>
	</tr>
</table>
