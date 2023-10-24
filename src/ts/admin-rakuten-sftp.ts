import Vue from 'vue/dist/vue.min';
import store from './modules/admin-rakuten-sftp-store'
import axios from 'axios';

jQuery( async function($){	
	window['n2'].vue = new Vue({
		el: '#ss-sftp',
		store,
		created() {
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce)
		}
	});
})