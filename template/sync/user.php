<?php
/**
 * GS ユーザー同期
 *
 * @package neoneng
 */

global $n2;
$settings = get_option( 'n2_sync_settings_spreadsheet' );
?>
<table class="form-table">
	<tr>
		<th>ユーザースプレットシートのURL</th>
		<td>
			<input type="text" id="user_url" class="large-text" name="n2_sync_settings_spreadsheet[user_url]" value="<?php echo esc_attr( $settings['user_url'] ); ?>" v-model="user.url" placeholder="スプレッドシートのURL" form="n2_sync_settings_spreadsheet" @change="set_data('user')">
			<p v-if="typeof user.data === 'object'">
				<span class="dashicons dashicons-media-spreadsheet" style="color: green;"></span>
				<a :href="user.url" target="_blank">
					{{user.data.title}} | {{user.data.range}}
				</a>
			</p>
			<p v-if="typeof user.data === 'string'" style="color: red;">{{user.data}}</p>
		</td>
	</tr>
	<tr v-if="typeof user.data === 'object'">
		<th>同期項目</th>
		<td>
			<form method="get" action="admin-ajax.php" target="_blank">
				<input type="hidden" name="action" value="n2_sync_from_spreadsheet">
				<input type="hidden" name="mode" value="user">
				<input type="hidden" name="spreadsheetid" :value="user.url">
				<label>
					<input type="checkbox" v-model="user.checked.all" @change="user.checked.data = user.checked.all ? user.data.header : []"> 全項目チェック
				</label>
				<div style="display: flex; flex-wrap: wrap; margin: 1em 0;">
					<label style="margin: 0 1em 1em 0;" v-for="name in user.data.header">
						<input type="checkbox" name="target_cols[]" :value="name" v-model="user.checked.data"> {{name}}
					</label>
				</div>
				<div>
					<button class="button" :disabled="update_disabled('user')">チェックした項目を同期する</button>
				</div>
			</form>
		</td>
	</tr>
</table>
