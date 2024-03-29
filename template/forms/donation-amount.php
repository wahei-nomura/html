<?php
/**
 * 寄附金額
 *
 * @package neoneng
 */

$defaults = array(
	':class' => '`${tmp.寄附金額チェッカー} rounded-0 rounded-end`',
);
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>

<div class="input-group flex-nowrap">

	<!-- 寄附金額ロック -->
	<label class="input-group-text" title="寄附金額を手動入力する">
		<input class="form-check-input mt-0" type="checkbox" name="n2field[寄附金額固定][]" value="固定する" v-model="寄附金額固定" @mouseover="set_info($event.target);">
		<input type="hidden" name="n2field[寄附金額固定][]">
	</label>

	<!-- 寄附金額 -->
	<span class="d-inline-block position-relative">
		<input type="text"<?php echo $attr; ?>>
		<template v-if="寄附金額固定.filter(v=>v).length">
			<input v-if="tmp.number_format" type="text" :value="Number(寄附金額).toLocaleString()" :class="`position-absolute start-0 end-0 pe-none rounded-0 rounded-end ${tmp.寄附金額チェッカー}`">
		</template>
		<template v-else>
			<input type="text" :value="Number(寄附金額).toLocaleString()" class="position-absolute top-0 start-0 end-0 rounded-0 bg-white text-dark" @mouseover="set_info($event.target)" @focus="$event.target.blur()" readonly>
		</template>
	</span>

	<!-- 寄附金額再計算 -->
	<div class="btn btn-dark py-1" style="white-space: nowrap;" @click="update_donation()" v-if="!寄附金額固定.filter(v=>v).length">再計算</div>

</div>
<p v-html="`寄附金額自動計算値：${Number(tmp.寄附金額自動計算値).toLocaleString()}円（価格：${Number(価格).toLocaleString()}円　送料：${Number(送料).toLocaleString()}円　定期便回数： ${定期便}回）`" class="n2-field-addition d-none"></p>
