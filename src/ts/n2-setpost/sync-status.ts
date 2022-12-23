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
				// 投稿ステータスとプログレストラッカーそれぞれのclass名が連動しておりactiveを付け替えている
				$(`#${prefix}-progress-tracker li`).removeClass('active').parent().find(`.${wp(window)
					.data.select("core/editor")
					.getEditedPostAttribute("status")}`).addClass("active");
			}
		});
	})
}