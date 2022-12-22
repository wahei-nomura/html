import { RuleTester } from "eslint";
import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($: any) {


		/**
		 * アップロード画像が8枚に満たない場合のアラート表示
		 */
		const checkImgblockLength = () => {
			$('.neo-neng-image-alert').remove()
			if ($('.neo-neng-image-block').length < 8) {
				if (!$('.neo-neng-image-alert').length) {
					$('.neo-neng-image-wrapper').before('<p class="neo-neng-image-alert">可能であれば8枚以上を推奨いたします</p>')
				}
				$('.neo-neng-image-alert').css('background', 'pink')
				setTimeout(() => {
					$('.neo-neng-image-alert').css('background', 'none')
				}, 1000)
			}
		}

		/**
		 *  wordpressのメディアアップロード呼び出し
		 */

		const wpMedia = () => {
			const wp = (window as any).wp;
			return wp.media({
				title: "画像を選択",
				button: {
					text: "選択した画像を登録",
				},
				library: {
					type: "image",
				},
				multiple: "add",
			});
		};

		// 画像をソートした時に番号を再セット
		const setImageNum = () => {
			$.each($(`.${prefix}-image-num`), (i, v) => {
				$(v).text(i + 1);
			});
		};

		// 画像の手動ソート
		const imgSortable = (): void => {
			const imagesWrapper = $(`.${prefix}-image-wrapper`);
			$.each($(`.${prefix}-image-block`), (i, v) => {
				$(v).appendTo(imagesWrapper);
			});
			imagesWrapper.sortable({
				update: () => {
					setImageNum();
				},
				placeholder: "placeholder",
			});
			setImageNum();
		};

		// アップローダーオープン時に動かしたい処理
		const uploaderOpen = (customUploader, parent) => {
			const imgUrls = [];
			$.each($(`input[name="商品画像[]"]`), (i, v) => {
				imgUrls[i] = $(v).val();
			});

			// 画像URLをwp-ajaxに渡してIDの配列で受け取る
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Setpost_image",
					imgurls: imgUrls,
				},
			})
				.done((res) => {
					const imageIds =
						res !== "noselected" ? JSON.parse(res) : [];

					// アップローダー展開
					customUploader.on("open", () => {
						const selection = customUploader
							.state()
							.get("selection");

						// idを使ってアップローダー展開時に画像選択状態を保持
						if (imageIds.length > 0) {
							imageIds.forEach((id) => {
								const attachment = (
									window as any
								).wp.media.attachment(id);
								attachment.fetch();
								selection.add(attachment ? [attachment] : []);
							});
						}
					});

					customUploader.open();


					// 画像選択時にHTML生成
					customUploader.on("select", () => {
						const datas = customUploader.state().get("selection");
						// 画層は最大25枚
						if (datas.length > 25) {
							alert('画像は最大25枚まででお願いします。')
							customUploader.open();
							return
						}
						parent.find(`.${prefix}-image-block`).remove();
						datas.each((data) => {
							parent.append(
								$(`<div class="${prefix}-image-block">
				<input type="hidden" name="商品画像[]" class="${prefix}-image-input" value="${data.attributes.url
									}">
				<span class="dashicons dashicons-no-alt ${prefix}-image-delete"></span><span class="${prefix}-image-big dashicons dashicons-editor-expand"></span><span class="${prefix}-image-num"></span>
				<img class="${prefix}-image-url" src="${data.attributes.url.replace(
										/\.(png|jpg|jpeg)$/,
										"-150x150.$1"
									)}" width="100%">
				</div>`)
							);
						});

						imgSortable();
						checkImgblockLength()
					});
				})
				.fail((error) => {
					console.log(error);
					alert(
						"画像データの読み込みに失敗しました。ページをリロードしてください"
					);
				});
		};

		checkImgblockLength()

		// 画像選択ボタン表示
		$('label[for="商品画像"]')
			.parent()
			.next()
			.next()
			.before(
				$(
					`<button class="button button-primary ${prefix}-media-toggle">画像選択</button><input type="hidden" name="商品画像" value=""><div class="${prefix}-image-wrapper"></div>`
				)
			);

		// sortable起動
		imgSortable();

		// 画像選択ボタンクリック
		$("body").on("click", `.${prefix}-media-toggle`, (e) => {
			e.preventDefault();
			// アップローダー起動
			uploaderOpen(wpMedia(), $(`.${prefix}-image-wrapper`));
		});

		// 画像拡大ボタン
		$("body").on("click", `.${prefix}-image-big`, (e) => {
			e.preventDefault();
			const url = $(e.target).parent().find("input").val();
			$("body").append(
				$(
					`<div class="${prefix}-image-big-modal"><img src="${url}" /></div>`
				)
			);
		});

		$("body").on("click", `.${prefix}-image-big-modal`, (e) => {
			e.preventDefault();
			$(`.${prefix}-image-big-modal`).remove();
		});

		// 画像削除イベント
		$("body").on("click", `.${prefix}-image-delete`, (e) => {
			$(e.target).parent().remove();
			setImageNum();
			checkImgblockLength()
		});
	});
};
