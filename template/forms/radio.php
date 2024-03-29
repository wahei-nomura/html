<?php
/**
 * form radio
 *
 * @package neoneng
 */

$default      = array(
	'style' => 'display: inline-block; margin: 0 2em 0 0;',
);
$args         = wp_parse_args( $args, $default );
$option       = $args['option'];
$value        = $args['value'];
$style        = $args['style'];
$option_equal = array_values( $option ) === $option;// optionのvalueと表示名が一緒かどうか判定
$attr         = '';
unset( $args['option'], $args['value'], $args['style'] );
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
foreach ( $option as $k => $v ) :
?>
<label style="<?php echo $style; ?>">
	<input
		type="radio"
		value="<?php echo $option_equal ? $v : $k; ?>"
		<?php echo $attr; ?>
		<?php checked( $value, $option_equal ? $v : $k ); ?>
	>
	<span v-text="`<?php echo $v; ?>`"></sapn>
</label>
<?php endforeach; ?>
