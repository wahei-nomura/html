<?php
/**
 * template/crew-check.php
 *
 * @package neoneng
 */

?>
<!-- Load sass.js -->
<script src="https://cdn.jsdelivr.net/gh/steamships/in-browser-sass/dist/in-browser-sass.bundle.min.js"></script>

<style type="text/scss">
	.n2-crew-check {
		table{
			width: 100%;
			border-spacing: 0;
			
			tr {
				background: #eee;
				&.aleart {
					background-color: pink;
				}
				&.normal.none {
					display: none;
				}
			}
						
			th{
				border-bottom: solid 2px #fb5144;
				padding: 10px 0;
			}
			
			td{
				border-bottom: solid 2px #ddd;
				text-align: center;
				padding: 4px 16px;
				max-width: 500px; /* 省略せずに表示するサイズを指定 */
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
		}
		.change-btn {
			cursor: pointer;
			margin-bottom: 8px;
			padding: 4px 8px;
		}
	}
</style>

<section class="n2-crew-check">
	<h1>事業者確認状況（クルー専用ページ）</h1>
	<button class="change-btn">確認済みを非表示</button>
	<table>
	<tbody>
		<tr>
			<th>事業者名</th>
			<th>コード</th>
			<th>商品名</th>
			<th>確認状況</th>
			<th>公開日</th>
		</tr>
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			$item_checked = ! empty( get_post_meta( get_the_ID(), '事業者確認', true ) ) ? true : false;
			?>
			<tr class="<?php echo ! $item_checked ? 'aleart' : 'normal'; ?>">
				<td><?php the_author(); ?></td>
				<td><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ); ?></td>
				<td><a href="<?php the_permalink(); ?>" target=”_blank”><?php the_title(); ?></a></td>
				<td><?php echo $item_checked ? '済' : '未'; ?></td>
				<td><?php the_modified_date(); ?></td>
			</tr>
			<?php
		endwhile;
	endif;
	?>
</tbody>
</table>
</sectoin>

<script>
	jQuery(function($){
		$('.n2-crew-check .change-btn').on('click', ()=>{
			$('.n2-crew-check .normal').toggle('none')
		})
	})
</script>
