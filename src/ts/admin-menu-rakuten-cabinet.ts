import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';
import store from './modules/admin-menu-cabinet-store'
import App from './modules/admin-menu-cabinet-app'
import { mapState,mapActions,mapGetters } from 'vuex/dist/vuex.min';

jQuery(function($){

	window['n2'].vue = new Vue({
		el: '#ss-cabinet',
		components :{
			App,
		},
		store,
		computed:{
			...mapState([
				'isTrashBox',
				'isLoading',
				'selectedFolderId',
				'files',
				'viewMode',
				'modal',
				'rootFolder',
			]),
			...mapGetters([
				'filterFiles',
			])
		},
		methods:{
		},
		created(){
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce)
		},
		template: `
		<div id="ss-cabinet" class="container-fluid">
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
			<App/>
		</div>
		`
	});
})
