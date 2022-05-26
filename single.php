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
	/* パターン1 */
	.progressbar {
		margin: 40px 0;
		padding: 0;
		height: 150px;
		counter-reset: step;
		z-index: 0;
		position: relative;
	}
	.progressbar li {
		list-style-type: none;
		width: 25%;
		float: left;
		font-size: 24px;
		position: relative;
		text-align: center;
		text-transform: uppercase;
		color: #aaa;
	}
	.progressbar li:before {
		width: 120px;
		height: 120px;
		content: counter(step);
		counter-increment: step;
		line-height: 120px;
		display: block;
		text-align: center;
		margin: 0 auto 10px auto;
		border-radius: 50%;
		background-color: #eee;
	}
	.progressbar li:after {
		width: 100%;
		height: 4px;
		content: '';
		position: absolute;
		background-color: #eee;
		top: 60px;
		left: -50%;
		z-index: -1;
	}
	.progressbar li:first-child:after {
		content: none;
	}
	.progressbar li.active {
		color: #1a4899;
	}
	.progressbar li.active:before {
		background-color: #1a4899;
		color:#fff;
	}
	.progressbar li.active + li:after {
		background-color: #1a4899;
	}

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

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<?php if ( 'publish' !== get_post_status() ) : ?>

		<h1><?php the_title(); ?></h1>	

		<ul class="progressbar">
			<li class="">商品基本情報入力</li>
			<li class="active">スチームシップ確認作業</li>
			<li>スチームシップ確認済み</li>
			<li>ポータルサイト登録</li>
		</ul>

		<table>
			<tr><th width="30%">項目</th><th width="70%">内容</th></tr>
				<?php foreach ( $fields as $key => $value ) : ?>
					<?php
					preg_match( '/画像/', $key, $m );
					if ( $m[0] && ! empty( $post_data[ $key ] ) ) :
						?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><img src=<?php echo $post_data[ $key ]; ?> width='200px'></td>
					</tr>
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
							} else {
								$cheked = 'なし';
							}
						}
						?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><?php echo 'select' === $value['type'] ? $new_options[ $post_data[ $key ] ] : $cheked; ?></td>
					</tr>
				<?php else : ?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><?php echo $post_data[ $key ] ? preg_replace( '/\n/', '<br>', $post_data[ $key ] ) : '入力無し'; ?></td>
					</tr>
				<?php endif; ?>
				<?PHP endforeach; ?>
		</table>
	<?php else : ?>
		<p>公開中の商品は違う感じの表示にする。</p>
		<?php
	endif;
endwhile;
endif;


get_footer();
