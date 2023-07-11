<?php
	/**
	 * template/change-sku.php
	 *
	 * @package neoneng
	 */

?>

<!DOCTYPE html>

<style>
	#n2-change-author-modal {
		padding: 24px;
	}
</style>

<div id="n2-change-author-modal-wrapper">
	<div id="n2-change-author-modal" class="media-modal-content">
		<form action="/wp-admin/admin-ajax.php?action=n2_change_sku_firstaid&mode=debug" target="_blank" method="post" enctype="multipart/form-data">
				<input name="item_files[]" type="file" multiple="multiple">
				<input type="submit" class="button" value="item.csvをnormal-item.csv(SKU対応版)に変換">
		</form>

	</div>
</div>
