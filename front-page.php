<?php
/**
 * front-page.php
 *
 * @package neoneng
 */

?>
<?php get_header(); ?>
	<?php /*<div class="dn" style="display:none;">*/?>
	<article class="product-wrap front">
		<?php get_template_part( 'template/front-search' ); ?>
		<?php get_template_part( 'template/front-list' ); ?>
	</article>
	<?php /*</div><!--dn-->*/?>
<?php get_footer(); ?>