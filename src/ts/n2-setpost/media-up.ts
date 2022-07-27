import { RuleTester } from "eslint";
import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($: any) {
		/**
		 *  wordpressのメディアアップロード呼び出し
		 */

		// wpMediaにわたすオブジェクトの型定義
		type wpMediaObj = {
			title: string;
			btnText: string;
			type: string;
		};

		const wpMedia = (object: wpMediaObj, window: any) => {
			const wp = window.wp;
			return wp.media({
				title: object.title,
				button: {
					text: object.btnText,
				},
				library: {
					type: object.type,
				},
				multiple: true,
			});
		};

		const imageObj: wpMediaObj = {
			title: "画像を選択（controlキーまたはShiftキーを押しながら複数選択してください）",
			btnText: "設定した画像を登録",
			type: "image",
		};

		// const zipObj: wpMediaObj = {
		// 	title: "zipファイルを選択",
		// 	btnText: "zipファイルを設定",
		// 	type: "application/zip",
		// };

		const uploaderOpen = (customUploader, parent) => {
			const imgUrls = [];
			$.each($(`input[name="画像[]"]`), (i, v) => {
				imgUrls[i] = $(v).val();
			});

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

					// console.log(`imageIds:${imageIds}`);
					console.log(imageIds);

					// アップローダー展開
					customUploader.on("open", () => {

						const selection = customUploader
							.state()
							.get("selection");

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

					customUploader.on("select", () => {
						parent.find(`.${prefix}-image-block`).remove();
						const datas = customUploader.state().get("selection");
						datas.each((data) => {
							parent.append(
								$(`<div class="${prefix}-image-block">
				<input type="hidden" name="画像[]" class="${prefix}-image-input" value="${data.attributes.url}">
				<span class="dashicons dashicons-no-alt ${prefix}-image-delete"></span><span class="${prefix}-image-num"></span>
				<img class="${prefix}-image-url" src="${data.attributes.url}" width="100%">
				</div>`)
							);
						});

						imgSortable();
					});
				})
				.fail((error) => {
					console.log(error);
					alert(
						"画像データの読み込みに失敗しました。ページをリロードしてください"
					);
				});
		};

		$('label[for="画像"]')
			.parent()
			.next()
			.next()
			.before(
				$(
					`<button class="button button-primary ${prefix}-media-toggle">画像選択</button><input type="hidden" name="画像" value=""><div class="${prefix}-image-wrapper"></div>`
				)
			);

		// 画像アップイベント
		$("body").on("click", `.${prefix}-media-toggle`, (e) => {
			e.preventDefault();

			uploaderOpen(
				wpMedia(imageObj, window),
				$(`.${prefix}-image-wrapper`)
			);
		});

		const setImageNum = () => {
			$.each($(`.${prefix}-image-num`), (i, v) => {
				$(v).text(i + 1);
			});
		};


		const imgSortable = (): void => {
			const imagesWrapper = $(`.${prefix}-image-wrapper`);
			$.each($(`.${prefix}-image-block`), (i, v) => {
				$(v).appendTo(imagesWrapper);
			});
			imagesWrapper.sortable({
				update: () => {
					setImageNum();
				},
			});
			setImageNum()
		};

		imgSortable();

		// // zipアップイベント
		// $(`.${prefix}-zip-toggle`).on("click", (e) => {
		// 	e.preventDefault();

		// 	uploaderOpen(wpMedia(zipObj, window), $(e.target).parent());
		// });

		// 画像削除イベント
		$("body").on("click", `.${prefix}-image-delete`, (e) => {
			$(e.target).parent().remove();
			setImageNum();
		});
	});
};
