/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/scss/admin-post-lists.scss":
/*!****************************************!*\
  !*** ./src/scss/admin-post-lists.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://neo-neng/./src/scss/admin-post-lists.scss?");

/***/ }),

/***/ "./src/ts/_ajax-dl.ts":
/*!****************************!*\
  !*** ./src/ts/_ajax-dl.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar _functions_1 = __webpack_require__(/*! ./_functions */ \"./src/ts/_functions.ts\"); /**\n * 返礼品一覧ページの画像ダウンロードで使用するAjax用のファイル\n */\njQuery(function ($) {\n    // チェックが入った返礼品のidを配列で返す\n    var getIds = function () {\n        var checkbox = $.makeArray($('input[name=\"post[]\"]'));\n        var checked = checkbox.flatMap(function (v) {\n            return $(v).prop(\"checked\") ? $(v).val() : [];\n        });\n        return checked.length ? checked.join() : \"\";\n    };\n    // loading要素を追加\n    $('#download_img').after('<span class=\"loading_background\"><span id=\"text_loading\"></span><span class=\"progressbar\"></span></span>');\n    var text_loading = document.getElementById(\"text_loading\");\n    $(document).on(\"click\", '.dlbtn', function (e) {\n        $('.loading_background').addClass(\"active\"); // クリックと同時にオーバーレイ要素(loading_background)class付けて二重クリックできないようにする\n        text_loading.textContent = \"登録画像確認中… \"; // #text_loadingのテキスト書き換え(追加)\n        var btnName = $(e.target).attr(\"id\");\n        e.preventDefault();\n        download((0, _functions_1.ajaxUrl)(window), btnName, getIds());\n        setTimeout(function () {\n            $(e.target).removeClass(\"not-click\"); // 2秒待ってから再度クリックできるようにする\n        }, 2000);\n    });\n    // downloadさせる\n    function download(url, action, id) {\n        var data = new FormData();\n        data.append(\"id\", id);\n        var xhr = new XMLHttpRequest();\n        xhr.open(\"POST\", url + \"?action=\" + action, true);\n        xhr.responseType = \"blob\";\n        xhr.onload = function (e) {\n            var blob = this.response;\n            if (blob.size === 0) {\n                alert(\"\\n選択した返礼品全てに画像が登録されていません。\");\n                return;\n            }\n            var a = document.createElement(\"a\");\n            document.body.appendChild(a);\n            a.href = window.URL.createObjectURL(new Blob([blob], { type: blob.type }));\n            a.download = decodeURI(this.getResponseHeader(\"Download-Name\"));\n            a.click();\n            a.remove();\n        };\n        xhr.send(data);\n        downloadProgress(xhr);\n    }\n    function downloadProgress(xhr) {\n        var dlper = '';\n        var dlfontsize = 0;\n        xhr.addEventListener('progress', function (e) {\n            if (e.lengthComputable) {\n                dlper = Math.floor((e.loaded / e.total) * 100) + \"%\";\n                text_loading.textContent = \"ダウンロード中… \" + dlper;\n                $('.progressbar').css('width', dlper);\n            }\n            else {\n                text_loading.textContent = \"読み込み中\";\n            }\n        });\n        xhr.onreadystatechange = function () {\n            if (xhr.readyState === 4 && xhr.status === 200) {\n                $('.loading_background').removeClass(\"active\");\n                text_loading.textContent = \"\";\n                $('.progressbar').css('width', dlper);\n            }\n        };\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_ajax-dl.ts?");

/***/ }),

/***/ "./src/ts/_ajax-rakuten-transfer.ts":
/*!******************************************!*\
  !*** ./src/ts/_ajax-rakuten-transfer.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar _functions_1 = __webpack_require__(/*! ./_functions */ \"./src/ts/_functions.ts\");\n/**\n * 返礼品一覧ページで使用するAjax用のファイル\n */\njQuery(function ($) {\n    $(\".sisfile\").on(\"submit\", function (e) {\n        e.preventDefault();\n        var $this = $(this), fd = new FormData($this[0]), txt = $this.find('[type=\"submit\"]').val();\n        $this.find('[type=\"submit\"]').val(txt.replace(\"転送\", \"転送中...\"));\n        fd.append('action', \"transfer_rakuten\");\n        fd.append('judge', $this.find('[type=\"file\"]').attr('name').replace(\"[]\", \"\"));\n        $.ajax({\n            url: (0, _functions_1.ajaxUrl)(window),\n            type: 'POST',\n            data: fd,\n            dataType: 'html',\n            contentType: false,\n            processData: false,\n            success: function (data) {\n                console.log(data);\n                alert(data);\n                $this.find('[type=\"submit\"]').val(txt);\n            }\n        });\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_ajax-rakuten-transfer.ts?");

/***/ }),

/***/ "./src/ts/_ajax.ts":
/*!*************************!*\
  !*** ./src/ts/_ajax.ts ***!
  \*************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar _functions_1 = __webpack_require__(/*! ./_functions */ \"./src/ts/_functions.ts\");\n/**\n * 返礼品一覧ページで使用するAjax用のファイル\n */\njQuery(function ($) {\n    $(\".sisbtn\").on(\"click\", function (e) {\n        var btnName = $(e.target).attr(\"id\");\n        openByPostAnotherPage((0, _functions_1.ajaxUrl)(window), btnName, getIds());\n        console.log(getIds());\n    });\n    $(document).on(\"click\", '.siserror', function (e) {\n        var btnName = $(e.target).attr(\"id\");\n        openByPostAnotherPage((0, _functions_1.ajaxUrl)(window), btnName, '1');\n        console.log(getIds());\n    });\n    // チェックが入った返礼品のidを配列で返す\n    var getIds = function () {\n        var checkbox = $.makeArray($('input[name=\"post[]\"]'));\n        var checked = checkbox.flatMap(function (v) {\n            return $(v).prop(\"checked\") ? $(v).val() : [];\n        });\n        return checked.length ? checked.join() : \"\";\n    };\n    // POST送信してURLを別タブで開く\n    var openByPostAnotherPage = function (url, btnName, ids) {\n        if (!ids)\n            return;\n        var win = window.open(\"about:blank\", 'n2_another');\n        var form = document.createElement(\"form\");\n        var body = document.getElementsByTagName(\"body\")[0];\n        form.action = url + \"?action=\" + btnName;\n        form.method = \"post\";\n        form.target = \"n2_another\";\n        var input = document.createElement(\"input\");\n        input.type = \"hidden\";\n        input.name = btnName;\n        input.value = ids;\n        form.appendChild(input);\n        body.appendChild(form);\n        form.submit();\n        body.removeChild(form);\n        return win;\n    };\n    // POST送信してURLを開く\n    var openByPost = function (url, btnName, ids) {\n        if (!ids)\n            return;\n        var win = window.open(\"about:blank\", url);\n        var form = document.createElement(\"form\");\n        var body = document.getElementsByTagName(\"body\")[0];\n        form.action = url + \"?action=\" + btnName;\n        form.method = \"post\";\n        var input = document.createElement(\"input\");\n        input.type = \"hidden\";\n        input.name = btnName;\n        input.value = ids;\n        form.appendChild(input);\n        body.appendChild(form);\n        form.submit();\n        body.removeChild(form);\n        return win;\n    };\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_ajax.ts?");

/***/ }),

/***/ "./src/ts/_copypost.ts":
/*!*****************************!*\
  !*** ./src/ts/_copypost.ts ***!
  \*****************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar _functions_1 = __webpack_require__(/*! ./_functions */ \"./src/ts/_functions.ts\");\n/** ===============================================================\n *\n * 投稿複製用\n *\n================================================================== */\njQuery(function ($) {\n    /**\n     * フォーム内の表示やinput内容切り替え\n     * @param teikiNum 定期回数\n     */\n    var formControll = function (teikiNum) {\n        if (teikiNum > 1) {\n            $('.is-teiki').css('display', 'block');\n            $('#n2-copypost-modal .new-title span').text(\"\\u3010\\u5168\".concat(teikiNum, \"\\u56DE\\u5B9A\\u671F\\u4FBF\\u3011\"));\n            $('#n2-copypost-form .is-teiki input').prop('disabled', false);\n        }\n        else {\n            $('.is-teiki').css('display', 'none');\n            $('#n2-copypost-form .is-teiki input').prop('disabled', true);\n            $('#n2-copypost-modal .new-title span').text('');\n        }\n    };\n    // 初回読み込み\n    $(\"#wpbody-content\").append(\"<div id=\\\"\".concat(_functions_1.prefix, \"-content\\\"></div>\"));\n    $(\"#\".concat(_functions_1.prefix, \"-content\")).load((0, _functions_1.neoNengPath)(window) + \"/template/copy-post.php\");\n    /**\n     * 複製用テンプレートにてモーダル表示\n     * @param id\n     * @param title\n     */\n    var setModal = function (id, title) {\n        $('#n2-copypost-modal-wrapper').css('display', 'block');\n        $(\"#n2-copypost-modal .original-title\").text(title);\n        $('input[name=\"複写後商品名\"]').val(title);\n        $(\"#n2-copypost-modal input[name='id']\").val(id);\n        $(\"select[name='定期']>option[value='1']\").prop('selected', true);\n        formControll(1);\n    };\n    // モーダル展開クリックイベント\n    $(\".\".concat(_functions_1.prefix, \"-copypost-btn\")).on(\"click\", function (e) {\n        var itemTr = $(e.target).parent().parent();\n        var originalId = Number(itemTr.find(\"th.check-column input\").val());\n        var itemTitle = itemTr.find(\".item-title a\").text();\n        setModal(originalId, itemTitle);\n    });\n    // 定期便、単品切り替え\n    $('body').on('change', 'select[name=\"定期\"]', function (e) {\n        var teikiNum = +$(e.target).val();\n        formControll(teikiNum);\n    });\n    // モーダルキャンセル\n    $(\"body\").on(\"click\", \"#n2-copypost-modal .close-btn,#n2-copypost-modal-wrapper\", function (e) {\n        if ($(e.target).attr('id') === 'n2-copypost-modal-wrapper' || $(e.target).hasClass('dashicons-no')) {\n            $('#n2-copypost-modal-wrapper').css('display', 'none');\n        }\n    });\n    // 複製submit\n    $(\"body\").on(\"submit\", '#n2-copypost-form', function () {\n        // inputのvalueに空のものがあるか判定\n        if ($('#n2-copypost-form').serializeArray().length && $('#n2-copypost-form').serializeArray().map(function (v) { return v.value; }).includes('')) {\n            alert('全ての項目を入力してください');\n            return false;\n        }\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_copypost.ts?");

/***/ }),

/***/ "./src/ts/_functions.ts":
/*!******************************!*\
  !*** ./src/ts/_functions.ts ***!
  \******************************/
/***/ ((__unused_webpack_module, exports) => {

eval("\n/**\n * 複数ファイルで使いまわしたい変数や関数があればここに\n *\n * 読み込むファイルではimport { prefix, neoNengPath, ajaxUrl } from '../n2-functions/index'を記載\n */\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nexports.homeUrl = exports.ajaxUrl = exports.neoNengPath = exports.prefix = void 0;\n// クラス名にプレフィックスを付けてるところがある\nexports.prefix = \"neo-neng\";\n// PHPからこのテーマのディレクトリパスを受けとっている\nvar neoNengPath = function (window) {\n    return window.tmp_path.tmp_url;\n};\nexports.neoNengPath = neoNengPath;\n// wp_ajax用のパスを受け取っている\nvar ajaxUrl = function (window) {\n    return window.tmp_path.ajax_url;\n};\nexports.ajaxUrl = ajaxUrl;\n// PHPからWordpressのトップパスを受け取っている\nvar homeUrl = function (window) {\n    return window.tmp_path.home_url;\n};\nexports.homeUrl = homeUrl;\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_functions.ts?");

/***/ }),

/***/ "./src/ts/_search.ts":
/*!***************************!*\
  !*** ./src/ts/_search.ts ***!
  \***************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar _functions_1 = __webpack_require__(/*! ./_functions */ \"./src/ts/_functions.ts\");\n/** ===============================================================\n *\n * 検索用\n * 絞り込み検索で事業者を絞り込むと、関連する返礼品コードのみを選択できるようにJS制御\n *\n================================================================== */\njQuery(function ($) {\n    var url = new URL(location.href);\n    var params = url.searchParams;\n    // 返礼品コード監視変更用\n    var changeItemcode = function () {\n        $.ajax({\n            url: (0, _functions_1.ajaxUrl)(window),\n            data: {\n                action: \"N2_Postlist\",\n                事業者: $('#jigyousya-value').val(),\n            },\n        }).done(function (res) {\n            var data = JSON.parse(res);\n            console.log(data);\n            $('select[name=\"返礼品コード[]\"]>*').remove();\n            $('select[name=\"返礼品コード[]\"]').append('<option value=\"\">返礼品コード</option>');\n            Object.keys(data).forEach(function (key) {\n                var selected = params.get(\"返礼品コード\") === key ? \"selected\" : \"\";\n                $('select[name=\"返礼品コード[]\"]').append($(\"<option value=\\\"\".concat(key, \"\\\" \").concat(selected, \">\").concat(data[key], \"</option>\")));\n            });\n        });\n    };\n    // ページ表示時と事業者選択変更時に返礼品コードを監視、変更\n    changeItemcode();\n    // n2-class-postlist.phpのpost_requestのSQLがぐちゃぐちゃなのでいったんor検索コメントアウト　taiki\n    // キーワード検索にOR用チェックボックス\n    // const checked: string = params.get(\"or\") === \"1\" ? \"checked\" : \"\";\n    // $(\"#post-search-input\").before(\n    // \t$(\n    // \t\t`<label style=\"float:left\"><input name=\"or\" value=\"1\" type=\"checkbox\" ${checked}>OR検索</label>`\n    // \t)\n    // );\n    // 事業者絞り込みコンボボックス\n    $('#jigyousya-list-tag').on('change', function (e) {\n        var id = $(\"#jigyousya-list option[value=\\\"\".concat($(e.target).val(), \"\\\"]\")).data('id');\n        $('#jigyousya-value').val(id);\n        changeItemcode();\n    });\n    // 条件クリアボタン\n    $('#ss-search-clear').on('click', function () {\n        $('#posts-filter .actions select[name=\"ステータス\"] option:selected').prop('selected', false);\n        $('#posts-filter .actions select[name=\"定期便\"] option:selected').prop('selected', false);\n        $('#posts-filter .actions input[name=\"事業者\"], #jigyousya-list-tag').val('');\n        $('select[name=\"返礼品コード[]\"]>*').remove();\n        $('select[name=\"返礼品コード[]\"]').append('<option value=\"\">返礼品コード</option>');\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/_search.ts?");

/***/ }),

/***/ "./src/ts/admin-post-lists.js":
/*!************************************!*\
  !*** ./src/ts/admin-post-lists.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _scss_admin_post_lists__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/admin-post-lists */ \"./src/scss/admin-post-lists.scss\");\n/* harmony import */ var _ajax__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_ajax */ \"./src/ts/_ajax.ts\");\n/* harmony import */ var _search__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_search */ \"./src/ts/_search.ts\");\n/* harmony import */ var _copypost__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_copypost */ \"./src/ts/_copypost.ts\");\n/* harmony import */ var _ajax_dl__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./_ajax-dl */ \"./src/ts/_ajax-dl.ts\");\n/* harmony import */ var _ajax_rakuten_transfer__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./_ajax-rakuten-transfer */ \"./src/ts/_ajax-rakuten-transfer.ts\");\n\n\n\n\n\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-post-lists.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./src/ts/admin-post-lists.js");
/******/ 	
/******/ })()
;