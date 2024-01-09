<?php
/**
 * 投稿IDを選択して保存するUI
 * 削除・印刷・エクスポート・画像ダウンロード
 *
 * @package neoneng
 */

global $n2;
?>
<div popover id="n2-admin-post-list-tool" :class="`n2-admin-post-list-tool ${item.ステータス}`" v-if="HTMLElement.prototype.hasOwnProperty('popover')">
	<div id="n2-admin-post-list-tool-header">
		<ul>
			<li v-if="'trash' === item.ステータス" @click="confirm('ゴミ箱から復元します。よろしいですか？') ? location.href=`admin-ajax.php?action=n2_items_api&mode=untrash&p=${id}` : 0">ゴミ箱から復元</li>
			<template v-else>
				<li @click="window.open(`post.php?post=${id}&action=edit`, '_parent')"><span class="dashicons dashicons-edit"></span> 編集</li>
				<li @click="confirm('返礼品を複製します。よろしいですか？') ? window.open(`admin-ajax.php?action=n2_items_api&mode=copy&p=${id}`, '_parent') : 0">
					<span class="dashicons dashicons-admin-page"></span> 複製
				</li>
				<!-- ある権限かつあるステータスの場合はできないでいい -->
				<li v-if="!('draft' !== item.ステータス && 'jigyousya' === n2.current_user.roles[0])" @click="confirm('ゴミ箱に入れます。よろしいですか？') ? location.href=`admin-ajax.php?action=n2_items_api&mode=delete&p=${id}` : 0">
					<span class="dashicons dashicons-trash"></span> 削除
				</li>
				<li v-if="'jigyousya' !== n2.current_user.roles[0]" @click="window.open(`admin-ajax.php?action=n2_post_history_api&post_id=${id}&type=table`, '_blank')">
				<span class="dashicons dashicons-clipboard"></span> 履歴
				</li>
			</template>
		</ul>
		<span id="n2-admin-post-list-tool-close" class="dashicons dashicons-no-alt" @click="document.getElementById('n2-admin-post-list-tool').hidePopover()"></span>
	</div>
	<div id="n2-admin-post-list-tool-content">
		<div v-if="item._n2_required && item._n2_required.length" id="n2-admin-post-list-tool-content-required">
			最低必須漏れ：　{{item._n2_required.join('、').replace('全商品ディレクトリID', '楽天ジャンルID')}}
		</div>
		<table class="widefat striped">
			<tr>
				<th>返礼品名</th>
				<td>{{item.タイトル}}</td>
			</tr>
			<template v-for="name in custom_field">
				<tr v-if="item[name]">
					<th style="text-align: left;">{{name}}</th>
					<td style="text-align: left;">
						<div v-if="Array.isArray(item[name])">
							<template v-if="'商品属性' === name">
								<div v-for="{nameJa, value, unitValue, properties} in item[name]">
									{{nameJa}}{{properties.rmsMandatoryFlg ? '*' : ''}}：{{value}}{{ unitValue ? '：' + unitValue : '' }}
								</div>
							</template>
							<template v-else>
								{{item[name].join(', ')}}
							</template>
						</div>
						<div v-else v-html="item[name].toString().replace(/\r\n|\r|\n/g,'<br>')"></div>
					</td>
				</tr>
			</template>
		</table>
		<div v-if="(item.商品画像 || []).length" id="n2-admin-post-list-tool-content-imgs">
			<img :src="img.sizes.thumbnail.url || img.sizes.thumbnail" v-for="img in item.商品画像" >
		</div>
	</div>
</div>
