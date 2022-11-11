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
