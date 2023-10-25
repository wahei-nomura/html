import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';

Vue.use(Vuex);
export default new Vuex.Store({
	state:{
		n2nonce: null,
		sftpLog : {
			items: [],
		},
	},
	mutations: {
		SET_N2NONCE(state, n2nonce:string){
			state.n2nonce = n2nonce;
		},
		SET_SFTP_LOG(state, log:[]){
			state.sftpLog = log;
		},
	},
	actions: {
		async updateSFTPLog ({commit}) {
			const params = new URLSearchParams({
				action: 'n2_items_api',
				post_type: 'n2_sftp',
			});
			return await axios.get( 
				`${window['n2'].ajaxurl}?${params}`,
			).then(res=>{
				commit('SET_SFTP_LOG',res.data);
				return res;
			});
		},
	},
});