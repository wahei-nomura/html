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

<?php if ( 'check' === $_GET['crew'] ) : ?>

<section class="n2-crew-check">
	<h2 class="display-12 p-2 border-bottom border-success border-3">クルー専用事業者確認状況チェック</h2>
	<p>事業者の返礼品確認状況（<span class="text-danger">確認ボタンを押したかどうか</span>）を確認することができます。</p>
	<button class="change-btn btn btn-success m-1">確認済みを非表示</button>
	<table class="table table-hover">
	<tbody>
		<tr>
			<th>事業者名</th>
			<th>コード</th>
			<th>商品名</th>
			<th>公開日</th>
			<th>確認状況</th>
			<th>確認パラメータ最終更新日</th>
		</tr>
	<?php
	$posts = get_posts(
		array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '事業者確認',
					'value' => '確認未',
					'compare' => 'LIKE',
				),
				array(
					'key' => '事業者確認',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

		foreach ( $posts as $post ) :
			// var_dump($post);
			// $check_param = get_post_meta( get_the_ID(), '事業者確認', true );
			// $confirmed   = '' === $check_param || '確認未' === $check_param[0] ? false : true;
			?>
			<tr>
				<td><a href="<?php echo home_url() . '?jigyousya=' . $post->post_author . '&look=true'; ?>" target='_blank'><?php echo get_the_author_meta( 'display_name', $post->post_author ); ?></a></td>
				<td><?php echo get_post_meta( $post->ID, '返礼品コード', true ); ?></td>
				<td><a href="<?php echo get_permalink( $post->ID ); ?>&look=true" target='_blank'><?php echo $post->post_title; ?></a></td>
				<td><?php echo $post->post_date; ?></td>
				<td>未</td>
				<td><?php echo ! empty( get_post_meta( $post->ID, '事業者確認', true ) ) ? get_post_meta( $post->ID, '事業者確認', true )[1] : '更新なし'; ?></td>
			</tr>
		
		<?php endforeach; ?>
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

<?php endif ?>

<?php if ( 'comment' === $_GET['crew'] ) : ?>
	<section class="n2-crew-check">
	<h2 class="display-12 p-2 border-bottom border-success border-3">クルー専用コメント確認ページ</h2>
	<p>返礼品ごとのコメント状況を確認できます</p>
	<div class='accordion accordion-flush' id='accordionFlushExample'>

	<?php
	// コメントを取得するための引数
	$get_comments_args = array(
		'type'    => 'comment',
		'orderby' => 'comment_post_ID',
	);

	$now_id = '';
	// コメント一覧を取得して1つずつ出力
	foreach ( get_comments( $get_comments_args ) as $comment ) :
		if ( $now_id === $comment->comment_post_ID ) {
			continue;
		}
		$now_id = $comment->comment_post_ID;

		if ( 'スチームシップ' !== $comment->comment_author ) :
		?>
		<div class="accordion-item">
			<h2 class="accordion-header" id="headingOne">
				<button class="row accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-<?php echo $comment->comment_post_ID; ?>" aria-expanded="false" aria-controls="flush-collapseOne">
					<span class="col"><?php echo $comment->comment_author ; ?></span>
					<span class="col"><?php echo $comment->comment_date ; ?></span>
				</button>
			</h2>
			<div id="flush-<?php echo $comment->comment_post_ID; ?>" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
				<div class="accordion-body">
					<p><a href="<?php echo get_the_permalink( $comment->comment_post_ID ) . '&look=true'; ?>" target="_blank"><?php echo get_the_title( $comment->comment_post_ID ) ; ?></a></p>
					<p><?php echo $comment->comment_content ; ?></p>
				</div>
			</div>
		</div>
	<?php
		endif;
	endforeach;
	?>

	</div>
</sectoin>
<?php endif ?>
