<?php
/**
 * N2設定
 *
 * @package neoneng
 */

global $n2;
$settings = array(
	'LedgHOME' => array( 'lhcloud' => 'クラウド版レジ', 'ledghome' => '通常版レジ' ),
	'商品タイプ' => array( '食品', 'やきもの', 'eチケット' ),
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
			<?php foreach ( $settings['商品タイプ'] as $v ) : ?>
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
		<th>ポータル共通説明文</th>
		<td>
			<label style="margin: 0 2em 0 0;">
				<textarea name="n2_settings[N2][ポータル共通説明文]" rows="5" style="width: 100%;"><?php echo $n2->settings['N2']['ポータル共通説明文']; ?></textarea>
			</label>
		</td>
	</tr>
	<tr>
		<th>役場確認</th>
		<td>
			<input type="hidden" name="n2_settings[N2][役場確認]">
			<label>
				<input type="checkbox" name="n2_settings[N2][役場確認]" value="1" <?php checked( $n2->settings['N2']['役場確認'] ); ?>> 役場確認する
			</label>
		</td>
	</tr>
</table>
