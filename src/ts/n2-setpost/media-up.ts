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

		const uploaderOpen=(customUploader,parent) => {
			customUploader.open();
			customUploader.on("select", () => {
				const datas = customUploader.state().get("selection");
				datas.each((data) => {
					parent
						.find(`.${prefix}-image-url`)
						.attr("src", data.attributes.url)
						.text(data.attributes.filename)
					parent
						.find(`.${prefix}-image-input`)
						.val(data.attributes.url);
				});

				createDelBtn();
			});
		}

		const createDelBtn=():void => {
			$.each($(`.${prefix}-image-input`), (i, input) => {
				const parent=$(input).parent();
				if($(input).val()!=='' && !parent.find(`.${prefix}-image-delete`).length) {
					parent.find('button')
						.after($(`<button type="button" class="${prefix}-image-delete button button-secondary">削除</button>`))
				}
			})
		}

		createDelBtn();

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

			uploaderOpen(customUploader, parent);

		});

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

			uploaderOpen(customUploader, parent);
		});
		
		// 画像削除ボタン
		$("body").on("click",`.${prefix}-image-delete,.${prefix}-zip-delete`, (e) => {
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
