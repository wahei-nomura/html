import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions,mapMutations} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'Main',
	data(){
		return {
			table:{
				header:{
					name:'ファイル名',
					size: 'サイズ',
				},
			},
		}
	},
	computed:{
		...mapState([
			'currentDir',
		]),
	},
	methods:{
		handleClick(){
			console.log('click');
		},
		handleChange(){
			console.log('change');
		},
		handleDrop(){
			console.log('drop');
		},
		formatSize(byte){
			if( byte >> 10 < 1 ) return byte.toFixed(1) + 'B';
			if( byte >> 20 < 1 ) return ((byte >> 7) / 8).toFixed(1) + 'KB';
			if( byte >> 30 < 1 ) return ((byte >> 17) / 8).toFixed(1) + 'MB';
			else 				 return ((byte >> 27) / 8).toFixed(1) + 'GB';
		},
	},
	template:`
	<main class="d-flex flex-column justify-content-between">
		<table class="table">
			<thead>
				<tr>
					<th v-for="(label,th) in table.header">{{label}}</th>
					<th>最終更新日</th>
				</tr>
				</thead>
			<tbody v-if="currentDir.children">
				<tr v-for="(meta,child) in currentDir.children" v-if="meta.type ==='f'">
					<template v-for="(label,th) in table.header" v-if="meta[th]">
						<td v-if="th==='size'">{{formatSize(meta[th])}}</td>
						<td v-else>{{meta[th]}}</td>
					</template>
					<td>{{meta.lastmod + ' ' + meta.time}}</td>
				</tr>
			</tbody>
		</table>
		<div
			@click="handleClick" @drop.prevent="handleDrop" @dragover.prevent
			class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light"
		>
			ファイルをドラッグ&ドロップで転送する
			<form style="display:none;">
				<input ref=file @change="handleChange" type="file" multiple="multiple" class="d-none">
			</form>
		</div>
	</main>
	`,
});