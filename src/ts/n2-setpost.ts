export default () => {

	jQuery(function($) {
		// クラスにテーマ名をprefixつける
		const prefix='neo-neng';

		// 返礼品編集画面
		$('form').on('submit', (e) => {

			const vError=[];
		
			$(`.${prefix}-hissu`).each((i, v) => {
				if($(v).val()==='') {
					if(!$(v).parent().find(`.${prefix}-hissu-alert`).length) {
						$(v).before($(`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`))
					}
					$(v).css('background-color', 'pink');
					vError.push(v);
				}
			})

			$(`.${prefix}-notzero`).each((i, v) => {
				if(Number($(v).val())===0) {
					if(!$(v).parent().find(`.${prefix}-notzero-alert`).length) {
						$(v).before($(`<p class="${prefix}-notzero-alert" style="color:red;">※0以外の値を入力してください。</p>`))
					}
					$(v).css('background-color', 'pink');
					vError.push(v);
				}
			})

			if(vError.length) {
				alert('入力内容をご確認ください。')
				e.preventDefault();
				return false;
			}
		
		})

		// inputにmaxlengthが設定されているもののみ入力中の文字数表示
		$('#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea').each((i, v) => {
			if($(v).attr('maxlength')) {
				$(v).parent().append($(`<p>${String($(v).val()).length}文字</p>`))
				$(v).on('keyup', () => {
					$(v).parent().find('p').text(String($(v).val()).length+'文字');
				})
			}
		})
		

		/**
		 *  wordpressのメディアアップロード呼び出し
		 */


		const wpMedia=(title: string, btnText: string, type: string, window: any) => {
			const wp=window.wp;
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
			const parent=$(e.target).parent();
			const customUploader=wpMedia('画像を選択', '画像を設定', 'image',window);

			customUploader.open();
			customUploader.on("select", () => {
				const images=customUploader.state().get("selection");
				images.each(image => {
					parent.find(`.${prefix}-image-url`).attr('src', image.attributes.url);
					parent.find(`.${prefix}-image-input`).val(image.attributes.url);
				});
			});
		
		});

		//zipアップローダーボタン 
		$(`.${prefix}-zip-toggle`).on('click', e => {
			e.preventDefault();
			const parent=$(e.target).parent();
			const customUploader=wpMedia('zipファイルを選択', 'zipファイルを設定', 'application/zip',window)


			customUploader.open();
			customUploader.on("select", () => {
				const zips=customUploader.state().get("selection");
				console.log(zips);

				zips.each(zip => {
					console.log(zip)
					parent.find(`.${prefix}-zip-url`).text(`${zip.attributes.filename}を選択中`);
					parent.find(`.${prefix}-zip-input`).val(zip.attributes.url);
				});
			});
		
		});

	});
}