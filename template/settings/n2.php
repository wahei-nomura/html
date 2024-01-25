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
			<label <?php echo ! $n2->settings_access ? 'style="pointer-events:none;"' : ''; ?>>
				<input type="checkbox" name="n2_settings[N2][稼働中]" value="1" <?php checked( $n2->settings['N2']['稼働中'] ?? '' ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> N2稼働中
			</label>
		</td>
	</tr>
	<tr>
		<th>LedgHOME</th>
		<td>
			<?php foreach ( $settings['LedgHOME'] as $name => $label ) : ?>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="radio" name="n2_settings[N2][LedgHOME]" value="<?php echo $name; ?>" <?php checked( $n2->settings['N2']['LedgHOME'] ?? '', $name ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> <?php echo $label; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>出品する特殊な商品タイプ</th>
		<td>
			<?php foreach ( $args->data['商品タイプ'] as $v ) : ?>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="checkbox" name="n2_settings[N2][商品タイプ][]" value="<?php echo $v; ?>" <?php checked( in_array( $v, $n2->settings['N2']['商品タイプ'], true ) ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[N2][商品タイプ][]">
		</td>
	</tr>
	<tr>
		<th>出品ポータル</th>
		<td>
			<?php foreach ( $args->portal_sites as $portal ) : ?>
			<label style="margin: 0 2em 0 0; <?php echo ! $n2->settings_access ? 'pointer-events:none;' : ''; ?>">
				<input type="checkbox" name="n2_settings[N2][出品ポータル][]" value="<?php echo esc_attr( $portal ); ?>" <?php checked( in_array( $portal, $n2->settings['N2']['出品ポータル'], true ) ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> <?php echo $portal; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[N2][出品ポータル][]">
		</td>
	</tr>
	<tr>
		<th>自治体確認</th>
		<td>
			<input type="hidden" name="n2_settings[N2][自治体確認]">
			<label <?php echo ! $n2->settings_access ? 'style="pointer-events:none;"' : ''; ?>>
				<input type="checkbox" name="n2_settings[N2][自治体確認]" value="1" <?php checked( $n2->settings['N2']['自治体確認'] ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 自治体確認する
			</label>
		</td>
	</tr>
	<tr>
		<th>提供事業者名入力</th>
		<td>
			<input type="hidden" name="n2_settings[N2][提供事業者名]">
			<label <?php echo ! $n2->settings_access ? 'style="pointer-events:none;"' : ''; ?>>
				<input type="checkbox" name="n2_settings[N2][提供事業者名]" value="1" <?php checked( $n2->settings['N2']['提供事業者名'] ); ?> <?php wp_readonly( ! $n2->settings_access ); ?><?php echo ! $n2->settings_access ? ' onclick="return false;"' : ''; ?>> 提供事業者名を入力可能にする
			</label>
		</td>
	</tr>
	<tr>
		<th>該当理由をポータルに表示する地場産品類型</th>
		<td>
			<?php foreach ( array_filter( array_keys( $n2->custom_field['事業者用']['地場産品類型']['option'] ) ) as $value ) : ?>
			<label style="display: inline-block; margin: 0 2em 1em 0;">
				<input type="checkbox" name="n2_settings[N2][理由表示地場産品類型][]" value="<?php echo esc_attr( $value ); ?>"<?php checked( $n2->settings['N2']['理由表示地場産品類型'] ); ?>>
				<?php echo $value; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
</table>
