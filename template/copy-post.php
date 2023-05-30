<?php
	/**
	 * template/copy-post.php
	 *
	 * @package neoneng
	 */

?>

<!DOCTYPE html>

<style>
	#n2-copypost-modal-wrapper {
		display: none;
	}

	#n2-copypost-modal {
		padding: 24px;
		overflow: scroll;
	}
	#n2-copypost-modal .new-title span {
		display: inline-block;
		font-size: 16px;
	}
	#n2-copypost-modal label {
		display: block;
		font-size: 18px;
		font-weight: bold;
		margin-bottom: 8px;
	}
	#n2-copypost-modal input[type="number"] {
		width: 60px;
	}
	#n2-copypost-modal input[type="text"] {
		width: 100%;
	}
	#n2-copypost-modal .form-block {
		position: relative;
		margin: 16px 0;
		padding: 16px;
		background-color: azure;
	}
	#n2-copypost-modal .form-block::after {
		content: '';
		position: absolute;
		bottom: -8px;
		left: 0;
		width: 100%;
		height: 1px;
		background-color: lightgray;
	}
	#n2-copypost-modal .close-btn {
		position: absolute;
		right: 16px;
		top: 16px;
		z-index: 1000;
		cursor: pointer;
	}
	#n2-copypost-modal .close-btn span {
		width: 32px;
		height: 32px;
		font-size: 32px;
	}
</style>

<div id="n2-copypost-modal-wrapper">
	<div id="n2-copypost-modal" class="media-modal-content">
		<form action="admin-ajax.php?action=N2_Copypost" method="POST" id="n2-copypost-form">
			<div class="close-btn"><span class="dashicons dashicons-no"></span></div>
			<input type="hidden" name="id" value="">
			<h1>返礼品の複製</h1>
			<div>
				複製元：<span class="original-title"></span>
				<p class="copypost-caution">【注意】「返礼品コード」等一部の項目は複製されません。</p>
			</div>
			<button class="button button-primary submit" type="submit">複製する</button>
		</form>
	</div>
