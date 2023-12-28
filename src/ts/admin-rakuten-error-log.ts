import Vue from 'vue/dist/vue.min';
import {mapActions,mapMutations,mapState} from 'vuex/dist/vuex.min';
import store from './modules/admin-rakuten-menu/sftp-store';

Vue.config.devtools = true;

jQuery( async function($){
	window['n2'].vue = new Vue({
		el: '#n2-sftp-error-log',
		data(){
			return {
				errorLog:{
					dir: 'ritem/logs',
					csv:[],
				},
				offset:{
					top: null,
					left:null,
				},
			};
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
			]),
			...mapMutations([
				'SFTP',
				'SET_N2NONCE',
				'SET_N2REFERER',
				'SET_LOADING',
			]),
			getCsvErrorLogs(){
				this.errorLog.csv = [];
			},
		},
		async created(){
			this.offset = $('#n2-sftp-error-log').offset();
			const n2nonce = $('input[name="n2nonce"]').val();
			this.SET_N2NONCE(n2nonce);

			const n2referer = $('input[name="_wp_http_referer"]').val();
			this.SET_N2REFERER(n2referer);
			
			this.sftpRequest({data:{
				judge: 'dirlist',
				path: this.errorLog.dir,
			}}).then(res=>{
				const dirlist = res.data;
				this.SFTP({dirlist});
				this.SET_LOADING({is:false,status:'接続完了'});
			})
			setTimeout(()=>{
				this.SET_LOADING({is:false,status:'接続エラー'});
			},8000);
			
		},
		store,
		template:`
		<div id="n2-sftp-error-log" class="row position-relative" :style="offsetHeight">
			<div id="loading" v-if="loading.is" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
				<span class="spinner-border text-primary" role="status"></span>
				<span class="ms-2 text-primary">{{loading.status}}</span>
			</div>
			<template v-if="! loading.is && sftp.dirlist === null ">
				<span class="text-danger">SFTP接続エラー<span>
			</template>
			<template v-if="Array.isArray(sftp.dirlist)">
				<span>エラーログはありません</span>
			</template>
			<template v-else>
				<table class="widefat striped" style="margin: 2em 0;">
					<tr v-for="(log,name) in sftp.dirlist">
					<td>{{log.time}}</td>
					<td>
						<button type="button" :popovertarget="log.name" class="button button-primary">エラー内容を見る</button>
						<div popover="auto" :id="log.name" style="width: 80%; max-height: 80%; overflow-y: scroll;">
							<pre>{{log.contents}}</pre>
						</div>
					</td>
					<td>{{errorLog.dir + '/' + log.name}}</td>
					</tr>
				</table>
			</template>
		</div>
		`,
	});
});