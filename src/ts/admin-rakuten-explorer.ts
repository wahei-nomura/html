import Vue from 'vue/dist/vue.min';
import { mapActions, mapState } from 'vuex/dist/vuex.min';
import store from './modules/admin-rakuten-menu/sftp-store';
import LeftAside from './modules/admin-rakuten-menu/sftp-explorer-left-aside';
import Main from './modules/admin-rakuten-menu/sftp-explorer-main';

Vue.config.devtools = true;

jQuery( async function($){	
	window['n2'].vue = new Vue({
		el: '#n2-sftp-explorer',
		async created() {
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce);

			const n2referer = $('input[name="_wp_http_referer"]').val();
			this.$store.commit('SET_N2REFERER',n2referer);
			this.sftpRequest({
				judge: 'dirlist',
				path: '/',
			}).then(res=>{
				const dirlist = res.data;
				this.$store.commit('SFTP',{dirlist});
			})
			setTimeout(()=>{
				this.loading = false;
			},8000);
		},
		data:{
			loading: true,
		},
		computed:{
			...mapState([
				'sftp'
			]),
		},
		methods:{
			...mapActions([
				'sftpRequest',
			])
		},
		store,
		components:{
			Main,
			LeftAside,
		},
		template: `
		<div id="n2-sftp-explorer" class="row">
			<template v-if="loading &&  sftp.dirlist === null ">
				<div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
					<span class="spinner-border text-primary" role="status"></span>
					<span class="ms-2 text-primary">接続中</span>
				</div>
			</template>
			<template v-else-if="! loading && sftp.dirlist === null ">
				<span class="text-danger">SFTP接続エラー<span>
			</template>
			<template v-else>
				<LeftAside class="col-3 p-3"/>
				<Main class="col-9 p-3 border-start border-dark"/>
			</template>
		</div>
		`,
	});
})