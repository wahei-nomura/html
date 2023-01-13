<?php
/**
 * form select
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$option   = $args['option'];
$value    = $args['value'];
unset( $args['option'], $args['value'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<select <?php echo $attr; ?>>
	<?php foreach ( $option as $k => $v ) : ?>
	<option value="<?php echo $k; ?>"<?php selected( $value, $k ); ?>><?php echo $v; ?></option>
	<?php endforeach; ?>
</select>
