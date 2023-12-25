import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions,mapMutations} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'LeftAside',
	computed:{
		...mapState([
			'sftp',
		]),
	},
	methods:{
		...mapMutations([
			'SET_CURRENT_DIR',
		]),
	},
	components:{
		FolderTree,
	},
	template:`
	<aside>
		<ul class="n2-tree-parent">
			<li class="n2-tree-node">
				<label v-if="sftp.dirlist" class="has-child">
					<input type="checkbox" checked>
				</label>
				<label data-path="/" @click="SET_CURRENT_DIR({path:'/', children:sftp.dirlist})">
					<span class="dashicons dashicons-open-folder"></span>
					<span>root</span>
				</label>
				<FolderTree v-if="sftp.dirlist" :children="sftp.dirlist" />
			</li>
		</ul>
	</aside>
	`,
});