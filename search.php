<?php
/**
 * search.php
 *
 * @package neoneng
 */

$template = ! empty( $_GET['crew'] ) && 'check' === $_GET['crew'] ? 'crew-check' : 'front-list';

?>
<?php get_header(); ?>

<article class="product-wrap search">
	<?php get_template_part( 'template/front-search' ); ?>
	<?php get_template_part( "template/{$template}" ); ?>
</article>
<?php get_footer(); ?>