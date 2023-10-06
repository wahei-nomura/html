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
			<input type="text" id="item_url" class="large-text" name="n2_sync_settings_spreadsheet[item_url]" value="<?php echo esc_attr( $settings['item_url'] ); ?>" v-model="item.url" placeholder="スプレッドシートのURL" form="n2_sync_settings_spreadsheet" @change="set_data()">
			<p v-if="typeof item.data === 'object'">
				<span class="dashicons dashicons-media-spreadsheet" style="color: green;"></span>
				<a :href="item.url" target="_blank">
					{{item.data.title}} | {{item.data.range}}
				</a>
			</p>
			<p v-if="typeof item.data === 'string'" style="color: red;">{{item.data}}</p>
		</td>
	</tr>
	<tr v-if="typeof item.data === 'object'">
		<th>同期項目</th>
		<td>
			<form method="get" action="admin-ajax.php" target="_blank">
				<input type="hidden" name="action" value="n2_sync_from_spreadsheet">
				<input type="hidden" name="mode" value="item">
				<input type="hidden" name="spreadsheetid" :value="item.url">
				<label>
					<input type="checkbox" v-model="item.checked.all" @change="item.checked.data = item.checked.all ? item.data.header : []"> 全項目チェック
				</label>
				<div style="display: flex; flex-wrap: wrap; margin: 1em 0;">
					<label style="margin: 0 1em 1em 0;" v-for="name in item.data.header">
						<input type="checkbox" name="target_cols[]" :value="name" v-model="item.checked.data"> {{name}}
					</label>
				</div>
				<div>
					<button class="button" :disabled="update_disabled()">チェックした項目を同期する</button>
				</div>
			</form>
		</td>
	</tr>
</table>
