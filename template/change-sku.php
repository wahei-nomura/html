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
		overflow: scroll;
	}
	#n2-change-author-modal label {
		display: block;
		font-size: 18px;
		font-weight: bold;
		margin-bottom: 8px;
	}
	#n2-change-author-modal .form-block {
		position: relative;
		margin: 16px 0;
		padding: 16px;
		background-color: lightgray;
	}
	#n2-change-author-modal .form-block::after {
		content: '';
		position: absolute;
		bottom: -8px;
		left: 0;
		width: 100%;
		height: 1px;
	}
	#n2-change-author-modal .close-btn {
		position: absolute;
		right: 16px;
		top: 16px;
		z-index: 1000;
		cursor: pointer;
	}
	#n2-change-author-modal .close-btn span {
		width: 32px;
		height: 32px;
		font-size: 32px;
	}
</style>

<div id="n2-change-author-modal-wrapper">
	<div id="n2-change-author-modal" class="media-modal-content">
		<form action="/wp-admin/admin-ajax.php?action=n2_change_sku_firstaid" target="_blank" method="post" enctype="multipart/form-data">
				<input name="item_files[]" type="file" multiple="multiple">
				<input type="submit" class="button" value="item.csvをnormal-item.csv(SKU対応版)に変換">
		</form>

	</div>
</div>
