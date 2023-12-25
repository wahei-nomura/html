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
	<aside class="col-3">
		<FolderTree v-if="sftp.dirlist" :children="sftp.dirlist" />
	</aside>
	`,
});