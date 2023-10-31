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
			logTable: {
				upload_type: {
					th: {
						value: '転送モード',
						icon: 'dashicons dashicons-cloud-saved',
					},
					td:{
						icon: {
							img_upload: 'dashicons dashicons-format-gallery',
							csv_upload: 'dashicons dashicons-media-spreadsheet',
						},
						value: {
							img_upload: '商品画像',
							csv_upload: '商品CSV',
						},
					},
				},
				upload_date: {
					th:{
						value:'アップロード',
						icon:'dashicons dashicons-backup',
					},
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
			// フォルダ作成ログは除外する
			const arr = data.filter(d=>! d.includes('cabinet/images'));
			return arr.join('<br>');
		},
	},
	template:`
	<table class="table align-middle lh-1 text-center">
		<thead>
			<tr>
				<th v-for="col in logTable">
					<span :class="col.th.icon"></span>
					{{col.th.value}}
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
					<td v-for="(col,meta) in logTable">
						<template v-if="col?.detail">
							<button
								type="button" class="btn btn-sm btn-outline-info"
								:popovertarget="meta + item.id"
							>
								{{item[meta]}}
							</button>
							<div
								popover="auto" :id="meta + item.id"
								style="width: 80%; max-height: 80%; overflow-y: scroll;"
								v-html="formatUploadLogs(item[col.detail])"
							>
							</div>
						</template>
						<template v-else>
							<span :class="col.td.icon?.[item.upload_type] ?? col.td.icon ?? ''"></span>
							{{col.td.value?.[item.upload_type] ?? col.td.value ?? item[meta]}}
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
					<template v-else-if="item.upload_type==='csv_upload'">
						<td></td>
						<td></td>
					</template>
				</tr>
			</template>
			<template v-else>
				<tr>
					<td :colspan="Object.keys(logTable).length">アップロードログはありません</td>
				</tr>
			</template>
		</tbody>
	</table>
	`,
});