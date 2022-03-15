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


	//メディアアップローダーボタン 
	$(`.${prefix}-media-toggle`).on('click', e => {
		e.preventDefault();
		const parent = $(e.target).parent();
		const customUploader = wp.media({
			title: "画像を選択", //タイトルのテキストラベル
			button: {
			   text: "画像を設定" //ボタンのテキストラベル
			},
			library: {
				type: "image" //imageにしておく。
			},
			multiple: false //選択できる画像を1つだけにする。
		});
		customUploader.open();
		customUploader.on("select", () => {
			const images = customUploader.state().get("selection");
			images.each(file => {
				parent.find(`.${prefix}-image-url`).attr('src',file.attributes.url); 
				parent.find(`.${prefix}-image-input`).val(file.attributes.url); 
			});
		});
		
	});

});