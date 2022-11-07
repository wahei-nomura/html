import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * フロントページajax関連
	 * 
	================================================================== */
	jQuery(function ($) {
		// 計算パターンを受け取ってから処理
		const updateItemConfirm = (postId: number, confirmFlag: boolean): void => {
			$.ajax({
				url: ajaxUrl(window),
				type: "POST",
				dataType: "json",
				data: {
					action: "N2_Front_item_confirm",
					post_id: postId,
					confirm_flag: confirmFlag,
				},
			}).done((res) => {
				console.log('更新OK')
			}).fail(error => {
				console.log(error)
			});
		};
		$('button.ok-btn').on('click', e => {
			console.log($(e.target).hasClass('confirmed'))
			const confirmFlag = $(e.target).hasClass('confirmed')
			if( !confirm(`この商品を確認${confirmFlag ? '未' : '済み'}にして良いですか？`)){
				return;
			}

			$(e.target).toggleClass('confirmed').text(`確認${confirmFlag ? '未' : '済み'}`);
			updateItemConfirm(Number($(e.target).val()), confirmFlag);
		});
	})
};
