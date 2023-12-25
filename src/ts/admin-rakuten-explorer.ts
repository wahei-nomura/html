import Vue from 'vue/dist/vue.min';

import store from './modules/admin-rakuten-menu/sftp-store';
import LeftAside from './modules/admin-rakuten-menu/sftp-explorer-left-aside';
import Main from './modules/admin-rakuten-menu/sftp-explorer-main';
// import store from './modules/admin-rakuten-menu/explorer-store';

Vue.config.devtools = true;

jQuery( async function($){	
	window['n2'].vue = new Vue({
		el: '#n2-sftp-explorer',
		async created() {
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce);

			const n2referer = $('input[name="_wp_http_referer"]').val();
			this.$store.commit('SET_N2REFERER',n2referer);
		},	
		store,
		components:{
			Main,
			LeftAside,
		},
		template: `
		<div id="n2-sftp-explorer" class="row border-top border-dark">
			<LeftAside class="col-3"/>
			<Main class="col-9 border-start border-dark"/>
		</div>
		`,
	});
})