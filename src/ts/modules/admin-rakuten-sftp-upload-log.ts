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
				link_rms: {
					th:{
						value:'RMS連携',
						icon:'dashicons dashicons-cloud-upload',
					},
				},
				link_rms_history: {
					th:{
						value:'RMS連携履歴',
						icon:'dashicons dashicons-clipboard',
					},
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
		setLinkIndex(index){
			this.linkIndex = index;
		},
		async linkImage2RMS (item){
			// RMS 更新用
			const getRmsItemRequests = [];
			const updateRmsItemRequests = [];
			// N2 更新用
			const update_item = structuredClone(item);
			const update_item_before_revisions = {};

			// RMSから返礼品情報を取得
			Object.keys(item.upload_data).forEach(manageNumber=>{
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_item_api_ajax');
				formData.append('call', 'items_get');
				formData.append('mode', 'json');
				const request = axios.post(
					window['n2'].ajaxurl,
					formData,
				);
				getRmsItemRequests.push(request);
			})
			await Promise.all(getRmsItemRequests).then(responses=>{
				responses.forEach(res=>{
					update_item_before_revisions[res.data.manageNumber] = res.data.images.map(image=>image.location);
				})
			});

			// 更新の必要性を確認
			const updateItems = [];
			Object.keys(update_item_before_revisions).forEach(manageNumber=>{
				// 明らかに配列の長さが違う場合は必要
				if(update_item.upload_data[manageNumber].length !== update_item_before_revisions[manageNumber].length) {
					updateItems.push(manageNumber);
					return;
				}
				// diffの精査
				const mergeArr = [...update_item.upload_data[manageNumber],...update_item_before_revisions[manageNumber]];
				const diff = update_item.upload_data[manageNumber].filter( (i:number) => mergeArr.indexOf(i) === -1 );
				if (diff.length) updateItems.push(manageNumber);
			})

			if(! updateItems.length){
				alert('更新不要です');
				this.linkIndex = null;
				return;
			};

			// 確認用メッセージ作成
			let confirmMessage = [];
			updateItems.forEach(manageNumber=>{

				const add = update_item.upload_data[manageNumber].filter( (i:number) => update_item_before_revisions[manageNumber].indexOf(i) === -1 );
				if (add.length){
					confirmMessage.push('【追加】' + manageNumber);
					confirmMessage = [...confirmMessage,...add];
				}
				const remove  = update_item_before_revisions[manageNumber].filter( (i:number) => update_item.upload_data[manageNumber].indexOf(i) === -1 );
				if (remove.length){
					confirmMessage.push('【解除】' + manageNumber);
					confirmMessage = [...confirmMessage,...remove];
				}
			});
			if (!confirm('以下の内容で更新しますか？\n'+ confirmMessage.join('\n'))){
				this.linkIndex = null;
				return
			}

			// RMS更新用
			updateItems.forEach(manageNumber => {
				const formData = new FormData();
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
				)
				updateRmsItemRequests.push(request)
			})
			await Promise.all(updateRmsItemRequests).then( async res => {
				console.log('item_batch',res);
				// N2を更新
				const formData = new FormData();
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rakuten_sftp_update_post');
				formData.append('post_id', update_item.id );
				// id削除
				delete update_item.id;
				// 画像用revision追加
				update_item.image_revisions.before = update_item_before_revisions;
				update_item.image_revisions.after  = update_item.upload_data;
				formData.append('post_content', JSON.stringify(update_item));
				await axios.post(
					window['n2'].ajaxurl,
					formData,
				);
				// 最新情報に更新
				await this.updateSFTPLog()
				alert('更新完了しました！')
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
						<template v-else-if="meta==='link_rms' && item.upload_type==='img_upload'">
							<button
								@click="linkImage2RMS(item), setLinkIndex(item.id)"
								:disabled="! item?.upload_data"
								type="button" class="btn btn-sm btn-secondary"
							>
								<span :class="{'spinner-border spinner-border-sm':linkIndex===item.id}"></span>
								商品ページと紐付ける
							</button>
						</template>
						<template v-else-if="meta==='link_rms_history' && item.upload_type==='img_upload'">
							<button
								@click="displayHistory(item)"
								:disabled="!Object.keys(item.image_revisions.after).length"
								type="button" class="btn btn-sm btn-outline-warning"
							>
								時を見る
							</button>
						</template>
						<template v-else>
							<span :class="col.td?.icon?.[item.upload_type] ?? col.td?.icon ?? ''"></span>
							{{col.td?.value?.[item.upload_type] ?? col.td?.value ?? item[meta] ?? ''}}
						</template>
					</td>
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