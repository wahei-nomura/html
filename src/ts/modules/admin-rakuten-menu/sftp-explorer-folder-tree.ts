import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'FolderTree',
	props: {
		children: {
			type: Object,
			required: true
		},
		path: {
			type: String,
			default: '',
		},
	},
	data(){
		return {

		};
	},
	created(){
		console.log('mounted',this.children);
	},
	components:{
		FolderTree
	},
	methods:{
		/**
		 * サブディレクトリ判定用
		 * @param files array|object
		 * @returns bool
		 */
		hasDirChildren(files){
			if( Array.isArray(files) ) return false;
			return 0 < Object.keys(files).filter(file=>files[file].type==='d').length
		},
	},
	template: `
	<ul class="n2-tree-parent">
	<template v-for="(meta,dir) in children">
		<li class="n2-tree-node" v-if="meta.type==='d'">
		<template v-if="meta.files && hasDirChildren(meta.files)">
			<label>
				<span class="dashicons dashicons-open-folder"></span>
				<span>{{dir}}</span>
				<input type="checkbox">
			</label>
			<FolderTree :children="meta.files" :path="path + '/' + dir">
		</template>
		<template v-else>
			<label>
				<span class="dashicons dashicons-open-folder"></span>
				{{dir}}
			</label>
		</template>
		</li>
	</template>
	</ul>
	`
});