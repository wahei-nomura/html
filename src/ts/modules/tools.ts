import { post } from "jquery";
import { prefix, neoNengPath, ajaxUrl } from "./functions";


/** ===============================================================
 * 
 * 投稿複製用
 * 
================================================================== */
jQuery(function($) {

	const n2 = window['n2'];

	// 初回読み込み
	$("#wpbody-content").append(`<div id="${prefix}-content"></div>`);
	$(`#${prefix}-content`).load(
		neoNengPath(window) + "/template/copy-post.php"
	);

	$("#wpbody-content").append(`<div id="${prefix}-change-author-content"></div>`);
	$(`#${prefix}-change-author-content`).load(
		neoNengPath(window) + "/template/change-author.php"
	);

	/**
	 * 複製用テンプレートにてモーダル表示
	 * @param id 
	 * @param title 
	 */
	const setModal = (id: number, title: string): void => {
		$('#n2-copypost-modal-wrapper').css('display', 'block')
		$("#n2-copypost-modal .original-title").text(title);
		$('input[name="複写後商品名"]').val(title);
		$("#n2-copypost-modal input[name='id']").val(id);
	}

	// モーダル展開クリックイベント
	$(`.${prefix}-copypost-btn`).on("click", (e) => {
		const itemTr = $(e.target).parents('tr');
		const originalId: number = Number(
			itemTr.find("th.check-column input").val()
		);
		const itemTitle=itemTr.find(".item-title a").text();
		setModal(originalId, itemTitle);
	});

	// 事業者変更クリックイベント
	$(`.${prefix}-change-author-btn`).on("click", (e) => {
		console.log('change-author')
		const itemTr = $(e.target).parents('tr');
		const originalId: number = Number(
			itemTr.find("th.check-column input").val()
		);
		const itemCode = itemTr.find(".column-code div").text();
		const author = itemTr.find(".column-poster div").text();
		const itemTitle=itemTr.find(".item-title a").text();

		$('#n2-change-author-modal-wrapper').css('display', 'block')
		$("#n2-change-author-modal .original-title").text(itemTitle);
		$("#n2-change-author-modal .item-code>span").text(itemCode);
		$("#n2-change-author-modal .current-author>span").text(author);
		$("#n2-change-author-modal input[name='post_id']").val(originalId);

		$.ajax({
			url: n2.ajaxurl,
			data:{
				action: 'n2_users_api',
				role: 'jigyousya'
			}
		}).done(res=>{
			const users = JSON.parse(res)
			$('#n2-change-author-modal .author-select>option').remove()
			$('#n2-change-author-modal .author-select').append('<option value="">事業者を選択</option>')

			Object.keys(users).forEach(i=>{
				$('#n2-change-author-modal .author-select').append(`<option value='${users[i].ID}'>${users[i].display_name}</option>`)
			})
		}).fail(error => {
			console.log(error)
		});
	});

	// 事業者変更submit
	$("body").on("click", '#n2-change-author-modal button', () => {

		$.ajax({
			url: n2.ajaxurl,
			type: 'POST',
			data:{
				action: 'n2_post_author_update',
				post_id: $('#n2-change-author-modal input[name="post_id"]').val(),
				author_id: $('#n2-change-author-modal select[name="author_id"]').val()
			}
		}).done(res=>{
			location.reload();
		}).fail(error => {
			alert('更新に失敗しました')
			console.log(error)
		});

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
	$("body").on("click", "#n2-copypost-modal .close-btn,#n2-copypost-modal-wrapper", (e) => {
		if($(e.target).attr('id')==='n2-copypost-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
			$('#n2-copypost-modal-wrapper').css('display', 'none')
		}
	});
	$("body").on("click", "#n2-change-author-modal .close-btn,#n2-change-author-modal-wrapper", (e) => {
		if($(e.target).attr('id')==='n2-change-author-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
			$('#n2-change-author-modal-wrapper').css('display', 'none')
		}
	});

	// 複製submit
	$("body").on("submit", '#n2-copypost-form', () => {

		// inputのvalueに空のものがあるか判定
		if($('#n2-copypost-form').serializeArray().length && $('#n2-copypost-form').serializeArray().map(v => v.value).includes('')) {
			alert('全ての項目を入力してください')
			return false;	
		}

	});
});

