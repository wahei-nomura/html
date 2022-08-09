import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 寄附金額計算
	 * 
	================================================================== */
	jQuery(function ($) {
		// 計算パターンを受け取ってから処理
		console.log("ajaxtest2");
		$.ajax({
			// url: ajaxUrl(window),
			url: "https://ore.steamship.co.jp/wp/kawatana/wp-admin/admin-ajax.php",
			data: {
				action: "SS_Portal_Scraper",
				id: "FBX001",
				town: "yoshinogari",
			},
		}).done((res) => {
			const data = JSON.parse(res);
			console.log(data);
		});
	
		// ここまで寄附金額計算 ==============================================================================================================================
	});
};
	