jQuery(function($){
	// 管理者以外のユーザーは管理者を追加できない
	if ( ! window['n2'].current_user.roles.includes('administrator') ) {
		$("option[value='administrator'").remove();      // 管理者
	}
})