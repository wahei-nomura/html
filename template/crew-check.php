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
			
			tr:nth-child(even) {
				background: #eee;
			}
						
			th{
				border-bottom: solid 2px #fb5144;
				padding: 10px 0;
			}
			
			td{
				border-bottom: solid 2px #ddd;
				text-align: center;
				padding: 4px 16px;
				max-width: 600px; /* 省略せずに表示するサイズを指定 */
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
		}
	}
</style>

<section class="n2-crew-check">
	<table>
	<tbody>
		<tr>
			<th>事業者名</th>
			<th>商品名</th>
			<th>確認状況</th>
			<th>公開日</th>
		</tr>
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>
			<tr>
				<td><?php the_author(); ?></td>
				<td><a href="<?php the_permalink(); ?>" target=”_blank”><?php the_title(); ?></a></td>
				<td>未</td>
				<td>2022-10-31</td>
			</tr>
			<?php
		endwhile;
	endif;
	?>
</tbody>
</table>
</sectoin>
