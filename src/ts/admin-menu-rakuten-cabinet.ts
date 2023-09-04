import Vue from 'vue/dist/vue.min';
import Modals from './modules/admin-menu-cabinet-modals';
import store from './modules/admin-menu-cabinet-store'
import App from './modules/admin-menu-cabinet-app'
import axios from 'axios';

jQuery( async function($){
	// 接続確認
	const connect = await axios.get(
		window['n2'].ajaxurl,
		{
			params: {
				action: 'n2_rms_cabinet_api_ajax',
				call: 'connect',
				mode: 'json',
			},
		},
	)
	if ( ! connect.data ) {
		document.getElementById('ss-cabinet').innerText = 'RMS CABINETに接続できませんでした。';
		return;
	}

	window['n2'].vue = new Vue({
		el: '#ss-cabinet',
		components :{
			App,
			Modals,
		},
		store,
		created(){
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce)
		},
		template: `
		<div id="ss-cabinet" class="container-fluid">
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
			<App/>
			<Modals/>
		</div>
		`
	});
})
