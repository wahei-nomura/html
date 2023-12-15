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
				転送モード: {
					th: {
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
				アップロード: {
					th:{
						icon:'dashicons dashicons-backup',
					},
					detail: "アップロードログ",
				},
				RMS連携: {
					th:{
						icon:'dashicons dashicons-cloud-upload',
					},
				},
				RMS連携履歴: {
					th:{
						icon:'dashicons dashicons-clipboard',
					},
				},
			},
			action: 'n2_rakuten_sftp_upload_to_rakuten',
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
		async setLink(log){
			this.linkIndex = log.id;
			await this.linkImage2RMS(log);
			this.linkIndex = null;
		},
		async getRmsItemImages(log){

			// RMSから返礼品情報を取得
			const requests = Object.keys(log.アップロード.data).map(manageNumber=>{
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_item_api_ajax');
				formData.append('call', 'items_get');
				formData.append('mode', 'json');
				return axios.post(
					window['n2'].ajaxurl,
					formData,
				);
			})
			return await Promise.all(requests).then(responses=>{
				return responses.reduce((obj,res)=>{
					obj[res.data.manageNumber] = res.data.images.map(image=>image.location);
					return obj;
				},{});
			});
		},

		diffImageItems (log, images) {
			const diffItemNumbers = Object.keys(images).map(manageNumber=>{
				// 明らかに配列の長さが違う場合は必要
				if(log.アップロード.data[manageNumber].length !== images[manageNumber].length) {
					return manageNumber;
				}
				// diffの精査
				const mergeArr = [...log.アップロード.data[manageNumber],...images[manageNumber]];
				const diff = log.アップロード.data[manageNumber].filter( (i:number) => mergeArr.indexOf(i) === -1 );
				if (diff.length) return manageNumber;
				return '';
			}).filter(x=>x);

			
			return diffItemNumbers.reduce((obj,manageNumber)=>{
				obj[manageNumber] = {};
				const add = log.アップロード.data[manageNumber].filter( (i:number) => images[manageNumber].indexOf(i) === -1 );
				if (add.length){
					obj[manageNumber]['add'] = add;
				}
				const remove  = images[manageNumber].filter( (i:number) => log.アップロード.data[manageNumber].indexOf(i) === -1 );
				if (remove.length){
					obj[manageNumber]['remove'] = remove;
				}
				return obj;
			},{});
		},
		async linkImage2RMS ( log ) {
			// RMS 更新用
			const updateRmsItemRequests = [];
			// N2 更新用
			const updateLog = structuredClone(log);
			const rmsImages = await this.getRmsItemImages(updateLog);

			// 更新の必要性を確認
			const updateItems = this.diffImageItems(updateLog,rmsImages);
			if(! Object.keys(updateItems).length){
				alert('更新不要です');
				return;
			};

			// 確認用メッセージ作成
			let confirmMessage = [];
			Object.keys(updateItems).forEach(manageNumber=>{
				if ( updateItems[manageNumber].add ){
					confirmMessage.push('【追加】' + manageNumber);
					confirmMessage = [...confirmMessage,...updateItems[manageNumber].add];
				}
				if (updateItems[manageNumber].remove){
					confirmMessage.push('【解除】' + manageNumber);
					confirmMessage = [...confirmMessage,...updateItems[manageNumber].remove];
				}
			});
			if (!confirm('以下の内容で更新しますか？\n'+ confirmMessage.join('\n'))){
				return
			}

			// RMS更新用
			const itemPatchRequests = Object.keys(updateItems).map(manageNumber => {
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_item_api_ajax');
				formData.append('call', 'items_patch');
				formData.append('mode', 'json');
				const images = updateLog.アップロード.data[manageNumber].map(path=>{
					return {
						type: 'CABINET',
						location: path,
					};
				});
				formData.append('body',JSON.stringify({images}));
				return axios.post(
					window['n2'].ajaxurl,
					formData,
				)
			})
			await Promise.all(itemPatchRequests).then( async res => {
				console.log('item_batch',res);
				// N2を更新
				const formData = new FormData();
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', this.action);
				formData.append('judge', 'update_post' );
				formData.append('post_id', updateLog.id );
				// id削除
				delete updateLog.id, updateLog.log;
				// 画像用revision追加
				updateLog.RMS商品画像.変更前 = rmsImages;
				updateLog.RMS商品画像.変更後 = updateLog.アップロード.data;
				formData.append('post_content', JSON.stringify(updateLog));
				await axios.post(
					window['n2'].ajaxurl,
					formData,
				);
				// 最新情報に更新
				await this.updateSFTPLog()
				alert('更新完了しました！')
			})
		},
		async displayHistory(log){
			const param   = new URLSearchParams({
				action: 'n2_post_history_api',
				post_id: log.id,
				type: 'table',
				post_type: 'n2_sftp',
			}).toString();
			window.open(
				`${window['n2'].ajaxurl}?${param}`,
				'_blank'
			)
		},
		formatUploadLogs(log){
			log['テスト'] = 'test';
			const logs = log.アップロード.log.filter(d=>! d.includes('cabinet/images')).join('<br>');
			// フォルダ作成ログは除外する
			return logs
		},
	},
	template:`
	<table class="table align-middle lh-1 text-center">
		<thead>
			<tr>
				<th v-for="(col,meta) in logTable">
					<span :class="col.th?.icon"></span>
					{{meta}}
				</th>
			</tr>
		</thead>
		<tbody>
			<template v-if="sftpLog.items.length">
				<tr v-for="log in sftpLog.items" :key="log.id">
					<td v-for="(col,meta) in logTable">
						<template v-if="meta === 'アップロード'">
							<button
								type="button" class="btn btn-sm btn-outline-info"
								:popovertarget="meta + log.id"
							>
								{{log.アップロード.date}}
							</button>
							<div
								popover="auto" :id="meta + log.id"
								style="width: 80%; max-height: 80%; overflow-y: scroll;"
								v-html="formatUploadLogs(log)"
							>
							</div>
						</template>
						<template v-else-if="meta==='RMS連携' && log.転送モード==='img_upload'">
							<button
								@click="setLink(log)"
								:disabled="! log?.アップロード.data"
								type="button" class="btn btn-sm btn-secondary"
							>
								<span :class="{'spinner-border spinner-border-sm':linkIndex===log.id}"></span>
								商品ページと紐付ける
							</button>
						</template>
						<template v-else-if="meta==='RMS連携履歴' && log.転送モード==='img_upload'">
							<button
								@click="displayHistory(log)"
								:disabled="! log.RMS商品画像.変更後 || !Object.keys(log.RMS商品画像.変更後).length"
								type="button" class="btn btn-sm btn-outline-warning"
							>
								時を見る
							</button>
						</template>
						<template v-else>
							<span :class="col.td?.icon?.[log.転送モード] ?? col.td?.icon ?? ''"></span>
							{{col.td?.value?.[log.転送モード] ?? col.td?.value ?? log[meta] ?? ''}}
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