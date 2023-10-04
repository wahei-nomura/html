import Vue from 'vue/dist/vue.min'
/**
 * Vueで制御
*/
jQuery($=>{
	const n2 = window['n2'];
	const target = '#n2sync';
	const data = {
		item_url: false,
		item_data: false,
		user_url: false,
		user_data: false,
	};
	const created = async function(){
		this.item_url = $('#item_url').val();
		this.set_item_data();
	};
	const methods = {
		async set_item_data(mode = 'item') {
			const params = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_get_spreadsheet_data_api',
					spreadsheetid: this[`${mode}_url`],
				}
			}
			this[`${mode}_data`]   = await $.ajax(params);
			this[`${mode}_data`].header = this.adjust_header(this[`${mode}_data`].header||[]);
		},
		adjust_header(h){
			h.unshift('全て');
			h = h.filter(v => !v.match(/id|ID/));
			return h;
		}
	};
	// メタボックスが生成されてから
	$('#n2sync').ready(()=>{
		n2.vue = new Vue({
			el: '#n2sync',
			data,
			created,
			methods,
		});
	});
})