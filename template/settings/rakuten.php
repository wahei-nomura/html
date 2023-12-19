<?php
/**
 * 楽天市場
 *
 * @package neoneng
 */

global $n2;
?>
<table class="form-table">
	<tr>
		<th>SKU対応</th>
		<td>
			<label>
				<input type="checkbox" name="n2_settings[楽天][SKU]" value="1" <?php checked( $n2->settings['楽天']['SKU'] ?? '' ); ?> <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?><?php echo $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ? ' onclick="return false;"' : ''; ?>> SKU対応済
			</label>
		</td>
	</tr>
	<tr>
		<th>FTPユーザー</th>
		<td>
			<input type="text" name="n2_settings[楽天][FTP][user]" value="<?php echo esc_attr( $n2->settings['楽天']['FTP']['user'] ); ?>" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>>
		</td>
	</tr>
	<tr>
		<th>FTPパスワード</th>
		<td>
			<input type="password" name="n2_settings[楽天][FTP][pass]" value="<?php echo esc_attr( $n2->settings['楽天']['FTP']['pass'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>楽天SPA</th>
		<td>
			<label>
				<input type="checkbox" name="n2_settings[楽天][楽天SPA]" value="1" <?php checked( $n2->settings['楽天']['楽天SPA'] ?? '' ); ?> <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?><?php echo $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ? ' onclick="return false;"' : ''; ?>> 楽天SPA
			</label>
		</td>
	</tr>
	<tr>
		<th>商品画像ディレクトリ</th>
		<td>
			<input type="text" name="n2_settings[楽天][商品画像ディレクトリ]" value="<?php echo esc_attr( $n2->settings['楽天']['商品画像ディレクトリ'] ); ?>" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>>
		</td>
	</tr>
	<tr>
		<th>共通タグID</th>
		<td>
			<input type="text" name="n2_settings[楽天][共通タグID]" value="<?php echo esc_attr( $n2->settings['楽天']['共通タグID'] ); ?>" style="width: 100%;" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>>
		</td>
	</tr>
	<tr>
		<th>説明文追加html</th>
		<td>
			<p>※商品説明文の最後に共通で追加される文言を設定できます(タグ使用可能)</p>
			<textarea name="n2_settings[楽天][説明文追加html]" style="width: 100%;" rows="10" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>><?php echo esc_attr( $n2->settings['楽天']['説明文追加html'] ); ?></textarea>
		</td>
	</tr>
	<tr>
		<th>項目選択肢</th>
		<td>
			<p>※それぞれの項目選択肢は２つ以上の連続改行をして下さい</p>
			<p>※選択肢は最大16文字で１つの改行で区切って下さい</p>
			<textarea name="n2_settings[楽天][項目選択肢]" style="width: 100%;" rows="30" placeholder="<?php echo "1.ふるさと納税専用ページです。注文内容確認画面に表示される「注文者情報」を住民票情報とみなします。\n理解した\n\n2.寄附金の用途を選択\n用途１\n用途２\n..."; ?>" <?php wp_readonly( $n2->settings['N2']['稼働中'] && 'administrator' !== $n2->current_user->roles[0] ); ?>><?php echo esc_attr( $n2->settings['楽天']['項目選択肢'] ); ?></textarea>
		</td>
	</tr>
</table>
