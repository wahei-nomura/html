<?php
/**
 * form radio
 *
 * @package neoneng
 */

$default = array(
	'style' => 'display: inline-block; margin: 0 1em 0 0;',
);
$args    = wp_parse_args( $args, $default );
$option  = $args['option'];
$value   = $args['value'];
$style   = $args['style'];
$attr    = '';
unset( $args['option'], $args['value'], $args['style'] );
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
foreach ( $option as $k => $v ) :
?>
<label style="<?php echo $style; ?>">
	<input
		type="radio"
		value="<?php echo $k; ?>"
		<?php echo $attr; ?>
		<?php checked( $value, $k ); ?>
	>
	<?php echo $v; ?>
</label>
<?php endforeach; ?>
