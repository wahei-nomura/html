<?php
/**
 * 投稿の履歴閲覧
 *
 * @package neoneng
 * $argsにget_template_partの第３引数
 */

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
	<div id="n2-history" @click="say_unko" class="p-5">
		{{unko}}
	</div>
	<pre><?php print_r( $args );?></pre>
</body>
</html>
