<?php
/**
 * single.php
 *
 * @package neoneng
 */
?>
<?php get_header(); ?>
<?php
	// 商品詳細ページ
	get_template_part('template/product-page/index');
	if ( ! empty( $_GET['look'] ) ) {
		comments_template();
	}
?>
<?php get_footer(); ?>