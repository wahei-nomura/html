import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'Modals',
	data(){
		return {
			folderName: null,
			directoryName: null,
			targetFolderName: this.rootFolder?.FolderName,
		};
	},
	computed:{
		...mapState([
			'isLoading',
			'isTrashBox',
			'modal',
			'focusFile',
			'selectedFiles',
			'folders',
			'rootFolder',
			'selectedFolder',
		]),
		currentFolderId(){
			return this.selectedFolder?.FolderId
		},
		currentFolderName(){
			return this.selectedFolder?.FolderName
		},
		targetFolderId(){
			return this.folders.filter(folder=>folder.FolderName === this.targetFolderName)[0]?.FolderId;
		},
	},
	methods:{
		...mapActions([
			'updateFiles',
			'updateFolders',
			'ajaxPost',
			'ajaxPostSelectedFileIds',
		]),
		hideModeal(){
			this.$store.commit('SET_MODAL',null);
		},
		async create(){
			const formData = {
				call: 'folder_insert',
				folderName: this.folderName,
				directoryName: this.directoryName,
			};
			if ( this.selectedFolder.FolderId !== '0' ) {
				formData['upperFolderId'] = this.selectedFolder.FolderId;
			}
			await this.ajaxPost({formData})
			.then(async resp=>{
				if ("OK" === resp.data.status.systemStatus) {
					console.log(resp.data);
					await this.updateFolders();
					const message = [
						`[${this.folderName}]を作成しました。`,
					];
					alert(message.join('\n'));
					this.$store.commit('SET_MODAL',null);
				} else {
					alert(resp.data.status.message);
				}
			});
		},
		async move(){
			const formData = {
				call: 'files_move',
				currentFolderId: this.currentFolderId,
				targetFolderId: this.targetFolderId,
			};
			await this.ajaxPostSelectedFileIds({formData})
			.then(()=>{
				this.$store.commit('SET_MODAL',null);
				this.updateFiles(this.selectedFolder);
			});
		},
	},
	template:`
	<div>
		<div @click="hideModeal" :class="{'d-none': ! modal}" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<form :class="{'d-none':modal !== 'move'}" @click.stop class="bg-light px-3 py-2 rounded">
				<div>
					<h4>フォルダ間移動</h4>
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">移動元</span>
					<input type="hidden" :value="currentFolderId">
					<input type="text" :value="currentFolderName" disabled>
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">移動先</span>
					<input type="hidden" :value="targetFolderId">
					<datalist id="folders">
						<option v-for="(folder, index) in folders" :value="folder.FolderName"></option>
					</datalist>
					<input type="text" v-model="targetFolderName" list="folders">
				</div>
				<div class="d-flex pb-2">
					<button @click.prevent="move" class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">移動する</button>
				</div>
			</form>
			<form :class="{'d-none':modal !== 'create'}" @click.stop class="bg-light px-3 py-2 rounded">
				<div>
					<h4>新規作成</h4>
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">フォルダ名</span>
					<input v-model="directoryName" type="text" class="form-control" placehodler="directoryName" name="directoryName">
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">表示名</span>
					<input v-model="folderName" type="text" class="form-control" placehodler="folderName" name="folderName">
				</div>
				<div class="d-flex pb-2">
					<button @click.prevent="create" class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">フォルダを作成</button>
				</div>
			</form>
			<img v-if="!isTrashBox" :class="{'d-none':modal !== 'image'}" @click.stop :src="focusFile?.FileUrl" :alt="focusFile?.FileName" class="img-fluid" style="max-width:75vw;">
		</div>
		<div id="loadingModal" :class="{'d-none':!isLoading }" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<span class="spinner-grow text-primary" role="status"></span>
		</div>
	</div>
	`,
});