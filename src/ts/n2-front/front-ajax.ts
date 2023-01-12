import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import $ from 'jquery';

export default () => {
	/** ===============================================================
	 * 
	 * フロントページajax関連
	 * 
	================================================================== */
	jQuery(function ($) {
		// 計算パターンを受け取ってから処理
		const updateItemConfirm = (postId: number, confirmValue: string): void => {
			$.ajax({
				url: ajaxUrl(window),
				type: "POST",
				dataType: "json",
				data: {
					action: "N2_Front_item_confirm",
					post_id: postId,
					confirm_value: confirmValue,
				},
			}).done((res) => {
				console.log('更新OK')
			}).fail(error => {
				console.log(error)
			});
		};

		$('.n2-jigyousya-radiobox input[type="radio"]').on('change', e=>{
			const value = $(e.target).val()
			const postId = Number($(e.target).attr('id').match(/\d+/)[0])

			updateItemConfirm(postId, String(value));
		})

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
				Object.keys(res).forEach(key=>{
					if(res[key] !== ''){
						$('.search-code-list').append($(`<option value="${res[key]}">${key}</option>`))
					}
				})
			}).fail(error => {
				console.log(error)
			});
		}

		if($('#jigyousya-value').val() !== ''){
			$('#search-code-list').removeClass('d-none');
			searchItemCode(Number($('#jigyousya-value').val()))
		}

		$('#jigyousya-list-tag').on('change', e => {
			setTimeout(()=>{
				if($('#jigyousya-value').val() !== ''){	
					$('#search-code-list').removeClass('d-none');
					$('.search-code-list option').remove()
					searchItemCode(Number($('#jigyousya-value').val()))
				}
			},300)
		})
	})
};

export const portalScrapingAjax = ( method:string, data:object) => {
    return $.ajax({
        url: ajaxUrl(window) + "?action=N2_Portal_Scraper",
        type: method == "GET" ? "GET" : "POST",
        dataType: "json",
        data: { ...data},
    })
};