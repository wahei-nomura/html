import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 投稿複製用
	 * 
	================================================================== */
	jQuery(function ($) {

		type setData = {
			title: string,
			teiki: number | null,
			monthlyNumber: number | null,
			firstDate: number | null,
			everyDate: number | null,
		}

		const createCopyPost = (id: number, setData: setData): void => {
			$.ajax({
				type: "POST",
				url: ajaxUrl(window),
				data: {
					action: "N2_Copypost",
					original_id: id,
					set_data: setData,
				},
			})
				.done((res) => {
					const response = JSON.parse(res)
					alert(`「${response['title']}」を作成しました`);
					console.log(response)
					location.reload();
				})
				.fail((error) => {
					console.log(error);
					alert("複製に失敗しました");
				});
		};

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
			const teikiNum = $(e.target).val();
			$('#n2-copypost-modal .new-title span').text(teikiNum ? `【全${teikiNum}回定期便】` : '');

			if (teikiNum) {
				$('.is-teiki').css('display', 'block')
			} else {
				$('.is-teiki').css('display', 'none')
				$('input[name="同月回数"]').val(''),
					$('input[name="初回発送日"]').val(''),
					$('input[name="毎月発送日"]').val('')
			}
		})

		$("body").on("click", "#n2-copypost-modal .close-btn", () => {
			$(`#${prefix}-content`).remove();
		});

		$("body").on("click", "#n2-copypost-modal .submit", () => {

			if (
				$('select[name="定期"] option:selected').val() > 1 &&
				($('input[name="同月回数"]').val() === '' || $('input[name="初回発送日"]').val() === '' || $('input[name="毎月発送日"]').val() === '')
			) {
				alert('全ての項目を入力してください')
				return
			}

			const setData: setData = {
				title: String($('input[name="複写後商品名"]').val()),
				teiki: Number($('select[name="定期"] option:selected').val()),
				monthlyNumber: Number($('input[name="同月回数"]').val()),
				firstDate: Number($('input[name="初回発送日"]').val()),
				everyDate: Number($('input[name="毎月発送日"]').val())
			}

			createCopyPost(Number($("#n2-copypost-modal input[name='id']").val()), setData);
		});
	});
};
