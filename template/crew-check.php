<?php
/**
 * template/crew-check.php
 *
 * @package neoneng
 */

?>

<?php if ( 'check' === $_GET['crew'] ) : ?>

<section class="container">
	<h2 class="display-12 p-2 border-bottom border-success border-3">クルー専用事業者確認状況チェック</h2>
	<p>事業者の返礼品確認状況（<span class="text-danger">確認ボタンを押したかどうか</span>）を確認することができます。</p>
	<p>※確認未の商品のみ表示しております</p>
	<table class="table table-hover">
	<tbody>
		<tr class='text-center'>
			<th class='col-2'>事業者名</th>
			<th class='col'>コード</th>
			<th class='col'>商品名</th>
			<th class='col'>公開日</th>
			<th class='col-1'>確認状況</th>
			<th class='col'>確認パラメータ最終更新日</th>
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
					'value' => '修正希望',
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
		$checked_value = ! empty( get_post_meta( $post->ID, '事業者確認', true ) ) ? get_post_meta( $post->ID, '事業者確認', true )[0] : 確認未;
		?>
		<tr>
			<td><a href="<?php echo home_url() . '?jigyousya=' . $post->post_author . '&look=true'; ?>" target='_blank'><?php echo get_the_author_meta( 'display_name', $post->post_author ); ?></a></td>
			<td><?php echo get_post_meta( $post->ID, '返礼品コード', true ); ?></td>
			<td><a href="<?php echo get_permalink( $post->ID ); ?>&look=true" target='_blank'><?php echo $post->post_title; ?></a></td>
			<td><?php echo $post->post_date; ?></td>
			<td class='text-center <?php echo '修正希望' === $checked_value ? 'bg-danger text-white' : ''; ?>'><?php echo $checked_value; ?></td>
			<td>
				<?php echo ! empty( get_post_meta( $post->ID, '事業者確認', true ) ) ? get_post_meta( $post->ID, '事業者確認', true )[1] : '更新なし'; ?><br>
				<?php echo ! empty( get_post_meta( $post->ID, '事業者確認', true ) ) ? get_post_meta( $post->ID, '事業者確認', true )[2] : ''; ?>
			</td>
		</tr>
	
	<?php endforeach; ?>
	</tbody>
	</table>
</sectoin>

<?php endif ?>

<?php if ( 'comment' === $_GET['crew'] ) : ?>
	<section class="n2-crew-check container">
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
