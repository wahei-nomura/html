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
		setLinkIndex(index){
			this.linkIndex = index;
		},
		async getRmsItemImages(item){
			const rmsItemImages = {};

			// RMSから返礼品情報を取得
			const requests = Object.keys(item.アップロード).map(manageNumber=>{
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
			let errorMessages = [];
			await Promise.all(requests).then(responses=>{
				responses.forEach(res=>{
					const errors = res.data?.errors;
					if ( errors?.length ) {
						errors.map(err=>{
							errorMessages = [ ...errorMessages, err.message ];
						})
					} else {
						rmsItemImages[res.data.manageNumber] = res.data.images.map(image=>image.location);
					}
				})
			});

			if ( errorMessages.length && ! confirm( [
				'【返礼品情報が取得できませんでした】',
				...errorMessages,
				'続けますか？'
			].join('\n') ) ) {
				return [];
			}
			return rmsItemImages;
		},
		async linkImage2RMS (item){
			// RMS 更新用
			const updateRmsItemRequests = [];
			// N2 更新用
			const update_item = structuredClone(item);
			const rms_images = await this.getRmsItemImages(item);
			if ( ! rms_images.length ) {
				alert('商品画像を取得できませんでした');
				this.linkIndex = null;
				return;
			}

			// 更新の必要性を確認
			const updateItems = [];
			Object.keys(rms_images).forEach(manageNumber=>{
				// 明らかに配列の長さが違う場合は必要
				if(update_item.アップロード[manageNumber].length !== rms_images[manageNumber].length) {
					updateItems.push(manageNumber);
					return;
				}
				// diffの精査
				const mergeArr = [...update_item.アップロード[manageNumber],...rms_images[manageNumber]];
				const diff = update_item.アップロード[manageNumber].filter( (i:number) => mergeArr.indexOf(i) === -1 );
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

				const add = update_item.アップロード[manageNumber].filter( (i:number) => rms_images[manageNumber].indexOf(i) === -1 );
				if (add.length){
					confirmMessage.push('【追加】' + manageNumber);
					confirmMessage = [...confirmMessage,...add];
				}
				const remove  = rms_images[manageNumber].filter( (i:number) => update_item.アップロード[manageNumber].indexOf(i) === -1 );
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
				const images = item.アップロード[manageNumber].map(path=>{
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
				formData.append('action', this.action);
				formData.append('judge', 'update_post' );
				formData.append('post_id', update_item.id );
				// id削除
				delete update_item.id;
				// 画像用revision追加
				update_item.RMS商品画像.変更前 = rms_images;
				update_item.RMS商品画像.変更後 = update_item.アップロード;
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
		async displayHistory(item){
			const param   = new URLSearchParams({
				action: 'n2_post_history_api',
				post_id: item.id,
				type: 'table',
				post_type: 'n2_sftp',
			}).toString();
			window.open(
				`${window['n2'].ajaxurl}?${param}`,
				'_blank'
			)
		},
		formatUploadLogs(data){
			// フォルダ作成ログは除外する
			return data.filter(d=>! d.includes('cabinet/images')).join('<br>');
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
				<tr v-for="item in sftpLog.items" :key="item.id">
					<td v-for="(col,meta) in logTable">
						<template v-if="meta === 'アップロード'">
							<button
								type="button" class="btn btn-sm btn-outline-info"
								:popovertarget="meta + item.id"
							>
								{{item.アップロード日時}}
							</button>
							<div
								popover="auto" :id="meta + item.id"
								style="width: 80%; max-height: 80%; overflow-y: scroll;"
								v-html="formatUploadLogs(item.アップロードログ)"
							>
							</div>
						</template>
						<template v-else-if="meta==='RMS連携' && item.転送モード==='img_upload'">
							<button
								@click="linkImage2RMS(item), setLinkIndex(item.id)"
								:disabled="! item?.アップロード"
								type="button" class="btn btn-sm btn-secondary"
							>
								<span :class="{'spinner-border spinner-border-sm':linkIndex===item.id}"></span>
								商品ページと紐付ける
							</button>
						</template>
						<template v-else-if="meta==='RMS連携履歴' && item.転送モード==='img_upload'">
							<button
								@click="displayHistory(item)"
								:disabled="! item.RMS商品画像.変更後 || !Object.keys(item.RMS商品画像.変更後).length"
								type="button" class="btn btn-sm btn-outline-warning"
							>
								時を見る
							</button>
						</template>
						<template v-else>
							<span :class="col.td?.icon?.[item.転送モード] ?? col.td?.icon ?? ''"></span>
							{{col.td?.value?.[item.転送モード] ?? col.td?.value ?? item[meta] ?? ''}}
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