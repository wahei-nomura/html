<?php
/**
 * レジホーム
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'送料反映' => array( 'レターパック', 'その他' ),
	'その他経費' => array( '利用しない', 'ヤマト以外の送料を登録', 'ヤマト以外の送料を登録（定期便の場合は1回目に全額）' ),
	'送料' => array( '送料を全て登録', 'ヤマト以外は送料を空欄で登録', '送料は空欄で登録' ),
	'価格' => array( '定期便1回ごとに登録', '定期便初回に全額をまとめて登録' ),
	'eチケット' => array( '対応する' ),
);
?>
<table class="form-table">
	<tr>
		<th>eチケット</th>
		<td>
			<?php foreach ( $settings['eチケット'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[portal_setting][LedgHOME][eチケット]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['LedgHOME']['eチケット'] ?? false, $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>カテゴリー</th>
		<td>
			<p>※改行区切りでカテゴリーを記入</p>
			<textarea name="n2_settings[portal_setting][LedgHOME][カテゴリー]" rows="10" style="width: 100%;"><?php echo esc_attr( implode( "\n", (array) $n2->portal_setting['LedgHOME']['カテゴリー'] ) ); ?></textarea>
		</td>
	</tr>
	<tr>
		<th>送料</th>
		<td>
			<?php foreach ( $settings['送料'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[portal_setting][LedgHOME][送料]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['LedgHOME']['送料'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>送料反映</th>
		<td>
			<?php foreach ( $settings['送料反映'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="checkbox" name="n2_settings[portal_setting][LedgHOME][送料反映][]" value="<?php echo $v; ?>" <?php checked( in_array( $v, $n2->portal_setting['LedgHOME']['送料反映'], true ) ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
			<input type="hidden" name="n2_settings[portal_setting][LedgHOME][送料反映][]">
		</td>
	</tr>
	<tr>
		<th>その他経費</th>
		<td>
			<?php foreach ( $settings['その他経費'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[portal_setting][LedgHOME][その他経費]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['LedgHOME']['その他経費'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th>価格</th>
		<td>
			<?php foreach ( $settings['価格'] as $v ) : ?>
			<label style="margin: 0 2em 0 0;">
				<input type="radio" name="n2_settings[portal_setting][LedgHOME][価格]" value="<?php echo $v; ?>" <?php checked( $n2->portal_setting['LedgHOME']['価格'], $v ); ?>> <?php echo $v; ?>
			</label>
			<?php endforeach; ?>
		</td>
	</tr>
</table>
