import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions,mapMutations} from 'vuex/dist/vuex.min';
import FolderTree from './sftp-explorer-folder-tree';

export default Vue.extend({
	name: 'LeftAside',
	data(){
		return {
			mkdirName: '',
		}
	},
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
		...mapActions([
			'sftpRequest',
			'refleshDir',
		]),
		/**
		 * 英数字のみ入力許可
		 */
		filterAlphanumeric(){
			this.mkdirName = this.mkdirName.replace(/[^a-zA-Z0-9]/g,'');
		},
		async mkdir(){
			const newDir = `${this.currentDir.path}/${this.mkdirName}`;
			const data = {
				judge:'mkdir',
				path: newDir,
			};
			this.sftpRequest({data}).then(res=>{
				console.log(res);
				this.refleshDir(newDir);
			})
		},
		dlDir(){
			console.log('dlDir');
			if(!confirm('選択中のフォルダ内は全て削除されます。続けますか？')) return;
			if(!confirm('本当に削除しますか？この操作は元に戻せません。')) return;
			const data = {
				judge:'delete',
				recursive: true,
				'paths[]': this.currentDir.path,
			};
			this.sftpRequest({data}).then(res=>{
				this.refleshDir('/');
			});
		},
	},
	components:{
		FolderTree,
	},
	template:`
	<aside>
		<div
			popover id="popover-mkdir"
			class="p-4"
			style="width: 80%; max-height: 80%;"
		>
			<div><h4>新規作成</h4></div>
			<div class="input-group pb-2">
				<span class="input-group-text">フォルダ名</span>
				<input v-model="mkdirName" @input="filterAlphanumeric" type="text" class="form-control" placehodler="directoryName" name="directoryName">
			</div>
			<div class="d-flex pb-2">
				<button
					@click="mkdir"
					:class="{disabled:!mkdirName}"
					class="btn btn-secondary flex-fill" type="submit"
					data-bs-dismiss="modal"
					popovertarget="popover-mkdir"
					popovertargetaction="hide"
				>フォルダを作成</button>
			</div>
		</div>
		<nav class="mb-3 d-flex justify-content-around">
			<button
				type="button"
				class="btn btn-outline-secondary btn-sm"
				popovertarget="popover-mkdir"
			>新規作成</button>
			<div class="btn btn-outline-danger btn-sm" @click="dlDir">削除</div>
		</nav>
		<ul class="n2-tree-parent">
			<li class="n2-tree-node">
				<label v-if="sftp.dirlist" class="has-child">
					<input type="checkbox" checked>
				</label>
				<label data-path="" :class="{active:'' === currentDir.path }"
					@click="SET_CURRENT_DIR({path:'', children:sftp.dirlist})"
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