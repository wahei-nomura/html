import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions,mapMutations} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'LeftAside',
	computed:{
		...mapState([
			'sftp',
			'currentDir',
		]),
	},
	methods:{
		...mapMutations([
			'SET_CURRENT_DIR',
		]),
		mkdir(){
			console.log('mkDir');
		},
		dlDir(){
			console.log('dlDir');
		},
	},
	components:{
		FolderTree,
	},
	template:`
	<aside>
		<nav class="mb-3 d-flex justify-content-around">
			<div class="btn btn-outline-secondary btn-sm" @click="mkdir">新規作成</div>
			<div class="btn btn-outline-danger btn-sm" @click="dlDir">削除</div>
		</nav>
		<ul class="n2-tree-parent">
			<li class="n2-tree-node">
				<label v-if="sftp.dirlist" class="has-child">
					<input type="checkbox" checked>
				</label>
				<label data-path="/" :class="{active:'/' === currentDir.path }"
					@click="SET_CURRENT_DIR({path:'/', children:sftp.dirlist})"
				>
					<span class="dashicons dashicons-open-folder"></span>
					<span>root</span>
				</label>
				<FolderTree v-if="sftp.dirlist" :children="sftp.dirlist" />
			</li>
		</ul>
	</aside>
	`,
});