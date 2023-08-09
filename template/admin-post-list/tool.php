<?php
/**
 * 投稿IDを選択して保存するUI
 * 削除・印刷・エクスポート・画像ダウンロード
 *
 * @package neoneng
 */

global $n2;
?>
<div popover id="n2-admin-post-list-tool" :class="`n2-admin-post-list-tool ${item.ステータス}`">
	<div id="n2-admin-post-list-tool-header">
		<ul>
			<li v-if="'trash' === item.ステータス">ゴミ箱から復元</li>
			<template v-else>
				<li><span class="dashicons dashicons-admin-users"></span> 事業者変更</li>
				<li><span class="dashicons dashicons-admin-page"></span> 複製</li>
				<li>
					<form action="admin-ajax.php">
						<input type="hidden" name="action" value="n2_items_api">
						<input type="hidden" name="mode" value="delete">
						<input type="hidden" name="id" :value="id">
						<button><span class="dashicons dashicons-trash"></span> 削除</button>
					</form>
					
				</li>
			</template>
		</ul>
		<span id="n2-admin-post-list-tool-close" class="dashicons dashicons-no-alt" @click="document.getElementById('n2-admin-post-list-tool').hidePopover()"></span>
	</div>
	<div id="n2-admin-post-list-tool-content">
		<table class="widefat striped">
			<tr v-for="name in custom_field">
				<th style="text-align: left;">{{name}}</th>
				<td style="text-align: left;">{{item[name]}}</td>
			</tr>
		</table>
	</div>
	{{custom_field}}
</div>
