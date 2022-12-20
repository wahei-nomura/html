import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {

		const wp = (window) => {
			return window.wp;
		};

		// プログレストラッカーをリアルタイム更新
		wp(window).data.subscribe(() => {
			// 投稿が更新されたかどうかを判定する
			if (wp(window).data.select('core/editor').isSavingPost()) {
				// 投稿が更新されている場合の処理をここに記述する
				$(`#${prefix}-progress-tracker li`).removeClass('active')
				$(
					`.${wp(window)
						.data.select("core/editor")
						.getEditedPostAttribute("status")}`
				).addClass("active");
			}
		});
	})
}