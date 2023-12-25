import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'LeftAside',
	computed:{
		...mapState([
			'sftp'
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
				<label data-path="/">
					<span class="dashicons dashicons-open-folder"></span>
					<span>root</span>
				</label>
				<FolderTree v-if="" :children="sftp.dirlist" />
			</li>
		</ul>
	</aside>
	`,
});