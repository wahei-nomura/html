import { prefix, neoNengPath, ajaxUrl } from "../functions/index";/**
 * 返礼品一覧ページの画像ダウンロードで使用するAjax用のファイル
 */
export default () => {
	jQuery(function ($) {
		$(".dlbtn").on("click", (e) => {
			$(e.target).addClass("not-click"); // クリックと同時にclass付けて二重クリックできないようにする
			const btnName = $(e.target).attr("id");
			e.preventDefault();
			download(ajaxUrl(window), btnName, getIds());
			setTimeout(function(){
				$(e.target).removeClass("not-click"); // 2秒待ってから再度クリックできるようにする
			},2000);
				
		});

		// チェックが入った返礼品のidを配列で返す
		const getIds = (): string => {
			const checkbox = $.makeArray($('input[name="post[]"]'));
			const checked = checkbox.flatMap((v) =>
				$(v).prop("checked") ? $(v).val() : []
			);
			return checked.length ? checked.join() : "";
		};

		// downloadさせる
		function download(url, action, id) {
			const data = new FormData();
			data.append("id", id);
			const xhr = new XMLHttpRequest();
			xhr.open("POST", url + "?action=" + action, true);
			xhr.responseType = "blob";
			xhr.onload = function (e) {
				const blob = this.response;
				if (blob.size === 0) {
					alert("\n選択した返礼品全てに画像が登録されていません。");
					return;
				}
				const a = document.createElement("a");
				document.body.appendChild(a);
				a.href = window.URL.createObjectURL(
					new Blob([blob], { type: blob.type })
				);
				a.download = decodeURI(this.getResponseHeader("Download-Name"));
				a.click();
				a.remove();
			};
			xhr.send(data);
		}
	});
};
