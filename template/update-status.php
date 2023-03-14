<?php
	/**
	 * template/copy-post.php
	 *
	 * @package neoneng
	 */

?>

<!DOCTYPE html>

<style>
	#n2-update-status-modal-wrapper {
		display: none;
	}

	#n2-update-status-modal {
		padding: 24px;
		overflow: scroll;
	}
	#n2-update-status-modal label {
		display: block;
		font-size: 18px;
		font-weight: bold;
		margin-bottom: 8px;
	}
	#n2-update-status-modal .form-block {
		position: relative;
		margin: 16px 0;
		padding: 16px;
		background-color: azure;
	}
	#n2-update-status-modal .form-block::after {
		content: '';
		position: absolute;
		bottom: -8px;
		left: 0;
		width: 100%;
		height: 1px;
		background-color: lightgray;
	}
	#n2-update-status-modal .close-btn {
		position: absolute;
		right: 16px;
		top: 16px;
		z-index: 1000;
		cursor: pointer;
	}
	#n2-update-status-modal .close-btn span {
		width: 32px;
		height: 32px;
		font-size: 32px;
	}
</style>

<div id="n2-update-status-modal-wrapper">
	<div id="n2-update-status-modal" class="media-modal-content">
		<form action="admin-ajax.php?action=N2_update-stauts" method="POST" id="n2-update-status-form">
			<div class="close-btn"><span class="dashicons dashicons-no"></span></div>
			<h1>ステータスの一括変更</h1>
			<select class="form-select" aria-label="変更後のステータス">
				<option selected>変更後のステータス</option>
				<option value="draft">入力中</option>
				<option value="pending">スチームシップ確認中</option>
				<option value="publish">ポータル登録準備中</option>
				<option value="registered">ポータル登録済</option>
			</select>
			<button type="button" class="btn btn-outline-primary btn-sm">一括変更</button>
			<p>以下の返礼品のステータスを一括変更します</p>
			<ul class="n2-selected-item-wrapper list-group"></ul>
		</form>
	</div>
</div>
