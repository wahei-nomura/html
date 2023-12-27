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

			this.offset = $('#n2-sftp-explorer').offset();
			const n2nonce = $('input[name="n2nonce"]').val();
			this.$store.commit('SET_N2NONCE',n2nonce);

			const n2referer = $('input[name="_wp_http_referer"]').val();
			this.$store.commit('SET_N2REFERER',n2referer);
			this.sftpRequest({data:{
				judge: 'dirlist',
				path: '',
			}}).then(res=>{
				const dirlist = res.data;
				this.$store.commit('SFTP',{dirlist});
				this.$store.commit('SET_LOADING',{is:false,status:'接続完了'});
				this.$store.commit('SET_CURRENT_DIR',{
					path: '',
					children: dirlist,
				});
			})
			setTimeout(()=>{
				this.$store.commit('SET_LOADING',{is:false,status:'接続エラー'});
			},8000);
		},
		data:{
			offset:{
				top: null,
				left:null,
			},
		},
		computed:{
			...mapState([
				'sftp',
				'loading',
			]),
			offsetHeight(){
				let top = 0;
				if( this.offset.top !== null ) top = this.offset.top + 80;
				return `height: calc(100vh - ${top}px);`
			},
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
		<div id="n2-sftp-explorer" class="row position-relative" :style="offsetHeight">
			<template v-if="! loading.is && sftp.dirlist === null ">
				<span class="text-danger">SFTP接続エラー<span>
			</template>
			<template v-else>
				<div id="loading" v-if="loading.is" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
					<span class="spinner-border text-primary" role="status"></span>
					<span class="ms-2 text-primary">{{loading.status}}</span>
				</div>
				<LeftAside class="col-3 p-3"/>
				<Main class="col-9 p-3 border-start border-dark"/>
			</template>
		</div>
		`,
	});
})