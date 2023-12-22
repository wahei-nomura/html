import Vue from 'vue/dist/vue.min';
import Modals from './modules/admin-rakuten-menu/cabinet-modals';
import store from './modules/admin-rakuten-menu/cabinet-store'
import App from './modules/admin-rakuten-menu/cabinet-app'
import axios from 'axios';
import { mapState } from 'vuex';

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
	if ( connect.data !== true ) {
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
		data() {
			return {
				timer: null,
			}
		},
		beforeDestroy() {
			if ( this.timer ) {
				clearTimeout(this.timer);
			}
		},
		watch: {
			isLoading(newVal, _){
				if ( this.timer ) {
					clearTimeout(this.timer);
				}
				if ( newVal ) {
					this.timer = setTimeout(()=>{
						document.getElementById('ss-cabinet').innerText = 'RMS CABINETに接続できませんでした。';
					},5000);
				}
			},
		},
		computed: {
			...mapState([
				'isLoading',
			]),
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
