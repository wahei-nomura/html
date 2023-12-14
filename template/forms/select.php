<?php
/**
 * form select
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$option   = ( $args[':option'] ?? (array) $args['option'] ) ?? '';
$value    = $args['value'];

unset( $args[':option'], $args['option'], $args['value'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
// option_html（文字列ならv-for）
$option_html = is_array( $option ) ? '' : "<option v-for='(v,k) in {$option}' :value='k' v-text='v'></option>";
if ( is_array( $option ) ) {
	foreach ( $option as $val => $data ) {
		// 属性の追加
		$text = $data;
		$attr = '';
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				switch ( $k ) {
					case 'label':
						$text = $v;
						break;
					default:
						$attr .= " {$k}=\"{$v}\"";
				}
			}
		}
		// 値とラベルが同一の場合
		$val          = array_values( $option ) === $option ? $text : $val;
		$option_html .= sprintf( '<option %s value="%s" %s>%s</option>', $attr, $val, selected( $value, $val, false ), $text );
	}
}
printf( '<select %s>%s</select>', $attr, $option_html );
