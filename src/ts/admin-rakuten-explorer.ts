import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';
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
			const res = await this.sftp({
				judge: 'dirlist',
				path: '/',
			})
		},
		methods:{
			...mapActions([
				'sftp'
			])
		},
		store,
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