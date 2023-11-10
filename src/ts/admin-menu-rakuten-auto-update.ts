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
		async update(){

			this.folders = await this.foldersGet().then(res=>res.data);
			this.offset = 0
			this.items = await this.itemsGet();
			// 対象の返礼品(100~)だけに絞る
			this.items = this.items.filter(item=>{
				return this.parseManageNumber(item.manageNumber).num >= 100;
			});

			// 対象返礼品
			if( ! this.items.length ) {
				this.addLog('not found items', JSON.stringify(this.items));
				return;
			} else {
				this.addLog('get_target_items', JSON.stringify(this.items));
			}

			
			
			// 返礼品情報
			this.items.forEach(async item => {
				let body = {};
				const post_id = await this.addLog(`${item.manageNumber}: search item patch`, JSON.stringify(item) );
				const image_path = this.make_image_path(item.manageNumber);

				// CABINET移動
				await this.imageMove(item.manageNumber,image_path,post_id)
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
					console.log(body);
					await this.itemPatch(item.manageNumber, JSON.stringify(body)).then(()=>{
						this.addLog(`${item.manageNumber}: done item patch`, JSON.stringify(body), post_id);
					});
				} else {
					this.addLog(`${item.manageNumber}: unecessary item patch`, '', post_id);
				}
			});
		},
		replace_path( target, path:{old:string,new:string} ) {
			if ( target.indexOf( path.new ) === -1 && target.indexOf( path.old ) !== -1 ) {
				return target.replaceAll(path.old, path.new);
			};
			return false;
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
		async imageMove(manageNumber:string, path, post_id){
			const oldPath = path.old.replace(`/${manageNumber}`,'');
			const newPath = path.new.replace(`/${manageNumber}`,'');
			await this.imageSearch(manageNumber).then( async res=>{
				// 移動前のファイルに絞る
				const images = res.data[manageNumber].filter(image=>{
					return image.FolderPath === oldPath
				});
				if ( ! images.length ) {
					throw new Error('not found necessary moved images')
				}

				// folderID取得
				let folderID:string;
				const newFolder = this.folders.filter(f=>f.FolderPath === newPath );
				if ( ! newFolder.length ) {
					const oldFolder = this.folders.filter(f=>f.FolderPath === oldPath );
					if ( ! oldFolder.length ){
						console.log(oldFolder);
						
						throw new Error(`${oldPath}: Failed to find FolderID`)
					}
					const res = await this.folderInsert(manageNumber,oldFolder[0].FolderId).then(res=>res.data.cabinetFolderInsertResult);
					this.addLog( `${manageNumber}: insert folder`, JSON.stringify(res),post_id );
					folderID = res.FolderId;
				}else {
					folderID = newFolder[0].FolderId;
				}
				return {folderID,images};
			}).then( ({folderID,images})=>{
				console.log(folderID,images);
				
				const formData = new FormData();
				images.map( image => {
					formData.append('fileIds[]',image.FileId);
				});
				formData.append('n2nonce',this.n2nonce);
				formData.append('action',this.actions.rms.cabinet);
				formData.append('call','files_move');
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
				this.addLog(`${manageNumber}: moved cabinet files`,JSON.stringify(res),post_id);
			})
			.catch(err=>{
				this.addLog(`${manageNumber}: ${err.message}`,"",post_id);
			});
		},
		async itemsGet() {
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.actions.rms.items);
			formData.append('call','search');
			formData.append('mode','json');
			formData.append('hits', '-1');
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(res=>{
				console.log(res);
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
					log: 'n2_rakuten_sftp_insert_log_post',
				},
				itemCount: 0,
				offset: 0,
				items: [],
				folders: [],
			};
		},
		created(){
			this.n2nonce = $('input[name="n2nonce"]').val();
			this.imgDir = $('input[name="imgDir"]').val();
		},
		methods,
		template: `
		<div id="ss-rakuten-auto-update" class="container-fluid">
			<button @click=update>更新する</button>
		</div>
		`
	});
})
