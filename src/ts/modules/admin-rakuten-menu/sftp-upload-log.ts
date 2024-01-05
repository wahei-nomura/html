import axios from 'axios';
import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';


class UploadPoppoverDate {
	success   = [];
	error     = [];
	diff      = null;
	rms       = null;
	rmsOrigin = null;
	unique    = null;
	id        = null;
}

export default Vue.extend({
	async created(){
		await this.updateSFTPLog();
	},
	data() {
		return {
			linkIndex: null,
			linkData : new UploadPoppoverDate(),
			logTable: {
				転送モード: {
					th: {
						icon: 'dashicons dashicons-cloud-saved',
					},
					td:{
						icon: {
							img_upload: 'dashicons dashicons-format-gallery',
							csv_upload: 'dashicons dashicons-media-spreadsheet',
							upload: 'dashicons dashicons-upload',
							download: 'dashicons dashicons-download',
							delete: 'dashicons dashicons-trash',
							mkdir: 'dashicons dashicons-plus',
						},
						value: {
							img_upload: ' キャビアップ',
							csv_upload: 'CSV転送',
							upload: 'アップロード',
							download: 'ダウンロード',
							delete: '削除',
							mkdir: 'フォルダ作成',
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
			popover: {
				'アップロード': {
					display: '',
				}
			},
			settings:{
				showLog:{
					more:false,
					limit: 20,
				}
			},
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
		viewLogMore(){
			this.settings.showLog.more = true;
		},
		async setLink(log){
			this.linkIndex = log.ID;
			await this.linkImage2RMS(log);
			this.linkIndex = null;
		},
		async getRmsItemImages(log){
			// RMSから返礼品情報を取得
			const requests = Object.keys(log.アップロード.data).map(manageNumber=>{
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_items_api_ajax');
				formData.append('call', 'items_get');
				formData.append('mode', 'json');
				return axios.post(
					window['n2'].ajaxurl,
					formData,
				);
			})
			return await Promise.all(requests).then(responses=>{
				return responses.reduce((obj,res)=>{
					if(res.data.manageNumber) {
						obj[res.data.manageNumber] = res.data?.images?.map(image=>image.location) ?? [];
					}
					if(res.data.errors) {
						console.error(res.data.errors);
						
						alert(['商品画像が取得できませんでした',...res.data.errors.map(error=>error.message)].join('\n'))
					}
					return obj;
				},{});
			});
		},
		diffImageItems (log, images) {
			console.log(log);
			
			const diffItemNumbers = Object.keys(images).map(manageNumber=>{
				// 明らかに配列の長さが違う場合は必要
				if(log.アップロード.data[manageNumber]?.length !== images[manageNumber]?.length) {
					return manageNumber;
				}
				// diffの精査
				const mergeArr = [...log.アップロード.data[manageNumber],...images[manageNumber]];
				const diff = log.アップロード.data[manageNumber]?.filter( (i:number) => mergeArr.indexOf(i) === -1 ) ?? [];
				if (diff.length) return manageNumber;
				return '';
			}).filter(x=>x);

			
			return diffItemNumbers.reduce((obj,manageNumber)=>{
				obj[manageNumber] = {};
				const add = log.アップロード.data[manageNumber]?.filter( (i:number) => images[manageNumber].indexOf(i) === -1 ) ?? [];
				if (add.length){
					obj[manageNumber]['add'] = add;
				}
				const remove  = images[manageNumber]?.filter( (i:number) => log.アップロード.data[manageNumber].indexOf(i) === -1 ) ?? [];
				if (remove.length){
					obj[manageNumber]['remove'] = remove;
				}
				return obj;
			},{});
		},
		async linkImage2RMS ( log ) {
			// N2 更新用
			const updateLog = structuredClone(log.post_content);
			if ( ! this.linkData.id || this.linkData.id !== log.ID ) {
				this.popover.アップロード.display = '情報取得中...';
				if ( ! await this.setLinkData(log) ) {
					this.popover.アップロード.display ='取得に失敗しました'
					return
				}
				this.linkData.id = log.ID;
			}
			
			
			if(! Object.keys(this.linkData.diff).length){
				alert('更新不要です');
				return;
			};

			// 確認用メッセージ作成
			

			// RMS更新用
			const itemPatchRequests = Object.keys(this.linkData.diff).map(manageNumber => {
				const formData = new FormData();
				formData.append('manageNumber', manageNumber);
				formData.append('n2nonce', this.n2nonce);
				formData.append('action', 'n2_rms_items_api_ajax');
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
				formData.append('post_id', log.ID );
				// id削除
				delete updateLog.display;
				// 画像用revision追加
				updateLog.RMS商品画像.変更前 = this.linkData.rmsOrigin;
				updateLog.RMS商品画像.変更後 = updateLog.アップロード.data;
				formData.append('post_content', JSON.stringify(updateLog));
				await axios.post(
					window['n2'].ajaxurl,
					formData,
				);
				// 最新情報に更新
				await this.updateSFTPLog()
				this.linkData.id = null;
				alert('更新完了しました！')
			})
		},
		async displayHistory(log){
			const param   = new URLSearchParams({
				action: 'n2_post_history_api',
				post_id: log.ID,
				type: 'table',
				post_type: 'n2_sftp',
			}).toString();
			window.open(
				`${window['n2'].ajaxurl}?${param}`,
				'_blank'
			)
		},
		async setLinkData(log){
			this.linkData = new UploadPoppoverDate();
			// status(転送成功|転送失敗)用
			const logToObj = (arr,status) => {
				arr = arr.filter(d=> d.status.includes(status));
				if (!arr.length) return {};
				return arr.reduce((obj,d)=>{
					const manageNumber = d.context.match(/([0-4]{1}[0-9]{1}[a-z]{4}[0-9]{3}|[a-z]{2,4}[0-9]{2,3})/)[0];
					if ( ! obj[manageNumber] ) obj[manageNumber] = [];
					obj[manageNumber] = [...obj[manageNumber],d];
					return obj;
				},{});
			};
			// フォルダ作成ログは除外する
			this.linkData.error = logToObj(log.post_content.アップロード.log,'失敗');
			this.linkData.success = logToObj(log.post_content.アップロード.log,'成功');
			
			const succesItems = Object.keys(this.linkData.success);
			// 改変用にディープコピー
			const updateLog = structuredClone(log.post_content);
			this.linkData.rmsOrigin = await this.getRmsItemImages(updateLog);

			if( ! Object.keys(this.linkData.rmsOrigin).length ) {
				return false;
			}

			// 更新の必要性を確認
			const diffImages = this.diffImageItems(updateLog,this.linkData.rmsOrigin);
			this.linkData.diff = Object.keys(diffImages).reduce((obj,manageNumber)=>{
				obj[manageNumber] = {};
				if (diffImages[manageNumber].add) obj[manageNumber].add = diffImages[manageNumber].add.map( image => {
					const arr = image.split('/');
					return arr[arr.length-1];
				});
				if (diffImages[manageNumber].remove) obj[manageNumber].remove = diffImages[manageNumber].remove.map( image => {
					const arr = image.split('/');
					return arr[arr.length-1];
				});
				return obj
			},{});
			this.linkData.rms = Object.keys(this.linkData.rmsOrigin).reduce((obj,manageNumber)=>{
				obj[manageNumber] = this.linkData.rmsOrigin[manageNumber].map( image => {
					const arr = image.split('/');
					return arr[arr.length-1];
				});
				return obj
			},{});
			this.linkData.unique = succesItems.reduce((obj,manageNumber) => {
				obj[manageNumber] = Array.from(
					new Set([
						...(this.linkData.success?.[manageNumber]?.map((x:any)=>x.context) ?? []),
						...(this.linkData.error?.[manageNumber]?.map((x:any)=>x.context) ?? []),
						...(this.linkData.rms?.[manageNumber] ?? []),
						...(this.linkData.diff?.[manageNumber]?.add ?? []),
						...(this.linkData.diff?.[manageNumber]?.remove ?? []),
					]).values()
				).sort();
				return obj;
			},{});
			return true;
		},
		async formatUploadLogs(log){
			if (log.post_content.転送モード !== 'img_upload') {
				this.popover.アップロード.display = log.post_content.アップロード.log.map((l)=>{
					return `${l.status} ${l.context}`;
				}).join('<br>');
				this.linkData.id = log.id;
				return;
			}

			//  キャビアップのみ別処理
			if ( log.post_content.RMS商品画像.変更後 ) {
				this.popover.アップロード.display = Object.keys(log.post_content.RMS商品画像.変更後).map(manageNumber=>{
					const unique = Array.from(
						new Set([
							...(log.post_content.RMS商品画像.変更後[manageNumber]),
							...(log.post_content.RMS商品画像.変更前[manageNumber] ?? []),
						]).values()
					).sort();
					
					return unique.map(image => {
						const row = [];
						const imagePathArr = image.split('/');
						const imageName = imagePathArr[imagePathArr.length -1 ];

						if ( log.post_content.RMS商品画像.変更前[manageNumber] && ! log.post_content.RMS商品画像.変更前[manageNumber].includes(image) ) row.push( '追加成功' );
						if ( log.post_content.RMS商品画像.変更後[manageNumber] && ! log.post_content.RMS商品画像.変更後[manageNumber].includes(image) ) row.push( '解除成功' );

						const preLog = log.post_content.アップロード.log.filter(x=>x.context.includes(imageName));
						if ( !row.length && preLog.length ) row.push( preLog[0].status );
						row.push(imageName)
						
						return row.join(' ');
					}).join('<br>');
				}).join('<br>');
				this.linkData.id = log.ID;
				return;
			}

			// 更新
			if ( ! ( this.linkData.id && this.linkData.id == log.ID ) ) {
				this.popover.アップロード.display = '情報取得中...';
				if ( ! await this.setLinkData(log) ) {
					return this.popover.アップロード.display ='取得に失敗しました'
				}
				this.linkData.id = log.ID;
			}
			this.popover.アップロード.display = Object.keys(this.linkData.unique).map(manageNumber => {
				return this.linkData.unique[manageNumber].toSorted().map(image=>{
					let row = [];
					const success = this.linkData.success?.[manageNumber]?.filter(x=>x.context === image ) ?? [];
					if(success.length) row = [...row, success[0].status,success[0].context];
					const error = this.linkData.error?.[manageNumber]?.filter(x=>x.context === image ) ?? [];
					if(error.length) row = [...row, error[0].status,error[0].context];
					if(!row.length) row.push(image)
					if(this.linkData.diff[manageNumber]?.add?.includes(image) ) row.push('<span class="ms-1 text-primary">追加</span>');
					if(this.linkData.diff[manageNumber]?.remove?.includes(image)) row.push('<span class="ms-1 text-danger">解除</span>');
					return row.join(' ');
				}).join('<br>')
			}).join('<br>');
		},
	},
	template:`
	<div>
		<div
			popover="auto" id="popover-upload"
			style="width: 80%; max-height: 80%; overflow-y: scroll;"
			v-html="popover.アップロード.display"
			:class="{loading:!linkData.id}"
		>
		</div>
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
					<tr v-for="log in sftpLog.items">
						<td v-for="(col,meta) in logTable">
							<template v-if="meta === 'アップロード'">
								<button
									type="button" class="btn btn-sm btn-outline-info"
									popovertarget="popover-upload"
									popovertargetaction="show"
									@click="formatUploadLogs(log)"
								>
									{{log.post_content.アップロード.date || log.post_date}}
								</button>
							</template>
							<template v-else-if="meta==='RMS連携' && log.post_content.転送モード==='img_upload'">
								<button
									@click="setLink(log)"
									:disabled="! log.post_content?.アップロード.data || log.post_content.RMS商品画像.変更後"
									type="button" class="btn btn-sm btn-secondary"
								>
									<span :class="{'spinner-border spinner-border-sm':linkIndex===log.ID}"></span>
									商品ページ画像への追加・解除
								</button>
							</template>
							<template v-else-if="meta==='RMS連携履歴' && log.post_content.転送モード==='img_upload'">
								<button
									@click="displayHistory(log)"
									:disabled="! log.post_content.RMS商品画像.変更後"
									type="button" class="btn btn-sm btn-outline-warning"
								>
									時を見る
								</button>
							</template>
							<template v-else>
								<span :class="col.td?.icon?.[log.post_content.転送モード] ?? col.td?.icon ?? ''"></span>
								{{col.td?.value?.[log.post_content.転送モード] ?? col.td?.value ?? log[meta] ?? ''}}
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
		<div id="n2-sftp-upload-log-view-more" class="btn btn-sm btn-info position-fixed bottom-0 end-0"
			@click="viewLogMore" v-show="!settings.showLog.more"
		>
			もっと見る
		</div>
	</div>
	`,
});