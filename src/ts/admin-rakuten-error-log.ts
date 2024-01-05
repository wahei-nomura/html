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
					name: '',
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
			errorLogContents(){
				return this.sftp.get_contents[this.errorLog.name];
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
			async getContents(name){
				this.errorLog.name = name;
				const get_contents = this.sftp.get_contents;
				// 取得済みならキャッシュ利用
				if(get_contents[name]){
					return;
				}
				this.SET_LOADING({is:true,status:'取得中...'});
				const data = {
					judge: 'get_contents',
					path: `${this.errorLog.dir}/${this.errorLog.name}`
				};
				await this.sftpRequest({data}).then(res=>{
					get_contents[name] = res.data;
					this.SFTP({get_contents});
					this.SET_LOADING({is:false,status:'取得完了'});
				});
			},
			formatDate(date){
				return new Date(date).toLocaleDateString('sv-SE');
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
		<div id="n2-sftp-error-log" class="position-relative" :style="offsetHeight">
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
			<template v-else-if="sftp?.dirlist">
				<table class="widefat striped" style="margin: 2em 0;">
					<tr v-for="[name,log] in Object.entries(sftp.dirlist).reverse()">
					<td>{{formatDate(log.lastmodunix*1000)}}</td>
					<td>
						<button type="button" popovertarget="popover-file-contents" class="button button-primary"
							@click="getContents(log.name)"
						>
							エラー内容を見る
						</button>
					</td>
					<td>{{errorLog.dir + '/' + log.name}}</td>
					</tr>
				</table>
				<div popover id="popover-file-contents" class="p-4"
					style="width: 80%; height: 80%; overflow:hidden;"
					v-show="!loading.is"
				>
					<textarea class="w-100 h-100" v-text="errorLogContents">
					</textarea>
				</div>
			</template>
		</div>
		`,
	});
});