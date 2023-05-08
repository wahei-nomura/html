<?php
/**
 * 寄附金額
 *
 * @package neoneng
 */

$defaults = array(
	':class' => '寄附金額チェッカー',
);
$args     = wp_parse_args( $args, $defaults );
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>

<div class="input-group flex-nowrap">

	<!-- 寄附金額ロック -->
	<label class="input-group-text" title="寄附金額を手動入力する">
		<input class="form-check-input mt-0" type="checkbox" name="n2field[寄附金額固定][]" value="固定する" v-model="寄附金額固定">
		<input type="hidden" name="n2field[寄附金額固定][]">
	</label>

	<!-- 寄附金額 -->
	<template v-if="!寄附金額固定.filter(v=>v).length">
		<input type="text"<?php echo $attr; ?> readonly>
	</template>
	<template v-else>
		<input type="text"<?php echo $attr; ?>>
	</template>

	<!-- 寄附金額再計算 -->
	<div class="btn btn-dark" style="white-space: nowrap;" @click="update_donation()" v-if="!寄附金額固定.filter(v=>v).length">再計算</div>

</div>
<p v-html="`寄附金額自動計算値：${Number(寄附金額自動計算値).toLocaleString()}（価格：${価格}　送料：${Number(送料).toLocaleString()}　定期便回数： ${定期便}）`" class="n2-field-addition d-none"></p>
