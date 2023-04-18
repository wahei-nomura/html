<?php
	/**
	 * template/copy-post.php
	 *
	 * @package neoneng
	 */

?>

<!DOCTYPE html>

<style>
	#n2-change-author-modal-wrapper {
		display: none;
	}

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
		<form action="admin-ajax.php?action=n2_post_author_update" method="POST" id="n2-change-author-form">
			<div class="close-btn"><span class="dashicons dashicons-no"></span></div>
			<h1>以下の返礼品の事業者を変更しますか？</h1>
			<div class="form-block">
				<h2 class="original-title"></h2>
				<p class="item-code">返礼品コード:<span><span></p>
				<p class="current-author">現在の作成者:<span><span></p>
			</div>
			<input type="hidden" name="post_id" value="">
			<div>
				<select name="author_id" class="form-select author-select" aria-label="変更後の事業者"></select>
				<button type="button" class="btn btn-outline-primary btn-sm">変更</button>
			</div>
		</form>
	</div>
</div>
