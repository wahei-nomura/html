import Vue from 'vue/dist/vue.min';
import Vuex,{mapState,mapGetters} from 'vuex/dist/vuex.min';
import $ from 'jquery';
import ImageCard from './admin-menu-cabinet-main-card';

Vue.use(Vuex);

export default Vue.extend({
    name: 'Main',
    data() {
        return {
        };
    },
	computed:{
		...mapState([
			'n2nonce',
			'isTrashBox',
			'viewMode',
			'files',
			'selectedFolder',
			'selectedFile',
		]),
		...mapGetters([
			'filterFiles',
		])
	},
    methods: {
		handleToggleViewMode(e){
			const viewMode = $(e.target).data('view-mode')
			this.$store.commit('SET_VIEW_MODE',viewMode)
		},
    },
	components:{
		ImageCard
	},
	template:`
	<main class="border-start border-dark overflow-auto" :class="{'col-9': !selectedFile, 'col-6': selectedFile}" :style="">
		<nav class="navbar navbar-light bg-light position-sticky top-0 start-0 align-items-strech">
		<div class="navbar-brand" id="current-direcotry">{{selectedFolder?.FolderName||'基本フォルダ'}}</div>
		<div class="navbar-text me-auto" id="file-count">{{ filterFiles.length }}</div>
		<div class="d-flex ms-auto">
			<div class="d-flex align-items-center">
				選択した画像を
				<div class="btn-group" role="group">
					<button @click.prevent="showModal('move')" id="cabinet-navbar-btn-move" :class="{ 'd-none' : isTrashBox }" class="btn btn-outline-secondary rounded-pill px-4 py-0" type="button" name="files_move" data-bs-toggle="modal" data-bs-target="#filesMoveModal">移動</button>
					<form>
						<input type="hidden" name="call" value="">
						<button id="cabinet-navbar-btn" class="btn btn-outline-warning rounded-pill px-4 py-0" name="file_delete">
							{{ isTrashBox ? '元に戻す' : '削除' }}
						</button>
					</form>
					<form :class="{ 'd-none' : isTrashBox }" :action="window.n2.ajaxurl" method="POST" enctype="multipart/form-data">
						<input type="hidden" name="action" value="n2_download_multiple_image_by_url">
						<input type="hidden" name="n2nonce" :value="n2nonce">
						<button @click.prevent="showModal('download')" id="cabinet-navbar-btn-dl" class="btn btn-outline-secondary rounded-pill px-4 py-0" name="file_download">DL</button>
					</form>
					</div>
				</div>
			<div class="px-3">
				<label>
					<input class="grid-radio view-radio" type="radio" name="view-mode" value="1" hidden checked>
					<i @click="handleToggleViewMode" class="radio-icon bi bi-grid-3x2-gap-fill fs-4" data-view-mode="grid" style="transform: translateX(5px);"></i>
				</label>
				<label>
					<input class="list-radio view-radio" type="radio" name="view-mode" value="2" hidden>
					<i @click="handleToggleViewMode" class="radio-icon bi bi-list-task fs-4" data-view-mode="list"></i>
				</label>
			</div>
		</div>
	</nav>
	<div id="ss-cabinet-images" :class="{ 'd-none' : viewMode !== 'grid' }" class="pb-3 position-relative d-flex align-content-start justify-content-start align-items-start flex-wrap">
		<ImageCard v-for="(image, index) in files" :key="image.FileId" :image="image" />
	</div>
	<div id="ss-cabinet-lists" :class="{ 'd-none' : viewMode !== 'list' }">
		<table class="table align-middle lh-1">
			<thead>
				<tr>
					<th scope="col"><input type="checkbox" name="selectedAll"></th>
					<th scope="col">画像</th>
					<th scope="col">ファイル名<i class="bi bi-caret-down"></i></th>
					<th scope="col">サイズ<i class="bi bi-caret-down"></i></th>
					<th scope="col">登録/変更日<i class="bi bi-caret-down"></i></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	<div class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light">
		ファイルをドラッグ&ドロップで転送する
		<form :action="window.n2.ajaxurl" method="POST" enctype="multipart/form-data" style="display:none;">
			<input type="hidden" name="n2nonce" :value="n2nonce">
			<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
			<input type="hidden" name="mode" value="json">
			<input type="hidden" name="call" value="file_insert">
			<input type="hidden" name="folderId" value="">
			<input type="file" multiple="multiple" name="cabinet_file[]">
			<input type="submit" value="リクエストを送信">
		</form>
	</div>
	</main>
	`
});