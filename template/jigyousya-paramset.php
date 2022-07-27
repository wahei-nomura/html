<?php
/**
 * class-n2-jigyousyaparam.phpのadd_setup_menu_pageメソッドで使用
 *
 * @package neoneng
 */

?>

<!-- Load sass.js -->
<script src="https://cdn.jsdelivr.net/gh/steamships/in-browser-sass/dist/in-browser-sass.bundle.min.js"></script>

<style type="text/scss">

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

<form class="ss-jigyousya-modal">
	<input type="hidden" name="action" value="<?php echo $this->cls; ?>">

	<?php
	foreach ( $params as $key => $value ) :
		$result = get_user_meta( wp_get_current_user()->ID, $value['meta'], true ) ? get_user_meta( wp_get_current_user()->ID, $value['meta'], true ) : '';
		?>
		<div class="ss-check-item">
			<div class="flex-item1">
				<h3><?php echo $value['title']; ?></h3>
				<p><?php echo $value['description']; ?></p>
			</div>
			<div class="flex-item2">
				<input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>yes" value="有"<?php checked( $result, '有' ); ?>>
				<label for="<?php echo $key; ?>yes" class="radioyes">はい</label>
			</div>
			<div class="flex-item3">
				<input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>no" value="無"<?php checked( $result, '無' ); ?>>
				<label for="<?php echo $key; ?>no" class="radiono">いいえ</label>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="ss-jigyousya-button">
		<button type="button" class="button button-primary sissubmit">登録する</button>
	</div>
</form>
