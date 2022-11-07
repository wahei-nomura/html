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
		width: 90%;
		overflow-x: scroll;
		table{
			border-spacing: 0;
			
			tr {
				&.normal.none {
					display: none;
				}
			}
						
			th{
				border-bottom: solid 2px #fb5144;
				padding: 10px 0;
				text-align: center;
			}

			td {
				.check-state {
					position: relative;
					cursor: pointer;
					color: green;
					&:hover {
						.hidden {
							display: block;
							opacity: 1;
						}
					}
					.hidden {
						display: none;
						opacity: 0;
						position: absolute;
						top: 0;
						right: 0;
						z-index: 100;
						width: 200px;
						background: white;
						padding: 16px;
						transition: .3s;
					}
				}
			}
		}
	}
</style>

<section class="n2-crew-check">
	<h2 class="display-12 p-2 border-bottom border-success border-3">クルー専用事業者確認状況チェック</h2>
	<p>事業者の返礼品確認状況（<span class="text-danger">確認ボタンを押したかどうか</span>）を確認することができます。</p>
	<button class="change-btn btn btn-success m-1">確認済みを非表示</button>
	<table class="table table-secondary table-hover">
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

			$check_param = get_post_meta( get_the_ID(), '事業者確認', true );
			$confirmed   = '' === $check_param || '確認未' === $check_param[0] ? false : true;
			?>
			<tr class="<?php echo ! $confirmed ? 'table-danger' : 'normal'; ?>">
				<td><?php the_author(); ?></td>
				<td><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ); ?></td>
				<td><a href="<?php the_permalink(); ?>" target=”_blank”><?php the_title(); ?></a></td>
				<td>
					<div class='<?php echo '' !== $check_param ? 'check-state' : ''; ?>'>
						<?php echo $confirmed ? '済' : '未'; ?>
						<div class='hidden'>
							<?php echo '' !== $check_param ? $check_param[1] : ''; ?>
							<?php echo '' !== $check_param ? $check_param[2] : ''; ?>
						</div>
					</div>
				</td>
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
