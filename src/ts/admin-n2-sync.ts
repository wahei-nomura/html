import Vue from 'vue/dist/vue.min'
/**
 * Vueで制御
*/
jQuery($=>{
	const n2 = window['n2'];
	const target = '#n2sync';
	const data = {
		item: {
			url: false,
			data: false,
			checked: {
				all: false,
				data: [],
			},
		},
		user: {
			url: false,
			data: false,
			checked: {
				all: false,
				data: [],
			},
		},
	};
	const created = async function(){
		// データの初期セット
		for ( const mode of ['item', 'user'] ) {
			this[mode].url = $(`#${mode}_url`).val();
			this.set_data(mode);
		}
	};
	const methods = {
		async set_data(mode = 'item') {
			if ( ! this[mode].url ) {
				this[mode].data = false;
				return;
			}
			this[mode].data = false;
			const params = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_get_spreadsheet_data_api',
					spreadsheetid: this[mode].url,
				}
			}
			this[mode].data  = await $.ajax(params);
			if ( this[mode].data ) {
				this[mode].data.header  = this.adjust_header(this[mode].data.header||[]);
				this[mode].checked.all  = false;
				this[mode].checked.data = [];
			} else {
				this[mode].data = 'スプレットシートのURLが間違っています。';
			}
			$('#n2sync, #n2sync-loading').addClass('is-active');
		},
		adjust_header(h){
			h = h.filter(v => !v.match(/^(id|ID)$/));
			return h;
		},
		update_disabled(mode = 'item'){
			return this[mode].checked.data.length ? false : true;
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