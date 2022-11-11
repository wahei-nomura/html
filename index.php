<?php
/**
 * search.php
 *
 * @package neoneng
 */

$template = ! empty( $_GET['crew'] ) ? 'crew-check' : 'front-list';

?>
<?php get_header(); ?>

<?php
	if ( ! empty( $_GET['look'] ) ) {
		get_template_part( 'template/front-manual' );
	}
?>

<article class="product-wrap search">
	<?php
	if ( empty( $_GET['look'] ) ) {
		get_template_part( 'template/front-search' );
	}
	get_template_part( "template/{$template}" );
	?>
</article>
<?php get_footer(); ?>
