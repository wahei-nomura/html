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
	<?php wp_print_scripts( array( 'jquery' ) ); ?>
	<script src="<?php echo get_theme_file_uri( 'dist/js/view-post-history.js' ); ?>"></script>
	<link rel="stylesheet" href="<?php echo get_theme_file_uri( 'dist/css/view-post-history.css' ); ?>">
</head>
<body>
	<div id="n2-history" class="p-3">
		<table class="table mb-4 shadow" v-for="v in history" >
			<thead>
				<tr><td colspan="3" class="bg-secondary text-white">{{v.date}}　｜　{{v.author}}</td></tr>
				<tr>
					<th style="width: 10em;"></th>
					<th class="text-success">After</th>
					<th class="text-danger">Before</th>
				</tr>
			</thead>
			<tbody>
				<template v-for="(after, name) in v.after">
					<tr v-if="'商品画像' == name">
						<th>{{name}}</th>
						<td>
							<template v-for="img in after">
								<img :src="img.sizes.thumbnail.url || img.sizes.thumbnail">
							</template>
						</td>
						<td>
							<template v-if="v.before">
								<template v-for="img in v.before[name]">
									<img :src="img.sizes.thumbnail.url || img.sizes.thumbnail">
								</template>
							</template>
						</td>
					</tr>
					<tr v-else>
						<th>{{name}}</th>
						<td class="text-success">
							{{Array.isArray(after) ? after.join(', ') : after}}
						</td>
						<td class="text-danger">
							<template v-if="v.before">
								{{Array.isArray(v.before[name]) ? v.before[name].join(', ') : v.before[name]}}
							</template>
						</td>
					</tr>
				</template>
			</tbody>
		</table>
	</div>
</body>
</html>
