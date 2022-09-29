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
		let siteHomeUrl = homeUrl(window) + '/'; // locationと合わせるため'/'追加
		let nowUrl = location.href;
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
			});
		};
		const searchFrontItem = (): void => {
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Front",
				},
			}).done((res) => {
				const data = JSON.parse(res);
			});
		};
		// if( nowUrl === siteHomeUrl ){ // トップページでない(=single)場合にスクレイピング
		// 	console.log('test2');
		// 	// searchFrontItem();
		// 	$('.portalsite').on("change", () => {
		// 		console.log($(this).prop('id'));
		// 		searchFrontItem();
		// 	});
		// }else if(searchStrings !== ''){
		// 	console.log('search');
		// }else{
		// 	scrapingItem();
		// }
		if("" != searchStrings){
			const newSearchStrings = searchStrings.replace("?","");
			searchStringsArray = newSearchStrings.split('&');
			for(var i = 0; i < searchStringsArray.length; i++){
				key = searchStringsArray[i].split("=");
				paramArray[key[0]] = key[1];
				let terms = decodeURIComponent(key[1]);
				$('input').each(function(index,elem){
					let val = $(this).val();
					if($(this).attr('name') == key[0]){
						if('checkbox' == $(this).attr('type')){
							if('1' == terms){
								$(this).prop("checked", true);
							}
						}else{
							$(this).val(terms);
						}
					}
				});
			}
		}else{ // 
			$('.front-portal-wrap').find('input').prop("checked", true);
		}
		searchFrontItem();
		$('.portalsite').on("change", () => {
			searchFrontItem();
		});

	});
};
	