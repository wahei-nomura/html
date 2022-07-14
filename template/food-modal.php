<?php
/**
 * class-n2-foodparam.phpのshow_food_modalメソッドで使用
 *
 * @package neoneng
 */

?>

<style>
	.ss-food-modal {
		position: fixed;
		top: 50%;
		left: 50%;
		z-index: 10000000;
		transform: translate(-50%,-50%);
		padding: 60px;
		background-color: #fff;
		box-shadow: 0 5px 15px rgba(0,0,0,.7);
	}
	.ss-food-check {
		margin-top: 40px;
		display: flex;
		justify-content: space-around;
		background-color: lightblue;
		padding: 8px 16px;
		border-radius: 4px;
	}
	.ss-food-check label {
		user-select: none;
		font-size: 18px;
		font-weight: bold;
	}
	.ss-food-button {
		display: flex;
		justify-content: center;
		margin-top: 40px;
	}
</style>

<div class="ss-food-modal-wrapper media-modal-backdrop"></div>
<form class="ss-food-modal">
	<h2>初回ログインにあたり、事業者様の食品取扱の有無についてお尋ねします。</h2>
	<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
	<div class="ss-food-check">
		<label for="foodyes"><input type="radio" name="food" id="foodyes" value="有">食品を取り扱っている</label>
		<label for="foodno"><input type="radio" name="food" id="foodno" value="無">食品を取り扱っていない</label>
	</div>
	<p>
		※返礼品登録時のアレルギー選択項目の表示に使用します。<br>
		またこの設定は画面左側のメニュー<b>「食品取扱設定」</b>よりいつでも変更ができます。
	</p>
	<div class="ss-food-button">
		<button type="button" class="button button-primary sissubmit" disabled>登録する</button>
	</div>
</form>
<script>
	jQuery(function($){

		$('.ss-food-check input[type="radio"]').on('change',()=>{
			$('.ss-food-button button').prop('disabled',false);
		})

		$('.sissubmit').on('click',()=>{
			setTimeout(()=>{
				$('.ss-food-modal-wrapper').remove()
				$('.ss-food-modal').remove()
			},1000)
		})
	})
</script>
