<?php
/**
 * upload
 *
 * @package neoneng
 */

$radio_count = 0;
?>
<div class="container mt-2 mb-4">
	<form action="admin-ajax.php" target="_blank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="<?php echo esc_attr( $args['action'] ); ?>">
		<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
		<div class="mb-2">
			<span>モード選択 ：　</span>
			<?php foreach ( $args['radio'] as $value => $text ) : ?>
				<label><input type="radio" name="judge" value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr( $radio_count ? '' : 'checked' ); ?>> <?php echo $text; ?></label>
				<?php ++$radio_count; ?>
			<?php endforeach; ?>
		</div>
		<div class="mb-2 input-group">
			<input class="form-control" name="<?php echo esc_attr( $args['file'] ); ?>" type="file" multiple="multiple" style="padding: 0.375rem 0.75rem;">
			<input type="submit" class="btn btn-outline-secondary" value="楽天に転送する">
		</div>
	</form>
</div>
