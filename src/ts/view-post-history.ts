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
		data_shaping( data ) {
			return data;
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