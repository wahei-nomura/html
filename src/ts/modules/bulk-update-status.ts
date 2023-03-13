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

	// モーダル展開クリックイベント
	$(`#bulk_update_status`).on("click", (e) => {
		setModal();
	});

	// ゴミ箱へ移動イベント
	$(`.${prefix}-deletepost-btn`).on("click", (e) => {
		if(!confirm('この返礼品をゴミ箱へ移してもいいですか？')){
			return
		}
		const itemTr = $(e.target).parents('tr');
		const originalId: number = Number(
			itemTr.find("th.check-column input").val()
		);

		$.ajax({
			url: n2.ajaxurl,
			data:{
				action: 'N2_Postlist_deletepost',
				id: originalId
			}
		}).done(res=>{
			console.log(res)
		}).fail(error => {
			console.log(error)
		});
		
		itemTr.remove()
	});
	// ゴミ箱から復元イベント
	$(`.${prefix}-recoverypost-btn`).on("click", (e) => {
		const itemTr = $(e.target).parents('tr');
		const originalId: number = Number(
			itemTr.find("th.check-column input").val()
		);

		$.ajax({
			url: n2.ajaxurl,
			data:{
				action: 'N2_Postlist_recoverypost',
				id: originalId
			}
		}).done(res=>{
			console.log(res)
		}).fail(error => {
			console.log(error)
		});

		itemTr.remove()
	});

	// モーダルキャンセル
	$("body").on("click", "#n2-update-status-modal .close-btn,#n2-update-status-modal-wrapper", (e) => {
		if($(e.target).attr('id')==='n2-update-status-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
			$('#n2-update-status-modal-wrapper').css('display', 'none')
		}
	});

	// 複製submit
	$("body").on("submit", '#n2-update-status-form', () => {

		// inputのvalueに空のものがあるか判定
		if($('#n2-update-status-form').serializeArray().length && $('#n2-update-status-form').serializeArray().map(v => v.value).includes('')) {
			alert('全ての項目を入力してください')
			return false;	
		}

	});
});

