import "bootstrap";

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
					"data-file-path": file["FilePath"],
					"data-folder-path": file["FolderPath"],
					"data-time-stamp": file["TimeStamp"],
				})
				.addClass("cabinet-img");
			$card.find(".card-header .card-text").text(file["FileSize"]);
			$card.find(".card-img-overlay .card-title").text(file["FileName"]);
			$card.find(".card-img-overlay .card-text").text(file["FilePath"]);
			$cardGroup.append($card);
		});
		$("#file-count").text(
			$cardGroup.find(".card").filter((i, card) => {
				return (
					$(card).find(".card-text").text() !== "フォルダは空です。"
				);
			}).length
		);
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
					<td>${file["TimeStamp"].split(/\s/)[0].replace(/-/g, "/")}</td>
				</tr>
			`);
			$tr.find("img").attr({
				alt: file["FileName"],
				"data-url": file["FileUrl"],
				"data-file-id": file["FileId"],
				"data-file-size": file["FileSize"],
				"data-file-path": file["FilePath"],
				"data-folder-path": file["FolderPath"],
				"data-time-stamp": file["TimeStamp"],
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
		$(".dragable-area form")
			.find("input")
			.each((_, input) => {
				switch ($(input).attr("name")) {
					case "folderId":
						$(input).val($active.data("id"));
						break;
				}
			});
	};

	const getSelectedImages = (): JQuery<HTMLImageElement> => {
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
		return $selected_images;
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
		$("#cabinet-navbar-btn-dl").parent('form').removeClass('d-none');
		$cardGroup.addClass("loading");

		$(this).addClass("active");
		const $active = $(this);
		$("#current-direcotry").text($active.text());
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
			const $form = $(this).find("form");
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
				let faildCount = 0;
				Object.keys(response).forEach((index) => {
					const res = response[index];
					if (!res.success) {
						++faildCount;
						const xmlDoc = $.parseXML(res.body);
						const message = $(xmlDoc).find("message").text();
						alert(message);
					}
				});
				const alertMessage = [
					Object.keys(response).length -
						faildCount +
						"件アップロードが完了しました。",
					"画像の登録、更新、削除後の情報が反映されるまでの時間は最短10秒です。",
				];
				alert(alertMessage.join("\n"));
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
			const addNewFolder = `
				<li>
					<label class="folder-open">
						<input name="folder-open" type="checkbox">
					</label>
					<span data-path="${folderPath}/${directoryName}" data-id="${folderId}">
						<i class="bi bi-folder2-open close"></i>${folderName}
					</span>
				</li>
			`;
			if ($active.siblings("ul").length) {
				$active.siblings("ul").append(addNewFolder);
			} else {
				$active.after(`
					<ul class="d-none">
						${addNewFolder}
					</ul>
				`);
			}
		} else {
			alert(res.status.message);
		}
	});

	// ゴミ箱内のファイルを表示
	$("#show-trashbox-btn").on("click", async function (e) {
		e.preventDefault();
		$("#current-direcotry").text("ゴミ箱");
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
		$("#cabinet-navbar-btn-dl").parent('form').addClass('d-none');

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

		const form = $(this).parents("form")[0];
		$(form).find('[name="call"]').val($(this).attr("name"));
		const data = new FormData(form);

		// 選択したファイルをFormDataに追加
		const $selected_images = getSelectedImages();
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

		const sorted_tr = $("#ss-cabinet-lists tbody tr")
			.toArray()
			.sort((a, b) => {
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
			})
			.map((element) => element.outerHTML)
			.join("");
		$("#ss-cabinet-lists tbody").html(sorted_tr);
	});

	// 検索
	$("#cabinet-search-btn").on("click", async function (e) {
		e.preventDefault();
		const form = $(this).parents("form")[0];
		const data = new FormData(form);
		const keywords = data.get("keywords") as string;
		data.delete("keywords");
		const splitKeywords = keywords.split(/\s/g).filter((x) => x);
		splitKeywords.forEach((keyword, i) => {
			data.append(`keywords[${i}]`, keyword);
		});
		await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: data,
			processData: false,
			contentType: false,
		}).then(async (response) => {
			const files = Object.values(response).flat();
			const $cardGroup = $("#ss-cabinet-images");
			await addFiles2CardGroup($cardGroup, files);
			await addFiles2ListTable(
				$cardGroup.siblings("#ss-cabinet-lists"),
				files
			);
			$("#cabinet-navbar-btn").attr("name", "file_delete").text("削除");
			$("#current-direcotry").text("検索結果");
			$(".tree .active").removeClass("active");
			$("#show-trashbox-btn").removeClass("active");
		});
	});

	// 右側の詳細情報
	$(document).on(
		"click",
		"#ss-cabinet-images .card, #ss-cabinet-lists tbody tr",
		function () {
			$("#right-aside").show();
			$("#ss-cabinet main").removeClass("col-9").addClass("col-6");
			const $img = $(this).find("img");
			const fileName = $img.attr("alt");
			$("#right-aside-list")
				.find("li")
				.each((index, elem) => {
					const key = $(elem).data("key");
					switch (key) {
						case "FileName":
							$(elem).text($img.attr("alt"));
							break;
						case "FilePath":
							$(elem).text($img.data("file-path"));
							break;
						case "TimeStamp":
							$(elem).text(
								$img
									.data("time-stamp")
									.split(/\s/)[0]
									.replace(/-/g, "/")
							);
							break;
						case "FileSize":
							$(elem).text($img.data("file-size"));
							break;
						case "FileUrl":
							$(elem)
								.find(" > .url-clipboard")
								.attr("value", $img.data("url"));
							break;
						default:
							break;
					}
				});
			$("#right-aside-list-img").attr({
				src: $img.attr("src").split("_ex")[0] + "_ex=200x200",
				alt: $img.attr("alt"),
			});

			const $active = $(
				"#ss-cabinet-images .card, #ss-cabinet-lists tbody tr"
			)
				.removeClass("active table-active")
				.filter((index, elem) => {
					return $(elem).find("img").attr("alt") === fileName;
				});
			$active.each((_, active) => {
				const tag = $(active).prop("tagName");
				switch (tag) {
					case "TR":
						$(active).addClass("table-active");
						break;
					default:
						$(active).addClass("active");
				}
			});
		}
	);
	// クリップボードにコピペ
	$(".url-clipboard").on("click", function () {
		const $this = $(this);
		$this
			.addClass("active")
			.delay(1000)
			.queue(() => {
				$this.removeClass("active").dequeue();
			});
		navigator.clipboard.writeText($(this).attr("value"));
	});

	// 画像DL
	$("#cabinet-navbar-btn-dl").on("click", async function (e) {
		e.preventDefault();
		// 選択したファイルをFormDataに追加
		const $selected_images = getSelectedImages();

		if( ! $selected_images.length ) {
			alert('画像が選択されていません')
			return;
		}

		const form = $(this).parents("form");
		const formData = new FormData(form[0]);
		$selected_images.each((i, img) => {
			formData.append(`url[${i}][url]`, $(img).data("url").replace('https://image.rakuten.co.jp','https://cabinet.rms.rakuten.co.jp/shops'));
			formData.append(`url[${i}][fileName]`, $(img).attr("alt"));
			formData.append(`url[${i}][filePath]`, $(img).data("file-path"));
			formData.append(`url[${i}][folderName]`, $('.tree .active').text().trim());
		});
		const getFormattedDate = ():string => {
			const now = new Date();
			const [year, month, day, hours, minutes] = [
				now.getFullYear(),
				(now.getMonth() + 1).toString().padStart(2, '0'),
				now.getDate().toString().padStart(2, '0'),
				now.getHours().toString().padStart(2, '0'),
				now.getMinutes().toString().padStart(2, '0'),
			];
			return `${year}-${month}-${day}-${hours}-${minutes}`;
		};

		const zipName = `【${window['n2'].town}】楽天Cabinet_${getFormattedDate()}`;
		formData.append('zipName',zipName);

		await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: formData,
			xhrFields: {
				responseType: 'blob'  // レスポンスタイプとしてblobを指定します。
			},
			processData: false,
			contentType: false,
		}).then(async (data) => {
			var url = window.URL.createObjectURL(data);
			// `<a>`タグを作成し、ダウンロードリンクとして使用します。
			const a = document.createElement('a');
			a.href = url;
			a.download = zipName + '.zip';  // ダウンロードされるファイル名を指定します。
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
		});
	});

	// ファイル移動(nav-btn)
	$('#cabinet-navbar-btn-move').on('click',function(){
		const form = $("#filesMoveModal form");
		form.find('input[name="currentFolderName"]').attr('value',$('.tree .active').text().trim())
		form.find('input[name="currentFolderId"]').attr('value',$('.tree .active').data('id'))
	});
	// ファイル移動(modal)
	$("#filesMoveModal button").on('click', async function(e){
		e.preventDefault();

		// 選択したファイルをFormDataに追加
		const $selected_images = getSelectedImages();
		if( ! $selected_images.length ) {
			alert('画像が選択されていません')
			return;
		}
		const form = $(this).parents("form");
		const formData = new FormData(form[0]);
		const targetFolderName = formData.get('targetFolderName')
		const $target = $('.tree span').filter((index,elem)=> {
			return $(elem).text().trim() === targetFolderName;
		});
		if( $target.length !== 1 ) {
			alert('移動先が不明な値です')
			return;
		}
		formData.set('targetFolderId', $target.data('id'))

		$selected_images.each((i, img) => {
			formData.append(`fileId[]`, $(img).data("file-id"));
		});

		await $.ajax({
			url: window["n2"].ajaxurl,
			type: "POST",
			data: formData,
			processData: false,
			contentType: false,
		}).then((response) => {
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
	})
});
