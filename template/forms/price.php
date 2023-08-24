<?php
/**
 * form price
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<div>
	<span class="d-inline-block position-relative">
		<input type="text"<?php echo $attr; ?>>
		<input v-if="number_format" type="text" :value="Number(価格).toLocaleString()" class="position-absolute start-0 end-0 pe-none">
	</span>
	<template v-if="定期便 > 1">
		円 × {{定期便}}回 = 
		<span class="d-inline-block position-relative">
			<input type="text" name="n2field[価格総額]" :value="Number(定期便*価格)" @change="価格=force_half_size_text($event, 'number')/定期便;auto_adjust_price()" @focus="number_format=false" @blur="number_format=true" style="width: 8em;">
			<input v-if="number_format" type="text" :value="Number(定期便*価格).toLocaleString()" class="position-absolute start-0 end-0 pe-none">
		</span>
		円（価格総額）
	</template>
</div>
