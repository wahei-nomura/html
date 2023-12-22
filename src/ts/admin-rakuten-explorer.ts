import Vue from 'vue/dist/vue.min';
// import store from './modules/admin-rakuten-menu/explorer-store';
import LeftAside from './modules/admin-rakuten-menu/sftp-explorer-left-aside';
import Main from './modules/admin-rakuten-menu/sftp-explorer-main';
// import store from './modules/admin-rakuten-menu/explorer-store';

Vue.config.devtools = true;

jQuery( async function($){	
	window['n2'].vue = new Vue({
		el: '#n2-sftp-explorer',
		created() {
			const n2nonce = $('input[name="n2nonce"]').val();
		},
		components:{
			Main,
			LeftAside,
		},
		template: `
		<div id="n2-sftp-explorer" class="container mt-4 mb-4">
			<LeftAside/>
			<Main/>
		</div>
		`,
	});
})