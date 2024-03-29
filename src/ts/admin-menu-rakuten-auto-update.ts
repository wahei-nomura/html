import Vue from 'vue/dist/vue.min';
import axios from 'axios';
Vue.config.devtools = true;

jQuery( async function($){
	// 接続確認
	const connect = await axios.get(
		window['n2'].ajaxurl,
		{
			params: {
				action: 'n2_rms_cabinet_api_ajax',
				call: 'connect',
				mode: 'json',
			},
		},
	)
	if ( connect.data !== true ) {
		document.getElementById('ss-rakuten-auto-update').innerText = 'RMS CABINETに接続できませんでした。';
		return;
	}

	const methods = {
		showLogContent(postContent){
			this.showContents = postContent
		},
		async update(){
			const confirmMessage = [
				'以下の内容で更新を開始しますか？',
				'(実行状況はconsoleまたはN2のpostで確認してください)',
				'・CABINET画像移動',
				'・SP商品説明文内の画像リンク修正',
				'・PC商品説明文内の画像リンク修正',
				'・PC販売説明文内の画像リンク修正',
			];
			if( !confirm(confirmMessage.join('\n'))){
				return;
			}
			this.folders = await this.foldersGet().then(res=>res.data);

			await this.updateByStockOut(false);
			await this.updateByStockOut(true);
		},
		async updateByStockOut(isStockout){
			this.items = await this.itemsGet(isStockout);
			// 対象の返礼品(100~)だけに絞る
			this.items = this.items.filter(item=>{
				return /^([0-9]{0,2}[a-z]{2,4})([0-9]{2,3})/.test(item.manageNumber) && this.parseManageNumber(item.manageNumber).num >= 100;
			});
			
			// 対象返礼品
			if( ! this.items.length ) {
				this.addLog('not found items', JSON.stringify(this.items));
				alert(( isStockout ? '在庫なし':'在庫あり' ) + ':更新不要です！');
				return;
			} else {
				this.addLog('get_target_items', JSON.stringify(this.items));
			}

			// 商品数をセット
			this.counts.items[isStockout] = this.items.length;

			// 初期化
			this.counts.info.patch[isStockout] = 0;
			this.counts.info.stay[isStockout] = 0;
			this.counts.img[isStockout] = 0;
			this.counts.error[isStockout] = 0;
			
			// 返礼品情報
			this.items.forEach(async item => {
				try{
					// 実行タイミングをズラす
					this.randomWait(1000);
					let body = {};
					const post_id = await this.addLog(`${item.manageNumber}: search item patch`, JSON.stringify(item) );
					const image_path = this.make_image_path(item.manageNumber);

					// CABINET移動
					this.counts.img[isStockout] += await this.imageMove(item.manageNumber,image_path,post_id, isStockout)
					let img_patch_log = [];

					// 商品画像
					const images = item.images.map(img=>{
						const replace = this.replace_path( img.location, image_path );
						if ( replace ) {
							img_patch_log = [...img_patch_log, replace];
							img.location = replace;
						}
						return img;
					});
					if ( img_patch_log.length ) {
						body = {images};
					}

					// PC販売説明文
					const salesDescription = this.replace_path( item.salesDescription, image_path );
					if ( salesDescription ) {
						body = {...body,salesDescription};
					}

					// スマホ商品説明文
					const productDescription = {};
					const replaceSPproductDescription = this.replace_path( item.productDescription.sp, image_path );
					if ( replaceSPproductDescription ) {
						productDescription['sp'] = replaceSPproductDescription;
						body = {...body,productDescription};
					}

					// PC商品説明文
					const replacePCproductDescription = this.replace_path( item.productDescription.pc, image_path );
					if ( replacePCproductDescription ) {
						productDescription['pc'] = replacePCproductDescription;
						body = {...body,productDescription};
					}

					if ( Object.keys(body).length ) {
						await this.itemPatch(item.manageNumber, JSON.stringify(body)).then(()=>{
							this.addLog(`${item.manageNumber}: done item patch`, JSON.stringify(body), post_id);
						});
						this.counts.info.patch[isStockout] += 1;
					} else {
						this.counts.info.stay[isStockout] += 1;
						this.addLog(`${item.manageNumber}: unecessary item patch`, '', post_id);
					}


				}catch(err){
					this.addLog(`Error: ${err.message}`,JSON.stringify(item));
					this.counts.error[isStockout] += 1;
				} finally {
					// 終了時
					if ( this.counts.items[isStockout] === this.counts.info.patch[isStockout] + this.counts.info.stay[isStockout] + this.counts.error[isStockout] ) {
						const alertMessage = [
							( isStockout ? '在庫なし':'在庫あり' ) + ':更新終了！',
							`置換: ${this.counts.info.patch[isStockout]}件`,
							`移動: ${this.counts.img[isStockout]}枚`,
							`エラー: ${this.counts.error[isStockout]}件`,
						];
						this.addLog(`finish: isStockOut: ${isStockout}`, JSON.stringify({
							item_patch: this.counts.info.patch[isStockout],
							error: this.counts.error[isStockout],
							img_move:this.counts.img[isStockout],
						}));
						alert(alertMessage.join('\n'));
					}
				}
			});
		},
		replace_path( target, path:{old:string,new:string} ) {
			if( ! target ) return false;
			if ( target.indexOf( path.new ) === -1 && target.indexOf( path.old ) !== -1 ) {
				return target.replaceAll(path.old, path.new);
			};
		},

		make_image_path( manageNumber:string, abs= false ){
			const {sku,num} = this.parseManageNumber( manageNumber );
			const path =  abs
				? `${this.imgDir}/${sku}`
				: `/item/${sku}`;
			return {
				new: `${path}/${num[0]}/${manageNumber}`,
				old: `${path}/${manageNumber}`,
			}
		},
		async foldersGet(){
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.cabinet);
			formData.append('call','folders_get');
			formData.append('mode','json');
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			);
		},
		async folderInsert(manageNumber, upperFolderId){
			const {num} = this.parseManageNumber( manageNumber );
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.cabinet);
			formData.append('call','folder_insert');
			formData.append('folderName',num[0]);
			formData.append('directoryName',num[0]);
			formData.append('upperFolderId', upperFolderId);
			formData.append('call','folder_insert');
			formData.append('mode','json');
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			);
		},
		async imageSearch( manageNumber:string ){
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.cabinet);
			formData.append('call','files_search');
			formData.append('mode','json');
			formData.append('keywords[]', manageNumber);
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			);
		},
		async imageMove(manageNumber:string, path, post_id, isStockout:string){
			const oldPath = path.old.replace(`/${manageNumber}`,'');
			const newPath = path.new.replace(`/${manageNumber}`,'');
			return await this.imageSearch(manageNumber).then( async res=>{
				// 移動前のファイルに絞る
				const images = res.data[manageNumber].filter(image=>{
					return image.FolderPath === oldPath
				});
				if ( ! images.length ) {
					throw new Error('not found necessary moved images')
				}
				const folderID = await this.getFolderId(manageNumber, path, post_id)
				return {folderID,images};
			}).then( ({folderID,images})=>{
				
				const formData = new FormData();
				images.map( image => {
					formData.append('fileIds[]',image.FileId);
				});
				formData.append('n2nonce',this.n2nonce);
				formData.append('action',this.actions.rms.cabinet);
				if (this.isMinashiba) formData.append('call','files_copy');
				else formData.append('call','files_move');
				formData.append('overwrite','false');
				formData.append('targetFolderId',folderID);

				formData.append('currentFolderId',images[0].FolderId);
				formData.append('mode','json');
				return axios.post(
					window['n2'].ajaxurl,
					formData,
				).then(res=>{
					return {res:res.data,images}
				})
			}).then((res)=>{
				const errorLog = res.res.insert?.filter(l=>l.status_code !== 200)?.length || 0
					+ res.res.delete?.filter(l=>l.status_code !== 200)?.length || 0;
				this.addLog(`${manageNumber}: cabinet logs`,JSON.stringify(res),post_id);
				return res.images.length - errorLog;
			}).catch(err=>{
				this.addLog(`${manageNumber}: ${err.message}`,"",post_id);
				return 0;
			});
		},
		async getFolderId(manageNumber,path, post_id ){
			const oldPath = path.old.replace(`/${manageNumber}`,'');
			const newPath = path.new.replace(`/${manageNumber}`,'');
			// folderID取得
			let folderID:string;
			const newFolder = this.folders.filter(f=>f.FolderPath === newPath );
			if ( ! newFolder.length ) {
				const oldFolder = this.folders.filter(f=>f.FolderPath === oldPath );
				if ( ! oldFolder.length ){
					throw new Error(`${oldPath}: Failed to find FolderID`)
				}
				const res = await this.folderInsert(manageNumber,oldFolder[0].FolderId).then(res=>res.data.cabinetFolderInsertResult);
				if ( res.resultCode ) {
					await this.randomWait(1000);
					// 最新の情報に更新
					this.folders = await this.foldersGet().then(res=>res.data);
					return await this.getFolderId( manageNumber, path, post_id);
				}
				this.addLog( `${manageNumber}: insert folder`, JSON.stringify(res),post_id );
				folderID = res.FolderId;
			}else {
				folderID = newFolder[0].FolderId;
			}
			return folderID;
		},
		async itemsGet(isStockout) {
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.items);
			formData.append('call','search');
			formData.append('mode','json');
			formData.append('params[hits]', "-1");
			formData.append('params[isItemStockout]', isStockout);
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(res=>{
				this.offset += 1;
				this.itemCount = res.data.numFound ?? 0;
				return res.data.results.map(v => v.item);
			});
		},
		async itemPatch( manageNumber:string, body:string ){
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.items);
			formData.append('manageNumber', manageNumber);
			formData.append('body', body);
			formData.append('call','items_patch');
			formData.append('mode','json');
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(()=>manageNumber);
		},
		async addLog(title, postContent, parent_id = "0" ) {
			console.log(title,postContent)
			this.logs.push({title,postContent})
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action', this.actions.log);
			formData.append('post_id', parent_id);
			formData.append('title',title);
			formData.append('post_content',postContent);
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(res=> res.data.id);
		},
		parseManageNumber ( manageNumber ) {
			const match = manageNumber.match(/([0-9]{0,2}[a-z]{2,4})([0-9]{2,3})/);
			return {
				sku: match[1],
				num: match[2],
			};
		},
		async randomWait(ms:number) {
			return new Promise(resolve=>{
				setTimeout(() => {
					resolve(0);
				}, Math.floor(Math.random() * ( 9 * ms ) + ms ));
			})
		},
	};

	window['n2'].vue = new Vue({
		el: '#ss-rakuten-auto-update',
		data(){
			return {
				n2nonce: null,
				imgDir: null,
				actions: {
					rms: {
						cabinet:'n2_rms_cabinet_api_ajax',
						items: 'n2_rms_items_api_ajax',
					},
					log: 'n2_rakuten_sftp_insert_cabi_renho_log',
				},
				itemCount: 0,
				items: [],
				folders: [],
				counts:{
					items: {},
					error: {},
					info:{
						patch: {},
						stay: {},
					},
					img:{},
				},
				logs: [],
				showContents: null,
				isMinashiba: false,
			};
		},
		created(){
			this.n2nonce = $('input[name="n2nonce"]').val();
			this.imgDir = $('input[name="imgDir"]').val();
			this.isMinashiba = location.href.includes('f422142-minamishimabara');
		},
		methods,
		template: `
		<div id="ss-rakuten-auto-update" class="container-fluid">
			<div
				popover="auto" id="popover-cabi-renho"
				style="width: 80%; max-height: 80%; overflow-y: scroll;"
				v-html="showContents" v-show="showContents"
			>
			</div>
			<button @click=update>更新する</button>
			<table class="table">
				<tbody>
					<tr v-for="l in logs">
						<td>
							<button
								type="button" class="btn btn-sm btn-outline-info"
								popovertarget="popover-cabi-renho"
								popovertargetaction="show"
								@click="showLogContent(l.postContent)"
								:class="{disabled:!l.postContent}"
							>
								{{l.title}}
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		`
	});
})
