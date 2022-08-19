import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 投稿複製用
	 * 
	================================================================== */
	jQuery(function ($) {
		const createCopyPost = (id: number): void => {
			$.ajax({
				type: "POST",
				url: ajaxUrl(window),
				data: {
					action: "N2_Copypost",
					original_id: id,
				},
			})
				.done((res) => {
					alert(`「${res}」を複製しました`);
					location.reload();
				})
				.fail((error) => {
					console.log(error);
					alert("複製に失敗しました");
				});
		};

		$(`.${prefix}-copypost-btn`).on("click", (e) => {
			const originalId: number = Number(
				$(e.target)
					.parent()
					.parent()
					.find("th.check-column input")
					.val()
			);
			createCopyPost(originalId);
		});
	});
};
