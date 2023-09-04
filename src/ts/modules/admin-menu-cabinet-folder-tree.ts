import Vue from 'vue/dist/vue.min';
import Vuex,{mapState} from 'vuex/dist/vuex.min';
import FolderTree from './admin-menu-cabinet-folder-tree';
import { mapActions } from 'vuex';

Vue.use(Vuex);
export default Vue.extend({
    name: 'FolderTree',
	props: {
		parent: {
			type: Object,
			required: true
		},
		path: {
			type: String,
			default: null
		},
		folder:{
			type:Object,
			required: true
		},
	},
	computed:{
		...mapState([
			'folders',
			'selectedFolder',
			'isTrashBox',
			'isLoading',
		]),
		check(){
			return this.shouldCheck();
		},
	},
	methods: {
		...mapActions([
			'updateFiles',
		]),
		getPath(li){
			return this.path !== null ? `${this.path}/${li}` : '';
		},
		getDataPath(li) {
			return this.getPath(li) || '/';
		},
		getFolder(li){
			const dir = this.getDataPath(li);
			const index = this.folders.findIndex(folder => folder.FolderPath === dir );
			return this.folders[index];
		},
		getFolderId(li) {
			return this.getFolder(li).FolderId;
		},
		getFolderName(li) {
			return this.getFolder(li).FolderName;
		},
		getSpanClass(li) {
			const folderId = this.getFolderId(li);
			return {
				active: !this.isTrashBox && this.selectedFolder?.FolderId === folderId
			};
		},
		getIconClass(li) {
			const folderId = this.getFolderId(li);
			return {
				'spinner-border spinner-border-sm': this.isLoading && folderId === this.selectedFolder?.FolderId,
				'bi bi-folder2-open': !(this.isLoading && folderId === this.selectedFolder?.FolderId)
			};
		},
		shouldCheck(){
			return this.isChecked;
		},
	},
	data() {
		return {
			// Initialize your data properties here, e.g.:
			isChecked: false,
		};
	},
	mounted(){
		this.isChecked = this.path === null;
	},
	components:{
		FolderTree
	},
	template: `
	<ul>
		<li v-for="(child, li) in parent" :key="li" :class="{ 'hasChildren': Object.keys(child).length > 0 }">
			<label class='folder-open'>
				<input name='folder-open' type="checkbox" :checked="check">
			</label>
			<span :data-path="getDataPath(li)" :data-id="getFolderId(li)"  @click="updateFiles(getFolder(li))" :class="getSpanClass(li)">
				<i :class="getIconClass(li)"></i> {{ getFolderName(li) }}
			</span>
			<FolderTree v-if="child" :parent="child" :path="getPath(li)" :folder="getFolder(li)" />
		</li>
	</ul>
	`
});