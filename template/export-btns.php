<?php
/**
 * template/export.php
 *
 * @package neoneng
 */

?>

<div id="neo-neng-export-btns">
<div style="clear:both;padding:10px 0;">
		<form class="sisfile">
			<input name="ftp_img[]" type="file" multiple="multiple">
			<input type="submit" class="button" value="楽天に商品画像転送">
		</form>
	</div>
	<div style="clear:both;padding:10px 0;">
		<form class="sisfile">
			<input name="ftp_file[]" type="file" multiple="multiple">
			<input type="submit" class="button" value="楽天に商品CSV転送">
		</form>
	</div>
	<div style="clear:both;padding:10px 0;">
		<a href="#" class="button sisbtn" id="print">印刷</a>
		<a href="#" class="button button-primary sisbtn" id="n2_item_export_base">エクスポート</a>
		<a href="#" class="button button-primary sisbtn" id="n2_item_export_ledghome">Ledg HOME</a>
		<a href="#" class="button button-primary sisbtn" id="n2_item_export_furusato_choice">チョイス</a>
		<a href="#" class="button button-primary sisbtn" id="n2_item_export_rakuten">楽天</a>
		<a href="#" class="button button-primary siserror" id="error_log">楽天エラーログ</a>
		<a href="#" class="button button-primary dlbtn" id="download_by_url">画像をダウンロード</a>
		<button type="button" class="button button-primary" id="bulk_update_status">ステータス一括変更</button>
	</div>
</div>

<script>
	jQuery(function($){
		$('.subsubsub').before($('#neo-neng-export-btns'));
	})
</script>
