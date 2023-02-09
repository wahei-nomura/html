import { prefix, neoNengPath, ajaxUrl } from "./_functions";/**
 * 返礼品一覧ページの画像ダウンロードで使用するAjax用のファイル
 */
jQuery(function ($) {
	// チェックが入った返礼品のidを配列で返す
	const getIds = (): string => {
		const checkbox = $.makeArray($('input[name="post[]"]'));
		const checked = checkbox.flatMap((v) =>
			$(v).prop("checked") ? $(v).val() : []
		);
		return checked.length ? checked.join() : "";
	};

	// loading要素を追加
	$('#download_img').after('<span class="loading_background"><span id="text_loading"></span><span class="progressbar"></span></span>');
	const text_loading = document.getElementById("text_loading");
	$(document).on("click", '.dlbtn', (e) => {
		$('.loading_background').addClass("active"); // クリックと同時にオーバーレイ要素(loading_background)class付けて二重クリックできないようにする
		text_loading.textContent = "登録画像確認中… "; // #text_loadingのテキスト書き換え(追加)
		const btnName = $(e.target).attr("id");
		e.preventDefault();
		download(ajaxUrl(window), btnName, getIds());
		setTimeout(function(){
			$(e.target).removeClass("not-click"); // 2秒待ってから再度クリックできるようにする
		},2000);
			
	});

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
		downloadProgress(xhr);
	}		
	function downloadProgress(xhr){
		var dlper = '';
		var dlfontsize = 0;
		xhr.addEventListener('progress', (e) => {
			if( e.lengthComputable ) {
				dlper = Math.floor((e.loaded / e.total) * 100) + "%";
				text_loading.textContent = "ダウンロード中… " + dlper;
				$('.progressbar').css('width',dlper);
			} else {
				text_loading.textContent = "読み込み中";
			}
		});
		xhr.onreadystatechange = ()=>{
			if (xhr.readyState === 4 && xhr.status === 200){
				$('.loading_background').removeClass("active"); 
				text_loading.textContent = "";
				$('.progressbar').css('width', dlper);
			}
		}
	}
});
