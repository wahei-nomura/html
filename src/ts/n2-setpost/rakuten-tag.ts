import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {
		/** ===============================================================
		 * 
		 * タグID用
		 * 
		================================================================== */

		// JS読み込んだ時点で、表示用のタグを生成する ============================================================================

		// ディレクトリID用
		$("#全商品ディレクトリID").before(
			$(`<p>ディレクトリ階層：<span id="${prefix}-genre"></span><p>`)
		);
		$(`#${prefix}-genre`).text(
			String($("#全商品ディレクトリID-text").val())
		);
		$("#全商品ディレクトリID").after(
			$(`<p>ディレクトリID：<span id="${prefix}-genreid"></span><p>`)
		);
		$(`#${prefix}-genreid`).text(String($("#全商品ディレクトリID").val()));

		// タグID用
		$("#タグID").before(
			$(`<p>選択中のタグ：<span id="${prefix}-tag"></span><p>`)
		);
		$(`#${prefix}-tag`).text(String($("#タグID-text").val()));
		$("#タグID").after(
			$(`<p>タグID：<span id="${prefix}-tagid"></span><p>`)
		);
		$(`#${prefix}-tagid`).text(String($("#タグID").val()));

		// ================================================================================================================

		// タグ取得のAPI
		const rakutenApiUrl: string =
			"https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=";

		// ジャンル>ジャンル>ジャンルの形式のテキストを保持
		let genreText: string = "";
		// 1234567/1234567/1234567みたいにする
		let tagChain: string = "";
		// タグネーム/タグネーム/タグネーム
		let tagText: string = "";

		// 最大タグID数に達していないかをカウントして表示
		let tagCount: number = 32;
		const showTagCount = (tagCount: number): void => {
			$(".tags-count  span").text(tagCount);
		};

		// ジャンルIDをパラメータで渡すことでJSONを返す
		const getRakutenId = (genreId: number) => {
			return $.ajax({
				url: rakutenApiUrl + genreId,
				dataType: "JSON",
			});
		};

		// 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく
		const setRakutenId = (
			genreId: number = 0,
			genreLevel: number = 1
		): void => {
			getRakutenId(genreId).done((res) => {
				// 子のジャンルがなければ終わり
				if (!res.children.length) {
					return;
				}
				// select数字クラスを自動生成
				if (
					!$(`#n2-setpost-rakuten-genreid .select${genreLevel}`)
						.length
				) {
					$("#n2-setpost-rakuten-genreid .select-wrapper").append(
						$(
							`<select class="select${genreLevel}"><option value="" selected>未選択</option></select>`
						)
					);
					$.each(res.children, (index, val) => {
						$(
							`#n2-setpost-rakuten-genreid select.select${genreLevel}`
						).append(
							$(
								`<option value="${val.child.genreId}">${val.child.genreName}</option>`
							)
						);
					});
				}

				// セレクトを変更するとジャンルIDと階層テキストを保持してまたsetRakutenIdをまわす
				$(`#n2-setpost-rakuten-genreid select.select${genreLevel}`).on(
					"change",
					(e) => {
						$("#n2-setpost-rakuten-genreid .result span").text(
							String($(e.target).val())
						);
						$(e.target).nextAll().remove();
						genreText +=
							" > " +
							$(e.target).find($("option:selected")).text();
						genreId = Number($(e.target).val());
						genreLevel++;
						setRakutenId(genreId, genreLevel);
					}
				);
			});
		};

		// genreIdをセットし、tagGroupからtagIdまでとっていく
		const setRakutenTagId = (
			genreId: number = 0,
			tagLevel: number = 1
		): void => {
			getRakutenId(genreId).done((res) => {
				showTagCount(tagCount);

				$.each(res.tagGroups, (index, val) => {
					// 含まれる全グループのブロックを生成
					$(`#n2-setpost-rakuten-tagid .groups`).append(
						$(
							`<div><input type="radio" name="tag-group" id="gid${val.tagGroup.tagGroupId}" value="${val.tagGroup.tagGroupName}"><label for="gid${val.tagGroup.tagGroupId}">${val.tagGroup.tagGroupName}</label></div>`
						)
					);
					$(`#n2-setpost-rakuten-tagid .tags`).append(
						$(`<div class="gid${val.tagGroup.tagGroupId}"></div>`)
					);

					// グループごとのブロック内にタグを配置
					$.each(val.tagGroup.tags, (index, v) => {
						$(
							`#n2-setpost-rakuten-tagid .tags .gid${val.tagGroup.tagGroupId}`
						).append(
							$(
								`<div><input type="checkbox" name="tags" id="tid${v.tag.tagId}" value="${v.tag.tagName}"><label for="tid${v.tag.tagId}">${v.tag.tagName}</label></div>`
							)
						);
					});

					// 全ブロック非表示
					$(`#n2-setpost-rakuten-tagid .tags>*`).css(
						"display",
						"none"
					);
				});

				// グループを選択
				$('#n2-setpost-rakuten-tagid .groups input[type="radio"]').on(
					"click",
					(e) => {
						const gid: number = Number(
							$(e.target).attr("id").replace("gid", "")
						);

						// 表示中のグループブロックを非表示
						$(`#n2-setpost-rakuten-tagid .tags>*`).css(
							"display",
							"none"
						);

						// 選択したグループブロックを表示
						$(`#n2-setpost-rakuten-tagid .tags .gid${gid}`).css(
							"display",
							"block"
						);
					}
				);

				// tagを選択
				$(`#n2-setpost-rakuten-tagid .tags input[name="tags"]`).on(
					"change",
					(e) => {
						const tagId: number = Number(
							$(e.target).attr("id").replace("tid", "")
						);
						const tagName = $(e.target).val();

						// チェック未→済
						if ($(e.target).prop("checked")) {
							if (tagCount !== 0) {
								$(
									"#n2-setpost-rakuten-tagid .result .checked-tags"
								).append(
									$(
										`<div data-tid="${tagId}">${tagId}:${tagName}<span></span></div>`
									)
								);
								tagCount--;
								showTagCount(tagCount);
							} else {
								$(e.target).prop("checked", false);
								alert("32件選択中です。");
							}
							// チェック済→未
						} else {
							$(
								`#n2-setpost-rakuten-tagid .result .checked-tags div[data-tid="${tagId}"]`
							).remove();
							tagCount++;
							showTagCount(tagCount);
						}
					}
				);

				// バツボタンで選択中のタグを削除するとcheckboxも未選択に戻る
				$(document).on(
					"click",
					`#n2-setpost-rakuten-tagid .result .checked-tags div span`,
					(e) => {
						$(`#tid${$(e.target).parent().data("tid")}`).prop(
							"checked",
							false
						);
						$(e.target).parent().remove();
						tagCount++;
						showTagCount(tagCount);
					}
				);
			});
		};

		// ディレクトリID検索スタート
		$(`#${prefix}-genreid-btn`).on("click", (e) => {
			if ($("#タグID").val() !== "") {
				if (
					!confirm(
						"ディレクトリIDを変更すると、下のタグIDがリセットされますのでご注意ください。"
					)
				) {
					return;
				}
			}
			$("#ss_setting").append(
				$('<div id="n2-setpost-rakuten-genreid-wrapper"></div>')
			);
			// テンプレートディレクトリからHTMLをロード
			$("#n2-setpost-rakuten-genreid-wrapper").load(
				neoNengPath(window) +
					"/template/rakuten-genreid.html #n2-setpost-rakuten-genreid",
				() => {
					$("body").css("overflow-y", "hidden");

					// 保持テキストをリセットしてからsetRakutenId回す
					genreText = "";
					setRakutenId();

					// モーダル内の各ボタンの処理制御
					$("#n2-setpost-rakuten-genreid button").on("click", (e) => {
						if ($(e.target).hasClass("clear")) {
							$(
								"#n2-setpost-rakuten-genreid .select-wrapper>*"
							).remove();
							$("#n2-setpost-rakuten-genreid .result span").text(
								"指定なし"
							);
							setRakutenId();
						}
						if (
							$(e.target).hasClass("done") &&
							confirm(
								"選択中のIDをセットしますか？(タグIDがリセットされます)"
							)
						) {
							$(`#${prefix}-genre`).text(genreText);
							$(`#${prefix}-genreid`).text(
								$(
									"#n2-setpost-rakuten-genreid .result span"
								).text()
							);
							$("#全商品ディレクトリID-text").val(genreText);
							$("#全商品ディレクトリID").val(
								Number(
									$(
										"#n2-setpost-rakuten-genreid .result span"
									).text()
								)
							);
							$("#n2-setpost-rakuten-genreid-wrapper").remove();
							$("body").css("overflow-y", "auto");

							$(`#${prefix}-tag`).text("");
							$(`#${prefix}-tagid`).text("");
							$("#タグID-text").val("");
							$("#タグID").val("");
						}
						if (
							$(e.target).hasClass("close") &&
							confirm(
								"選択中のIDはリセットされますがそれでも閉じますか？"
							)
						) {
							$("#n2-setpost-rakuten-genreid-wrapper").remove();
							$("body").css("overflow-y", "auto");
						}
					});
				}
			);
		});

		// タグID検索スタート
		$(`#${prefix}-tagid-btn`).on("click", (e) => {
			if ($("#全商品ディレクトリID").val() === "") {
				alert("ディレクトリIDを選択してから再度お試しください。");
				return;
			}

			$("#ss_setting").append(
				$('<div id="n2-setpost-rakuten-tagid-wrapper"></div>')
			);
			// テンプレートディレクトリからHTMLをロード
			$("#n2-setpost-rakuten-tagid-wrapper").load(
				neoNengPath(window) +
					"/template/rakuten-tagid.html #n2-setpost-rakuten-tagid",
				() => {
					$("body").css("overflow-y", "hidden");

					tagCount = 32;
					showTagCount(tagCount);

					// 保持テキストをリセットしてからsetRakutenId回す
					tagChain = "";
					tagText = "";

					setRakutenTagId(Number($("#全商品ディレクトリID").val()));

					// モーダル内の各ボタンの処理制御
					$("#n2-setpost-rakuten-tagid button").on("click", (e) => {
						if ($(e.target).hasClass("clear")) {
							$("#n2-setpost-rakuten-tagid .tags>*").remove();
							$(
								"#n2-setpost-rakuten-tagid .result .checked-tags>*"
							).remove();
							tagCount = 32;
							showTagCount(tagCount);
							setRakutenTagId(
								Number($("#全商品ディレクトリID").val())
							);
						}
						if (
							$(e.target).hasClass("done") &&
							confirm("選択中のIDをセットしますか？")
						) {
							const chekedTags = $(
								'#n2-setpost-rakuten-tagid .tags input[name="tags"]'
							).filter(":checked");

							$.each(chekedTags, (i, v) => {
								if (i === 0) {
									tagText += $(v).val();
									tagChain += v.id.replace("tid", "");
								} else {
									tagText += "/" + $(v).val();
									tagChain += "/" + v.id.replace("tid", "");
								}
							});

							$(`#${prefix}-tag`).text(tagText);
							$(`#${prefix}-tagid`).text(tagChain);
							$("#タグID-text").val(tagText);
							$("#タグID").val(tagChain);
							$("#n2-setpost-rakuten-tagid-wrapper").remove();
							$("body").css("overflow-y", "auto");
						}
						if (
							$(e.target).hasClass("close") &&
							confirm(
								"選択中のIDはリセットされますがそれでも閉じますか？"
							)
						) {
							$("#n2-setpost-rakuten-tagid-wrapper").remove();
							$("body").css("overflow-y", "auto");
						}
					});
				}
			);
		});

		/** ===============================================================
		 * 
		 * 楽天カテゴリー用
		 * 
		================================================================== */

		$(`#${prefix}-rakutencategory`).append(
			'<option value="">カテゴリーを選択してください</option>'
		);

		const folderCode: string = "1p7DlbhcIEVIaH7Rw2mTmqJJKVDZCumYK";
		const api: string = "https://www.googleapis.com/drive/v3/files/"; // API Request
		const key: string = "AIzaSyDQ1Mu41-8S5kBpZED421bCP8NPE7pneNU";
		let data: { key: string; q: string } = {
			key: key, // Gooleドライブ APIキー
			q: `'${folderCode}' in parents`, // フォルダの中を検索するクエリ
		};
		const town: string = $("#wp-admin-bar-site-name > a").text(); // 自治体名

		$.ajax(api, { data }).done((d) => {
			// .RakutenDataドライブのフォルダの中から該当する自治体のシートのIDを取得（セッションに保存したい）
			const sheetID = d.files.filter(
				(v) =>
					v.name.match(town) &&
					v.mimeType.split(".").slice(-1)[0] == "spreadsheet"
			);
			if (!sheetID.length) return false;
			$.ajax(
				`https://sheets.googleapis.com/v4/spreadsheets/${sheetID[0].id}/values/カテゴリー?key=${key}`
			).done((data) => {
				data = data["values"];
				let cats, lCat, mCat;
				$.each(data, (k: number, v: Array<String>) => {
					// 大カテの有無による大カテ・中カテの処理
					if (v[0]) {
						lCat = v[0].replace(".", ""); // 大カテあればそれ
						mCat = v[1] ? v[1].replace(".", "") : ""; // いったん中カテリセット
					} else {
						lCat = lCat; // 大カテなければ前のを継承
						mCat = v[1] ? v[1].replace(".", "") : mCat; // 中カテあればそれ・なければ継承
					}
					cats =
						"#/" +
						lCat +
						"/" +
						(mCat ? mCat + "/" : "") +
						(v[2] ? v[2].replace(".", "") + "/" : "");
					$(`#${prefix}-rakutencategory`).append(
						'<option value="' +
							cats +
							'" class="rakuten-category-item">' +
							cats +
							"</option>"
					);
				});
			});
			// 選択された項目をtextareaに値として追記していく
			$(`#${prefix}-rakutencategory`).on("change", () => {
				let textarea = $("textarea#楽天カテゴリー");
				let selected = String(
					$(".rakuten-category-item:selected").val()
				);
				selected =
					String(textarea.val()).search(selected) == -1
						? selected
						: ""; // textareaにすでにあったら入れない
				let cat = textarea.val()
					? textarea.val() + (selected ? "\n" + selected : "")
					: selected;

				textarea.val(cat);
			});
		});
		// ここまで楽天カテゴリー ==============================================================================================================================
<<<<<<< HEAD

	});
}
=======
	});
};
>>>>>>> origin/main
