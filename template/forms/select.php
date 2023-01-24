<?php
/**
 * form select
 *
 * @package neoneng
 */

$defaults     = array();
$args         = wp_parse_args( $args, $defaults );
$option       = $args['option'];
$value        = $args['value'];
$option_equal = $args['option-equal'] ?? false;// optionのvalueと表示名が一緒かどうか判定
unset( $args['option'], $args['value'], $args['option-equal'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<select <?php echo $attr; ?>>
	<?php foreach ( $option as $k => $v ) : ?>
	<option value="<?php echo $option_equal ? $v : $k; ?>"<?php selected( $value, $option_equal ? $v : $k ); ?>><?php echo $v; ?></option>
	<?php endforeach; ?>
</select>
