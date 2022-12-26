import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 投稿複製用
	 * 
	================================================================== */
	jQuery(function ($) {

		const openModal = (id: number, title: string): void => {
			// テンプレートディレクトリからHTMLをロード
			$("#wpbody-content").append(`<div id="${prefix}-content"></div>`);
			$(`#${prefix}-content`).load(
				neoNengPath(window) + "/template/copy-post.html",
				() => {
					$("#n2-copypost-modal .original-title").text(title);
					$('input[name="複写後商品名"]').val(title);
					$("#n2-copypost-modal input[name='id']").val(id);
				}
			);
		};

		$(`.${prefix}-copypost-btn`).on("click", (e) => {
			const itemTr = $(e.target).parent().parent();
			const originalId: number = Number(
				itemTr.find("th.check-column input").val()
			);
			const itemTitle = itemTr.find(".item-title a").text();
			openModal(originalId, itemTitle);
		});

		$('body').on('change', 'select[name="定期"]', e => {
			const teikiNum = +$(e.target).val();
			
			if (teikiNum > 1) {
				$('.is-teiki').css('display', 'block')
				$('#n2-copypost-modal .new-title span').text(`【全${teikiNum}回定期便】`);
			} else {
				$('.is-teiki').css('display', 'none')
				$('input[name="同月回数"]').val(''),
				$('input[name="初回発送日"]').val(''),
				$('input[name="毎月発送日"]').val('')
				$('#n2-copypost-modal .new-title span').text('');
			}
		})

		$("body").on("click", "#n2-copypost-modal .close-btn,#n2-copypost-modal-wrapper", (e) => {
			if($(e.target).attr('id')==='n2-copypost-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {	
				$(`#${prefix}-content`).remove();
			}
		});

		$("body").on("submit", '#n2-copypost-form', () => {

			if (
				$('select[name="定期"] option:selected').val() > 1 &&
				($('input[name="同月回数"]').val() === '' || $('input[name="初回発送日"]').val() === '' || $('input[name="毎月発送日"]').val() === '')
			) {
				alert('全ての項目を入力してください')
				return false;
			}

		});
	});
};
