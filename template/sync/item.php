<?php
/**
 * GS 返礼品同期
 *
 * @package neoneng
 */

global $n2;
$args = array(
	'cookies'   => $_COOKIE,
	'sslverify' => false,
	'body'      => array(
		'action'      => 'n2_item_export_base',
		'mode'        => 'json',
		'numberposts' => 1,
	),
);
// N2ベースエクスポートのヘッダー取得
$res = wp_remote_get( $n2->ajaxurl, $args );
if ( empty( $res['body'] ) ) {
	echo '<p>N2ベースエクスポートヘッダーが取得できません</p>';
	return;
}
$n2_export_header = json_decode( $res['body'], true )['header'];
$n2_export_header = array_filter( $n2_export_header, fn( $v ) => 'id' !== $v );// idは削除
$n2_export_header = array( '全て', ...$n2_export_header );

$default  = array(
	'id'    => '',
	'range' => '',
);
$settings = get_option( 'n2_sync_settings_spreadsheet', $default );
?>
<h2 style="margin-top: 3em; font-size: 2em;">GS 返礼品の同期</h2>
<p>Googleスプレットシートから返礼品データを同期します。</p>
<table class="form-table">
	<tr>
		<th>スプレットシートのURL</th>
		<td><input type="text" class="large-text" name="n2_sync_settings_spreadsheet[id]" value="<?php echo $settings['id']; ?>" placeholder="スプレッドシートのIDはたまURL" form="n2_sync_settings_spreadsheet"><a target="_blank"></a></td>
	</tr>
	<tr>
		<th>返礼品シートの範囲</th>
		<td>
			<input type="text" class="regular-text" name="n2_sync_settings_spreadsheet[item_range]" value="<?php echo $settings['item_range']; ?>" placeholder="item!A:ZZ" form="n2_sync_settings_spreadsheet">
			<p>※ シートの範囲については<a href="https://developers.google.com/sheets/api/guides/concepts?hl=ja#expandable-1" target="_blank">ココ</a>を参照。</p>
		</td>
	</tr>
	<tr>
		<th>同期項目</th>
		<td>
			<form method="get" action="admin-ajax.php" target="_blank">
				<input type="hidden" name="action" value="n2_sync_posts_from_spreadsheet">
				<div style="display: flex; flex-wrap: wrap;">
					<?php foreach ( $n2_export_header as $name ) : ?>
					<label style="margin: 1em 1em 0 0;">
						<input type="checkbox" name="target_cols[]" value="<?php echo esc_attr( $name ); ?>"> <?php echo $name; ?>
					</label>
					<?php endforeach; ?>
				</div>
				<div style="margin-top: 2em;">
					<button class="button">チェックした項目を同期する</button>
				</div>
			</form>
		</td>
	</tr>
</table>
<form method="post" action="options.php" id="n2_sync_settings_spreadsheet">
	<?php settings_fields( 'n2_sync_settings_spreadsheet' ); ?>
	<button class="button button-primary">設定を保存</button>
</form>
