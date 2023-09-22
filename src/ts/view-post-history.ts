import Vue from 'vue/dist/vue.min';
jQuery( $ => {
	const n2 = window['n2'];
	const data = {
		history: false,
	};
	const created = function() {
		this.history = n2.history;
	};
	const methods = {
		async turn_back_time(id) {
			if ( confirm(`ID: ${id}\n本当にこの時に戻りますか？\n※現在の設定が上書きされます。`) ) {
				new Audio('https://app.steamship.co.jp/ss-tool/assets/audio/toki_ed.mp3').play();
				const res = $.ajax( `${n2.ajaxurl}?action=n2_turn_back_time_api&id=${id}` );
				console.log(res)
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