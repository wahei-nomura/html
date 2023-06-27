jQuery(function($){
	/**
	 * n2_active_flagがfalseの時に注意文を出す
	 */

	const n2 = window['n2'];
	const cautionBox = $('<a class="no_active_caution" onclick="this.remove()">N2未稼働 更新作業はN1で行って下さい</a>');
	if ( ! n2.settings['N2']['稼働中'] ) {
		$('#wpwrap').append(cautionBox);
	}
})