import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function ($) {
		$(".sisbtn").on("click", (e) => {
			const btnName = $(e.target).attr("id");
			openByPost(ajaxUrl(window), btnName, getIds());

			console.log(getIds());
		});	
		$(document).on("click", '.siserror',(e) => {
			const btnName = $(e.target).attr("id");
			openByPostAnotherPage(ajaxUrl(window), btnName, '1');
			console.log(getIds());
		});

		// チェックが入った返礼品のidを配列で返す
		const getIds = (): string => {
			const checkbox = $.makeArray($('input[name="post[]"]'));
			const checked = checkbox.flatMap((v) =>
				$(v).prop("checked") ? $(v).val() : []
			);
			return checked.length ? checked.join() : "";
		};

		// POST送信してURLを開く
		const openByPostAnotherPage = (
			url: string,
			btnName: string,
			ids: string
		): Window => {
			if (!ids) return;
			const win = window.open("about:blank", 'n2_another');
			const form = document.createElement("form");
			const body = document.getElementsByTagName("body")[0];
			form.action = url + "?action=" + btnName;
			form.method = "post";
			form.target = "n2_another"
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
};
