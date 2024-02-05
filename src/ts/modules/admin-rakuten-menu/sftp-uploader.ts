import axios, {AxiosResponse} from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';
import { SftpUploadData } from './sftp-upload-interface';

export default Vue.extend({
	data():SftpUploadData{
		return {
			uploading: false,
			modeProp : {
				'text/csv': {
					mode:'csv_upload',
					icon: 'dashicons dashicons-media-spreadsheet',
					name: ['normal-item', 'item-cat', 'item-delete'],
				},
				'image/jpeg': {
					mode:'img_upload',
					icon: 'dashicons dashicons-format-gallery',
				},
			},
			action: 'n2_rakuten_sftp_upload_to_rakuten',
			files: [],
			fileResetCount: 0,
		}
	},
	computed:{
		...mapState([
			'n2nonce',
		]),
		uploadType(): string|undefined{
			return this.files[0]?.type;
		},
		uploadMode(): string|undefined{
			return this.modeProp[this.uploadType]?.mode;
		},
		uploadIcon(): string|undefined{
			return this.modeProp[this.uploadType]?.icon;
		},
	},
	methods:{
		...mapActions([
			'updateSFTPLog'
		]),
		setFiles():void{
			const files: File[] = Array.from(this.$refs.fileInput.files);
			// ファイルが選択されなければ何もしない
			if( ! files.length ) {
				this.resetFiles()
				return
			}
			const type = files[0].type
			// 違う種類のファイルが混ざっていないか判定
			if ( files.some((file : File)=> type !== file.type) ) {
				this.resetFiles();
				alert('アップロードできるファイル形式(CSV,画像)が複数選択されています。形式毎にアップロードしてください');
				return
			}
			// ファイル形式が正しいか判定
			if ( ! Object.keys(this.modeProp).some( key=> key === type ) ) {
				this.resetFiles();
				alert('アップロードできる拡張子は.csvまたは.jpgです');
				return
			}
			// ファイル名判定
			if (this.modeProp[type]?.name && ! files.every((file : File)=> this.modeProp[type].name.some( name => file.name.includes(name) ) ) ) {
				this.resetFiles();
				alert(`ファイル名に指定のワード(${this.modeProp[type].name.join(',')})が含まれていません`);
				return
			}
			const hasDeleteCSV = files.filter(file=>file.name.indexOf( 'item-delete') !== -1 ).length;
			if ( hasDeleteCSV && ! confirm( 'item-delete.csv が選択されています。続けますか？' ) ) {
				this.resetFiles();
				return;
			}
			this.files = files;
		},
		resetFiles():void{
			++this.fileResetCount;
			this.$refs.fileInput.value = '';
			this.files = [];
		},
		async postFiles(){
			const files:File[] = this.files;
			if( ! files.length ) {
				alert('ファイルが選択されていません。')
				return;
			}
			this.uploading = true;
			const formData:FormData = new FormData();
			formData.append( 'n2nonce', this.n2nonce);
			formData.append( 'action', this.action);
			formData.append( 'judge', this.uploadMode);
			formData.append( 'mode', 'text');
			for (let i = 0; i < files.length; i++) {
				formData.append('sftp_file[]', files[i]);
			}
			const headers = {
				'content-type': 'multipart/form-data',
			};
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
				{headers:headers}
			).then((res)=>{
				alert(res.data);
				// ログ一覧の更新
				this.updateSFTPLog();
			}).catch(err=>{
				console.error(err);
				alert(err.response.data);
			}).then(()=>{
				this.uploading = false;
				this.resetFiles();
			});
		},
	},
	template:`
		<form enctype="multipart/form-data" class="mb-4">
			<input type="hidden" :value="uploadMode">
			<div class="mb-2 input-group">
				<input @change="setFiles" :key="fileResetCount" ref="fileInput" class="form-control" name="sftp_file[]" type="file" multiple="multiple" style="padding: 0.375rem 0.75rem;" aria-describedby="files-label">
				<button @click.prevent="postFiles" class="btn btn-outline-secondary" :class="{'active':uploading}" :disabled="!files.length || uploading">
					<template v-if="uploading">
						<span class="spinner-border spinner-border-sm"></span>
					</template>
					<template v-else-if="files.length">
						<span :class="uploadIcon"></span>
						楽天に転送する
					</template>
					<template v-else>
						楽天に転送する
					</template>
				</button>
			</div>
		</form>
	`
});



