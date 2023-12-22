<?php
/**
 * 寄附金額・送料
 *
 * @package neoneng
 */

global $n2;
?>
<table class="form-table">
	<tr>
		<th>送料を寄附金額計算に含める</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[寄附金額・送料][送料乗数]" value="0" <?php checked( $n2->settings['寄附金額・送料']['送料乗数'] ?? 0, 0 ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 含めない
			</label>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[寄附金額・送料][送料乗数]" value="1" <?php checked( $n2->settings['寄附金額・送料']['送料乗数'] ?? 0, 1 ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 含める
				（価格が <input type="number" step="100" min="0" style="width: 6em;" name="n2_settings[寄附金額・送料][送料加算分岐点]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['送料加算分岐点'] ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?>> 円未満に限る）
			</label>
		</td>
	</tr>
	<tr>
		<th>寄附金額計算の除数</th>
		<td>
			<input type="number" step="0.01" name="n2_settings[寄附金額・送料][除数]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['除数'] ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?> style="width: 6em;">
		</td>
	</tr>
	<tr>
		<th>下限寄附金額</th>
		<td>
			<input type="number" step="1000" name="n2_settings[寄附金額・送料][下限寄附金額]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['下限寄附金額'] ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?> style="width: 6em;">
			<p><small>自動計算時に、ここに設定された「下限寄附金額」を下回る場合は「下限寄附金額」に自動設定されます</small></p>
		</td>
	</tr>
	<tr>
		<th>価格の端数の自動調整</th>
		<td>
			<?php foreach ( array( '調整しない', '1回毎に調整する', '総額で調整する' ) as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[寄附金額・送料][自動価格調整]" value="<?php echo $v; ?>" <?php checked( $n2->settings['寄附金額・送料']['自動価格調整'] ?? '調整しない', $v ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>送料</th>
		<td>
			<p style="margin-bottom: 1em;">
				<input type="hidden" name="n2_settings[寄附金額・送料][税込送料]" <?php wp_readonly( ! $n2->settings_access ); ?>>
				<label>
					<input type="checkbox" name="n2_settings[寄附金額・送料][税込送料]" value="1" <?php checked( $n2->settings['寄附金額・送料']['税込送料'] ?? '' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 税込送料　<small>↓税込送料の場合は全て税込で入力</samll>
				</label>
			</p>
			<p><span style="display:inline-block; width: 11em;">宅急便コンパクト : </span><input type="number" name="n2_settings[寄附金額・送料][送料][0100]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['送料']['0100'] ?? '' ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?> style="width: 7em;"></p>
			<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
			<p><span style="display:inline-block; width: 11em;"><?php echo ( 20 * $i ) + 40; ?> サイズ : </span><input type="number" name="n2_settings[寄附金額・送料][送料][010<?php echo $i; ?>]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['送料'][ "010{$i}" ] ?? '' ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?> style="width: 7em;"></p>
			<?php endfor; ?>
		</td>
	</tr>
	<tr>
		<th>ゆうパケット</th>
		<td>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<p><span style="display:inline-block; width: 11em;">厚さ<?php echo $i; ?>cm : </span><input type="number" name="n2_settings[寄附金額・送料][送料][ゆうパケット厚さ<?php echo $i; ?>cm]" value="<?php echo esc_attr( $n2->settings['寄附金額・送料']['送料'][ "ゆうパケット厚さ{$i}cm" ] ?? '' ); ?>" <?php wp_readonly( ! $n2->settings_access ); ?> style="width: 7em;"></p>
			<?php endfor; ?>
		</td>
	</tr>
	<tr>
		<th>レターパック</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[寄附金額・送料][送料][レターパックライト]" value="370" <?php checked( $n2->settings['寄附金額・送料']['送料']['レターパックライト'] ?? '', 370 ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> レターパックライト
			</label>
			<label>
				<input type="checkbox" name="n2_settings[寄附金額・送料][送料][レターパックプラス]" value="520" <?php checked( $n2->settings['寄附金額・送料']['送料']['レターパックプラス'] ?? '', 520 ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> レターパックプラス
			</label>
		</td>
	</tr>
</table>
