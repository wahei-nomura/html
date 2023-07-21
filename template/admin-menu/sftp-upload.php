<?php
/**
 * upload
 *
 * @package neoneng
 */

?>
<div style="clear:both;padding:10px 0;">
	<form action="admin-ajax.php" target="_blank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="n2_upload_to_rakuten_sftp">
		<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
		<div style="margin-bottom: 1em;">
			<span>モード選択 ：　</span>
			<label><input type="radio" name="judge" value="sftp_img" checked> 商品画像</label>
			<label><input type="radio" name="judge" value="sftp_csv"> 商品CSV</label>
		</div>
		<input name="sftp_file[]" type="file" multiple="multiple">
		<input type="submit" class="button" value="楽天に転送する">
	</form>
</div>
