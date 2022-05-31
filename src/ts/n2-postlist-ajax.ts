/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function($) { 
		const ajaxUrl = (window): string => {
			return window.tmp_path.ajax_url;
		}

		console.log(ajaxUrl(window))

		$('.sisbtn').on('click', e => {
			// const id=$(e.target).attr('id');
			// $.ajax({
			// 	type: 'POST',
			// 	url: ajaxUrl(window),
			// 	data: {
			// 		action: "N2_Ajax",
			// 		id: id
			// 	}
			// }).done(res => {
			// 	console.log(res)
			// })
			openByPost(ajaxUrl(window))
		})

		// POST送信してURLを開く
		function openByPost( url ){
			var win = window.open("about:blank",url);
			var form = document.createElement("form");
			var body = document.getElementsByTagName("body")[0];
			form.target = url;
			form.action = url;
			form.method='post';
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = 'action';
			input.value = 'N2_Ajax';
			form.appendChild(input);
			body.appendChild(form);
			form.submit();
			body.removeChild(form);
			return win;
		}
	})
}