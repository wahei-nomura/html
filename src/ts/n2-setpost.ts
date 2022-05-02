export default () => {

	jQuery(function ($) {
		// クラスにテーマ名をprefixつける
		const prefix = 'neo-neng';

		const neoNengPath = (window): string => {
			return window.tmp_path.tmp_url;
		}

		// 返礼品編集画面
		$('form').on('submit', (e) => {

			const vError = [];

			$(`.${prefix}-hissu`).each((i, v) => {
				if ($(v).val() === '') {
					if (!$(v).parent().find(`.${prefix}-hissu-alert`).length) {
						$(v).before($(`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`))
					}
					$(v).css('background-color', 'pink');
					vError.push(v);
				}
			})

			$(`.${prefix}-notzero`).each((i, v) => {
				if (Number($(v).val()) === 0) {
					if (!$(v).parent().find(`.${prefix}-notzero-alert`).length) {
						$(v).before($(`<p class="${prefix}-notzero-alert" style="color:red;">※0以外の値を入力してください。</p>`))
					}
					$(v).css('background-color', 'pink');
					vError.push(v);
				}
			})

			if (vError.length) {
				alert('入力内容をご確認ください。')
				e.preventDefault();
				return false;
			}

		})

		// inputにmaxlengthが設定されているもののみ入力中の文字数表示
		$('#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea').each((i, v) => {
			if ($(v).attr('maxlength')) {
				$(v).parent().append($(`<p>${String($(v).val()).length}文字</p>`))
				$(v).on('keyup', () => {
					$(v).parent().find('p').text(String($(v).val()).length + '文字');
				})
			}
		})


		/**
		 *  wordpressのメディアアップロード呼び出し
		 */


		const wpMedia = (title: string, btnText: string, type: string, window: any) => {
			const wp = window.wp;
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
			const customUploader = wpMedia('画像を選択', '画像を設定', 'image', window);

			customUploader.open();
			customUploader.on("select", () => {
				const images = customUploader.state().get("selection");
				images.each(image => {
					parent.find(`.${prefix}-image-url`).attr('src', image.attributes.url);
					parent.find(`.${prefix}-image-input`).val(image.attributes.url);
				});
			});

		});

		//zipアップローダーボタン 
		$(`.${prefix}-zip-toggle`).on('click', e => {
			e.preventDefault();
			const parent = $(e.target).parent();
			const customUploader = wpMedia('zipファイルを選択', 'zipファイルを設定', 'application/zip', window)


			customUploader.open();
			customUploader.on("select", () => {
				const zips = customUploader.state().get("selection");
				console.log(zips);

				zips.each(zip => {
					console.log(zip)
					parent.find(`.${prefix}-zip-url`).text(`${zip.attributes.filename}を選択中`);
					parent.find(`.${prefix}-zip-input`).val(zip.attributes.url);
				});
			});

		});

		/** ===============================================================
		 * 
		 * 楽天タグID用
		 * 
		================================================================== */

		// タグ取得のAPI
		const rakutenApiUrl: string = 'https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=';

		// ジャンル>ジャンル>ジャンルの形式のテキストを保持
		let genreText: string = '';
		let tagText: string = '';

		// ジャンルIDをパラメータで渡すことでJSONを返す
		const getRakutenId = (genreId: number) => {
			const url: string = `https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=${genreId}`;

			return $.ajax({
				url: url,
				dataType: 'JSON',
			})
		}


		// 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく
		const setRakutenId = (genreId: number = 0, genreLevel: number = 1): void => {
			getRakutenId(genreId).done(res => {
				// 子のジャンルがなければ終わり
				if (!res.children.length) {
					return
				}
				// select数字クラスを自動生成
				if (!$(`#n2-setpost-rakuten-genreid .select${genreLevel}`).length) {
					$('#n2-setpost-rakuten-genreid .select-wrapper').append($(`<select class="select${genreLevel}"><option value="" selected>未選択</option></select>`))
					$.each(res.children, (index, val) => {
						$(`#n2-setpost-rakuten-genreid select.select${genreLevel}`).append($(`<option value="${val.child.genreId}">${val.child.genreName}</option>`))
					})
				}

				// セレクトを変更するとジャンルIDと階層テキストを保持してまたsetRakutenIdをまわす
				$(`#n2-setpost-rakuten-genreid select.select${genreLevel}`).on('change', e => {
					$('#n2-setpost-rakuten-genreid .result span').text(String($(e.target).val()))
					$(e.target).nextAll().remove();
					genreText += ' > ' + $(e.target).find($('option:selected')).text()
					genreId = Number($(e.target).val());
					genreLevel++;
					setRakutenId(genreId, genreLevel);
				})
			})
		}

		// 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく
		const setRakutenTagId = (genreId: number = 0,tagLevel: number = 1): void => {
			getRakutenId(genreId).done(res => {

				$.each(res.tagGroups, (index, val) => {
					$(`#n2-setpost-rakuten-tagid .groups`).append(
						$(`<div><input type="radio" name="tag-group" id="gid${val.tagGroup.tagGroupId}" value="${val.tagGroup.tagGroupName}"><label for="gid${val.tagGroup.tagGroupId}">${val.tagGroup.tagGroupName}</label></div>`)
					)
					$(`#n2-setpost-rakuten-tagid .tags`).append($(`<div class="gid${val.tagGroup.tagGroupId}"></div>`))

					$.each(val.tagGroup.tags, (index, v) => {
						$(`#n2-setpost-rakuten-tagid .tags .gid${val.tagGroup.tagGroupId}`).append($(`<div><input type="checkbox" name="tags" id="tid${v.tag.tagId}" value="${v.tag.tagName}"><label for="tid${v.tag.tagId}">${v.tag.tagName}</label></div>`))
					})

					$(`#n2-setpost-rakuten-tagid .tags>*`).css('display', 'none');
				})

				$('#n2-setpost-rakuten-tagid .groups input[type="radio"]').on('click', e => {
					const gid: number=Number($(e.target).attr('id').replace('gid', ''))

					$(`#n2-setpost-rakuten-tagid .tags>*`).css('display', 'none');


					$(`#n2-setpost-rakuten-tagid .tags .gid${gid}`).css('display','block')

					
				})

				$(`#n2-setpost-rakuten-tagid .tags input[name="tags"]`).on('change', e => {
					const tagId: number=Number($(e.target).attr('id').replace('tid', ''))
					const tagName=$(e.target).val();

					if($(e.target).prop('checked')) {
						$('#n2-setpost-rakuten-tagid .result .checked-tags').append($(`<span class="${tagName}" data-tid="${tagId}">${tagId}:${tagName}</span>`))
					} else {
						$(`#n2-setpost-rakuten-tagid .result .checked-tags .${tagName}`).remove()
					}

					$(`#n2-setpost-rakuten-tagid .result .checked-tags span`).on('click', e => {
						$(`#tid${$(e.target).data('tid')}`).prop('checked', false)
						$(e.target).remove()
					})
				})
			})
		}

		// JS読み込んだ時点で、表示用のタグを生成する ============================================================================

		// ディレクトリID用
		$('#全商品ディレクトリID').before($(`<p>ディレクトリ階層：<span id="${prefix}-genre"></span><p>`))
		$(`#${prefix}-genre`).text(String($('#全商品ディレクトリID-text').val()));
		$('#全商品ディレクトリID').after($(`<p>ディレクトリID：<span id="${prefix}-genreid"></span><p>`))
		$(`#${prefix}-genreid`).text(String($('#全商品ディレクトリID').val()));

		// タグID用
		$('#楽天タグID').before($(`<p>選択中のタグ：<span id="${prefix}-tag"></span><p>`))
		$(`#${prefix}-tag`).text(String($('#楽天タグID-text').val()));
		$('#楽天タグID').after($(`<p>タグID：<span id="${prefix}-tagid"></span><p>`))
		$(`#${prefix}-tagid`).text(String($('#楽天タグID').val()));
		
		// ================================================================================================================

		// ディレクトリID検索スタート
		$(`#${prefix}-genreid-btn`).on('click', e => {
			$('#ss_setting').append($('<div id="n2-setpost-rakuten-genreid-wrapper"></div>'))
			// テンプレートディレクトリからHTMLをロード
			$('#n2-setpost-rakuten-genreid-wrapper').load(neoNengPath(window) + '/template/rakuten-genreid.html #n2-setpost-rakuten-genreid', () => {

				// 保持テキストをリセットしてからsetRakutenId回す
				genreText = '';
				setRakutenId();

				// モーダル内の各ボタンの処理制御
				$('#n2-setpost-rakuten-genreid button').on('click', e => {
					if ($(e.target)[0].className === 'clear') {
						$('#n2-setpost-rakuten-genreid .select-wrapper>*').remove();
						$('#n2-setpost-rakuten-genreid .result span').text('指定なし')
						setRakutenId();
					}
					if ($(e.target)[0].className === 'done' && confirm('選択中のIDをセットしますか？')) {
						$(`#${prefix}-genre`).text(genreText)
						$(`#${prefix}-genreid`).text($('#n2-setpost-rakuten-genreid .result span').text())
						$('#全商品ディレクトリID-text').val(genreText)
						$('#全商品ディレクトリID').val(Number($('#n2-setpost-rakuten-genreid .result span').text()))
						$('#n2-setpost-rakuten-genreid-wrapper').remove();
					}
					if ($(e.target)[0].className === 'close' && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {
						$('#n2-setpost-rakuten-genreid-wrapper').remove();
					}
				})
			})
		})

		// タグID検索スタート
		$(`#${prefix}-tagid-btn`).on('click', e => {
			if($('#全商品ディレクトリID').val()==='') {
				alert('ディレクトリIDを選択してから再度お試しください。')
				return;
			}

			$('#ss_setting').append($('<div id="n2-setpost-rakuten-tagid-wrapper"></div>'))
			// テンプレートディレクトリからHTMLをロード
			$('#n2-setpost-rakuten-tagid-wrapper').load(neoNengPath(window) + '/template/rakuten-tagid.html #n2-setpost-rakuten-tagid', () => {

				// 保持テキストをリセットしてからsetRakutenId回す
				tagText='';

				setRakutenTagId(Number($('#全商品ディレクトリID').val()));

				// モーダル内の各ボタンの処理制御
				$('#n2-setpost-rakuten-tagid button').on('click', e => {
					if ($(e.target)[0].className === 'clear') {
						$('#n2-setpost-rakuten-tagid .tags>*').remove();
						$('#n2-setpost-rakuten-tagid .result span').text('指定なし')
						// setRakutenId();
					}
					if ($(e.target)[0].className === 'done' && confirm('選択中のIDをセットしますか？')) {
						$(`#${prefix}-tag`).text(genreText)
						$(`#${prefix}-tagid`).text($('#n2-setpost-rakuten-tagid .result span').text())
						$('#楽天タグID-text').val(genreText)
						$('#楽天タグID').val(Number($('#n2-setpost-rakuten-tagid .result span').text()))
						$('#n2-setpost-rakuten-tagid-wrapper').remove();
					}
					if ($(e.target)[0].className === 'close' && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {
						$('#n2-setpost-rakuten-tagid-wrapper').remove();
					}
				})
			})
		})


	});
}
