import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	jQuery(function ($) {
		const wp = (window) => {
			return window.wp;
		};
		// ブロックエディターレンダリング後にDOM操作して不要なメニュー削除
		$("#editor").ready(() => {
			$('[role="toolbar"]').remove(); //ツールバー
			$('button[aria-label="設定"]').remove(); //設定ボタン
			$('button[aria-label="オプション"]').remove(); //３点リーダー
			$("button.block-editor-post-preview__button-toggle").remove(); //プレビュー表示リンク
			$(".is-root-container.block-editor-block-list__layout").remove(); //タイトル下のブロック
			$(".wp-block-post-title").css("max-width", "95%");
			// プログレストラッカーの表示調整
			$(".interface-interface-skeleton__content").prepend(
				$(`#${prefix}-progress-tracker`)
			);
			$(
				`.${wp(window)
					.data.select("core/editor")
					.getEditedPostAttribute("status")}`
			).addClass("active");

			$(".editor-post-publish-button__button").on("click", (e) => {
				e.preventDefault();

				if ($(e.target).attr("aria-disabled") === "true") return;

				// ここからバリデーション ===========================================================================================================================
				const vError = []; // エラーを溜める

				// アレルゲンは必須
				if (
					$(`input[name="アレルギー有無確認[]"]`)
						.val("アレルギー品目あり")
						.prop("checked")
				) {
					if ($('input[name="アレルゲン[]"]:checked').length === 0) {
						$('input[name="アレルゲン[]"]')
							.parent()
							.parent()
							.parent()
							.css("background-color", "pink");

						$($('input[name="アレルゲン[]"]')[0])
							.parent()
							.parent()
							.before(
								$(
									`<p class="${prefix}-hissu-alert" style="color:red;">※最低１つは選択してください。該当がない場合は上のアレルギー品目ありのチェックをはずしてください。</p>`
								)
							);
						vError.push($('input[name="アレルゲン[]"]')[0]);
					}
				}

				if ($("input#title").val() === "") {
					$("input#title").before(
						$(
							`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`
						)
					);
					$("input#title").css("background-color", "pink");
					vError.push($("input#title"));
				}

				// 必須
				$(`.${prefix}-hissu`).each((i, v) => {
					if ($(v).val() === "") {
						if (
							!$(v).parent().find(`.${prefix}-hissu-alert`).length
						) {
							$(v).before(
								$(
									`<p class="${prefix}-hissu-alert" style="color:red;">※必須項目です</p>`
								)
							);
						}
						$(v).css("background-color", "pink");
						vError.push(v);
					}
				});

				// 0はダメ
				$(`.${prefix}-notzero`).each((i, v) => {
					if (Number($(v).val()) === 0) {
						if (
							!$(v).parent().find(`.${prefix}-notzero-alert`)
								.length
						) {
							$(v).before(
								$(
									`<p class="${prefix}-notzero-alert" style="color:red;">※0以外の値を入力してください。</p>`
								)
							);
						}
						$(v).css("background-color", "pink");
						vError.push(v);
					}
				});

				if (vError.length) {
					alert(
						"入力必須項目が未入力です。入力内容をご確認ください。"
					);
					// 公開をロック
					wp(window)
						.data.dispatch("core/editor")
						.lockPostSaving("my-lock");
					setTimeout(() => {
						$(
							".editor-post-publish-panel__header-cancel-button .components-button.is-secondary"
						).trigger("click");
					}, 10);
				} else {
					// 寄附金額<=価格÷0.4だったらエラー
					if (
						Number($("#寄附金額").val()) <=
						Math.ceil(Number($("#価格").val()) / 400) * 1000
					) {
						if (
							!$("#寄附金額").parent().find(`.${prefix}-alert`)
								.length
						) {
							$("#寄附金額").before(
								$(
									`<p class="${prefix}-alert" style="color:red;">※寄附金額が低すぎます。</p>`
								)
							);
						}
						if (
							!confirm(
								"自動計算された値に対して寄附金額が低すぎます。それでも更新しますか？"
							)
						) {
							// 公開をロック
							wp(window)
								.data.dispatch("core/editor")
								.lockPostSaving("my-lock");
							setTimeout(() => {
								$(
									".editor-post-publish-panel__header-cancel-button .components-button.is-secondary"
								).trigger("click");
							}, 10);
							return;
						}
					}

					// 公開ロックを解除
					wp(window)
						.data.dispatch("core/editor")
						.unlockPostSaving("my-lock");
				}
				// ここまでバリデーション==========================================================================================================================

				if (
					!$("#n2-setpost-check-modal").length &&
					$(e.target).text() === "公開" &&
					!vError.length
				) {
					$("body").css("overflow-y", "hidden");
					e.preventDefault();

					// ここから確認用モーダル==========================================================================================================================

					$.ajax({
						url: ajaxUrl(window),
						data: {
							action: "N2_Setpost",
						},
					}).done((res) => {
						const data = JSON.parse(res);
						console.log(data);
						if (data.ss_crew === "false") {
							$("body").append(
								$(
									'<div id="n2-setpost-check-modal-wrapper"></div>'
								)
							);

							$("#n2-setpost-check-modal-wrapper").load(
								neoNengPath(window) +
									"/template/check-modal.html #n2-setpost-check-modal",
								() => {
									$(
										"#n2-setpost-check-modal .result table"
									).append(
										`<tr><td>返礼品名</td><td>${$(
											"h1.editor-post-title"
										).text()}</td></tr>`
									);
									const inputs = $(
										"#default_setting .n2-input"
									);

									let checkbox = {};
									$.each(inputs, (i, v) => {
										const inputName = $(v).attr("name");
										const tag = v.tagName;

										if (
											(tag === "INPUT" &&
												$(v).attr("type") === "text") ||
											tag === "TEXTAREA"
										) {
											const value: string =
												$(v).val() !== ""
													? String(
															$(v).val()
													  ).replace("\n", "<br>")
													: '<span class="noset">入力なし</span>';
											$(
												"#n2-setpost-check-modal .result table"
											).append(
												`<tr><td>${inputName}</td><td>${value}</td></tr>`
											);
										}

										if (tag === "SELECT") {
											let selected = "未選択";
											$.each(
												$(v).find("option"),
												(i2, v2) => {
													selected =
														$(v2).attr(
															"selected"
														) === "selected" &&
														$(v2).text() !==
															"未選択"
															? $(v2).text()
															: selected;
												}
											);
											selected =
												selected === "未選択"
													? `<span class="noset">${selected}</span>`
													: selected;
											$(
												"#n2-setpost-check-modal .result table"
											).append(
												`<tr><td>${inputName}</td><td>${selected}</td></tr>`
											);
										}

										if (
											tag === "INPUT" &&
											$(v).attr("type") === "checkbox"
										) {
											const checkedName = $(v)
												.parent()
												.text();
											const key = inputName.replace(
												"[]",
												""
											);
											if ($(v).prop("checked")) {
												checkbox[key] =
													checkbox[key] === undefined
														? "" + checkedName
														: (checkbox[key] =
																checkbox[
																	key
																] === undefined
																	? "" +
																	  checkedName
																	: checkbox[
																			key
																	  ] +
																	  "," +
																	  checkedName);
											} else {
												checkbox[key] =
													checkbox[key] ===
														undefined ||
													checkbox[key] === "なし"
														? "なし"
														: checkbox[key].replace(
																"なし,",
																""
														  );
											}
										}
										if (
											tag === "INPUT" &&
											$(v).attr("type") === "hidden"
										) {
											let value =
												$(v).val() !== ""
													? $(v).val()
													: false;
											value =
												value && inputName.match(/画像/)
													? `<img src="${value}" width="100%">`
													: '<span class="noset">なし</span>';
											$(
												"#n2-setpost-check-modal .result table"
											).append(
												`<tr><td>${inputName}</td><td>${value}</td></tr>`
											);
										}
									});

									$.each(checkbox, (k, v) => {
										if (v === "なし") {
											$(
												"#n2-setpost-check-modal .result table"
											).append(
												`<tr><td>${k}</td><td><span class="noset">${v}</span></td></tr>`
											);
										} else {
											$(
												"#n2-setpost-check-modal .result table"
											).append(
												`<tr><td>${k}</td><td>${v}</td></tr>`
											);
										}
									});

									$(
										"#n2-setpost-check-modal button.cancel"
									).on("click", (e) => {
										$(
											"#n2-setpost-check-modal-wrapper"
										).remove();
										$("body").css("overflow-y", "auto");
										$(
											".editor-post-publish-panel__header-cancel-button .components-button.is-secondary"
										).trigger("click");
									});
									$("#n2-setpost-check-modal button.done").on(
										"click",
										(e) => {
											$(e.target).prop("disabled", true);
											$(
												".editor-post-publish-button__button"
											).trigger("click");
											$(
												"#n2-setpost-check-modal-wrapper"
											).remove();
											$("body").css("overflow-y", "auto");
										}
									);
								}
							);
						} // end if(res==='false')
					});
				} // end if(!$('#n2-setpost-check-modal').length)
				// ここまで確認用モーダル==========================================================================================================================
			});
		});

		// inputにmaxlengthが設定されているもののみ入力中の文字数表示
		$(
			"#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea"
		).each((i, v) => {
			if ($(v).attr("maxlength")) {
				$(v)
					.parent()
					.append($(`<p>${String($(v).val()).length}文字</p>`));
				$(v).on("keyup", () => {
					$(v)
						.parent()
						.find("p")
						.text(String($(v).val()).length + "文字");
				});
			}
		});
	});
};
