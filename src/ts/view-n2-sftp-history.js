import Vue from 'vue/dist/vue.min';
Vue.config.devtools = true;
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
			// リビジョン取得
			this.item = await $.ajax( `${n2.ajaxurl}?action=n2_checkout_revision_api&id=${id}` );
			// カスタムフィールドを整頓
			this.custom_field = Object.keys(this.item);
			// popover開
			$('#n2-history-chechout-revision').get(0).showPopover();
		},
		async checkout_revision() {
			const id = this.checkout_revision_id;
			if ( confirm(`ID: ${id}\n本当にこの時に戻りますか？\n※現在の設定が上書きされます。`) ) {
				new Audio('https://app.steamship.co.jp/ss-tool/assets/audio/toki_ed.mp3').play();
				// 時を戻す
				const res = await $.ajax(`${n2.ajaxurl}?action=n2_rakuten_sftp_checkout_revision&post_id=${id}&update=1`);
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