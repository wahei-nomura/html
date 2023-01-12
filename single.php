<?php
/**
 * single.php
 *
 * @package neoneng
 */
?>
<?php get_header(); ?>
<?php
	if ( ! empty( $_GET['look'] ) ) {
		get_template_part( 'template/front-manual' );
	}
	// 商品詳細ページ
	get_template_part( 'template/product-page/index' );

	if ( ! empty( $_GET['look'] ) ) {
		get_template_part( 'template/look-alert' );
		comments_template();
	}
?>
<?php get_footer(); ?>