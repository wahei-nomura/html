<?php
/**
 * front-page.php
 *
 * @package neoneng
 */

?>
<?php get_header(); ?>
	<?php /*<div class="dn" style="display:none;">*/?>
	<?php if ( ! empty( $_GET['look'] ) && 'true' === $_GET['look'] || 'all' === $_GET['look'] ) : ?>
		<h1>商品確認ページ</h1>
		<p>商品の情報に誤りなどないかご確認後、それぞれの商品のOKボタンを押してください。</p>
	<?php endif; ?>
	<article class="product-wrap front">
		<?php get_template_part( 'template/front-search' ); ?>
		<?php get_template_part( 'template/front-list' ); ?>
	</article>
	<?php /*</div><!--dn-->*/?>
<?php get_footer(); ?>