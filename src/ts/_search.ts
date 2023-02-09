import { prefix, neoNengPath, ajaxUrl } from "./_functions";

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
				事業者: $('#jigyousya-value').val(),
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

	// n2-class-postlist.phpのpost_requestのSQLがぐちゃぐちゃなのでいったんor検索コメントアウト　taiki
	// キーワード検索にOR用チェックボックス
	// const checked: string = params.get("or") === "1" ? "checked" : "";
	// $("#post-search-input").before(
	// 	$(
	// 		`<label style="float:left"><input name="or" value="1" type="checkbox" ${checked}>OR検索</label>`
	// 	)
	// );

	// 事業者絞り込みコンボボックス
	$('#jigyousya-list-tag').on('change', e => {
		const id:number=$(`#jigyousya-list option[value="${$(e.target).val()}"]`).data('id')
		$('#jigyousya-value').val(id)

		changeItemcode();
	})

	// 条件クリアボタン
	$('#ss-search-clear').on('click', () => {
		$('#posts-filter .actions select[name="ステータス"] option:selected').prop('selected', false)
		$('#posts-filter .actions select[name="定期便"] option:selected').prop('selected',false)
		$('#posts-filter .actions input[name="事業者"], #jigyousya-list-tag').val('')
		$('select[name="返礼品コード[]"]>*').remove();
		$('select[name="返礼品コード[]"]').append(
			'<option value="">返礼品コード</option>'
		);
	})

});
