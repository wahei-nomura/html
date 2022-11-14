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
					if(res[key] !== ''){
						$('.search-code-list').append($(`<option value="${key}">${res[key]}</option>`))
					}
				}
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

export const getPortalScraping = (productID:string, town:string) => {
    return $.ajax({
        url: ajaxUrl(window),
        type: "GET",
        dataType: "json",
        data: {
            action: "N2_Portal_Scraper",
            id: productID,
            town: town,
        },
    })
};
export const saveScraping = ( postID:number, key:string, scraping:object ) => {
	return $.ajax({
        url: ajaxUrl(window) + '?action=N2_Portal_Scraper_save',
        type: "POST",
        dataType: "json",
        data: {
            postID: postID,
			key: key,
            value: scraping,
        },
    })
}
export const getImgsScraping = ( productID:string, town:string ) => {
	return $.ajax({
        url: ajaxUrl(window),
        type: "GET",
        dataType: "json",
        data: {
			action: 'N2_Portal_Scraper_imgs',
            id: productID,
			town: town,
        },
    })
}
