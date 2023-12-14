<?php
/**
 * form rakuten-attributes
 * 楽天 商品属性
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
<div @mouseover="set_info($event.target)">
	<div>
		<div type="button" class="btn btn-danger" @click="insert_rms_attributes(true)">必須の商品属性を取得</div>
		<div type="button" class="btn btn-dark" @click="insert_rms_attributes()">全ての商品属性を取得</div>
		<div v-if="商品属性アニメーション" class="spinner-border text-danger" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
	</div>
	<input type="hidden"<?php echo $attr; ?> >
	<table v-if="商品属性" class="d-block table align-middle m-0" style="width: 100%;">
		<tr v-for="(v, k) in JSON.parse(商品属性)">
			<td class="border-0">
				{{v.nameJa}} <span v-if="v.properties.rmsMandatoryFlg" class="badge bg-danger">必須</span>
			</td>
			<td class="border-0">
				<template v-if="v.dataType === 'STRING'">
					<template v-if="v.properties.rmsInputMethod === 'SELECTIVE'">
						<select :value="v.value || ''" @change="set_rms_attributes_value(k,$event.target.value)">
							<option v-for="v in v.dictionaryValues" :value="v.nameJa">{{ v.nameJa }}</option>
						</select>
					</template>
					<template v-else>
						<input type="text" :maxlength="v.maxLength" :value="v.value || ''" 
						@change="set_rms_attributes_value(k,$event.target.value)" placeholder="値">
					</template>
				</template>
				<template v-else-if="v.dataType === 'NUMBER'">
					<input type="number" min="0" :value="v.value || ''" 
					@change="set_rms_attributes_value(k,$event.target.value)" placeholder="値">
				</template>
				<template v-else-if="v.dataType === 'DATE'">
					<input type="date" :value="v.value || ''" 
					@change="set_rms_attributes_value(k,$event.target.value)" placeholder="値">
				</template>
				<template v-else>
					<input type="text" :value="v.value || ''" 
					@change="set_rms_attributes_value(k,$event.target.value)" placeholder="値">
				</template>
			</td>
			<td class="border-0">
				<template v-if="v.unit || v.unitValue || v.unitValue === ''">
					<input class="w-25" :list="`unit-list-${k}`" v-model="v.unitValue"
					@change="set_rms_attributes_unit(k,$event.target.value)" placeholder="単位">
					<datalist :id="`unit-list-${k}`">
						<option v-for="unit in get_units(v)">{{ unit }}</option>
					</datalist>
				</template>
			</td>
		</tr>
	</table>
</div>
