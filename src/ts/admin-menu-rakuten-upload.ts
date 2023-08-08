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
			$card.find("img").attr({
				src: thumbnailUrl,
				alt: file["FileName"],
				"data-url": url,
				"data-file-id": file["FileId"],
				"data-file-size": file["FileSize"],
			});
			$card.find(".card-header .card-text").text(file["FileSize"]);
			$card.find(".card-img-overlay .card-title").text(file["FileName"]);
			$card.find(".card-img-overlay .card-text").text(file["FilePath"]);
			$cardGroup.append($card);
		});
	};

	const initCardGroup = async ($cardGroup, $active) => {
		// files
		await addFiles2CardGroup(
			$cardGroup,
			await getFiles($active.data("id"))
		);
		// dragarea
		const $dragArea = $("#dragable-area-template .dragable-area").clone(
			false
		);
		$cardGroup.append($dragArea);
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
	$("#ss-cabinet-images").css({
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
		await initCardGroup($cardGroup, $active);
		$cardGroup.removeClass("loading");
		$(this).children("i").attr("class", icons[1]);
	});
	// 基本フォルダー開
	$tree.find("li > .folder-open").eq(0).trigger("click");
	// 基本フォルダーのファイルロード
	$tree.find("li > span").eq(0).trigger("click");

	// モーダル制御
	$(document).on("click", ".card-img-top", function () {
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

	// フォルダ削除・作成ボタン
	$(".cabinet-aside button").on("click", async function (elem) {
		const name = $(elem.target).attr("name");
		const data = new FormData();
		const n2nonce = $('[name="n2nonce"]').val();
		// inputやモーダルで設定したい
		const folderName = "test3";
		const directoryName = "test3";

		data.append("action", "n2_rms_cabinet_api_ajax");
		data.append("n2nonce", String(n2nonce));
		data.append("mode", "json");
		switch (name) {
			case "folder_insert":
				data.append("call", name);
				data.append("folderName", folderName);
				data.append("directoryName", directoryName);
				data.append("upperFolderId", $(".tree .active").data("id"));
				break;
			case "trashbox_files_get":
				data.append("call", name);
		}

		// const res = await $.ajax({
		// 	url: window["n2"].ajaxurl,
		// 	type: "POST",
		// 	data: data,
		// 	processData: false,
		// 	contentType: false,
		// });

		// switch (name) {
		// 	case "trashbox_files_get":
		// 		// ボタンをアクティブに。
		// 		$(this).addClass("active");
		// 		// フォルダツリーのアクティブ解除
		// 		$(".tree").find(".active").removeClass("active");
		// 		const $cardGroup = $("#ss-cabinet-images");
		// 		addFiles2CardGroup($cardGroup, res);
		// 		break;
		// 	case "folder_insert":
		// 		if ("OK" === res.status.systemStatus) {
		// 			const folderId = res.cabinetFolderInsertResult.FolderId;
		// 			console.log(folderId);
		// 			const $active = $(".tree").find(".active");
		// 			$active.parent("li").addClass("hasChildren");
		// 			$active.after(`
		// 				<ul class="d-none">
		// 					<li>
		// 						<label class="folder-open">
		// 							<input name="folder-open" type="checkbox">
		// 						</label>
		// 						<span data-path="${$active.data("path")}/${folderName}" data-id="${folderId}">
		// 							<i class="bi bi-folder2-open close"></i>${directoryName}
		// 						</span>
		// 					</li>
		// 				</ul>
		// 			`);
		// 		} else {
		// 			alert(res.status.message);
		// 		}

		// 		break;
		// }
	});
});
