import { prefix, neoNengPath, ajaxUrl, homeUrl } from "./_functions";
import $ from 'jquery';

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
			if(Object.keys(res).length){
				$('#search-code-list').removeClass('d-none');
				Object.keys(res).forEach(key=>{
					if(res[key] !== ''){
						$('.search-code-list').append($(`<option value="${res[key]}">${key}</option>`))
					}
				})
			} else {
				$('.jigyousa-search-wrap').append($('<p class="jigyousya-search-alert text-danger">この事業者の返礼品はありません</p>'))
			}
		}).fail(error => {
			console.log(error)
		});
	}

	if($('#jigyousya-value').val() !== ''){
		searchItemCode(+$('#jigyousya-value').val())
	}

	$('#jigyousya-list-tag').on('change', e => {
		$('.jigyousya-search-alert').remove()
		setTimeout(()=>{
			if($('#jigyousya-value').val() !== ''){	
				$('.search-code-list option').remove()
				searchItemCode(+$('#jigyousya-value').val())
			}
		},300)
	})
})
export const portalScrapingAjax = ( method:string, data:object) => {
    return $.ajax({
        url: ajaxUrl(window) + "?action=N2_Portal_Scraper",
        type: method == "GET" ? "GET" : "POST",
        dataType: "json",
        data: { ...data},
    })
};