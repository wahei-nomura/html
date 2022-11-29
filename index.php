<?php
/**
 * index.php
 *
 * @package neoneng
 */

 // 2022-11-29 コメントアウト taiki
// ネットワークトップサイト
// if ( is_main_site() ) {
// 	if ( 'wp-multi.ss.localhost' === get_network()->domain || in_array( $_SERVER['REMOTE_ADDR'], N2_IPS ) ) {
		// get_template_part( 'template/all-town' );
// 	} else {
// 		echo "このページにはアクセスできません（{$_SERVER['REMOTE_ADDR']}）";
// 	}
// 	exit;
// }

// 2022-11-29 コメントアウト taiki
// $template = ! empty( $_GET['crew'] ) ? 'crew-check' : 'front-list';

if ( is_main_site() ) {
	get_template_part( 'template/all-town' );
	exit;
}

?>
<?php get_header(); ?>

<?php
	// 2022-11-29 コメントアウト taiki
	// if ( ! empty( $_GET['look'] ) ) {
	// 	get_template_part( 'template/front-manual' );
	// 	get_template_part( 'template/look-alert' );
	// }
?>

<article class="product-wrap search<?php if(is_search()){ echo ' search-result'; } ?>">
	<?php
	// 2022-11-29 コメントアウト taiki
	// if ( empty( $_GET['look'] ) && empty( $_GET['crew'] ) ) {
		get_template_part( 'template/front-search' );
	// }
	// get_template_part( "template/{$template}" );
	get_template_part( "template/front-list" );
	?>
</article>
<?php get_footer(); ?>
