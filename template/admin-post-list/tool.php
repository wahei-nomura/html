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
			<li v-if="'trash' === item.ステータス" @click="confirm('ゴミ箱から復元します。よろしいですか？') ? location.href=`admin-ajax.php?action=n2_items_api&mode=untrash&id=${id}` : 0">ゴミ箱から復元</li>
			<template v-else>
				<li @click="location.href=`post.php?post=${id}&action=edit`"><span class="dashicons dashicons-edit"></span> 編集</li>
				<li @click="confirm('返礼品を複製します。よろしいですか？') ? location.href=`admin-ajax.php?action=n2_items_api&mode=copy&id=${id}` : 0">
					<span class="dashicons dashicons-admin-page"></span> 複製
				</li>
				<!-- ある権限かつあるステータスの場合はできないでいい -->
				<li v-if="!('draft' !== item.ステータス && 'jigyousya' === n2.current_user.roles[0])" @click="confirm('ゴミ箱に入れます。よろしいですか？') ? location.href=`admin-ajax.php?action=n2_items_api&mode=delete&id=${id}` : 0">
					<span class="dashicons dashicons-trash"></span> 削除
				</li>
			</template>
		</ul>
		<span id="n2-admin-post-list-tool-close" class="dashicons dashicons-no-alt" @click="document.getElementById('n2-admin-post-list-tool').hidePopover()"></span>
	</div>
	<div id="n2-admin-post-list-tool-content">
		<table class="widefat striped">
			<tr>
				<th>返礼品名</th>
				<td>{{item.タイトル}}</td>
			</tr>
			<tr v-for="name in custom_field" v-if="item[name]">
				<th style="text-align: left;">{{name}}</th>
				<td style="text-align: left;">
					<div v-if="Array.isArray(item[name])">{{item[name].join(', ')}}</div>
					<div v-else v-html="item[name].replace(/\r\n|\r|\n/g,'<br>')"></div>
				</td>
			</tr>
		</table>
		<div v-if="(item.商品画像 || []).length" id="n2-admin-post-list-tool-content-imgs">
			<img :src="img.sizes.thumbnail.url || img.sizes.thumbnail" v-for="img in item.商品画像" >
		</div>
	</div>
</div>
