jQuery(function($){
	// 管理者以外は管理者を削除できない
	if( ! window['n2'].current_user.roles.includes('administrator') ){
		// 一括編集が削除しか項目が無いのでセレクトボックスごと削除
		$("div[class='alignleft actions bulkactions'").remove();

		// 一括権限変更から管理者と権限剥奪を除去
		$("option[value='administrator']").remove();
		$("option[value='none']").remove();
		
		// 一括操作対策としてチェックボックスの除去と削除ボタンの除去
		$('td[data-colname="権限グループ"]').each(function() {
			if ($(this).text() === '管理者') {
				$(this).closest('tr').find('input[type="checkbox"]').remove();
				$(this).closest('tr').find('span[class="remove"]').remove();
			}
		});
	}
})