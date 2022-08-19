import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * フロントページajax関連
	 * 
	================================================================== */
	jQuery(function ($) {
		// 計算パターンを受け取ってから処理
		var siteHomeUrl = homeUrl(window) + '/'; // locationと合わせるため'/'追加
		var nowUrl = location.href;
		const scrapingItem = (): void => {
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "SS_Portal_Scraper",
					id: "FBM003",
					town: "yoshinogari",
				},
			}).done((res) => {
				const data = JSON.parse(res);
				console.log(data);
			});
		};
		const searchFrontItem = (): void => {
			console.log($('input[name="portalsite"]').val())
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Front",
					portalsitecheck: $('input[name="portalsite"]').val(),
				},
			}).done((res) => {
				const data = JSON.parse(res);
				console.log(data);
			});
		};
		if( nowUrl !== siteHomeUrl ){ // トップページでない(=single)場合にスクレイピング
			scrapingItem();
		}else{
			console.log('test1');
			searchFrontItem();
			$('.portalsite').on("change", () => {
				searchFrontItem();
			});
		}
	});
};
	