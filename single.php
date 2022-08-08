<?php
/**
 * single.php
 *
 * @package neoneng
 */

global $post;
$post_data = N2_Functions::get_all_meta( $post );
echo esc_html(get_post_type_object(get_post_type())->name);
$ini = parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true );

// プラグインn2-developのn2_setpost_show_customfields呼び出し
$fields = apply_filters( 'n2_setpost_show_customfields', $ini, 'default' );

?>

<style>
	table{
		table-layout: fixed;
		width: 100%;
		border-collapse: collapse;
		border-spacing: 0;
	}
	table th,table td{
		padding: 10px 24px;
	}
	table td:nth-child(odd){
		text-align: center;
	}
	table tr:nth-child(odd){
		background-color: #eee;
	}
	table th {
		position: sticky;
		top: 0;
		z-index: 1;
		background-color: gray;
		color:#fff;
	}
</style>

<?php get_header(); ?>
<body <?php body_class(); ?>>


<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
				<?php if ( 'publish' !== get_post_status() ) : ?>
		<!-- プログレストラッカー -->
		<?php get_template_part( 'template/progress' ); ?>
					<?php endif; ?>

		<h1><?php the_title(); ?></h1>	


		<table>
			<tr><th width="30%">項目</th><th width="70%">内容</th></tr>
				<?php foreach ( $fields as $key => $value ) : ?>
					<?php
					preg_match( '/画像/', $key, $m );
					if ( ! empty( $m[0] ) && $m[0] && ! empty( $post_data[ $key ] ) ) :
						?>
						<?php
						foreach( $post_data[ $key ] as $pickey => $postpicture):
							$picno = $pickey + 1;
						?>
					<tr>
						<td><?php echo $key . $picno ; ?></td>
						<td><img src="<?php echo ! empty( $postpicture ) ? $postpicture : ''; ?>" width='200px'></td>
					</tr>
						<?php
						endforeach;
						?>
						<?php
						elseif ( 'checkbox' === $value['type'] || 'select' === $value['type'] ) :
							$new_options = array();
							$options     = explode( ',', $value['option'] );
							foreach ( $options as $option ) {
								$new_options[ explode( '\\', $option )[0] ] = explode( '\\', $option )[1];
							}
							$cheked = '';
							if ( 'checkbox' === $value['type'] ) {
								if ( ! empty( $post_data[ $key ] ) ) {
									foreach ( $post_data[ $key ] as $chekedkey ) {
										$cheked .= $new_options[ $chekedkey ] . ',';
									}
								}
							} else {
								$cheked = 'なし';
							};
							?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><?php echo 'select' === $value['type'] ? $new_options[ $post_data[ $key ] ] : $cheked; ?></td>
					</tr>
					<?php else : ?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><?php echo ! empty( $post_data[ $key ] ) ? nl2br( $post_data[ $key ] ) : '入力無し'; ?></td>
					</tr>
				<?php endif; ?>
				<?PHP endforeach; ?>
		</table>
	<?php if ( 'publish' === get_post_status() ) : ?>
		<?php /* echo is_user_logged_in() ? get_template_part( 'template/progress' ) : '';*/ ?>
		<p>公開中の商品は違う感じの表示にする。</p>

</body>
		<?php
	endif;
endwhile;
endif;


get_footer();
