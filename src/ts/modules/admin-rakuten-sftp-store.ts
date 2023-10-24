import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';

Vue.use(Vuex);
export default new Vuex.Store({
	state:{
		n2nonce: null,
	},
	mutations: {
		SET_N2NONCE(state, n2nonce:string){
			state.n2nonce = n2nonce;
		},
	},
});