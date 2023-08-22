import Vue from 'vue/dist/vue.min';
jQuery( $ => {
	const data = {
		unko: 'うんこ',
	};
	const created = function() {
		console.log(this.unko);
	};
	const methods = {
		say_unko(){
			alert(this.unko);
		},
	};
	$('#n2-history').ready(()=>{
		new Vue({
			el: '#n2-history',
			data,
			created,
			methods,
		});
	});
});