<?php
/**
 * class-n2-jigyousyaparam.phpのshow_login_modalメソッドで使用
 *
 * @package neoneng
 */

?>

<style>
	.ss-jigyousya-modal {
		position: fixed;
		top: 50%;
		left: 50%;
		z-index: 10000000;
		width: 50%;
		min-width: 800px;
		transform: translate(-50%,-50%);
		padding: 60px;
		background-color: #fff;
		box-shadow: 0 5px 15px rgba(0,0,0,.7);
	}

	.ss-jigyousya-modal h2 {
		text-align: center;
	}

	.ss-check-item {
		margin-top: 40px;
		display: flex;
		justify-content: space-around;
		padding: 8px 16px;
		box-shadow: 0 0 5px 2px rgba(0,0,0,.2);
		border-radius: 4px;
	}

	.ss-check-item .flex-item1 {
		flex-basis: 70%;
	}
	.ss-check-item .flex-item2 {
		flex-basis: 15%;
	}
	.ss-check-item .flex-item3 {
		flex-basis: 15%;
	}

	.ss-check-item label {
		user-select: none;
		font-size: 18px;
		font-weight: bold;
		display: flex;
		flex-direction: column;
		justify-content: end;
		align-items: center;
		position: relative;
		color: lightgray;
		height: 100%;
	}
	.ss-check-item input {
		display: none;
	}

	.ss-check-item input:checked + label {
		color: #2271b1;
	}
	.ss-check-item input:checked + label::before {
		color: #2271b1;
	}

	.ss-check-item .radioyes::before {
		font-family: "dashicons";
		content: "\f159";
		position: absolute;
		top: 40%;
		left: 50%;
		transform: translate(-50%,-50%);
		font-size: 60px;
		color: lightgray;
	}

	.ss-check-item .radiono::before {
		font-family: "dashicons";
		content: "\f158";
		position: absolute;
		top: 40%;
		left: 50%;
		transform: translate(-50%,-50%);
		font-size: 60px;
		color: lightgray;
	}

	.ss-jigyousya-button {
		display: flex;
		justify-content: space-around;
		margin-top: 40px;
	}
</style>

<div class="ss-jigyousya-modal-wrapper media-modal-backdrop"></div>
<form class="ss-jigyousya-modal">
	<h2>はい　か　いいえ　を選んでください</h2>
	<input type="hidden" name="action" value="<?php echo $this->cls; ?>">

	<?php foreach( $jigyousya_data as $key => $value ): ?>
	<!-- ここから下は編集不可 -->
	<div class="ss-check-item">
		<div class="flex-item1">
			<h3>ふるさと納税の返礼品として食品を出品しますか？</h3>
			<p>
				出品される返礼品の中に食品が含まれる場合は「はい」を選択してください。<br>
				返礼品の情報をご入力いただく際のアレルギー選択項目をデフォルトで表示するかどうかに使用いたします。
			</p>
		</div>
		<div class="flex-item2"><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>yes" value="有"><label for="<?php echo $key; ?>yes" class="radioyes">はい</label></div>
		<div class="flex-item3"><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>no" value="無"><label for="<?php echo $key; ?>no" class="radiono">いいえ</label></div>
	</div>
	<?php endforeach; ?>
	<!-- ここまでで１ブロック -->
	<!-- <div class="ss-check-item">
		<div class="flex-item1">
			<h3>ふるさと納税の返礼品としてやきもの（陶器など）を出品しますか？</h3>
			<p>
				出品される返礼品の中にやきものが含まれる場合は「はい」を選択してください。<br>
				返礼品の情報をご入力いただく際のやきもの独自の項目をデフォルトで表示するかどうかに使用いたします。
			</p>
		</div>
		<div class="flex-item2"><input type="radio" name="yakimono" id="yakimonoyes" value="有"><label for="yakimonoyes" class="radioyes">はい</label></div>
		<div class="flex-item3"><input type="radio" name="yakimono" id="yakimonono" value="無"><label for="yakimonono" class="radiono">いいえ</label></div>
	</div> -->
	
	<p>
	※またこの設定は画面左側のメニュー<b>「返礼品設定」</b>よりいつでも変更ができます。
	</p>
	<div class="ss-jigyousya-button">
		<button type="button" class="button button-primary sissubmit" disabled>登録する</button>
	</div>
</form>
<script>
	jQuery(function($){

		$('.ss-check-item input[type="radio"]').on('change',()=>{
			$('.ss-jigyousya-button button').prop('disabled',false);
		})

		$('.sissubmit').on('click',()=>{
			setTimeout(()=>{
				$('.ss-jigyousya-modal-wrapper').remove()
				$('.ss-jigyousya-modal').remove()
			},1000)
		})
	})
</script>
