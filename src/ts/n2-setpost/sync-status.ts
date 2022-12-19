import { ajax } from "jquery";
import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {

		const wp = (window) => {
			return window.wp;
		};

		/**
		 * wp-ajaxにてpost statusを取得
		 */
		const syncPostStatus = () => {
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Setpost_syncstatus",
					postId: new URLSearchParams(location.search).get('post')
				}
			}).done(res => {
				console.log(res)

				// プログレストラッカーをリアルタイム更新
				$(`#${prefix}-progress-tracker li`).removeClass('active')
				$(
					`.${wp(window)
						.data.select("core/editor")
						.getEditedPostAttribute("status")}`
				).addClass("active");
			})
		}

		syncPostStatus()

		// 公開時にイベント発火
		$(document).on("click", ".editor-post-publish-button__button", (e) => {
			e.preventDefault();
			if ($(e.target).attr("aria-disabled")) {
				setTimeout(() => {
					syncPostStatus()
				}, 2000)
			}
		})
	})

}