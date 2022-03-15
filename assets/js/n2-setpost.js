jQuery(function($){
	// クラスにテーマ名をprefixつける
	const prefix = 'neo-neng';

	// 返礼品編集画面
	if(location.href.match(/(post|post-new)\.php/)){
		$('form').on('submit',(e)=>{

			const vError = [];
			
			$(`.${prefix}-hissu`).each( (i,v) => {
				if($(v).val() === ''){
					if(!$(v).parent().find(`.${prefix}-hissu-alert`).length){
						$(v).before($(`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`))
					}
					$(v).css('background-color','pink'); 
					vError.push(v);
				}
			})

			if(vError.length){
				alert('入力内容をご確認ください。')
				e.preventDefault();
				return false;
			}
			
		})
	}

});