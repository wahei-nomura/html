<?php
/**
 * form rakuten-spa-category
 * 楽天SPAカテゴリー
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
$value    = $args['value']['text'] ?? '';
unset( $args['value'] );
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
<div v-if="tmp.楽天カテゴリー.length" style="width:100%">
	<template v-for="index in [...Array(5).keys()]" :key="'楽天カテゴリー'+index">
	<div class="d-flex mb-1 input-group flex-nowrap w-100">
		<span class="input-group-text justify-content-center" style="width:40px">{{index+1}}</span>
		<select @change="update_textarea_by_selected_option(event,index)" class="flex-grow-1">
			<option v-text=""></option>
			<option
				v-for="v in tmp.楽天カテゴリー"
				v-text="v"
				:selected="v === 楽天カテゴリーselected?.[index]?? ''"
			></option>
		</select>
		<div @click="clearRakutenCategory(event,index)" class="btn btn-dark">クリア</div>
	</div>
	</template>
</div>
