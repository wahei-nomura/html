<?php
/**
 * form checkbox
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
foreach ( $option as $v ) :
?>
<label style="<?php echo $style; ?>">
	<input
		type="checkbox"
		name="<?php echo $name; ?>[]"
		value="<?php echo $v; ?>"
		<?php echo $attr; ?>
		<?php checked( in_array( (string) $v, $value, true ) ); ?>
	>
	<?php echo $v; ?>
</label>
<?php endforeach; ?>
<!-- 全チェック外しも保存するために必須 -->
<input type="hidden" name="<?php echo $name; ?>[]">
