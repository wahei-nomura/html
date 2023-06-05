<?php
/**
 * template/export.php
 *
 * @package neoneng
 */

?>
<div id="n2-checked-posts" :class="active ? 'is-active': ''" v-if="ids.length" style="display: none;">
	<div id="n2-checked-posts-title">
		<span v-text="`${ids.length} 件選択中`"></span>
		<span class="dashicons dashicons-no-alt" @click="active = ! active"></span>
	</div>
	<div id="n2-checked-posts-content">
		<table class="widefat striped">
			<thead>
				<tr>
					<template v-for="name in thead">
						<th v-if="name == ''" @click="clear_ids()" style="cursor: pointer">全解除</th>
						<th v-else v-text="name"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<tr v-for="item in items">
					<template v-for="name in thead">
						<td v-if="name == ''"><span class="dashicons dashicons-remove" @click="clear_ids(item.id)"></span></td>
						<td v-else v-text="item[name]"></td>
					</template>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="n2-checked-posts-toggler" @click="active = ! active"></div>
</div>
