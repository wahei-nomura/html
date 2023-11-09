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
			this.offset = 0
			this.items = await this.itemsGet();
			while( this.offset * 100 < this.itemCount ){
				this.items = [...this.items, ...await this.itemsGet()];
			}

			this.items.forEach(item => {
				console.log(this.parseManageNumber(item.manageNumber));
			});
			await this.addLog('get_items', JSON.stringify(this.items));
		},
		async itemsGet() {
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action',this.action);
			formData.append('call','search');
			formData.append('mode','json');
			formData.append('offset', String(this.offset));
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
		async addLog(title, postContent) {
			const formData = new FormData();
			formData.append('n2nonce',this.n2nonce);
			formData.append('action', 'n2_rakuten_sftp_insert_log_post');
			formData.append('title',title);
			formData.append('post_content',postContent);
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(res=>{
				console.log(res);
				return res.data;
			});
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
				action: 'n2_rms_items_api_ajax',
				itemCount: 0,
				offset: 0,
				items: [],
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
