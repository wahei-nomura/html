<?php
/**
 * クラウド版 レジホーム
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'送料反映' => array( 'レターパック', 'その他' ),
	'その他経費' => array( '利用しない', 'ヤマト以外の送料を登録', 'ヤマト以外の送料を登録（定期便の場合は1回目に総額）' ),
	'送料' => array( '送料を全て登録', 'ヤマト以外は送料を空欄で登録', '送料は空欄で登録' ),
	'価格' => array( '定期便1回ごとに登録', '定期便初回に全額をまとめて登録' ),
);
// 隠す
$hide = array(
	'lhcloud'  => 'lhcloud' === $n2->settings['N2']['LedgHOME'] ? 'style="display: none;"' : '',
	'ledghome' => 'ledghome' === $n2->settings['N2']['LedgHOME'] ? 'style="display: none;"' : '',
);
?>
<table class="form-table">
	<tr>
		<th>カテゴリー</th>
		<td>
			<p>※改行区切りでカテゴリーを記入</p>
			<textarea name="n2_settings[LedgHOME][カテゴリー]" rows="10" style="width: 100%;"><?php echo esc_attr( $n2->settings['LedgHOME']['カテゴリー'] ); ?></textarea>
		</td>
	</tr>
	<tr <?php echo $hide['ledghome']; ?>>
		<th>送料</th>
		<td>
			<?php foreach ( $settings['送料'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[LedgHOME][送料]" value="<?php echo $v; ?>" <?php checked( $n2->settings['LedgHOME']['送料'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr <?php echo $hide['ledghome']; ?>>
		<th>送料反映</th>
		<td>
			<?php foreach ( $settings['送料反映'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[LedgHOME][送料反映][]" value="<?php echo $v; ?>" <?php checked( in_array( $v, $n2->settings['LedgHOME']['送料反映'] ?? array(), true ) ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[LedgHOME][送料反映][]">
		</td>
	</tr>
	<tr <?php echo $hide['ledghome']; ?>>
		<th>その他経費</th>
		<td>
			<?php foreach ( $settings['その他経費'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[LedgHOME][その他経費]" value="<?php echo $v; ?>" <?php checked( $n2->settings['LedgHOME']['その他経費'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>価格</th>
		<td>
			<?php foreach ( $settings['価格'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[LedgHOME][価格]" value="<?php echo $v; ?>" <?php checked( $n2->settings['LedgHOME']['価格'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr <?php echo $hide['lhcloud']; ?>>
		<th>自動出荷依頼予約日（定期便）</th>
		<td>
			<input type="number" step="1" max="31" min="1" name="n2_settings[LedgHOME][自動出荷依頼予約日]" value="<?php echo $n2->settings['LedgHOME']['自動出荷依頼予約日'] ?? ''; ?>">
		</td>
	</tr>
</table>
