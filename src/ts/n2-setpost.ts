export default () => {

	jQuery(function ($) {
		// クラスにテーマ名をprefixつける
		const prefix = 'neo-neng';

		const neoNengPath = (window): string => {
			return window.tmp_path.tmp_url;
		}

		// 返礼品編集画面
		$('#publish').on('click', (e) => {

			
			// ここからバリデーション ===========================================================================================================================
			const vError = []; // エラーを溜める
			
			// 必須
			$(`.${prefix}-hissu`).each((i, v) => {
				if ($(v).val() === '') {
					if (!$(v).parent().find(`.${prefix}-hissu-alert`).length) {
						$(v).before($(`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`))
					}
					$(v).css('background-color', 'pink');
					vError.push(v);
				}
			})
			
			// 0はダメ
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
				alert('入力必須項目が未入力です。入力内容をご確認ください。')
				e.preventDefault()
				return
			}
			// ここまでバリデーション==========================================================================================================================
			
			if(!$('#n2-setpost-check-modal').length) {
			$('body').css('overflow-y', 'hidden')
			e.preventDefault()
				
				// ここから確認用モーダル==========================================================================================================================

				$('#default_setting').append($('<div id="n2-setpost-check-modal-wrapper"></div>'))
				
				$('#n2-setpost-check-modal-wrapper').load(neoNengPath(window)+'/template/check-modal.html #n2-setpost-check-modal', () => {
					
					const inputs=$('#default_setting .n2-input')
				
					let checkbox={}
					$.each(inputs, (i, v) => {
						const inputName=$(v).attr('name')
						const tag=v.tagName

						if((tag==='INPUT'&&$(v).attr('type')==='text')||tag==='TEXTAREA') {
							const value:string=$(v).val()!==''? String($(v).val()).replace('\n','<br>'):'<span class="noset">入力なし</span>'
							$('#n2-setpost-check-modal .result table').append(`<tr><td>${inputName}</td><td>${value}</td></tr>`)
						}
						
						if(tag==='SELECT') {
							let selected = '未選択'
							$.each($(v).find('option'), (i2, v2) => {
								selected=$(v2).attr('selected')==='selected' && $(v2).text() !== '未選択'? $(v2).text():selected
							})
							selected = selected === '未選択' ? `<span class="noset">${selected}</span>`: selected
							$('#n2-setpost-check-modal .result table').append(`<tr><td>${inputName}</td><td>${selected}</td></tr>`)
						}

						if(tag==='INPUT'&&$(v).attr('type')==='checkbox') {
							const checkedName=$(v).parent().text()
							const key = inputName.replace('[]', '')
							if($(v).prop('checked')) {
								checkbox[key] = checkbox[key] === undefined ? '' + checkedName: checkbox[key] = checkbox[key] === undefined ? '' + checkedName: checkbox[key]+','+checkedName
							} else {
								checkbox[key] = checkbox[key] === undefined || checkbox[key] === 'なし' ? 'なし': checkbox[key].replace('なし,','')
							}
						}
						if(tag==='INPUT'&&$(v).attr('type')==='hidden') {
							let value=$(v).val()!==''? $(v).val():false
							value = value && inputName.match(/画像/)?`<img src="${value}" width="100%">`:'<span class="noset">なし</span>'
							$('#n2-setpost-check-modal .result table').append(`<tr><td>${inputName}</td><td>${value}</td></tr>`)
						}
					})

					$.each(checkbox, (k, v) => {
						if(v==='なし') {
							$('#n2-setpost-check-modal .result table').append(`<tr><td>${k}</td><td><span class="noset">${v}</span></td></tr>`)
						} else {	
							$('#n2-setpost-check-modal .result table').append(`<tr><td>${k}</td><td>${v}</td></tr>`)
						}
					})
					
					$('#n2-setpost-check-modal button.cancel').on('click', e => {
						$('#n2-setpost-check-modal-wrapper').remove()
						$('body').css('overflow-y','auto')
					})
					$('#n2-setpost-check-modal button.done').on('click', e => {
						$('#publish').trigger('click')
					})
					
				})
			} // end if(!$('#n2-setpost-check-modal').length)
			// ここまで確認用モーダル==========================================================================================================================
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

		// タグ取得のAPI
		const rakutenApiUrl: string = 'https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=';

		// ジャンル>ジャンル>ジャンルの形式のテキストを保持
		let genreText: string='';
		// 1234567/1234567/1234567みたいにする
		let tagChain: string='';
		// タグネーム/タグネーム/タグネーム
		let tagText: string='';

		// 最大タグID数に達していないかをカウントして表示
		let tagCount: number=32;
		const showTagCount=(tagCount:number):void => {
			$('.tags-count  span').text(tagCount)
		}

		// ジャンルIDをパラメータで渡すことでJSONを返す
		const getRakutenId = (genreId: number) => {
			return $.ajax({
				url: rakutenApiUrl + genreId,
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

		// genreIdをセットし、tagGroupからtagIdまでとっていく
		const setRakutenTagId = (genreId: number = 0,tagLevel: number = 1): void => {
			getRakutenId(genreId).done(res => {

				showTagCount(tagCount)

				$.each(res.tagGroups, (index, val) => {
					// 含まれる全グループのブロックを生成
					$(`#n2-setpost-rakuten-tagid .groups`).append(
						$(`<div><input type="radio" name="tag-group" id="gid${val.tagGroup.tagGroupId}" value="${val.tagGroup.tagGroupName}"><label for="gid${val.tagGroup.tagGroupId}">${val.tagGroup.tagGroupName}</label></div>`)
					)
					$(`#n2-setpost-rakuten-tagid .tags`).append($(`<div class="gid${val.tagGroup.tagGroupId}"></div>`))

					// グループごとのブロック内にタグを配置
					$.each(val.tagGroup.tags, (index, v) => {
						$(`#n2-setpost-rakuten-tagid .tags .gid${val.tagGroup.tagGroupId}`).append($(`<div><input type="checkbox" name="tags" id="tid${v.tag.tagId}" value="${v.tag.tagName}"><label for="tid${v.tag.tagId}">${v.tag.tagName}</label></div>`))
					})

					// 全ブロック非表示
					$(`#n2-setpost-rakuten-tagid .tags>*`).css('display', 'none');
				})

				// グループを選択
				$('#n2-setpost-rakuten-tagid .groups input[type="radio"]').on('click', e => {
					const gid: number=Number($(e.target).attr('id').replace('gid', ''))

					// 表示中のグループブロックを非表示
					$(`#n2-setpost-rakuten-tagid .tags>*`).css('display', 'none');

					// 選択したグループブロックを表示
					$(`#n2-setpost-rakuten-tagid .tags .gid${gid}`).css('display','block')
				})

				// tagを選択
				$(`#n2-setpost-rakuten-tagid .tags input[name="tags"]`).on('change', e => {
					const tagId: number=Number($(e.target).attr('id').replace('tid', ''))
					const tagName=$(e.target).val();

					// チェック未→済
					if($(e.target).prop('checked')) {
						if(tagCount!==0) {
							$('#n2-setpost-rakuten-tagid .result .checked-tags').append($(`<div data-tid="${tagId}">${tagId}:${tagName}<span></span></div>`))
							tagCount--;
							showTagCount(tagCount)
						} else {
							$(e.target).prop('checked', false)
							alert('32件選択中です。')
						}
					// チェック済→未
					} else {
						$(`#n2-setpost-rakuten-tagid .result .checked-tags div[data-tid="${tagId}"]`).remove()
						tagCount++;
						showTagCount(tagCount)
					}
				})

				// バツボタンで選択中のタグを削除するとcheckboxも未選択に戻る
				$(document).on('click',`#n2-setpost-rakuten-tagid .result .checked-tags div span`, e => {
					$(`#tid${$(e.target).parent().data('tid')}`).prop('checked', false)
					$(e.target).parent().remove()
					tagCount++;
					showTagCount(tagCount)
				})
			})
		}

		// ディレクトリID検索スタート
		$(`#${prefix}-genreid-btn`).on('click', e => {
			$('#ss_setting').append($('<div id="n2-setpost-rakuten-genreid-wrapper"></div>'))

			if($('#楽天タグID').val()!=='') {
				if(!confirm('ディレクトリIDを変更すると、下の楽天タグIDがリセットされますのでご注意ください。')) {
					return;
				}
			}
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
					if ($(e.target)[0].className === 'done' && confirm('選択中のIDをセットしますか？(楽天タグIDがリセットされます)')) {
						$(`#${prefix}-genre`).text(genreText)
						$(`#${prefix}-genreid`).text($('#n2-setpost-rakuten-genreid .result span').text())
						$('#全商品ディレクトリID-text').val(genreText)
						$('#全商品ディレクトリID').val(Number($('#n2-setpost-rakuten-genreid .result span').text()))
						$('#n2-setpost-rakuten-genreid-wrapper').remove();

						$(`#${prefix}-tag`).text('')
						$(`#${prefix}-tagid`).text('')
						$('#楽天タグID-text').val('')
						$('#楽天タグID').val('')
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
			$('#n2-setpost-rakuten-tagid-wrapper').load(neoNengPath(window)+'/template/rakuten-tagid.html #n2-setpost-rakuten-tagid', () => {
				
				tagCount = 32
				showTagCount(tagCount)

				// 保持テキストをリセットしてからsetRakutenId回す
				tagChain='';
				tagText='';

				setRakutenTagId(Number($('#全商品ディレクトリID').val()));

				// モーダル内の各ボタンの処理制御
				$('#n2-setpost-rakuten-tagid button').on('click', e => {
					if ($(e.target)[0].className === 'clear') {
						$('#n2-setpost-rakuten-tagid .tags>*').remove();
						$('#n2-setpost-rakuten-tagid .result .checked-tags>*').remove();
						tagCount=32
						showTagCount(tagCount)
						setRakutenTagId(Number($('#全商品ディレクトリID').val()));
					}
					if($(e.target)[0].className==='done'&&confirm('選択中のIDをセットしますか？')) {
						const chekedTags = $('#n2-setpost-rakuten-tagid .tags input[name="tags"]').filter(':checked')

						$.each(chekedTags, (i, v) => {
							if(i===0) {
								tagText += $(v).val()
								tagChain += v.id.replace('tid', '')
							} else {
								tagText += '/' + $(v).val()
								tagChain += '/' + v.id.replace('tid', '')
							}
						})

						$(`#${prefix}-tag`).text(tagText)
						$(`#${prefix}-tagid`).text(tagChain)
						$('#楽天タグID-text').val(tagText)
						$('#楽天タグID').val(tagChain)
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
