<?php
/**
 * form checkbox2
 * valueとlabelの両方を保存
 * [{value,label},{value,label},...]
 *
 * @package neoneng
 */

$default = array(
	'style' => 'display: inline-block; margin: 0 1em 0 0;',
);
$args    = wp_parse_args( $args, $default );
$name    = $args['name'];
$option  = $args['option'];
$value   = (array) $args['value'];
$style   = $args['style'];
$attr    = '';
unset( $args['option'], $args['name'], $args['value'], $args['style'] );
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
$i = 0;
foreach ( $option as $k => $v ) :
?>
<label style="<?php echo $style; ?>">
	<input
		type="checkbox"
		name="<?php echo "{$name}[{$i}]"; ?>[value]"
		value="<?php echo $k; ?>"
		<?php echo $attr; ?>
		<?php checked( in_array( (string) $k, array_column( $value, 'value' ), true ) ); ?>
	>
	<input
		type="hidden"
		name="<?php echo "{$name}[{$i}]"; ?>[label]"
		value="<?php echo $v; ?>"
	>
	<?php echo $v; ?>
</label>
<?php $i++; ?>
<?php endforeach; ?>
<!-- 全チェック外しも保存するために必須 -->
<input type="hidden" name="<?php echo $name; ?>[checkbox2]">
