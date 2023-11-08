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
		<table class="table mb-4 shadow" v-for="(v,k) in history" v-if="v.after.RMS商品画像.変更後 !== null" >
			<thead>
				<tr>
					<td colspan="4" class="bg-secondary text-white">
						<div class="d-flex justify-content-between align-items-center">
							<div class="d-flex align-items-center">
								<span class="dashicons dashicons-clock me-2"></span>{{v.date}}　｜　<span class="dashicons dashicons-admin-users me-2"></span>{{v.author}}
							</div>
							<div class="btn btn-sm btn-dark" @click="popover_revision_data(v.ID)"><span class="dashicons dashicons-visibility me-1"></span>この時を見る</div>
						</div>
					</td>
				</tr>
				<tr>
					<th style="width: 10%;"></th>
					<th class="text-success" style="width:40%">共通</th>
					<th class="text-secondary" style="width:25%">追加</th>
					<th class="text-danger" style="width:25%">解除</th>
				</tr>
			</thead>
			<tbody>
				<template v-for="(after, name) in v.after.RMS商品画像.変更後">
					<tr>
						<th>{{name}}</th>
						<td>
							<div class="d-flex">
								<div
									class="card me-1"
									v-for="img in after.filter(img=>v.after.RMS商品画像.変更前[name].indexOf(img) !== -1 )"
									>
									<img
									witdh="125px" height="125px"
									class="card-img-top"
									:src="n2.settings.楽天.商品画像ディレクトリ.replace(/\/$/,'') + img.replace(/^(\/item\/)/,'/')"
									>
									<div class="card-img-overlay p-0 d-flex align-items-end justify-content-center">
										<h6 class="card-title text-nowrap text-center py-2  mb-0 bg-light w-100">{{img.split('/').slice(-1)[0]}}</h6>
									</div>
								</div>
							</div>
						</td>
						<td>
							<div class="d-flex">
								<div
									class="card me-1"
									v-for="img in after.filter(img=>v.after.RMS商品画像.変更前[name].indexOf(img) === -1 )"
									>
									<img
									witdh="125px" height="125px"
									class="card-img-top"
									:src="n2.settings.楽天.商品画像ディレクトリ.replace(/\/$/,'') + img.replace(/^(\/item\/)/,'/')"
									>
									<div class="card-img-overlay p-0 d-flex align-items-end justify-content-center">
										<h6 class="card-title text-nowrap text-center py-2 mb-0 bg-light w-100 ">{{img.split('/').slice(-1)[0]}}</h6>
									</div>
								</div>
							</div>
						</td>
						<td>
							<dib class="d-flex">
								<div
									class="card me-1"
									v-for="img in v.after.RMS商品画像.変更前[name].filter(img=> after.indexOf(img) === -1 )"
								>
									<img
										witdh="125px" height="125px"
										class="card-img-top"
										:src="n2.settings.楽天.商品画像ディレクトリ.replace(/\/$/,'') + img.replace(/^(\/item\/)/,'/')"
									>
									<div class="card-img-overlay p-0 d-flex align-items-end justify-content-center">
										<h6 class="card-title text-nowrap text-center py-2 mb-0 bg-light w-100 ">{{img.split('/').slice(-1)[0]}}</h6>
									</div>
								</div>
							</div>
						</td>
					</tr>
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
					<span class="dashicons dashicons-backup me-1"></span>商品画像を<b>変更前</b>に戻す
				</div>
			</div>
			<div id="n2-history-chechout-revision-content" class="px-2">
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
