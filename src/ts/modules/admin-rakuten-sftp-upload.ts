import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	data(){
		return {
			uploadMode: {
				'img_upload': '商品画像',
				'csv_upload': '商品CSV',
			},
			selectedRadio: 'img_upload',
			action: 'n2_upload_to_rakuten_sftp',
		}
	},
	computed:{
		...mapState([
			'n2nonce',
		])
	},
	methods:{
		...mapActions([
			'updateSFTPLog'
		]),
		async postFiles(){
			const formData = new FormData();
			formData.append( 'n2nonce', this.n2nonce);
			formData.append( 'action', this.action);
			formData.append( 'judge', this.selectedRadio);
			const files    = this.$refs.fileInput.files;
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
			).then(res=>{
				alert(
					res.data['log'].join('\n')
				);
				// ログ一覧の更新
				this.updateSFTPLog();
			}).catch(err=>{
				console.log(err);
				alert(err.response.data.message);
			});
		},
	},
	template:`
		<form enctype="multipart/form-data" class="mb-4">
			<div class="mb-2">
				<span>モード選択 ：</span>
				<template v-for="( text, mode ) in uploadMode">
					<label class="me-2">
						<input type="radio" :value="mode" v-model="selectedRadio">{{text}}
					</label>
				</template>
			</div>
			<div class="mb-2 input-group">
				<input ref="fileInput" class="form-control" name="sftp_file[]" type="file" multiple="multiple" style="padding: 0.375rem 0.75rem;">
				<input @click.prevent="postFiles" type="submit" class="btn btn-outline-secondary" value="楽天に転送する">
			</div>
		</form>
	`
});



