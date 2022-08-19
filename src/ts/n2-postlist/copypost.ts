import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 投稿複製用
	 * 
	================================================================== */
	jQuery(function ($) {

		const createCopyPost = (): void => {
			$.ajax({
				type: "POST",
				url: ajaxUrl(window),
				data: {
					action: "N2_Copypost",
					post_data: '',
				},
			}).done((res) => {
				const data = JSON.parse(res);
				console.log(data);
				alert(data);
			});
		};

		$('.copypost-btn').on('click', e => {
			createCopyPost()
		})
	});
};
