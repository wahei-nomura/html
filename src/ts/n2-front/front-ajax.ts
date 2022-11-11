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
		$('.check-toggle').on('change', e => {
			const confirmFlag = $(e.target).prop('checked')
			updateItemConfirm(Number($(e.target).val()), confirmFlag);
		});

		// 返礼品コード絞り込み用

		const searchItemCode = ( authorId: number) => {
			$.ajax({
				url: ajaxUrl(window),
				dataType: "json",
				data: {
					action: "N2_Front_search_code",
					author_id: authorId,
				},
			}).done((res) => {
				for(let key in res){
					$('.search-code-list').append($(`<option value="${key}">${res[key]}</option>`))
				}
			}).fail(error => {
				console.log(error)
			});
		}

		if($('#jigyousya-value').val() !== ''){
			searchItemCode(Number($('#jigyousya-value').val()))
		}

		$('#jigyousya-list-tag').on('change', e => {
			setTimeout(()=>{
				searchItemCode(Number($('#jigyousya-value').val()))
			},300)
		})
	})
};
