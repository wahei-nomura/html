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

		//imageアップローダーボタン
		$(`.${prefix}-media-toggle`).on("click", (e) => {
			e.preventDefault();
			const parent = $(e.target).parent();
			const customUploader = wpMedia(
				"画像を選択",
				"画像を設定",
				"image",
				window
			);

			customUploader.open();
			customUploader.on("select", () => {
				const images = customUploader.state().get("selection");
				images.each((image) => {
					parent
						.find(`.${prefix}-image-url`)
						.attr("src", image.attributes.url);
					parent
						.find(`.${prefix}-image-input`)
						.val(image.attributes.url);
				});
			});
		});

		$(`.${prefix}-media-delete`).on("click", e => {
			if(!confirm('選択中の画像を削除してもよろしいですか？')) {
				return;
			}
			const parent=$(e.target).parent();
			parent.find(`.${prefix}-image-input`).val('');
			parent.find(`.${prefix}-image-url`).attr('src', '');
		})

		//zipアップローダーボタン
		$(`.${prefix}-zip-toggle`).on("click", (e) => {
			e.preventDefault();
			const parent = $(e.target).parent();
			const customUploader = wpMedia(
				"zipファイルを選択",
				"zipファイルを設定",
				"application/zip",
				window
			);

			customUploader.open();
			customUploader.on("select", () => {
				const zips = customUploader.state().get("selection");
				console.log(zips);

				zips.each((zip) => {
					console.log(zip);
					parent
						.find(`.${prefix}-zip-url`)
						.text(`${zip.attributes.filename}を選択中`);
					parent.find(`.${prefix}-zip-input`).val(zip.attributes.url);
				});
			});
		});
	});
};
