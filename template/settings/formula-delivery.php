<?php
/**
 * 寄附金額・送料
 *
 * @package neoneng
 */

?>
<table class="form-table">
	<tr>
		<th>送料を寄附金額計算に含める</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[formula][送料乗数]" value="0" <?php checked( $args->setting_values['formula']['送料乗数'] ?? 0, 0 ); ?>> 含めない
			</label>
			<label>
				<input type="radio" name="n2_settings[formula][送料乗数]" value="1" <?php checked( $args->setting_values['formula']['送料乗数'] ?? 0, 1 ); ?>> 含める
			</label>
		</td>
	</tr>
	<tr>
		<th>寄附金額計算の除数</th>
		<td>
			<input type="number" step="0.01" name="n2_settings[formula][除数]" value="<?php echo esc_attr( $args->setting_values['formula']['除数'] ); ?>">
		</td>
	</tr>
	<tr>
		<th>送料</th>
		<td>
			<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
			<p><span style="display:inline-block; width: 7em;"><?php echo ( 20 * $i ) + 40; ?> サイズ : </span><input type="number" name="n2_settings[delivery_fee][010<?php echo $i; ?>]" value="<?php echo esc_attr( $args->setting_values['delivery_fee'][ "010{$i}" ] ?? '' ); ?>" style="width: 7em;"></p>
			<?php endfor; ?>
		</td>
	</tr>
	<tr>
		<th>レターパック</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[delivery_fee][レターパックライト]" value="370" <?php checked( $args->setting_values['delivery_fee']['レターパックライト'] ?? '', 370 ); ?>> レターパックライト
			</label>
			<label>
				<input type="checkbox" name="n2_settings[delivery_fee][レターパックプラス]" value="520" <?php checked( $args->setting_values['delivery_fee']['レターパックプラス'] ?? '', 520 ); ?>> レターパックプラス
			</label>
		</td>
	</tr>
</table>