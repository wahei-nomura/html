import { prefix, neoNengPath, ajaxUrl } from "./functions";


/** ===============================================================
 * 
 * 投稿複製用
 * 
================================================================== */
jQuery(function($) {

	/**
	 * フォーム内の表示やinput内容切り替え
	 * @param teikiNum 定期回数
	 */
	const formControll=(teikiNum: number) => {
		if(teikiNum>1) {
			$('.is-teiki').css('display', 'block')
			$('#n2-copypost-modal .new-title span').text(`【全${teikiNum}回定期便】`);
			$('#n2-copypost-form .is-teiki input').prop('disabled', false)
		} else {
			$('.is-teiki').css('display', 'none')
			$('#n2-copypost-form .is-teiki input').prop('disabled', true)
			$('#n2-copypost-modal .new-title span').text('');
		}
	}

	// 初回読み込み
	$("#wpbody-content").append(`<div id="${prefix}-content"></div>`);
	$(`#${prefix}-content`).load(
		neoNengPath(window) + "/template/copy-post.php"
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
		$("select[name='定期']>option[value='1']").prop('selected', true)
		formControll(1)
	}

	// モーダル展開クリックイベント
	$(`.${prefix}-copypost-btn`).on("click", (e) => {
		const itemTr = $(e.target).parent().parent();
		const originalId: number = Number(
			itemTr.find("th.check-column input").val()
		);
		const itemTitle=itemTr.find(".item-title a").text();
		setModal(originalId, itemTitle);
	});

	// 定期便、単品切り替え
	$('body').on('change', 'select[name="定期"]', e => {
		const teikiNum = +$(e.target).val();
		formControll(teikiNum)
	})

	// モーダルキャンセル
	$("body").on("click", "#n2-copypost-modal .close-btn,#n2-copypost-modal-wrapper", (e) => {
		if($(e.target).attr('id')==='n2-copypost-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
			$('#n2-copypost-modal-wrapper').css('display', 'none')
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

