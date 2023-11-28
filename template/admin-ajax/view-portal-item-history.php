<?php
/**
 * 投稿の履歴閲覧
 *
 * @package neoneng
 * $argsにget_template_partの第３引数
 */

global $n2;
$n2->history = $args;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>[履歴] <?php echo get_the_title( $args[0]['ID'] ); ?></title>
	<?php wp_print_styles( array( 'dashicons' ) ); ?>
	<link rel="stylesheet" href="<?php echo get_theme_file_uri( "dist/css/view-post-history.css?ver={$n2->cash_buster}" ); ?>">
</head>
<body style="mix-blend-mode: difference;">
	<div id="n2-history" class="p-3 bg-white">
	<?php foreach ( $n2->history as $v ) : ?>
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
				<?php if ( ! empty( $v['add'] ) ) : ?>
				<tr>
					<th class="text-success py-3">追加</th>
					<td colspan="2">
						<?php foreach ( $v['add'] as $code => $d ) : ?>
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
				<?php if ( ! empty( $v['delete'] ) ) : ?>
				<tr>
					<th class="text-danger py-3">削除・在庫無し</th>
					<td colspan="2">
					<?php foreach ( $v['delete'] as $code => $d ) : ?>
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
