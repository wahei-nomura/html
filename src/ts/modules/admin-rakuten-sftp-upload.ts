import axios, {AxiosResponse} from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	data(){
		return {
			uploading: false,
			modeProp : {
				'text/csv': {
					mode:'csv_upload',
					icon: 'dashicons dashicons-media-spreadsheet',
				},
				'image/jpeg': {
					mode:'img_upload',
					icon: 'dashicons dashicons-format-gallery',
				},
			},
			selectedRadio: 'img_upload',
			action: 'n2_rakuten_sftp_upload_to_rakuten',
			files: [],
			fileReset: 0,
		}
	},
	computed:{
		...mapState([
			'n2nonce',
		]),
		uploadType(){
			return this.files[0]?.type;
		},
		uploadMode(){
			return this.modeProp[this.uploadType]?.mode;
		},
		uploadIcon(){
			return this.modeProp[this.uploadType]?.icon;
		},
	},
	methods:{
		...mapActions([
			'updateSFTPLog'
		]),
		setFiles(){
			interface file {
				type:string
			}
			const files: file[]    = Array.from(this.$refs.fileInput.files);
			if( ! files.length ) {
				this.files = [];
				return
			}
			// 違う種類のファイルが混ざっていないか判定
			if ( files.filter((file : file)=> files[0].type !== file.type).length ) {
				this.files = [];
				++this.fileReset;
				alert('アップロードできるファイル形式(CSV,画像)は一種類です');
				return
			}
			const type = files[0].type
			// ファイル形式が正しいか判定
			if ( ! Object.keys(this.modeProp).filter( key=> key===type ).length ) {
				this.files = [];
				++this.fileReset;
				alert('アップロードできる拡張子は.csvまたは.jpgです');
				return
			}
			this.files = files;
		},
		async postFiles(){
			const files    = this.files;
			if( ! files.length ) {
				alert('ファイルが選択されていません。')
				return;
			}
			this.uploading = true;
			const formData = new FormData();
			formData.append( 'n2nonce', this.n2nonce);
			formData.append( 'action', this.action);
			formData.append( 'judge', this.selectedRadio);
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
				alert(res.data['message']);
				// ログ一覧の更新
				this.updateSFTPLog();
			}).catch(err=>{
				console.log(err);
				alert(err.response.data.message);
			}).then(()=>{
				this.uploading = false;
			});
		},
	},
	template:`
		<form enctype="multipart/form-data" class="mb-4">
			<input type="hidden" :value="uploadMode">
			<div class="mb-2 input-group">
				<span v-if="files.length" :class="uploadIcon"></span>
				<input @change="setFiles" :key="fileReset" ref="fileInput" class="form-control" name="sftp_file[]" type="file" multiple="multiple" style="padding: 0.375rem 0.75rem;" aria-describedby="files-label">
				<button @click.prevent="postFiles" class="btn btn-outline-secondary" :class="{'active':uploading}">
					<template v-if="uploading">
						<span class="spinner-border spinner-border-sm"></span>
					</template>
					<template v-else>
						楽天に転送する
					</template>
				</button>
			</div>
		</form>
	`
});



