import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'LeftAside',
	async created(){
		const dirlist = await this.sftpRequest({
			judge: 'dirlist',
			path: '/',
		}).then(res=>res.data)
		this.$store.commit('SFTP',{dirlist});
	},
	methods:{
		...mapActions([
			'sftpRequest'
		])
	},
	data(){
		return {
			dirlist:null,
		}
	},
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