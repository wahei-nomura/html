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
					alert(`「${res}」を複製しました`);
					// location.reload();
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
				teiki: $('select[name="定期"] option:selected').val()
			}

			createCopyPost(Number($("#n2-copypost-modal input[name='id']").val()),setData);
		});
	});
};
