import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * フロントページajax関連
	 * 
	================================================================== */
	jQuery(function ($) {
		const url = new URL(location.href);
		const params = url.searchParams;
		const searchStrings = url.search;
		let searchStringsArray = [];
		let paramArray = [];
		let key = null;
		// 計算パターンを受け取ってから処理
		var siteHomeUrl = homeUrl(window) + "/"; // locationと合わせるため'/'追加
		var nowUrl = location.href;
		const townName = homeUrl(window).match(/[^/]*$/)[0];
		console.log("siteHomeUrl", townName);
		let itemDetail;
		const scrapingItem = (): void => {
			 $.ajax({
				url: ajaxUrl(window),
				data: {
					action: "SS_Portal_Scraper",
					id: "DAJ009",
					town: townName,
				},
			}).done((res) => {
				const data = JSON.parse(res);
				itemDetail = data;
			});
		};
		const searchFrontItem = (): void => {
			console.log($('input[name="portalsite"]').val());
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Front",
				},
			}).done((res) => {
				const data = JSON.parse(res);
			});
		};
		if (nowUrl !== siteHomeUrl) {
			// トップページでない(=single)場合にスクレイピング
			scrapingItem();
		} else {
			console.log("test2");
			searchFrontItem();
			$(".portalsite").on("change", () => {
				searchFrontItem();
			});
		}
		searchFrontItem();
		$('.portalsite').on("change", () => {
			searchFrontItem();
		});

	});
};
