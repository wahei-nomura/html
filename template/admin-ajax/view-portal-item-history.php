<?php
/**
 * 投稿の履歴閲覧
 *
 * @package neoneng
 * $argsにget_template_partの第３引数
 */

global $n2;
$n2->history = $args;
// 表示用配列作成
$arr = array();
foreach ( $args as $i => $history ) {
	$arr[ $i ]['date'] = $history['date'];
	// 追加・削除・更新の生成
	$before = array_map( fn( $v ) => $v['goods_g_num'], $history['before'] );
	$after  = array_map( fn( $v ) => $v['goods_g_num'], $history['after'] );
	$codes  = array_unique( array( ...$before, ...$after ) );
	$plus   = array_values( array_diff( $after, $before ) );
	$minus  = array_values( array_diff( $before, $after ) );
	$update = array_diff( $codes, $minus, $plus );
	foreach ( compact( 'plus', 'minus', 'update' ) as $k => $codes ) {
		foreach ( $codes as $code ) {
			$before = array_reduce( array_filter( $history['before'], fn( $v ) => $code === $v['goods_g_num'] ), 'array_merge', array() );
			$after  = array_reduce( array_filter( $history['after'], fn( $v ) => $code === $v['goods_g_num'] ), 'array_merge', array() );
			// $arrに突っ込む
			$arr[ $i ][ $k ][ $code ]['url'] = $before['url'] ?? $after['url'];
			if ( isset( $before['parent_code'] ) ) {
				$arr[ $i ][ $k ][ $code ]['parent_code'] = $before['parent_code'];
				unset( $before['parent_code'] );
			}
			if ( isset( $after['parent_code'] ) ) {
				$arr[ $i ][ $k ][ $code ]['parent_code'] = $after['parent_code'];
				unset( $after['parent_code'] );
			}
			if ( 'update' === $k ) {
				$arr[ $i ][ $k ][ $code ]['before'] = array_diff( $before, $after );
				$arr[ $i ][ $k ][ $code ]['after']  = array_diff( $after, $before );
			}
		}
	}
}
$n2->poratal_item_history = $arr;// Vueでやるならいる
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>[履歴] <?php echo $n2->query->post->post_title; ?></title>
	<?php wp_print_styles( array( 'dashicons' ) ); ?>
	<link rel="stylesheet" href="<?php echo get_theme_file_uri( "dist/css/view-post-history.css?ver={$n2->cash_buster}" ); ?>">
</head>
<body style="mix-blend-mode: difference;">
	<div id="n2-history" class="p-3 bg-white">
	<?php foreach ( $arr as $v ) : ?>
		<table class="table mb-4 shadow">
			<thead>
				<tr>
					<td colspan="3" class="bg-secondary text-white">
						<div class="d-flex justify-content-between align-items-center">
							<div class="d-flex align-items-center">
								<span class="dashicons dashicons-clock me-2"></span><?php echo $v['date']; ?>
							</div>
						</div>
					</td>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $v['plus'] ) ) : ?>
				<tr>
					<th class="text-success py-3">追加</th>
					<td colspan="2">
						<?php foreach ( $v['plus'] as $code => $d ) : ?>
						<div>
							<a href="<?php echo $d['url']; ?>" target="_blank" class="btn btn-success btn-sm me-2 my-1">
								<?php echo $code; ?>
							</a>
							<?php if ( isset( $d['parent_code'] ) ) : ?>
							<?php foreach ( $d['parent_code'] as $c ) : ?>
							<small class="me-2 text-success"><?php echo $c; ?></small>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $v['minus'] ) ) : ?>
				<tr>
					<th class="text-danger py-3">削除・在庫無し</th>
					<td colspan="2">
					<?php foreach ( $v['minus'] as $code => $d ) : ?>
						<div>
							<a href="<?php echo $d['url']; ?>" target="_blank" class="btn btn-danger btn-sm me-2 my-1">
								<?php echo $code; ?>
							</a>
							<?php if ( isset( $d['parent_code'] ) ) : ?>
							<?php foreach ( $d['parent_code'] as $c ) : ?>
							<small class="me-2 text-danger"><?php echo $c; ?></small>
							<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $v['update'] ) ) : ?>
				<tr>
					<th style="width: 10em;">返礼品コード</th>
					<th class="text-success">After</th>
					<th class="text-danger">Before</th>
				</tr>
				<?php foreach ( $v['update'] as $code => $d ) : ?>
				<tr>
					<th>
						<a href="<?php echo $d['url']; ?>" target="_blank"><?php echo $code; ?></a>
						<?php if ( isset( $d['parent_code'] ) ) : ?>
						<?php foreach ( $d['parent_code'] as $pcode ) : ?>
						<div class="small fw-normal text-secondary"><?php echo $pcode; ?></div>
						<?php endforeach; ?>
						<?php endif; ?>
					</th>
					<td>
						<?php foreach ( $d['after'] as $name => $value ) : ?>
						<p><span class="badge bg-dark me-2"><?php echo $name; ?></span><?php echo $value; ?></p>
						<?php endforeach; ?>
					</td>
					<td>
						<?php foreach ( $d['before'] as $name => $value ) : ?>
						<p><span class="badge bg-dark me-2"><?php echo $name; ?></span><?php echo $value; ?></p>
						<?php endforeach; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
	</div>
</body>
</html>
