jQuery( $ => {
	$('.user-user-login-wrap .description').html('<p>ユーザー名を変更したい思いが強ければきっと変更できるでしょう。</p>');
	let count = 0;
	$('#user_login').css('pointer-events', 'none');
	$('.user-user-login-wrap').on('click',e =>{
		count++;
		if ( count == 10 ) {
			$('#user_login').removeAttr('disabled').css('pointer-events', 'auto');;
		}
	});
});