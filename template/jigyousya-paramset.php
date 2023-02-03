<?php
/**
 * class-n2-jigyousyaparam.phpのshow_login_modalメソッドで使用
 *
 * @package neoneng
 */

?>

<!-- Load sass.js -->
<script src="https://cdn.jsdelivr.net/gh/steamships/in-browser-sass/dist/in-browser-sass.bundle.min.js"></script>

<style type="text/scss">
	#ss-jigyousya-modal {
		padding: 60px;
		h2 {
			text-align: center;
		}
		&.login {
			position: fixed;
			top: 50%;
			left: 50%;
			z-index: 10000000;
			width: 50%;
			min-width: 800px;
			transform: translate(-50%,-50%);
			box-shadow: 0 5px 15px rgba(0,0,0,.7);
			background-color: #fff;
		}
	}

	.ss-check-item {
		margin-top: 40px;
		display: flex;
		justify-content: space-around;
		padding: 8px 16px;
		box-shadow: 0 0 5px 2px rgba(0,0,0,.2);
		border-radius: 4px;

		.flex-item1 {
		flex-basis: 70%;
		}
		.flex-item2 {
			flex-basis: 15%;
		}
		.flex-item3 {
			flex-basis: 15%;
		}

		label {
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
		input {
			display: none;
			&:checked + label {
				color: #2271b1;
				&::before {
					color: #2271b1;
				}
			}
		}
		.radioyes::before {
			font-family: "dashicons";
			content: "\f159";
			position: absolute;
			top: 40%;
			left: 50%;
			transform: translate(-50%,-50%);
			font-size: 60px;
			color: lightgray;
		}
		.radiono::before {
			font-family: "dashicons";
			content: "\f158";
			position: absolute;
			top: 40%;
			left: 50%;
			transform: translate(-50%,-50%);
			font-size: 60px;
			color: lightgray;
		}
	}

	.ss-jigyousya-button {
		display: flex;
		justify-content: space-around;
		margin-top: 40px;
	}
</style>

<div class="<?php echo 'n2_jigyousya_menu' !== $_GET['page'] ? 'ss-jigyousya-modal-wrapper media-modal-backdrop' : ''; ?>"></div>
<form id="ss-jigyousya-modal" class="<?php echo 'n2_jigyousya_menu' !== $_GET['page'] ? 'login' : ''; ?>">
	<h2>はい　か　いいえ　を選んでください</h2>
	<input type="hidden" name="action" value="<?php echo $args['cls']; ?>">

	<?php
		foreach ( $args['params'] as $key => $value ) :
		$result = get_user_meta( wp_get_current_user()->ID, $key, true ) ? get_user_meta( wp_get_current_user()->ID, $key, true ) : '';
	?>

		<div class="ss-check-item">
			<div class="flex-item1">
				<h3><?php echo $value['title']; ?></h3>
				<p><?php echo $value['description']; ?></p>
			</div>
			<div class="flex-item2"><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>yes" value="有"<?php checked( $result, '有' ); ?>><label for="<?php echo $key; ?>yes" class="radioyes">はい</label></div>
			<div class="flex-item3"><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>no" value="無"<?php checked( $result, '無' ); ?>><label for="<?php echo $key; ?>no" class="radiono">いいえ</label></div>
		</div>
	<?php endforeach; ?>

	<p>
	※またこの設定は画面左側のメニュー<b>「返礼品設定」</b>よりいつでも変更ができます。
	</p>
	<div class="ss-jigyousya-button">
		<button type="button" class="button button-primary sissubmit" disabled>登録する</button>
	</div>
</form>
<script>
	jQuery(function($){

		$('#ss-jigyousya-modal .ss-check-item input[type="radio"]').on('change',()=>{
			// 全てのチェックしているか判定
			if($('.ss-check-item input[type="radio"]').length === $('.ss-check-item input[type="radio"]:checked').length * 2){
				$('.ss-jigyousya-button button').prop('disabled',false);
			}
		})

		$('#ss-jigyousya-modal.login .sissubmit').on('click',()=>{
			setTimeout(()=>{
				$('.ss-jigyousya-modal-wrapper').remove()
				$('#ss-jigyousya-modal').remove()
			},1000)
		})
	})
</script>
