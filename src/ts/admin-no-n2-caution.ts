jQuery(function($){
	/**
	 * n2_active_flagがfalseの時に注意文を出す
	 */

	const n2 = window['n2'];
	const active_flag = n2.n2_active_flag;
	const cautionBox = $('<a class="no_active_caution" onclick="this.remove()">N2未稼働 更新作業はN1で行って下さい</a>');
	console.log(n2.n2_active_flag);
	if ( 'false' === active_flag ) {
		$('#wpwrap').append(cautionBox);
	}
})