import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * 検索用
	 * 絞り込み検索で事業者を絞り込むと、関連する返礼品コードのみを選択できるようにJS制御
	 * 
	================================================================== */
	jQuery(function ($) {
		const url = new URL(location.href);
		const params = url.searchParams;

		// 返礼品コード監視変更用
		const changeItemcode = (): void => {
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: "N2_Postlist",
					事業者: $('select[name="事業者"]').val(),
				},
			}).done((res) => {
				const data = JSON.parse(res);
				console.log(data);
				$('select[name="返礼品コード[]"]>*').remove();
				$('select[name="返礼品コード[]"]').append(
					'<option value="">返礼品コード</option>'
				);

				Object.keys(data).forEach((key) => {
					const selected =
						params.get("返礼品コード") === key ? "selected" : "";
					$('select[name="返礼品コード[]"]').append(
						$(
							`<option value="${key}" ${selected}>${data[key]}</option>`
						)
					);
				});
			});
		};

		// ページ表示時と事業者選択変更時に返礼品コードを監視、変更
		changeItemcode();

		$('select[name="事業者"]').on("change", () => {
			changeItemcode();
		});

		// キーワード検索にOR用チェックボックス
		const checked: string = params.get("or") === "1" ? "checked" : "";
		$("#post-search-input").before(
			$(
				`<label style="float:left"><input name="or" value="1" type="checkbox" ${checked}>OR検索</label>`
			)
		);
	});
};
