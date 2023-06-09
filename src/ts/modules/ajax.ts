import { prefix, neoNengPath, ajaxUrl } from "./functions";
import $ from 'jquery'

/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */

// チェックが入った返礼品のidを配列で返す
export const getIds = (): string => {
	const checkbox = $.makeArray($('input[name="post[]"]'));
	const checked = checkbox.flatMap((v) =>
		$(v).prop("checked") ? $(v).val() : []
	);
	return checked.length ? checked.join() : "";
};
// POST送信してURLを別タブで開く
export const openByPostAnotherPage = (
	url: string,
	btnName: string,
	ids: string
): Window => {
	if('changeallergen' !== btnName){
		if (!ids) return;
	}
	const target_tab = 'n2_another'
	const win = window.open("about:blank", target_tab);
	const form = document.createElement("form");
	const body = document.getElementsByTagName("body")[0];
	form.action = url + "?action=" + btnName;
	form.method = "post";
	form.target = target_tab
	const input = document.createElement("input");
	input.type = "hidden";
	input.name = btnName;
	input.value = ids;
	form.appendChild(input);
	body.appendChild(form);
	form.submit();
	body.removeChild(form);
	return win;
};

export const ajax = async (action:string) =>{
	return await $.ajax({
		url: ajaxUrl(window) + "?action=" + action,
		dataType: "json",
		type: "POST",
		data: {
			ids: getIds(),
		},
	})
}

jQuery(function ($) {
	$(".sisbtn").on("click", async (e) => {
		const btnName = $(e.target).attr("id");
		const banAjaxList = await ajax("N2_Postlist_ban_portal_list");
		console.log(banAjaxList);
		const banPortal = Object.keys(window['n2'].export).find((key)=> {
			return window['n2'].export[ key ].includes(btnName);
		});
		let alertMessage = '出品禁止ポータルに指定されている返礼品が含まれています。続けますか？';
		banAjaxList[banPortal].forEach(item => {
			alertMessage += `\n・${item}`;
		});
		console.log(banPortal);
		
		
		if ( banAjaxList[banPortal].length && confirm(alertMessage) ) {
			openByPostAnotherPage(ajaxUrl(window), btnName, getIds());
		}
		console.log(getIds());
	});	
	$(document).on("click", '.siserror',(e) => {
		const btnName = $(e.target).attr("id");
		openByPostAnotherPage(ajaxUrl(window), btnName, '1');
		console.log(getIds());
	});
	$(".arrbtn").on("click", async (e) => {
		const btnName = $(e.target).attr("id");
		console.log(btnName);
		openByPostAnotherPage(ajaxUrl(window), btnName, getIds());
		console.log(getIds());
	});	

	// POST送信してURLを開く
	const openByPost = (
		url: string,
		btnName: string,
		ids: string
	): Window => {
		if (!ids) return;
		const win = window.open("about:blank", url);
		const form = document.createElement("form");
		const body = document.getElementsByTagName("body")[0];
		form.action = url + "?action=" + btnName;
		form.method = "post";
		const input = document.createElement("input");
		input.type = "hidden";
		input.name = btnName;
		input.value = ids;
		form.appendChild(input);
		body.appendChild(form);
		form.submit();
		body.removeChild(form);
		return win;
	};
});

