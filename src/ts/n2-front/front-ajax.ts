import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * フロントページajax関連
	 * 
	================================================================== */
	jQuery(function ($) {
		// 計算パターンを受け取ってから処理
		const updateItemConfirm = (postId: number): void => {
			$.ajax({
				url: ajaxUrl(window),
				type: "POST",
				dataType: "json",
				data: {
					action: "N2_Front_item_confirm",
					post_id: postId,
				},
			}).done((res) => {
				console.log('更新OK')
			}).fail(error => {
				console.log(error)
			});
		};
		$('button.ok-btn').on('click', e => {
			$(e.target).prop('disabled',true);
			updateItemConfirm(Number($(e.target).val()))
		});
	})
};
