/**
 * 自爆ボタン
 */
import $ from 'jquery';

(function(){
	const destruct_self_account = () => {
		if( !confirm('アカウントを削除します。続けますか？') ){
			return;
		}
		const params = {
			action : 'n2_user_destruct',
		}
		const urlSearchParam =  new URLSearchParams(params).toString();
		const data = {
			id : window['n2'].current_user.ID,
			n2nonce: location.hash.replace('#',''),
		}
		$.ajax({
			url: window['n2'].ajaxurl + '?' + urlSearchParam,
			type: 'POST',
			data: data,
		}).then(res=>{
			alert(res);
			location.reload();
		})
	}
	$('#wp-admin-bar-destruct-self').on('click',destruct_self_account);
});