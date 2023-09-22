import Vue from 'vue/dist/vue.min';
jQuery( $ => {
	const n2 = window['n2'];
	const data = {
		history: false,
		item: false,
		checkout_revision_id: false,
		custom_field: [],
	};
	const created = function() {
		this.history = n2.history;
	};
	const methods = {
		async popover_revision_data(id){
			// リビジョンidをセット
			this.checkout_revision_id = id; 
			// カスタムフィールドを整頓
			let custom_field = [];
			for ( const v of Object.values( n2.custom_field ) ) {
				custom_field = [ ...custom_field, ...Object.keys(v) ];
			}
			custom_field = custom_field.filter(v => ! ['N1zip','商品画像'].includes(v));
			this.custom_field = custom_field;
			// リビジョン取得
			this.item = await $.ajax( `${n2.ajaxurl}?action=n2_checkout_revision_api&id=${id}` );
			// popover開
			$('#n2-history-chechout-revision').get(0).showPopover();
		},
		async checkout_revision() {
			const id = this.checkout_revision_id;
			if ( confirm(`ID: ${id}\n本当にこの時に戻りますか？\n※現在の設定が上書きされます。`) ) {
				new Audio('https://app.steamship.co.jp/ss-tool/assets/audio/toki_ed.mp3').play();
				// 時を戻す
				const res = await $.ajax( `${n2.ajaxurl}?action=n2_checkout_revision_api&id=${id}&update=1` );
				// popover閉
				$('#n2-history-chechout-revision').get(0).hidePopover();
				// historyの更新
				this.history = await $.ajax( location.href.replace('type=table','') );
			}
		},
	};
	$('#n2-history').ready(()=>{
		n2.vue = new Vue({
			el: '#n2-history',
			data,
			created,
			methods,
		});
	});
});