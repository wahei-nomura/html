import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';

Vue.use(Vuex);
export default new Vuex.Store({
	state:{
		n2nonce: null,
		n2referer: null,
		sftpLog : {
			items: [],
		},
		sftp:{
			dirlist: null,
		},
		currentDir:{
			path:null,
			children:null,
		},
		currentFile: null,
	},
	mutations: {
		SET_N2NONCE(state, n2nonce:string){
			state.n2nonce = n2nonce;
		},
		SET_N2REFERER(state, n2referer:string){
			state.n2referer = n2referer;
		},
		SET_SFTP_LOG(state, log:[]){
			state.sftpLog = log;
		},
		SFTP(state, res){
			state.sftp = {...state.sftp, ...res};
		},
		SET_CURRENT_DIR(state, payload){
			const {path,children} = payload;
			state.currentDir = {path,children};
		},
		SET_CURRENT_FILE(state, payload){
			state.currentFile = payload;
		},
	},
	actions: {
		async updateSFTPLog ({commit}) {
			const params = new URLSearchParams({
				action: 'n2_items_api',
				post_type: 'n2_sftp',
				orderby: "ID",
			});
			return await axios.get( 
				`${window['n2'].ajaxurl}?${params}`,
			).then(res=>{
				commit('SET_SFTP_LOG',res.data);
				return res;
			});
		},
		async sftpRequest({state},data){
			console.log(data);
			
			const params = {
				action: 'n2_rakuten_sftp_explorer',
				n2nonce: state.n2nonce,
				...data,
			};
			const formData = new FormData();
			for (const key in params) {
				formData.append( key, params[key]);
			}
			return await axios.post( 
				`${window['n2'].ajaxurl}`,
				formData,
			).then(res=>{
				console.log(res)
				return res;
			});
		},
	},
});