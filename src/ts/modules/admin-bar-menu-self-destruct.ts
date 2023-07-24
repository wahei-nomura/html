/**
 * 自爆ボタン
 */
jQuery(function($){
	const destruct_self_account = () => {
		if( !confirm('アカウントを削除します。続けますか？') ){
			return;
		}
		if( !confirm('本当に辞めるんですか？もう一度考えてください！') ){
			return;
		}
		if( !confirm('おつかれさまでした。ところで、本当に削除してもいいんですよね？') ){
			return;
		}
		if( !confirm('後悔はありませんか？まだ間に合いますよ！') ){
			return;
		}
		if( !confirm('これで最後です。本当にアカウントを削除しますか？\nよーく考えてからボタンをクリックしてくださいね！') ){
			return;
		}
		window.addEventListener('hashchange',function(){
			const params = {
				action : 'n2_user_destruct_self_account',
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
		})
	}
	$('#wp-admin-bar-destruct-self').on('click',destruct_self_account);
});