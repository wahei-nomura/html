<?php
/**
 * upload
 *
 * @package neoneng
 */

$radio_count = 0;
?>
<div style="clear:both;padding:10px 0;">
	<form action="admin-ajax.php" target="_blank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="<?php echo $args['action']; ?>">
		<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
		<div style="margin-bottom: 1em;">
			<span>モード選択 ：　</span>
			<?php foreach( $args['radio'] as $value => $text ) : ?>
				<label><input type="radio" name="judge" value="<?php echo $value; ?>" <?php echo ( $radio_count ? '' : 'checked' ); ?>> <?php echo $text; ?></label>
				<?php ++$radio_count; ?>
			<?php endforeach; ?>
		</div>
		<input name="<?php echo $args['file']; ?>" type="file" multiple="multiple">
		<input type="submit" class="button" value="楽天に転送する">
	</form>
</div>
