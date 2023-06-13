<?php
/**
 * 投稿IDを選択して保存するUI
 * 削除・印刷・エクスポート・画像ダウンロード
 *
 * @package neoneng
 */

global $n2;
?>
<div id="n2-checked-posts" :class="active ? 'is-active': ''" v-if="ids.length" style="display: none;">
	<div id="n2-checked-posts-header">
		<span v-text="`${ids.length} 件選択中`"></span>
		<span class="dashicons dashicons-no-alt" @click="active = ! active"></span>
		<ul id="n2-checked-posts-actions">
			<li>
				エクスポート
				<div class="childs">
					<form method="post" action="admin-ajax.php" target="_blank">
						<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
						<input type="hidden" name="include[]" v-for="id in ids" :value="id">
						<div style="margin-bottom: 1em;">
							<span>フォーマット選択 ：　</span>
							<label><input type="radio" name="action" value="n2_item_export_base" checked> N2</label>
							<label><input type="radio" name="action" value="n2_item_export_ledghome"> LedgHOME</label>
							<?php if ( in_array( 'ふるさとチョイス', $n2->portal_sites, true ) ) : ?>
								<label><input type="radio" name="action" value="n2_item_export_furusato_choice"> ふるさとチョイス</label>
							<?php endif; ?>
							<?php if ( in_array( '楽天ふるさと納税', $n2->portal_sites, true ) ) : ?>
								<label><input type="radio" name="action" value="n2_item_export_rakuten"> 楽天 [ item.csv ]</label>
								<label><input type="radio" name="action" value="n2_item_export_rakuten_select"> 楽天 [ select.csv ]</label>
							<?php endif; ?>
						</div>
						<div style="margin-bottom: 1em;">
							<span>モード選択 ：　</span>
							<label><input type="radio" name="mode" value="download" checked> CSV・TSVダウンロード</label>
							<label><input type="radio" name="mode" value="spreadsheet"> スプレットシート貼付</label>
							<label><input type="radio" name="mode" value="debug"> デバッグモード</label>
						</div>
						<button>エクスポート実行</button>
						<div style="margin-top: 1em;">
							<input type="checkbox" name="include" value=""> 選択しているものに関わらず「<?php bloginfo( 'name' ); ?>」全返礼品を対象にする
						</div>
					</form>
				</div>
			</li>
			<li style="padding: 0;">
				<form method="post" action="admin-ajax.php" target="_blank">
					<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
					<input type="hidden" name="action" value="n2_print_out">
					<input type="hidden" name="ids" :value="ids.join(',')">
					<button>印刷</button>
				</form>
			</li>
			<li style="padding: 0;" v-if="items.filter(v=>v.商品画像 && v.商品画像.length).length">
				<form method="post" action="admin-ajax.php">
					<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
					<input type="hidden" name="action" value="n2_download_images_by_id">
					<input type="hidden" name="ids" :value="ids.join(',')">
					<button>画像ダウンロード</button>
				</form>
			</li>
			<li>
				情報変更
				<div class="childs">
					<form method="post" action="admin-ajax.php" onsubmit="if ( ! confirm('本当に変更してよろしいですか？') ) return false;">
						<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
						<input type="hidden" name="include[]" v-for="id in ids" :value="id">
						<input type="hidden" name="action" value="n2_items_api">
						<input type="hidden" name="mode" value="update">
						<div style="margin-bottom: 1em;">
							<span>ステータス変更 ：　</span>
							<select name="change_status">
								<option value="">選択してください</option>
								<option value="draft">入力中</option>
								<option value="pending">スチームシップ確認中</option>
								<option value="publish">ポータル登録準備中</option>
								<option value="registered">ポータル登録済</option>
								<option value="trash">ゴミ箱</option>
							</select>
						</div>
						<div style="margin-bottom: 1em;">
							<span>ユーザー変更 ：　</span>
							<select name="change_author">
								<option value="">選択してください</option>
								<?php foreach ( get_users( 'role=jigyousya' ) as $user ) : ?>
									<option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<button>変更</button>
						<div style="margin-top: 1em;">
							<input type="checkbox" name="include" value=""> 選択しているものに関わらず「<?php bloginfo( 'name' ); ?>」全返礼品を対象にする
						</div>
					</form>
				</div>
			</li>
		</ul>
	</div>
	<div id="n2-checked-posts-content">
		<table class="widefat striped">
			<thead>
				<tr>
					<template v-for="name in thead">
						<th v-if="name == ''" @click="clear_ids()" style="cursor: pointer">全解除</th>
						<th v-else v-text="name"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in items">
					<template v-for="name in thead">
						<td v-if="name == ''"><span class="dashicons dashicons-remove" @click="clear_ids(item.id)"></span></td>
						<td v-else v-text="item[name]"></td>
					</template>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="n2-checked-posts-toggler" @click="active = ! active"></div>
</div>
