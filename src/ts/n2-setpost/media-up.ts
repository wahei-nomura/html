import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {
		/**
		 *  wordpressのメディアアップロード呼び出し
		 */

		const wpMedia = (
			title: string,
			btnText: string,
			type: string,
			window: any
		) => {
			const wp = window.wp;
			return wp.media({
				title: title,
				button: {
					text: btnText,
				},
				library: {
					type: type,
				},
				multiple: false,
			});
		};

		const uploaderOpen=(customUploader,parent, dataName:string) => {
			customUploader.open();
			customUploader.on("select", () => {
				const datas = customUploader.state().get("selection");
				datas.each((data) => {
					parent
						.find(`.${prefix}-${dataName}-url`)
						.attr("src", data.attributes.url)
					parent
						.find(`.${prefix}-${dataName}-input`)
						.val(data.attributes.url);
				});
			});
		}

		// imageアップローダーボタン
		$(`.${prefix}-media-toggle`).on("click", (e) => {
			e.preventDefault();
			const parent = $(e.target).parent();
			const customUploader = wpMedia(
				"画像を選択",
				"画像を設定",
				"image",
				window
			);

			uploaderOpen(customUploader, parent, 'image');

		});

		// // 画像削除ボタン
		// $(`.${prefix}-media-delete`).on("click", (e) => {
		// 	if (!confirm("選択中の画像を削除してもよろしいですか？")) {
		// 		return;
		// 	}
		// 	const parent = $(e.target).parent();
		// 	parent.find(`.${prefix}-image-input`).val("");
		// 	parent.find(`.${prefix}-image-url`).attr("src", "");
		// });

		// zipアップローダーボタン
		$(`.${prefix}-zip-toggle`).on("click", (e) => {
			e.preventDefault();
			const parent = $(e.target).parent();
			const customUploader = wpMedia(
				"zipファイルを選択",
				"zipファイルを設定",
				"application/zip",
				window
			);

			uploaderOpen(customUploader, parent, 'zip');
		});
		// zip削除ボタン
		// $(`.${prefix}-zip-delete`).on("click", (e) => {
		// 	if (!confirm("選択中のzipファイルを削除してもよろしいですか？")) {
		// 		return;
		// 	}
		// 	const parent = $(e.target).parent();
		// 	parent.find(`.${prefix}-zip-input`).val("");
		// 	parent.find(`.${prefix}-zip-url`).text("");
		// });
	});
};
