import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';
import ImageCard from './admin-rakuten-cabinet-main-card';
import TableRow from './admin-rakuten-cabinet-main-tr';
import { cabinetImage } from './admin-rakuten-cabinet-interface';

export default Vue.extend({
    name: 'Main',
    data() {
        return {
			selectionAll: false,
			sortKey : null,
			sortOrder : 1,
			ths: [
				{text: 'ファイル名',key: 'FileName',},
				{text: 'サイズ',key: 'FileSize',},
				{text: '登録/変更日',key: 'TimeStamp',},
			],
        };
    },
	computed:{
		...mapState([
			'n2nonce',
			'isTrashBox',
			'isSearchResult',
			'viewMode',
			'files',
			'selectedFiles',
			'selectedFolder',
			'focusFile',
			'offsetHeight',
		]),
		...mapGetters([
			'filterFiles',
		]),
		sotredFiles() {
			return this.filterFiles.slice().sort((a:cabinetImage,b:cabinetImage)=>{
				return  ( a[this.sortKey] - b[this.sortKey] ) * this.sortOrder ;
			});
		},
	},
    methods: {
		...mapActions([
			'showModal',
			'ajaxPost',
			'ajaxPostMulti',
			'ajaxPostSelectedFileIds',
			'selectionAllFiles',
		]),
		handleToggleViewMode(e){
			const viewMode = e.target.getAttribute('data-view-mode')
			this.$store.commit('SET_VIEW_MODE',viewMode)
		},
		async upload(files){
			const formData = {
				call:'file_insert',
				folderId: this.selectedFolder.FolderId,
			};
			Object.keys(files).forEach((i)=>{
				formData[`cabinet_file[${i}]`] = files[i];
			})
			const config = {
				headers: {
					'Content-Type': 'multipart/form-data'
				},
			};
			return await this.ajaxPostMulti({
				formData:formData,
				config:config,
			});
		},
		async handleDrop(e){
			// ドロップされたファイルを取得
			const files = e.dataTransfer.files;
			this.upload(files);
		},
		handleChange(){
			const files = this.$refs.file.files
			if ( ! files.length ) {
				return;
			}
			this.upload(files);
		},
		handleClick(){
			this.$refs.file.click();
		},
		async handleFiles(type:string){
			const formData = {};
			const config = {};
			switch(type){
				case 'undo':
					formData['call'] = 'trashbox_files_revert';
					await this.ajaxPostSelectedFileIds({formData}).then(()=>{
						this.$store.commit("REMOVE_ALL_SELECTED_FILE_ID");
					});
					break;
				case 'delete':
					if ( ! confirm('選択した画像を削除しますか？') ) {
						return;
					}
					formData['call'] = 'file_delete';
					await this.ajaxPostSelectedFileIds({formData}).then(()=>{
						this.$store.commit("REMOVE_ALL_SELECTED_FILE_ID");
					});
					break;
				case 'download':
					formData['action'] = 'n2_download_multiple_image_by_url';
					this.selectedFiles.forEach((fileId:string,i)=>{
						const index = this.$store.state.files.findIndex(file => file.FileId === fileId );
						const file = this.$store.state.files[index];

						formData[`url[${i}][url]`] = file.FileUrl.replace('https://image.rakuten.co.jp','https://cabinet.rms.rakuten.co.jp/shops');
						formData[`url[${i}][fileName]`] = file.FileName;
						formData[`url[${i}][filePath]`] = file.FilePath;
						formData[`url[${i}][folderName]`] = file.FolderName;
					});
					const zipName = `【${window['n2'].town}】楽天Cabinet_${this.getFormattedDate()}`;
					formData['zipName'] = zipName;
					config['responseType'] = 'blob';
					await this.ajaxPost({formData,config}).then((resp)=>{
						var url = window.URL.createObjectURL(resp.data);
						// `<a>`タグを作成し、ダウンロードリンクとして使用します。
						const a = document.createElement('a');
						a.href = url;
						a.download = zipName + '.zip';  // ダウンロードされるファイル名を指定します。
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
					});
					break;
			};
		},
		getFormattedDate():string {
			const now = new Date();
			const [year, month, day, hours, minutes] = [
				now.getFullYear(),
				(now.getMonth() + 1).toString().padStart(2, '0'),
				now.getDate().toString().padStart(2, '0'),
				now.getHours().toString().padStart(2, '0'),
				now.getMinutes().toString().padStart(2, '0'),
			];
			return `${year}-${month}-${day}-${hours}-${minutes}`;
		},
		toggleSelectionAll(){
			this.selectionAll ^= 1;
			if ( this.selectionAll ) {
				this.$store.dispatch('selectionAllFiles');
			} else {
				this.$store.commit('REMOVE_ALL_SELECTED_FILE_ID');
			}
		},
		sortTable(key) {
			if( key === this.sortKey ) {
				this.sortOrder *= -1;
			} else {
				this.sortKey = key;
				this.sortOrder = 1;
			}
		},
    },
	components:{
		ImageCard,
		TableRow,
	},
	template:`
	<main class="border-start border-dark overflow-auto" :class="{'col-9': ! focusFile || isTrashBox, 'col-6': focusFile && ! isTrashBox }" :style="offsetHeight">
		<nav class="navbar navbar-light bg-light position-sticky top-0 start-0 align-items-strech">
			<div class="navbar-brand" id="current-direcotry">{{selectedFolder?.FolderName||'基本フォルダ'}}</div>
			<div class="navbar-text me-auto" id="file-count">{{ filterFiles.length }}</div>
			<div class="d-flex ms-auto">
				<div class="d-flex align-items-center">
					選択した画像を
					<div class="btn-group" role="group">
						<template v-if=" ! ( isSearchResult || isTrashBox )">
							<button @click.prevent="showModal('move')" id="cabinet-navbar-btn-move"
								class="btn btn-outline-secondary rounded-pill px-4 py-0"
								type="button" name="files_move"
								:disabled="!selectedFiles.length"
							>
								移動
							</button>
						</template>
						<template v-if="isTrashBox">
							<button @click.prevent="handleFiles('undo')" v-if="isTrashBox"
								class="btn btn-outline-warning rounded-pill px-4 py-0"
								:disabled="!selectedFiles.length"
							>
								元に戻す
							</button>
						</template>
						<template v-else>
							<button @click.prevent="handleFiles('delete')"
								class="btn btn-outline-warning rounded-pill px-4 py-0"
								:disabled="!selectedFiles.length"
							>
								削除
							</button>
							<button @click.prevent="handleFiles('download')" id="cabinet-navbar-btn-dl"
								class="btn btn-outline-secondary rounded-pill px-4 py-0" name="file_download"
								:disabled="!selectedFiles.length"
							>
								DL
							</button>
						<template>
						</div>
					</div>
				<div class="px-3" :class="{'d-none':isTrashBox}">
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
						<th scope="col"><input type="checkbox" :checked="selectionAll" @click='toggleSelectionAll'></th>
						<th scope="col" :class="{'d-none':isTrashBox}">画像</th>

						<th v-for="th in ths" @click.stop="sortTable(th.key)" scope="col">
							{{th.text}}
							<i class="bi" :class="{
								'bi-caret-down': sortKey !== th.key,
								'bi-caret-down-fill': sortKey === th.key && sortOrder > 0,
								'bi-caret-up-fill': sortKey === th.key && sortOrder < 0,
							}"></i>
						</th>
					</tr>
				</thead>
				<tbody>
					<TableRow v-for="(image, index) in sotredFiles" :key="image.FileId" :image="image" />
				</tbody>
			</table>
		</div>
		<div @click="handleClick" @drop.prevent="handleDrop" @dragover.prevent :class="{'d-none':isTrashBox}" class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light">
			ファイルをドラッグ&ドロップで転送する
			<form style="display:none;">
				<input ref=file @change="handleChange" type="file" multiple="multiple" class="d-none">
			</form>
		</div>
	</main>
	`
});