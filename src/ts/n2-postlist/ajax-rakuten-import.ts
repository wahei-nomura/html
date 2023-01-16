import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function ($) {
		$(".sisfile").on("submit", function(e){
			e.preventDefault();
			var
			$this	= $(this),
			fd = new FormData($this[0]),
			txt = $this.find('[type="submit"]').val();
			$this.find('[type="submit"]').val(txt.replace("転送","転送中..."));
			// fd.append('action', "ss_item_sheet");
			fd.append('judge', $this.find('[type="file"]').attr('name').replace("[]",""));
			console.log(fd);
			console.log(txt);
			$.ajax({
				url: ajaxUrl(window),
				type: 'POST',
				data: fd,
				dataType: 'html',
				contentType:false,
				processData:false,
				success: function(data){
					console.log(data);
					alert(data);
					$this.find('[type="submit"]').val(txt);
				}
			});

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
