jQuery(function($){
	/**
	 * 事業者アカウントログイン時に右下にGoogleフォームのリンクを常時表示
	 */

	const n2 = window['n2'];
	const formLink = $('<a href="https://docs.google.com/forms/d/e/1FAIpQLScbze4H3puDboZ0zEZ_vfx7EzpiV0KJFeKFjFnGjymxqekw5Q/viewform" target="_blank"><span class="dashicons dashicons-warning"></span>システムの不具合は<br>こちら</a>');

	formLink.css({
		'position': 'fixed ',
		'bottom': '10px ',
		'right': '10px ',
		'z-index': '99999 ',
		'display':'flex ',
		'justify-content':'center ',
		'align-items':'center ',
		'flex-direction': 'column',
		'text-align':'center ',
		'color': '#fff ',
		'font-size': '13px',
		'background-color': '#b2292c ',
		'border-radius': '50% ',
		'box-shadow': '0 3px 5px rgba(0, 0, 0, 0.3)',
		'width': '130px ',
		'height': '90px ',
		'padding':  '2px ',
		'text-decoration':'none',
	});

	if(n2.current_user.roles[0] !== 'administrator') {
		$('#wpwrap').append(formLink);
	}
})