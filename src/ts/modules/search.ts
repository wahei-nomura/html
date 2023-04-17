import { prefix, neoNengPath, ajaxUrl } from "./functions";

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
	const changeItemcode=(): void => {
		$.ajax({
			url: ajaxUrl(window),
			data: {
				action: "N2_Postlist",
				事業者: ($('select[name="事業者[]"]').val() as string[]).join(','),
			},
		}).done((res) => {
			const data = JSON.parse(res);
			console.log(data);
			$('select[name="返礼品コード[]"]>*').remove();
			$('select[name="返礼品コード[]"]').append(
				'<option value="" style="padding-top: 4px;">返礼品コード</option >'
			);

			data.forEach((item) => {
				$('select[name="返礼品コード[]"]').append(
					$(
						`<option value="${item.id}">${item.code}</option>`
					)
				);
			});

			if(Object.keys(data).length) {
				$('select[name="返礼品コード[]"]').css('display', 'block');
			} else {
				$('select[name="返礼品コード[]"]').css('display', 'none');
			}
		});
	};


	$('body').on('focus','select[name="返礼品コード[]"],select[name="事業者[]"]', (e) => {
		$(e.target).prop('size',10)
	}).on('blur','select[name="返礼品コード[]"],select[name="事業者[]"]', (e) => {
		$(e.target).prop('size',1)
	})

	// n2-class-postlist.phpのpost_requestのSQLがぐちゃぐちゃなのでいったんor検索コメントアウト　taiki
	// キーワード検索にOR用チェックボックス
	// const checked: string = params.get("or") === "1" ? "checked" : "";
	// $("#post-search-input").before(
	// 	$(
	// 		`<label style="float:left"><input name="or" value="1" type="checkbox" ${checked}>OR検索</label>`
	// 	)
	// );

	// 事業者絞り込みコンボボックス
	$('select[name="事業者[]"]').on('change', e => {
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
		$('select[name="返礼品コード[]"]').css('display', 'none');
	})

});
