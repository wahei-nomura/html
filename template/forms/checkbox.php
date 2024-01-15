<?php
/**
 * form checkbox
 *
 * @package neoneng
 */

$default      = array(
	'style' => 'display: inline-block; margin: 0 1em 0 0;',
);
$args         = wp_parse_args( $args, $default );
$name         = $args['name'];
$option       = $args['option'];
$value        = (array) $args['value'];
$style        = $args['style'];
$option_equal = array_values( $option ) === $option;// optionのvalueと表示名が一緒かどうか判定
$attr         = '';
unset( $args['option'], $args['name'], $args['value'], $args['style'] );
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<!-- 全チェック外しも保存するために必須 -->
<input type="hidden" name="<?php echo $name; ?>[]">
<?php foreach ( $option as $k => $v ) : ?>
<label style="<?php echo $style; ?>">
	<input
		type="checkbox"
		name="<?php echo $name; ?>[]"
		value="<?php echo $option_equal ? $v : $k; ?>"
		<?php echo $attr; ?>
		<?php checked( in_array( (string) ( $option_equal ? $v : $k ), $value, true ) ); ?>
	>
	<span v-text="`<?php echo $v; ?>`"></sapn>
</label>
<?php endforeach; ?>
