<?php
/**
 * class-n2-foodparam.phpのshow_food_modalメソッドで使用
 *
 * @package neoneng
 */

?>

<style>
	.ss-food-modal {
		position:fixed;
		top:50%;
		left:50%;
		z-index:100000;
		background-color: pink;
	}
</style>

<div class="ss-food-modal">
	<form>
		<h2>事業者様の食品取扱いの有無を登録</h2>
		<div>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<label for="foodyes"><input type="radio" name="food" id="foodyes" value="有">食品を取り扱っている</label>
			<label for="foodno"><input type="radio" name="food" id="foodno" value="無">食品を取り扱っていない</label>
		</div>
		<p>※返礼品登録時のアレルギー選択項目の表示に使用します。</p>
		<div>
			<button type="button" class="button button-primary sissubmit">更新する</button>
		</div>
	</form>
	<script>
		jQuery(function($){
			$('.sissubmit').on('click',()=>{
				setTimeout(()=>{
					$('.ss-food-modal').remove()
				},1000)
			})
		})
	</script>
</div>
