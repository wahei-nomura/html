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
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<div class="btn btn-dark btn-sm py-0 px-3 d-none" @click="insert_example_description($event.target)">+ 項目を挿入</div>
<div class="btn btn-dark btn-sm py-0 px-3 d-none" @click="generate_example_description($event.target)">+ 例文を生成</div>
<textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
