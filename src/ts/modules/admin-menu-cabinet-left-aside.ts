import Vue from 'vue/dist/vue.min';
import Vuex,{mapState,mapActions} from 'vuex/dist/vuex.min';
import FolderTree from './admin-menu-cabinet-folder-tree';
import { cabinetFolder } from './admin-menu-cabinet-interface';

export default Vue.extend({
    name: 'LeftAside',
    data() {
        return {
        };
    },
	computed:{
		...mapState([
			'n2nonce',
			'isTrashBox',
			'folders',
			'rootFolder',
			'tree',
		]),
	},
    methods: {
		...mapActions([
			'updateFiles',
			'updateFolders',
		]),
		buildTree(){
			this.$store.commit('SET_TREE',this.folders);
		},
		updateTrahBoxFiles(){
			this.$store.commit('SET_FORMDATA',{
				call: 'trashbox_files_get',
			})
			this.$store.dispatch('updateTrahBoxFiles');
		},
		createFolder(){
			console.log('create?');
			this.showInputModal();
		},
		...mapActions([
			'showModal',
		]),
    },
	components:{
		FolderTree,
	},
	async created(){
	},
	async mounted(){
		await this.updateFolders();
		this.buildTree();
		this.updateFiles( this.rootFolder );
		console.log('create');
	},
	template:`
		<aside id="left-aside" ref="left-aside" class='overflow-auto col-3'>
			<nav class="d-flex justify-content-around pt-3 pb-1">
				<button @click.prevent="showModal('create')" class="btn btn-outline-secondary btn-sm" type="button" name="folder_insert"
				>
					新規作成
				</button>
				<button id="show-trashbox-btn" :class="{ active : isTrashBox }" @click.prevent="updateTrahBoxFiles"
					class="btn btn-outline-warning btn-sm" type="button" name="trashbox_files_get"
				>
					ゴミ箱を確認
				</button>
			</nav>
			<div class="tree overflow-auto p-1">
				<FolderTree :parent="tree" :folder="rootFolder" />
			</div>
		</aside>
	`
});