<?php
/**
 * form textarea
 *
 * @package neoneng
 */

$defaults = array(
	'style' => 'width: 100%; height: 10em;',
);
$args     = wp_parse_args( $args, $defaults );
$value    = $args['value'] ?? '';
unset( $args['value'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<div class="btn btn-dark btn-sm py-0 px-3 d-none" @click="insert_example_description($event.target)">+ 例文を挿入</div>
<textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
