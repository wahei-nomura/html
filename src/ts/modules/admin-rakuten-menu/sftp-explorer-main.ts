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
			selectedFile:{},
			selectAll:false,
		}
	},
	computed:{
		...mapState([
			'currentDir',
			'currentFile',
		]),
		hasFiles(){
			const children = this.currentDir.children;
			if( Array.isArray( children ) ) return false;
			return Object.keys( children ).filter(key=>children[key].type ==='f').length > 0;
		},
	},
	methods:{
		...mapMutations([
			'SET_CURRENT_FILE',
		]),
		handleFileAreaClick(){
			this.$refs.file.click();
		},
		handleFileAreaChange(){
			console.log('change');
		},
		handleFileAreaDrop(){
			console.log('drop');
		},
		formatSize(byte){
			if( byte >> 10 < 1 ) return byte.toFixed(1) + 'B';
			if( byte >> 20 < 1 ) return ((byte >> 7) / 8).toFixed(1) + 'KB';
			if( byte >> 30 < 1 ) return ((byte >> 17) / 8).toFixed(1) + 'MB';
			else 				 return ((byte >> 27) / 8).toFixed(1) + 'GB';
		},
	},
	watch:{
		currentDir(newDir,oldDir){
			if(JSON.stringify(newDir) !== JSON.stringify(oldDir)){
				const children = newDir.children;
				// 初期化
				if( Array.isArray( children ) ) this.selectedFile = {};
				else this.selectedFile = Object.keys( children ).filter(key=>children[key].type ==='f').reduce((obj,key)=>{
					if(key)	obj[key] = false;
					return obj;
				},{});
				this.SET_CURRENT_FILE(null);
			}
		},
		selectAll(newVal,_){
			this.selectedFile = Object.keys(this.selectedFile).reduce((obj,key)=>{
				if(key)	obj[key] = newVal;
				return obj;
			},{});
			if ( ! newVal ) {
				this.SET_CURRENT_FILE(null);
			}
		}
	},
	template:`
	<main class="d-flex flex-column justify-content-between">
		<table class="table table-hover">
			<thead>
				<tr>
					<th><input type="checkbox" v-model="selectAll"></th>
					<th v-for="(label,th) in table.header">{{label}}</th>
					<th>最終更新日</th>
				</tr>
				</thead>
			<tbody>
				<template v-if="currentDir.children && hasFiles">
					<tr v-for="(meta,child) in currentDir.children" v-if="meta.type ==='f'" @click="SET_CURRENT_FILE(meta)">
						<td><input type="checkbox" v-model="selectedFile[meta.name]"></td>
						<template v-for="(label,th) in table.header" v-if="meta[th]">
							<td v-if="th==='size'">{{formatSize(meta[th])}}</td>
							<td v-else>{{meta[th]}}</td>
						</template>
						<td>{{meta.lastmod + ' ' + meta.time}}</td>
					</tr>
				</template>
				<template v-else>
					<tr>
						<td colspan="3">no files<td>
					</tr>
				</template>
			</tbody>
		</table>
		<div
			@click="handleFileAreaClick" @drop.prevent="handleFileAreaDrop" @dragover.prevent
			class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light"
		>
			ファイルをドラッグ&ドロップで転送する
			<form style="display:none;">
				<input ref=file @change="handleFileAreaChange" type="file" multiple="multiple" class="d-none">
			</form>
		</div>
	</main>
	`,
});