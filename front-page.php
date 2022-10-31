<?php
/**
 * front-page.php
 *
 * @package neoneng
 */

$template = ! empty( $_GET['crew'] ) && 'check' === $_GET['crew'] ? 'crew-check' : 'front-list';

?>
<?php get_header(); ?>
	<?php /*<div class="dn" style="display:none;">*/?>
	<article class="product-wrap front">
		<?php get_template_part( 'template/front-search' ); ?>
		<?php get_template_part( "template/{$template}" ); ?>
	</article>
	<?php /*</div><!--dn-->*/?>
<?php get_footer(); ?>