import Vue from 'vue/dist/vue.min';
import store from './modules/admin-rakuten-menu/sftp-upload-store';
import sftpUpload from './modules/admin-rakuten-menu/sftp-uploader';
import sftpUploadLog from './modules/admin-rakuten-menu/sftp-upload-log';

Vue.config.devtools = true;

jQuery( async function($){	
	window['n2'].vue = new Vue({
		el: '#ss-sftp-upload',
		store,
		created() {
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce);
		},
		components :{
			sftpUpload, 
			sftpUploadLog, 
		},
		template: `
		<div id="ss-sftp-upload" class="container mt-4 mb-4">
			<sftpUpload />
			<sftpUploadLog />
		</div>
		`,
	});
})