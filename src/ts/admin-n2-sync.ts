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
	};
	const created = async function(){
		this.item.url = $('#item_url').val();
		this.set_data();
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
		},
		adjust_header(h){
			h = h.filter(v => !v.match(/id|ID/));
			return h;
		},
		check_all(mode = 'item'){
			if ( this[mode].checked.all ) {
				if ( ! confirm('全項目を更新すると、N2で直接変更された項目に関しても全て上書きされます。それでもよろしいですか？\n\n例）N2のスプシダウンロードして、スプシで価格を編集している間に誰かが説明文を変更した場合は、せっかく編集した説明文も上書きされます！') ) {
					return;
				}
			}
			this[mode].checked.data = this[mode].checked.all ? this[mode].data.header : [];
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