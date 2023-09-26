<?php
/**
 * N2設定
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'LedgHOME' => array(
		'lhcloud'  => 'クラウド版レジ',
		'ledghome' => '通常版レジ',
	),
);
?>
<table class="form-table">
	<tr>
		<th>稼働状況</th>
		<td>
			<input type="hidden" name="n2_settings[N2][稼働中]">
			<label>
				<input type="checkbox" name="n2_settings[N2][稼働中]" value="1" <?php checked( $n2->settings['N2']['稼働中'] ?? '' ); ?>> N2稼働中
			</label>
		</td>
	</tr>
	<tr>
		<th>LedgHOME</th>
		<td>
			<?php foreach ( $settings['LedgHOME'] as $name => $label ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[N2][LedgHOME]" value="<?php echo $name; ?>" <?php checked( $n2->settings['N2']['LedgHOME'] ?? '', $name ); ?>> <?php echo $label; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>出品する特殊な商品タイプ</th>
		<td>
			<?php foreach ( $args->data['商品タイプ'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[N2][商品タイプ][]" value="<?php echo $v; ?>" <?php checked( in_array( $v, $n2->settings['N2']['商品タイプ'], true ) ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[N2][商品タイプ][]">
		</td>
	</tr>
	<tr>
		<th>出品ポータル</th>
		<td>
			<?php foreach ( $args->portal_sites as $portal ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[N2][出品ポータル][]" value="<?php echo esc_attr( $portal ); ?>" <?php checked( in_array( $portal, $n2->settings['N2']['出品ポータル'], true ) ); ?>> <?php echo $portal; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[N2][出品ポータル][]">
		</td>
	</tr>
	<tr>
		<th>自治体確認</th>
		<td>
			<input type="hidden" name="n2_settings[N2][自治体確認]">
			<label>
				<input type="checkbox" name="n2_settings[N2][自治体確認]" value="1" <?php checked( $n2->settings['N2']['自治体確認'] ); ?>> 自治体確認する
			</label>
		</td>
	</tr>
	<tr>
		<th>返礼品の出品基準チェック</th>
		<td>
			<input type="hidden" name="n2_settings[N2][返礼品の出品基準チェック]">
			<label>
				<input type="checkbox" name="n2_settings[N2][返礼品の出品基準チェック]" value="1" <?php checked( $n2->settings['N2']['返礼品の出品基準チェック'] ); ?>> 返礼品の出品基準チェックする
			</label>
		</td>
	</tr>
	<tr>
		<th>提供事業者名入力</th>
		<td>
			<input type="hidden" name="n2_settings[N2][提供事業者名]">
			<label>
				<input type="checkbox" name="n2_settings[N2][提供事業者名]" value="1" <?php checked( $n2->settings['N2']['提供事業者名'] ); ?>> 提供事業者名を入力可能にする
			</label>
		</td>
	</tr>
	<tr>
		<th>類型該当理由を表示する地場産品類型</th>
		<td>
			<input type="hidden" name="n2_settings[N2][理由表示地場産品類型]">
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="1" <?php checked( in_array( '1', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 1
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="2" <?php checked( in_array( '2', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 2
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="3" <?php checked( in_array( '3', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 3
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="4" <?php checked( in_array( '4', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 4
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="5" <?php checked( in_array( '5', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 5
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="6" <?php checked( in_array( '6', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 6
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="7" <?php checked( in_array( '7', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 7
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="8イ" <?php checked( in_array( '8イ', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 8イ
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="8ロ" <?php checked( in_array( '8ロ', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 8ロ
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="8ハ" <?php checked( in_array( '8ハ', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 8ハ
			</label>
			<label>
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="9" <?php checked( in_array( '9', $n2->settings['N2']['理由表示地場産品類型'], true ) ); ?>> 9
			</label>
		</td>
	</tr>
</table>
