import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	async created(){
		await this.updateSFTPLog();
	},
	data() {
		return {
			linkIndex: null,
			tr: {
				upload_type: {
					name: '転送モード',
					icon: 'dashicons dashicons-cloud-saved',
					value:{
						img_upload: {
							icon:'dashicons dashicons-format-gallery',
							value:'商品画像',
						},
						csv_upload: {
							icon:'dashicons dashicons-media-spreadsheet',
							value:'商品CSV',
						},
					},
				},
				upload_date: {
					name:'アップロード',
					icon:'dashicons dashicons-backup',
					detail: "upload_log",
				},
			}
		};
	},
	computed:{
		...mapState([
			'sftpLog',
			'n2nonce',
		]),
	},
	methods:{
		...mapActions([
			'updateSFTPLog'
		]),
		linkImage2RMS(item){
			this.linkIndex = item.id;
			const updateRmsItems = [];
			Object.keys(item.upload_data).forEach(manageNumber => {
				let formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_item_api_ajax');
				formData.append('call', 'items_patch');
				formData.append('mode', 'json');
				const images = item.upload_data[manageNumber].map(path=>{
					return {
						type: 'CABINET',
						location: path,
					};
				});
				formData.append('body',JSON.stringify({images}));
				const request = axios.post(
					window['n2'].ajaxurl,
					formData,
				).then(async (res)=>{
					console.log('items_patch:',res.data);
					// N2を更新
					const temp_item = structuredClone(item);
					formData = new FormData();
					formData.append('n2nonce', this.n2nonce);
					formData.append('action', 'n2_rakuten_sftp_update_post');
					formData.append('post_id', temp_item.id );
					// id削除
					delete temp_item.id;
					// 画像用revision追加
					temp_item.image_revisions.push(temp_item.upload_data)
					formData.append('post_content', JSON.stringify(temp_item));
					return await axios.post(
						window['n2'].ajaxurl,
						formData,
					);
				});
				updateRmsItems.push(request)
			})
			Promise.all(updateRmsItems).then( async res => {
				console.log(res);
				// 最新情報に更新
				await this.updateSFTPLog()
				alert('紐付けが完了しました！')
				this.linkIndex = null;
			})
		},
		displayHistory(item){
			console.log(item);
		},
		formatUploadLogs(data){
			// フォルダ作成は除外する
			const arr = data.filter(d=>! d.includes('cabinet/images'));
			console.log(arr);

			return arr.join('\n');
		},
	},
	template:`
	<table class="table align-middle lh-1 text-center">
		<thead>
			<tr>
				<th v-for="th in tr">
					<span :class="th.icon"></span>
					{{th.name}}
				</th>
				<th>
					<span class="dashicons dashicons-cloud-upload"></span>
					RMS連携
				</th>
				<th>
					<span class="dashicons dashicons-clipboard"></span>
					RMS連携履歴
				</th>
			</tr>
		</thead>
		<tbody>
			<template v-if="sftpLog.items.length">
				<tr v-for="item in sftpLog.items" :key="item.id">
					<td v-for="(td,meta) in tr">
						<template v-if="td?.detail">
							<button
								type="button" class="btn btn-sm btn-outline-info"
								:popovertarget="meta + item.id"
							>
								{{item[meta]}}
							</button>
							<div popover="auto" :id="meta + item.id" style="width: 80%; max-height: 80%; overflow-y: scroll;">
								<pre>{{formatUploadLogs(item[td.detail])}}</pre>
							</div>
						</template>
						<template v-else-if="td.value">
							<span :class="td.value[item[meta]].icon"></span>
							{{td.value[item[meta]].value}}
						</template>
						<template v-else>
							{{item[meta]}}
						</template>
					</td>
					<template v-if="item.upload_type==='img_upload'">
					<td>
						<button
							@click="linkImage2RMS(item)"
							:disabled="! item?.upload_data"
							type="button" class="btn btn-sm btn-secondary"
						>
						<template v-if="linkIndex===item.id">
							<span class="spinner-border spinner-border-sm"></span>
						</template>
						<template v-else>
							紐付ける
						</template>
						</button>
					</td>
					<td>
						<button
							@click="displayHistory(item)"
							:disabled="!item?.image_revisions?.length"
							type="button" class="btn btn-sm btn-outline-warning"
						>
							時を見る
						</button>
					</td>
					</template>
				</tr>
			</template>
			<template v-else>
				<tr>
					<td :colspan="Object.keys(tr).length">アップロードログはありません</td>
				</tr>
			</template>
		</tbody>
	</table>
	`,
});