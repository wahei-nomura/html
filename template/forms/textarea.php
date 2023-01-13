<?php
/**
 * form textarea
 *
 * @package neoneng
 */

$defaults = array(
	'style' => 'width: 100%; height: 15em;',
);
$args     = wp_parse_args( $args, $defaults );
$value = $args['value'] ?? '';
unset( $args['value'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
