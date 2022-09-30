import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 投稿複製用
	 * 
	================================================================== */
	jQuery(function ($) {
		const createCopyPost = (id: number,setData): void => {
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

		$(`.${prefix}-copypost-btn`).on("click", (e) => {
			const itemTr = $(e.target).parent().parent();
			const originalId: number = Number(
				itemTr.find("th.check-column input").val()
			);
			const itemTitle = itemTr.find(".item-title a").text();
			// createCopyPost(originalId);
			openModal(originalId, itemTitle);
		});

		const openModal = (id, title) => {
			// テンプレートディレクトリからHTMLをロード
			$("#wpbody-content").append(`<div id="${prefix}-content"></div>`);
			$(`#${prefix}-content`).load(
				neoNengPath(window) + "/template/copy-post.html",
				() => {
					$("#n2-copypost-modal p").text(title);
					$("#n2-copypost-modal input[name='id']").val(id);
				}
			);
		};

		$("body").on("click", "#n2-copypost-modal button", () => {
			const setData={
				title: $('input[name="複写後商品名"]').val(),
				teiki: $('select[name="定期"] option:selected').val(),
				firstDate: $('input[name="初回発送日"]').val(),
				everyDate: $('input[name="毎月発送日"]').val()
			}

			createCopyPost(Number($("#n2-copypost-modal input[name='id']").val()),setData);
		});
	});
};
