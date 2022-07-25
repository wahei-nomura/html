import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {
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
			title: "画像を選択",
			btnText: "画像を設定",
			type: "image",
		};

		const zipObj: wpMediaObj = {
			title: "zipファイルを選択",
			btnText: "zipファイルを設定",
			type: "application/zip",
		};

		// アップローダー展開
		const uploaderOpen = (customUploader, parent) => {
			customUploader.open();
			customUploader.on("select", () => {
				const datas = customUploader.state().get("selection");
				console.log(datas);
				datas.each((data) => {
					console.log(data.attributes.url);
					parent.append(
						$(
							`<input type="hidden" name="画像[]" value="${data.attributes.url}">`
						)
					);
					parent.append($(`<img src="${data.attributes.url}">`));
				});

				// deleteボタン生成
				createDelBtn();
			});
		};

		// deleteボタン生成定義
		const createDelBtn = (): void => {
			$.each($(`.${prefix}-image-button`), (i, input) => {
				const parent = $(input).parent();
				if (
					$(input).val() !== "" &&
					!parent.find(`.${prefix}-image-delete`).length
				) {
					parent
						.find("button")
						.after(
							$(
								`<button type="button" class="${prefix}-image-delete button button-secondary">削除</button>`
							)
						);
				}
			});
		};

		// アップ済み画像にdeleteボタン生成
		createDelBtn();

		// 画像アップイベント
		$(`.${prefix}-media-toggle`).on("click", (e) => {
			e.preventDefault();

			uploaderOpen(wpMedia(imageObj, window), $(e.target).parent());
		});

		// zipアップイベント
		$(`.${prefix}-zip-toggle`).on("click", (e) => {
			e.preventDefault();

			uploaderOpen(wpMedia(zipObj, window), $(e.target).parent());
		});

		// 画像削除イベント
		$("body").on("click", `.${prefix}-image-delete`, (e) => {
			if (!confirm("選択中の画像を削除してもよろしいですか？")) {
				return;
			}
			const parent = $(e.target).parent();
			parent.find(`.${prefix}-image-input`).val("");
			parent.find(`.${prefix}-image-url`).attr("src", "").text("");
			$(e.target).remove();
		});
	});
};
