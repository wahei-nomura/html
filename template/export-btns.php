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
		<a href="#" class="button sisbtn" id="report">月次報告書</a>
		<a href="#" class="button sisbtn" id="export">エクスポート</a>
		<a href="#" class="button button-primary sisbtn" id="ledghome">Ledg HOME</a>
		<a href="#" class="button button-primary sisbtn" id="choice">チョイス</a>
		<a href="#" class="button button-primary sisbtn" id="item_csv">楽天item</a>
		<a href="#" class="button button-primary sisbtn" id="select_csv">楽天select</a>
		<a href="#" class="button button-primary siserror" id="error_log">楽天エラーログ</a>
		<a href="#" class="button button-primary sisbtn" id="ana">ANA</a>
		<a href="#" class="button button-primary dlbtn" id="download_by_url">画像をダウンロード</a>
	</div>
</div>

<script>
	jQuery(function($){
		$('.subsubsub').before($('#neo-neng-export-btns'));
	})
</script>
