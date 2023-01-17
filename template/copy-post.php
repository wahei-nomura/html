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
			<h1>さあ返礼品を複製しよう！</h1>
			<div>
				複製元：<span class="original-title"></span>
			</div>
			<div class="form-block">
				<label>定期便として複製しますか？</label>
				<select name="定期">
				<?php
				for ( $i = 1; $i <= 12; $i++ ) {
					printf( '<option value="%d">%s</option>', $i, $i < 2 ? '定期便ではない' : "{$i}回定期便として複製" );
				}
				?>
					
				</select>
			</div>
			<div class="form-block">
				<label>返礼品名(【全〇〇回定期便】は自動追加されます)</label>
				<p class="new-title"><span></span><input type="text" name="複写後商品名"></p>
			</div>
			<div class="form-block is-teiki">
				<label>内容量・規格(追加または置換されます)</label>
				<div>※以下の内容を全◯◯回（月<input type="number" name="同月回数" min="1" max="12">回）お届けいたします。</div>
			</div>
			<div class="form-block is-teiki">
				<label>配送期間(追加または置換されます)</label>
				<div>※初回発送はお申込み翌月の<input type="number" name="初回発送日" min="1" max="31">日までに発送致します。なお2回目以降も毎月<input type="number"
						name="毎月発送日" min="1" max="31">日までに発送致します。</div>
			</div>
			<button class="button button-primary submit" type="submit">複製する</button>
		</form>
	</div>
