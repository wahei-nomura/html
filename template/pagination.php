<?php
/**
 * template/paginaiton.php
 *
 * @package neoneng
 */

?>

<style>
	.pagination{
		margin:16px;
	}
	.nav-links{
		display:flex;
	}
	.pagination .page-numbers{
		width: 32px;
		text-align: center;
		display:inline-block;
		margin-right:8px;
		padding:4px 6px;
		color:#333;
		border-radius:3px;
		box-shadow:0 3px 3px #999;
		background:#fff;
		text-decoration: none;
	}
	.pagination .current{
	background:#69a4db;
	color:#fff;
	}
	.pagination .prev,
	.pagination .next{
	background:transparent;
	box-shadow:none;
	color:#69a4db;
	}
	.pagination .dots{
	background:transparent;
	box-shadow:none;
	}
</style>
<?php
	the_posts_pagination(
		array(
			'mid_size'      => 2, // 現在ページの左右に表示するページ番号の数
			'prev_next' => false, // 「前へ」「次へ」のリンクを表示する場合はtrue
			'prev_text' => __( '前へ' ), // 「前へ」リンクのテキスト
			'next_text' => __( '次へ' ), // 「次へ」リンクのテキスト
		)
	);
?>
