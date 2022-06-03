/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function($) { 
		const ajaxUrl = (window): string => {
			return window.tmp_path.ajax_url;
		}

		$('.sisbtn').on('click', e => {
			const btnName=$(e.target).attr('id');
			openByPost(ajaxUrl(window), btnName,getIds())
			
			console.log(getIds())
		})

		// チェックが入った返礼品のidを配列で返す
		const getIds=():string => {
			const checkbox=$.makeArray($('input[name="post[]"]'))
			const checked = checkbox.flatMap(v=>$(v).prop('checked') ? $(v).val():[])
			return checked.length? checked.join():''
		}

		// POST送信してURLを開く
		const openByPost=(url: string, btnName: string, ids:string): Window => {
			if(!ids) return
			const win=window.open("about:blank", url);
			const form=document.createElement("form");
			const body=document.getElementsByTagName("body")[0];
			form.action=url+'?action='+btnName;
			form.method='post';
			const input=document.createElement("input");
			input.type="hidden";
			input.name=btnName
			input.value=ids
			form.appendChild(input);
			body.appendChild(form);
			form.submit();
			body.removeChild(form);
			return win;
		}
	})
}