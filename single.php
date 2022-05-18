<?php
/**
 * single.php
 *
 * @package neoneng
 */

global $post;
$post_data = get_post_meta( $post->ID, 'post_data', true );

$ini = parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true );

// プラグインn2-developのn2_setpost_show_customfields呼び出し
$fields = apply_filters( 'n2_setpost_show_customfields', array( $ini, 'default' ) )[0];

?>

<style>
	table{
		border-collapse: collapse;
		border-spacing: 0;
	}
	table th,table td{
		padding: 10px 24px;
		text-align: center;
	}
	table tr:nth-child(odd){
		background-color: #eee;
	}
</style>

<?php get_header(); ?>

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		if ( 'publish' !== get_post_status() ) :
			?>

	<h1><?php the_title(); ?></h1>	

	<table>
			<?php
			foreach ( $fields as $key => $value ) :
				preg_match( '/画像/', $key, $m );
				if ( $m[0] && ! empty( $post_data[ $key ] ) ) :
					?>
		<tr>
			<td><?php echo $key; ?></td>
			<td><img src=<?php echo $post_data[ $key ]; ?> width='200px'></td>
		</tr>
				<?php else : ?>
		<tr>
			<td><?php echo $key; ?></td>
			<td><?php echo $post_data[ $key ] ? $post_data[ $key ] : '入力無し'; ?></td>
		</tr>
		<?php endif; ?>
			<?PHP endforeach; ?>
	</table>
			<?php endif; ?>
			<p>公開中の商品は違う感じの表示にする。</p>
		<?php
endwhile;
endif;

get_footer();
