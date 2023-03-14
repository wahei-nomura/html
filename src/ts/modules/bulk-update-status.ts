import { prefix, neoNengPath, ajaxUrl } from "./functions";


/** ===============================================================
 * 
 * 投稿複製用
 * 
================================================================== */
jQuery(function($) {

	const n2 = window['n2'];

	// 初回読み込み
	$("#wpbody-content").append(`<div id="${prefix}-update-status-content"></div>`);
	$(`#${prefix}-update-status-content`).load(
		neoNengPath(window) + "/template/update-status.php"
	);

	/**
	 * 複製用テンプレートにてモーダル表示
	 * @param id 
	 * @param title 
	 */
	const setModal = (): void => {
		$('#n2-update-status-modal-wrapper').css('display', 'block')
	}

	// チェックが入った返礼品のidを配列で返す
	const integrateItems = () => {
		const checkbox = $.makeArray($('input[name="post[]"]'));
		const items = checkbox.flatMap((v) => {
				if($(v).prop("checked")){
					return {
						id: $(v).val(),
						title: $(v).parent().parent().find('td.item-title>div.text-truncate').text() ,
						author: $(v).parent().parent().find('td.poster>div').text() ,
						status: $(v).parent().parent().find('td.item-title>div.progress').prop('outerHTML'),
						code: $(v).parent().parent().find('td.code>div').text()
					}
				} else {
					return [];
				}
			}
		);
		// return checked.length ? checked.join() : "";
		return items;
	};

	// モーダル展開クリックイベント
	$(`#bulk_update_status`).on("click", (e) => {
		setModal();

		$.each(integrateItems(),(_,v)=>{
			$('.n2-selected-item-wrapper').append(
				`<li class="list-group-item">
					${v.title}
					<span class="badge bg-secondary">${v.author}</span>
					<span class="badge bg-secondary ${v.code==='-' ? 'visually-hidden':''}">${v.code}</span><br>
					${v.status}
				</li>
				`
			)
		})
	});

	// モーダルキャンセル
	$("body").on("click", "#n2-update-status-modal .close-btn,#n2-update-status-modal-wrapper", (e) => {
		if($(e.target).attr('id')==='n2-update-status-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
			$('#n2-update-status-modal-wrapper').css('display', 'none')
			$('.n2-selected-item-wrapper>*').remove()
		}
	});

});

