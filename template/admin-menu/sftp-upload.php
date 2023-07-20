<?php
/**
 * upload
 *
 * @package neoneng
 */

?>
<div style="clear:both;padding:10px 0;">
	<form action="admin-ajax.php" target="_blank" method="post" enctype="multipart/form-data">
		<input type="hidden" name="action" value="n2_upload_to_rakuten">
		<div style="margin-bottom: 1em;">
			<span>モード選択 ：　</span>
			<label><input type="radio" name="judge" value="ftp_img" checked> 商品画像</label>
			<label><input type="radio" name="judge" value="ftp_file"> 商品CSV</label>
		</div>
		<input name="ftp_img[]" type="file" multiple="multiple">
		<input type="submit" class="button" value="楽天に転送する">
	</form>
</div>
