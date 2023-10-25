import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	async created(){
		await this.updateSFTPLog();
	},
	data() {
		return {
			tr: {
				upload_type: {
					name: '転送モード',
					value:{
						img_upload: '商品画像',
						csv_upload: '商品CSV',
					},
				},
				upload_date: {
					name:'アップロード',
				},
				upload_log: {
					name:'アップロードログ',
					details: true,
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
		linkImage2RMS(data){
			Object.keys(data).forEach(async manageNumber => {
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_item_api_ajax');
				formData.append('call', 'items_patch');
				formData.append('mode', 'json');
				const images = data[manageNumber].map(path=>{
					return {
						type: 'CABINET',
						location: path,
					};
				});
				formData.append('body',JSON.stringify({images}));
				await axios.post(
					window['n2'].ajaxurl,
					formData,
				).then(()=>{

				}).catch((err)=>{

				});
			})
		}
	},
	template:`
	<table class="table align-middle lh-1">
		<thead>
			<tr>
				<th v-for="th in tr">{{th.name}}</th>
				<th>RMS連携</th>
			</tr>
		</thead>
		<tbody>
			<template v-if="sftpLog.items.length">
				<tr v-for="item in sftpLog.items">
					<td v-for="(td,meta) in tr">
						<template v-if="td?.details">
							<button type="button" :popovertarget="meta + item.id" class="btn btn-sm btn-outline-info">詳細</button>
							<div popover="auto" :id="meta + item.id" style="width: 80%; max-height: 80%; overflow-y: scroll;">
								<pre>{{item[meta]}}</pre>
							</div>
						</template>
						<template v-else-if="td.value">
							{{td.value[item[meta]]}}
						</template>
						<template v-else>
							{{item[meta]}}
						</template>
					</td>
					<td>
					<template v-if="item.upload_type==='img_upload'">
						<button type="button" class="btn btn-sm btn-outline-warning">元に戻す</button>
						<button
							@click="linkImage2RMS(item.upload_data)"
							:disabled="! item?.upload_data"
							type="button" class="btn btn-sm btn-outline-secondary"
						>
							紐付ける
						</button>
					</template>
					</td>
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