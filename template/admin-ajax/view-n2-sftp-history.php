<?php
/**
 * 投稿の履歴閲覧
 *
 * @package neoneng
 * $argsにget_template_partの第３引数
 */

global $n2;
$n2->history = $args;
wp_localize_script( 'jquery', 'n2', $n2 );
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>履歴</title>
	<?php wp_print_styles( array( 'dashicons' ) ); ?>
	<?php wp_print_scripts( array( 'jquery' ) ); ?>
	<script src="<?php echo get_theme_file_uri( "dist/js/view-n2-sftp-history.js?ver={$n2->cash_buster}" ); ?>"></script>
	<link rel="stylesheet" href="<?php echo get_theme_file_uri( "dist/css/view-post-history.css?ver={$n2->cash_buster}" ); ?>">
</head>
<body>
	<div id="n2-history" class="p-3">
		<table class="table mb-4 shadow" v-for="(v,k) in history" >
			<thead>
				<tr>
					<td colspan="3" class="bg-secondary text-white">
						<div class="d-flex justify-content-between align-items-center">
							<div class="d-flex align-items-center">
								<span class="dashicons dashicons-clock me-2"></span>{{v.date}}　｜　<span class="dashicons dashicons-admin-users me-2"></span>{{v.author}}
							</div>
							<div v-if="0 !== k" class="btn btn-sm btn-dark" @click="popover_revision_data(v.ID)"><span class="dashicons dashicons-visibility me-1"></span>この時見る</div>
						</div>
					</td>
				</tr>
				<tr>
					<th style="width: 10em;"></th>
					<th class="text-success">After</th>
					<th class="text-danger">Before</th>
				</tr>
			</thead>
			<tbody>
				<template v-for="(after, name) in v.after">
					<template v-if="name==='RMS画像一覧'" v-for="(td,th) in after">
						<tr>
							<th>{{th}}</th>
							<td class="text-success">
								<details>
									<table class="">
										<tbody>
											<tr v-for="(val,key) in td">
												<th>{{key}}</th>
												<td v-html="Array.isArray(val) ? val.join(', '): val"></td>
											</tr>
										</tbody>
									</table>
								</details>
							</td>
							<td class="text-danger">
								<template v-if="v.before?.[name]?.[th]">
								<details>
									<table class="">
										<tbody>
											<tr v-for="(val,key) in v.before[name][th]">
												<th>{{key}}</th>
												<td v-html="Array.isArray(val) ? val.join(', '): val"></td>
											</tr>
										</tbody>
									</table>
								</details>
								</template>
							</td>
						</tr>
					</template>
				</template>
			</tbody>
		</table>
		<!-- popover -->
		<div popover id="n2-history-chechout-revision" v-if="HTMLElement.prototype.hasOwnProperty('popover')" class="border-0 p-0">
			<div id="n2-history-chechout-revision-header" class="bg-dark text-white position-sticky top-0 d-flex justify-content-between align-items-center">
				<div id="n2-history-chechout-revision-close" @click="document.getElementById('n2-history-chechout-revision').hidePopover()">
					<span class="dashicons dashicons-no-alt" ></span>
				</div>
				<div id="n2-history-chechout-revision-update" class="btn btn-sm btn-success me-2 px-3" @click="checkout_revision">
					<span class="dashicons dashicons-backup me-1"></span>この時に戻す
				</div>
			</div>
			<div id="n2-history-chechout-revision-content">
				<table class="table">
					<template v-for="name in custom_field">
						<tr v-if="item[name]">
							<th style="text-align: left;">{{name}}</th>
							<td style="text-align: left;">
								<div v-if="Array.isArray(item[name])"
									v-html="item[name].join('<br>')"
								></div>
								<div v-else-if="typeof(item[name]) === 'object'">
									<table>
										<tbody>
											<tr v-for="(val,key) in item[name]">
												<th>{{key}}</th>
												<td v-html="Array.isArray(val) ? val.join(', '): val"></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div v-else>
									{{item[name]}}
								</div>
							</td>
						</tr>
					</template>
				</table>
				<div v-if="(item.商品画像 || []).length" id="n2-history-chechout-revision-content-imgs">
					<img :src="img.sizes.thumbnail.url || img.sizes.thumbnail" v-for="img in item.商品画像" >
				</div>
			</div>
		</div>
	</div>
</body>
</html>
