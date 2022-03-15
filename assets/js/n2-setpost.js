jQuery(function($){
	// クラスにテーマ名をprefixつける
	const prefix = 'neo-neng';

	// 返礼品編集画面
	if(location.href.match(/(post|post-new)\.php/)){
		$('form').on('submit',(e)=>{
			
			if($(`.${prefix}-hissu`).val() === ''){
				$(`.${prefix}-hissu`).css('background-color','pink'); 
				$(`.${prefix}-hissu`).before($('<p style="color:red;">※必須項目です</p>'))
				$('#publish').prop('disabled', true);
				e.preventDefault();

				alert('入力内容をご確認ください。')
				return false;
			}
		})
	}

});