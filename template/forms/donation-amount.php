<?php
/**
 * 寄附金額
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>

<!-- 寄附金額ロック -->
<!-- 寄附金額再計算 -->
<div class="input-group flex-nowrap">
	<label class="input-group-text" title="寄附金額を手動入力する">
		<input class="form-check-input mt-0" type="checkbox" name="n2field[寄附金額固定][]" value="固定する" v-model="寄附金額固定">
		<input type="hidden" name="n2field[寄附金額固定][]">
	</label>
	<template v-if="!寄附金額固定.filter(v=>v).length">
		<input type="text"<?php echo $attr; ?> readonly>
	</template>
	<template v-else>
		<input type="text"<?php echo $attr; ?>>
	</template>
	<div class="btn btn-dark" style="white-space: nowrap;" @click="update_donation()" v-if="!寄附金額固定.filter(v=>v).length">再計算</div>
</div>
<p v-html="`価格：${価格}　送料：${Number(送料).toLocaleString()}　定期便回数： ${定期便}`" class="n2-field-addition d-none"></p>
