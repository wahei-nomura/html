import "../../node_modules/bootstrap/dist/js/bootstrap";

jQuery(function ($) {
	const getFiles = async (id: string) => {
		return await $.ajax({
			url: window["n2"]["ajaxurl"],
			type: "GET",
			data: {
				action: "n2_rms_cabinet_api_ajax",
				call: "files_get",
				mode: "json",
				folderId: id,
			},
		});
	};

	const addFiles2CardGroup = async ($cardGroup, files) => {
		$cardGroup.empty();
		files.forEach(async (file) => {
			const $card = $("#card-template .card").clone(false);
			const url = file["FileUrl"];
			if (!url) {
				$card.addClass("flex-fill");
				$card.find(".card-header").remove();
				$card.find("img").remove();
				$card
					.find(".card-img-overlay")
					.removeClass("card-img-overlay")
					.addClass("card-body");
				$card.css("max-width", "100%");
				$cardGroup.append($card);
				return;
			}
			let thumbnailUrl = url.replace(
				"image.rakuten.co.jp",
				"thumbnail.image.rakuten.co.jp/@0_mall"
			);
			thumbnailUrl += "?_ex=137x137";
			$card
				.find("img")
				.attr({
					src: thumbnailUrl,
					alt: file["FileName"],
					"data-url": url,
					"data-file-id": file["FileId"],
					"data-file-size": file["FileSize"],
					"data-folder-path": file["FolderPath"],
				})
				.addClass("cabinet-img");
			$card.find(".card-header .card-text").text(file["FileSize"]);
			$card.find(".card-img-overlay .card-title").text(file["FileName"]);
			$card.find(".card-img-overlay .card-text").text(file["FilePath"]);
			$cardGroup.append($card);
		});
	};
	const addFiles2ListTable = async ($listTable, files) => {
		const $table = $listTable.find("table");
		$table.find("tbody").empty();

		files.forEach((file) => {
			if (!file["FileUrl"]) {
				return;
			}
			let thumbnailUrl = file["FileUrl"].replace(
				"image.rakuten.co.jp",
				"thumbnail.image.rakuten.co.jp/@0_mall"
			);
			const $tr = $(`
				<tr>
					<td><input type="checkbox" name="selected"></td>
					<td><img class="cabinet-img" src="${thumbnailUrl}?_ex=50x28"></td>
					<td>${file["FileName"]}</td>
					<td>${file["FileSize"]}</td>
				</tr>
			`);
			$tr.find("img").attr({
				alt: file["FileName"],
				"data-url": file["FileUrl"],
				"data-file-id": file["FileId"],
				"data-file-size": file["FileSize"],
				"data-folder-path": file["FolderPath"],
				"data-bs-toggle": "modal",
				"data-bs-target": "#CabinetModal",
				role: "button",
				decoding: "async",
			});
			$table.find("tbody").append($tr);
		});
	};

	const initCardGroup = async ($cardGroup, $active) => {
		$(".dragable-area").show();
		// files
		const files = await getFiles($active.data("id"));
		await addFiles2CardGroup($cardGroup, files);
		$cardGroup.hide();
		await addFiles2ListTable(
			$cardGroup.siblings("#ss-cabinet-lists"),
			files
		);
		$("#cabinet-navbar-btn").attr("name", "file_delete").text("削除");
		//form
		$("#ss-cabinet form")
			.find("input")
			.each((_, input) => {
				switch (
					$("#ss-cabinet form").find("input").eq(_).attr("name")
				) {
					case "filePath":
						$("#ss-cabinet form")
							.find("input")
							.eq(_)
							.val($active.data("path"));
						break;
					case "folderId":
						$("#ss-cabinet form")
							.find("input")
							.eq(_)
							.val($active.data("id"));
						break;
				}
			});
	};

	// heightを制御
	const top = $("#ss-cabinet .row").offset().top;
	$("main:has(#ss-cabinet-images)").css({
		height: `calc(100vh - ${top}px )`,
	});
	$(".cabinet-aside").css({
		height: `calc(100vh - ${top}px )`,
	});
	const $tree = $(".tree");

	// フォルダーオープン
	$tree.on("change", ".folder-open > input", function () {
		$(this).parent().siblings("ul").toggleClass("d-none");
	});

	// フォルダツリー制御
	$tree.on("click", "li > span", async function (event) {
		const icons = [
			"spinner-border spinner-border-sm",
			"bi bi-folder2-open",
		];
		if (event.target !== this) {
			return;
		}

		const $cardGroup = $("#ss-cabinet-images");
		$("span.active").removeClass("active");
		$(".cabinet-aside button").removeClass("active");
		$(this).children("i").attr("class", icons[0]);
		$cardGroup.addClass("loading");

		$(this).addClass("active");
		const $active = $(this);
		$("#currnet-direcotry").text($active.text());
		await initCardGroup($cardGroup, $active);
		$cardGroup.removeClass("loading");
		$(this).children("i").attr("class", icons[1]);
	});
	// 基本フォルダー開
	$tree.find("li > .folder-open").eq(0).trigger("click");
	// 基本フォルダーのファイルロード
	$tree.find("li > span").eq(0).trigger("click");

	// モーダル制御
	$(document).on("click", ".cabinet-img", function () {
		$("#CabinetModalImage").attr({
			src: $(this).data("url"),
		});
	});

	// drag&drop制御
	{
		$(document).on("dragover", ".dragable-area", function (e) {
			e.preventDefault();
			$(this).addClass("dragover");
		});
		// ドラッグ＆ドロップエリアからドラッグが外れたときのイベントを追加
		$(document).on("dragleave", ".dragable-area", function (e) {
			e.preventDefault();
			$(this).removeClass("dragover");
		});
		// ドラッグ＆ドロップエリアにファイルがドロップされたときのイベントを追加
		$(document).on("drop", ".dragable-area", function (e) {
			e.preventDefault();
			$(this).removeClass("dragover");

			// ドロップされたファイルを取得
			const files = e.originalEvent.dataTransfer.files;
			const $form = $("#ss-cabinet form");
			$form.find('input[type="file"]').prop("files", files);
			const formData = new FormData($form[0] as HTMLFormElement);

			// アップロード
			$.ajax({
				url: window["n2"]["ajaxurl"],
				type: "POST",
				data: formData,
				processData: false, // FormDataを処理しないように設定
				contentType: false, // コンテンツタイプを設定しないように設定
			}).then(async (response) => {
				console.log(response);
				let faildCount = 0;
				Object.keys(response).forEach((index) => {
					const res = response[index];
					if (!res.success) {
						++faildCount;
						const xmlDoc = $.parseXML(res.body);
						const message = $(xmlDoc).find("message").text();
						console.log(message);
					}
				});
				if (faildCount) {
					const alertMessage = [
						faildCount + "件のアップロードに失敗しました。",
						"画像の登録、更新、削除後の情報が反映されるまでの時間は最短10秒です。",
					];
					alert(alertMessage.join("\n"));
				}
				await initCardGroup(
					$("#ss-cabinet-images"),
					$("#ss-cabinet .active")
				);
			});
		});
	}

	// フォルダ新規作成
	$("#folderInsertModal button").on("click", async function (e) {
		e.preventDefault();
		const form = $("#folderInsertModal").find("form")[0];
		$(form)
			.find('[name="upperFolderId"]')
			.val($(".tree .active").data("id"));
		const data = new FormData(form);

		const res = await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
		});

		if ("OK" === res.status.systemStatus) {
			const $active = $(".tree").find(".active");
			const folderId = res.cabinetFolderInsertResult.FolderId;
			const directoryName = data.get("directoryName");
			const folderName = data.get("folderName");
			const folderPath = $active.data("path");
			$active.parent("li").addClass("hasChildren");
			$active.after(`
				<ul class="d-none">
					<li>
						<label class="folder-open">
							<input name="folder-open" type="checkbox">
						</label>
						<span data-path="${folderPath}/${directoryName}" data-id="${folderId}">
							<i class="bi bi-folder2-open close"></i>${folderName}
						</span>
					</li>
				</ul>
			`);
		} else {
			alert(res.status.message);
		}
	});

	// ゴミ箱内のファイルを表示
	$("#show-trashbox-btn").on("click", async function (e) {
		e.preventDefault();
		$("#currnet-direcotry").text("ゴミ箱");
		const form = $(this).parents("form")[0];
		const data = new FormData(form);

		const res = await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
		});

		// ボタンをアクティブに。
		$(this).addClass("active");
		// フォルダツリーのアクティブ解除
		$(".tree").find(".active").removeClass("active");
		$(".dragable-area").hide();
		const $cardGroup = $("#ss-cabinet-images");
		await addFiles2CardGroup($cardGroup, res);
		await addFiles2ListTable($cardGroup.siblings("#ss-cabinet-lists"), res);
		$("#cabinet-navbar-btn")
			.attr("name", "trashbox_files_revert")
			.text("元に戻す");
	});

	//  リストビュー全選択・解除
	$(document).on(
		"click",
		'#ss-cabinet-lists thead tr th:nth-of-type(1) [name="selectedAll"]',
		function () {
			const checked = $(this).prop("checked");
			$('#ss-cabinet-lists tbody [name="selected"]').each((_, input) => {
				$(input)
					.attr("checked", checked)
					.prop("checked", checked)
					.trigger("change");
			});
		}
	);

	// 削除ボタンの挙動
	$(document).on(
		"change",
		"#ss-cabinet-lists tbody tr :nth-of-type(1) input",
		function () {
			const hasChecked = $(
				"#ss-cabinet-lists tbody tr :nth-of-type(1) input"
			).filter((_, input) => {
				return $(input).prop("checked");
			}).length;
			if (hasChecked) {
				$("#cabinet-navbar-btn").removeClass("disabled");
			} else {
				$("#cabinet-navbar-btn").addClass("disabled");
			}
		}
	);

	//　navbar-btn ファイル削除/ゴミ箱から元に戻す
	$("#cabinet-navbar-btn").on("click", async function (e) {
		e.preventDefault();
		const isGrid = $(".view-radio:checked").hasClass("grid-radio");
		let $selected_images: JQuery<HTMLImageElement>;
		switch (isGrid) {
			case true:
				$selected_images = $("#ss-cabinet-images")
					.find('[name="selected"]:checked')
					.parents(".card")
					.find("img");
				break;
			default:
				$selected_images = $("#ss-cabinet-lists")
					.find('[name="selected"]:checked')
					.parents("tr")
					.find("td:nth-of-type(2)")
					.find("img");
				break;
		}
		const form = $(this).parents("form")[0];
		$(form).find('[name="call"]').val($(this).attr("name"));
		const data = new FormData(form);

		// 選択したファイルをFormDataに追加
		$selected_images.each((i, img) => {
			data.append(`fileId[${i}]`, $(img).data("file-id"));
		});

		await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
		})
			.then((response) => {
				let count = 0;
				Object.values(response).forEach((res: any) => {
					if (!res.success) {
						const xmlDoc = $.parseXML(res.body);
						const message = $(xmlDoc).find("message").text();
						alert(message);
					} else {
						++count;
					}
				});
				const alertMessage = [
					count + "件の処理が完了しました。",
					"画像の登録、更新、削除後の情報が反映されるまでの時間は最短10秒です。",
				];
				alert(alertMessage.join("\n"));
			})
			.then(() => {
				if ($(".tree .active").length) {
					$(".tree .active").trigger("click");
				} else if ($("#show-trashbox").hasClass("active")) {
					$("#show-trashbox").trigger("click");
				}
			});
	});

	// ビューモードを変更(カードビュー <=> リストビュー)
	$(".view-radio").on("change", function () {
		// チェックボックスは保持しない
		$("#ss-cabinet-images")
			.find('[name="selected"]')
			.prop("checked", false);
		$("#ss-cabinet-lists").find('[name="selected"]').prop("checked", false);
		// ビューモードを入れ替える
		switch ($(this).hasClass("grid-radio")) {
			case true:
				$("#ss-cabinet-images").removeClass("d-none");
				$("#ss-cabinet-lists").addClass("d-none");
				break;
			default:
				$("#ss-cabinet-images").addClass("d-none");
				$("#ss-cabinet-lists").removeClass("d-none");
				break;
		}
	});

	// リストビューのソート
	$("#ss-cabinet-lists thead th").on("click", function () {
		const index = $(this).index();
		const hasASC = $(this).hasClass("asc");
		if (index < 2) {
			return;
		}
		const defaultIcon = "bi bi-caret-down";
		const icon = ["bi bi-caret-down-fill", "bi bi-caret-up-fill"];
		$("#ss-cabinet-lists thead th")
			.filter((i) => {
				return i !== index;
			})
			.find("i")
			.attr({
				class: defaultIcon,
			});
		if (hasASC) {
			$(this).removeClass("asc").addClass("desc");
		} else {
			$(this).removeClass("desc").addClass("asc");
		}
		$(this)
			.find("i")
			.attr({
				class: icon[Number(hasASC)],
			});

		const $sorted_tr = $("#ss-cabinet-lists tbody tr").sort((a, b) => {
			const a_val = $(a).find("td").eq(index).text();
			const b_val = $(b).find("td").eq(index).text();
			let sort: number;
			if (index === 3) {
				const a_float = parseFloat(a_val);
				const b_float = parseFloat(b_val);
				sort = a_float > b_float ? 1 : -1;
			} else {
				sort = a_val > b_val ? 1 : -1;
			}
			return hasASC ? -sort : sort;
		});
		$("#ss-cabinet-lists tbody").html($sorted_tr);
	});

	// 検索ビュー
});
