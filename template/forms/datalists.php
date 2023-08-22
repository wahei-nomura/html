<?php
/**
 * form input
 *
 * @package neoneng
 */

$defaults     = array();
$args         = wp_parse_args( $args, $defaults );
$args['list'] = wp_hash( $args['name'] );
$option       = $args['option'];
unset( $args['option'] );
$attr         = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<input type="text"<?php echo $attr; ?>>
<datalist id="<?php echo $args['list']; ?>">
	<?php foreach ( (array) $option as $value ) : ?>
		<option value="<?php echo esc_attr( $value ); ?>"></option>
	<?php endforeach; ?>
</datalist>
