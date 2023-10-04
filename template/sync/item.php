<?php
/**
 * GS 返礼品同期
 *
 * @package neoneng
 */

global $n2;
$settings             = get_option( 'n2_sync_settings_spreadsheet' );
$settings['item_url'] = $settings['item_url'] ?? $settings['id'];
?>
<h2 style="margin-top: 1em; font-size: 2em;">GS 返礼品の同期</h2>
<p>Googleスプレットシートから返礼品データを同期項目を選んで同期します。</p>
<table class="form-table">
	<tr>
		<th>スプレットシートのURL</th>
		<td>
			<input type="text" id="item_url" class="large-text" name="n2_sync_settings_spreadsheet[item_url]" value="<?php echo esc_attr( $settings['item_url'] ); ?>" v-model="item_url" placeholder="スプレッドシートのURL" form="n2_sync_settings_spreadsheet" @change="set_item_data()">
			<p v-if="item_data">
				<span class="dashicons dashicons-media-spreadsheet" style="color: green;"></span>
				<a :href="item_url" target="_blank">
					{{item_data.title}} | {{item_data.range}}
				</a>
			</p>
		</td>
	</tr>
	<tr v-if="item_data">
		<th>同期項目</th>
		<td>
			<form method="get" action="admin-ajax.php" target="_blank">
				<input type="hidden" name="action" value="n2_sync_posts_from_spreadsheet">
				<input type="hidden" name="spreadsheetid" :value="item_url">
				<!-- Vueを導入してリアクティブにidとitem_rangeをセット -->
				<div style="display: flex; flex-wrap: wrap; margin: 0 0 1em;">
					<label style="margin: 0 1em 1em 0;" v-for="name in item_data.header">
						<input type="checkbox" name="target_cols[]" :value="name"> {{name}}
					</label>
				</div>
				<div>
					<button class="button">チェックした項目を同期する</button>
				</div>
			</form>
		</td>
	</tr>
</table>
