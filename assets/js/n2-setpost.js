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

		// inputにmaxlengthが設定されているもののみ入力中の文字数表示
		$('#ss_setting input,#default_setting input').each((i,v) => {
			if($(v).attr('maxlength')){
				$(v).parent().append($(`<p>${$(v).val().length}文字</p>`))
				$(v).on('keyup',()=>{
					$(v).parent().find('p').text($(v).val().length + '文字');
				})
			}
		})
	}

	/**
	 *  wordpressのメディアアップロード呼び出し
	 */
	const wpMedia = (title,btnText,type) => {
		return wp.media({
			title: title,
			button: {
				text: btnText
			},
			library: {
				type: type
			},
			multiple: false
		});
	}

	//imageアップローダーボタン 
	$(`.${prefix}-media-toggle`).on('click', e => {
		e.preventDefault();
		const parent = $(e.target).parent();
		const customUploader = wpMedia('画像を選択','画像を設定','image');

		customUploader.open();
		customUploader.on("select", () => {
			const images = customUploader.state().get("selection");
			images.each(image => {
				parent.find(`.${prefix}-image-url`).attr('src',image.attributes.url); 
				parent.find(`.${prefix}-image-input`).val(image.attributes.url); 
			});
		});
		
	});

	//zipアップローダーボタン 
	$(`.${prefix}-zip-toggle`).on('click', e => {
		e.preventDefault();
		const parent = $(e.target).parent();
		const customUploader = wpMedia('zipファイルを選択','zipファイルを設定','application/zip')

		customUploader.open();
		customUploader.on("select", () => {
			const zips = customUploader.state().get("selection");
			zips.each(zip => {
				console.log(zip)
				parent.find(`.${prefix}-zip-url`).text(`${zip.attributes.filename}を選択中`); 
				parent.find(`.${prefix}-zip-input`).val(zip.attributes.url); 
			});
		});
		
	});

});