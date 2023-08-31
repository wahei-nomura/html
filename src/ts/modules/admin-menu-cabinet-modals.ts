import Vue from 'vue/dist/vue.min';
import Vuex,{mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'Modals',
	data(){
		return {
		};
	},
	computed:{
		...mapState([
			'isLoading',
			'modal',
			'focusFile',
		]),
	},
	methods:{
		hideModeal(){
			this.$store.commit('SET_MODAL',null);
		},
		submit(){
			console.log('submit');
		},
	},
	template:`
	<div>
		<div @click="hideModeal" :class="{'d-none': modal !== 'move' }" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<form @click.stop class="bg-light px-3 py-2 rounded">
				<div class="d-none">
					<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
					<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
					<input type="hidden" name="mode" value="json">
					<input type="hidden" name="call" value="files_move">
				</div>
				<div>
					<h4>フォルダ移動</h4>
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">移動元</span>
					<input type="hidden" name="currentFolderId" value="">
					<input type="text" name="currentFolderName" value="" readonly="readonly">
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">移動先</span>
					<input type="hidden" name="targetFolderId" value="">
					<datalist id='folders' >
						<?php foreach ( $folders as $folder ) : ?>
							<option value="<?php echo $folder['FolderName']; ?>"></option>
						<?php endforeach; ?>
					</datalist>
					<input type="text" name="targetFolderName" list="folders">
				</div>
				<div class="d-flex pb-2">
					<button @click.prevent="move" class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">移動する</button>
				</div>
			</form>
		</div>
		<div @click="hideModeal" :class="{'d-none': modal !== 'create' }" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<form @click.stop class="bg-light px-3 py-2 rounded">
				<div class="d-none">
					<input type="hidden" name="call" value="folder_insert">
					<input type="hidden" name="upperFolderId" value="">
				</div>
				<div>
					<h4>新規作成</h4>
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">フォルダ名</span>
					<input type="text" class="form-control" placehodler="directoryName" name="directoryName">
				</div>
				<div class="input-group pb-2">
					<span class="input-group-text">表示名</span>
					<input type="text" class="form-control" placehodler="folderName" name="folderName">
				</div>
				<div class="d-flex pb-2">
					<button @click.prevent="submit" class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">フォルダを作成</button>
				</div>
			</form>
		</div>
		<div @click="hideModeal" :class="{'d-none': modal !== 'image' }" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<img @click.stop :src="focusFile?.FileUrl" :alt="focusFile?.FileName" class="img-fluid" style="max-width:75vw;">
		</div>
		<div id="loadingModal" :class="{'d-none':!isLoading }" class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
			<span class="spinner-grow text-primary" role="status"></span>
		</div>
	</div>
	`,
});