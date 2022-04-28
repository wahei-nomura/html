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

/***/ "./src/scss/n2-postlist.scss":
/*!***********************************!*\
  !*** ./src/scss/n2-postlist.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://neo-neng/./src/scss/n2-postlist.scss?");

/***/ }),

/***/ "./src/scss/n2-setpost.scss":
/*!**********************************!*\
  !*** ./src/scss/n2-setpost.scss ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://neo-neng/./src/scss/n2-setpost.scss?");

/***/ }),

/***/ "./src/ts/index.ts":
/*!*************************!*\
  !*** ./src/ts/index.ts ***!
  \*************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

eval("\nvar __importDefault = (this && this.__importDefault) || function (mod) {\n    return (mod && mod.__esModule) ? mod : { \"default\": mod };\n};\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\n__webpack_require__(/*! ../scss/n2-postlist.scss */ \"./src/scss/n2-postlist.scss\");\n__webpack_require__(/*! ../scss/n2-setpost.scss */ \"./src/scss/n2-setpost.scss\");\nvar n2_setpost_1 = __importDefault(__webpack_require__(/*! ./n2-setpost */ \"./src/ts/n2-setpost.ts\"));\n// 返礼品編集画面\nif (location.href.match(/(post|post-new)\\.php/)) {\n    (0, n2_setpost_1.default)();\n}\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/index.ts?");

/***/ }),

/***/ "./src/ts/n2-setpost.ts":
/*!******************************!*\
  !*** ./src/ts/n2-setpost.ts ***!
  \******************************/
/***/ ((__unused_webpack_module, exports) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nexports[\"default\"] = (function () {\n    jQuery(function ($) {\n        // クラスにテーマ名をprefixつける\n        var prefix = 'neo-neng';\n        var neoNengPath = function (window) {\n            return window.tmp_path.tmp_url;\n        };\n        // 返礼品編集画面\n        $('form').on('submit', function (e) {\n            var vError = [];\n            $(\".\".concat(prefix, \"-hissu\")).each(function (i, v) {\n                if ($(v).val() === '') {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-hissu-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-hissu-alert\\\" style=\\\"color:red;\\\">\\u203B\\u5FC5\\u9808\\u9805\\u76EE\\u3067\\u3059</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            $(\".\".concat(prefix, \"-notzero\")).each(function (i, v) {\n                if (Number($(v).val()) === 0) {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-notzero-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-notzero-alert\\\" style=\\\"color:red;\\\">\\u203B0\\u4EE5\\u5916\\u306E\\u5024\\u3092\\u5165\\u529B\\u3057\\u3066\\u304F\\u3060\\u3055\\u3044\\u3002</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            if (vError.length) {\n                alert('入力内容をご確認ください。');\n                e.preventDefault();\n                return false;\n            }\n        });\n        // inputにmaxlengthが設定されているもののみ入力中の文字数表示\n        $('#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea').each(function (i, v) {\n            if ($(v).attr('maxlength')) {\n                $(v).parent().append($(\"<p>\".concat(String($(v).val()).length, \"\\u6587\\u5B57</p>\")));\n                $(v).on('keyup', function () {\n                    $(v).parent().find('p').text(String($(v).val()).length + '文字');\n                });\n            }\n        });\n        /**\n         *  wordpressのメディアアップロード呼び出し\n         */\n        var wpMedia = function (title, btnText, type, window) {\n            var wp = window.wp;\n            return wp.media({\n                title: title,\n                button: {\n                    text: btnText\n                },\n                library: {\n                    type: type\n                },\n                multiple: false\n            });\n        };\n        //imageアップローダーボタン \n        $(\".\".concat(prefix, \"-media-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('画像を選択', '画像を設定', 'image', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var images = customUploader.state().get(\"selection\");\n                images.each(function (image) {\n                    parent.find(\".\".concat(prefix, \"-image-url\")).attr('src', image.attributes.url);\n                    parent.find(\".\".concat(prefix, \"-image-input\")).val(image.attributes.url);\n                });\n            });\n        });\n        //zipアップローダーボタン \n        $(\".\".concat(prefix, \"-zip-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('zipファイルを選択', 'zipファイルを設定', 'application/zip', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var zips = customUploader.state().get(\"selection\");\n                console.log(zips);\n                zips.each(function (zip) {\n                    console.log(zip);\n                    parent.find(\".\".concat(prefix, \"-zip-url\")).text(\"\".concat(zip.attributes.filename, \"\\u3092\\u9078\\u629E\\u4E2D\"));\n                    parent.find(\".\".concat(prefix, \"-zip-input\")).val(zip.attributes.url);\n                });\n            });\n        });\n        /** ===============================================================\n         *\n         * 楽天タグID用\n         *\n        ================================================================== */\n        // タグ取得のAPI\n        var rakutenApiUrl = 'https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=';\n        // ジャンル>ジャンル>ジャンルの形式のテキストを保持\n        var genreText = '';\n        var tagText = '';\n        // ジャンルIDをパラメータで渡すことでJSONを返す\n        var getRakutenId = function (genreId) {\n            var url = \"https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=\".concat(genreId);\n            return $.ajax({\n                url: url,\n                dataType: 'JSON',\n            });\n        };\n        // 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく\n        var setRakutenId = function (genreId, genreLevel) {\n            if (genreId === void 0) { genreId = 0; }\n            if (genreLevel === void 0) { genreLevel = 1; }\n            getRakutenId(genreId).done(function (res) {\n                // 子のジャンルがなければ終わり\n                if (!res.children.length) {\n                    return;\n                }\n                // select数字クラスを自動生成\n                if (!$(\"#n2-setpost-rakuten-genreid .select\".concat(genreLevel)).length) {\n                    $('#n2-setpost-rakuten-genreid .select-wrapper').append($(\"<select class=\\\"select\".concat(genreLevel, \"\\\"><option value=\\\"\\\" selected>\\u672A\\u9078\\u629E</option></select>\")));\n                    $.each(res.children, function (index, val) {\n                        $(\"#n2-setpost-rakuten-genreid select.select\".concat(genreLevel)).append($(\"<option value=\\\"\".concat(val.child.genreId, \"\\\">\").concat(val.child.genreName, \"</option>\")));\n                    });\n                }\n                // セレクトを変更するとジャンルIDと階層テキストを保持してまたsetRakutenIdをまわす\n                $(\"#n2-setpost-rakuten-genreid select.select\".concat(genreLevel)).on('change', function (e) {\n                    $('#n2-setpost-rakuten-genreid .result span').text(String($(e.target).val()));\n                    $(e.target).nextAll().remove();\n                    genreText += ' > ' + $(e.target).find($('option:selected')).text();\n                    genreId = Number($(e.target).val());\n                    genreLevel++;\n                    setRakutenId(genreId, genreLevel);\n                });\n            });\n        };\n        // 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく\n        var setRakutenTagId = function (genreId, tagLevel) {\n            if (genreId === void 0) { genreId = 0; }\n            if (tagLevel === void 0) { tagLevel = 1; }\n            getRakutenId(genreId).done(function (res) {\n                console.log(res.tagGroups);\n                $.each(res.tagGroups, function (index, val) {\n                    $(\"#n2-setpost-rakuten-tagid .groups\").append($(\"<div><input type=\\\"radio\\\" name=\\\"tag-group\\\" id=\\\"gid\".concat(val.tagGroup.tagGroupId, \"\\\"><label for=\\\"gid\").concat(val.tagGroup.tagGroupId, \"\\\">\").concat(val.tagGroup.tagGroupName, \"</label></div>\")));\n                });\n                $('#n2-setpost-rakuten-tagid .groups input[type=\"radio\"]').on('click', function (e) {\n                    var gid = Number($(e.target).attr('id').replace('gid', ''));\n                    // クリックしたradioからグループID取得して下層tagを表示する\n                    var tags = res.tagGroups.filter(function (v) { return v.tagGroup.tagGroupId === gid ? v : null; })[0].tagGroup.tags;\n                    console.log(tags);\n                    $.each(tags, function (index, val) {\n                        $(\"#n2-setpost-rakuten-tagid .tags\").append($(\"<div><input type=\\\"checkbox\\\">\".concat(val.tag.tagName, \"</div>\")));\n                    });\n                });\n            });\n        };\n        // JS読み込んだ時点で、表示用のタグを生成する ============================================================================\n        // ディレクトリID用\n        $('#全商品ディレクトリID').before($(\"<p>\\u30C7\\u30A3\\u30EC\\u30AF\\u30C8\\u30EA\\u968E\\u5C64\\uFF1A<span id=\\\"\".concat(prefix, \"-genre\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-genre\")).text(String($('#全商品ディレクトリID-text').val()));\n        $('#全商品ディレクトリID').after($(\"<p>\\u30C7\\u30A3\\u30EC\\u30AF\\u30C8\\u30EAID\\uFF1A<span id=\\\"\".concat(prefix, \"-genreid\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-genreid\")).text(String($('#全商品ディレクトリID').val()));\n        // タグID用\n        $('#楽天タグID').before($(\"<p>\\u9078\\u629E\\u4E2D\\u306E\\u30BF\\u30B0\\uFF1A<span id=\\\"\".concat(prefix, \"-tag\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-tag\")).text(String($('#楽天タグID-text').val()));\n        $('#楽天タグID').after($(\"<p>\\u30BF\\u30B0ID\\uFF1A<span id=\\\"\".concat(prefix, \"-tagid\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-tagid\")).text(String($('#楽天タグID').val()));\n        // ================================================================================================================\n        // ディレクトリID検索スタート\n        $(\"#\".concat(prefix, \"-genreid-btn\")).on('click', function (e) {\n            $('#ss_setting').append($('<div id=\"n2-setpost-rakuten-genreid-wrapper\"></div>'));\n            // テンプレートディレクトリからHTMLをロード\n            $('#n2-setpost-rakuten-genreid-wrapper').load(neoNengPath(window) + '/template/rakuten-genreid.html #n2-setpost-rakuten-genreid', function () {\n                // 保持テキストをリセットしてからsetRakutenId回す\n                genreText = '';\n                setRakutenId();\n                // モーダル内の各ボタンの処理制御\n                $('#n2-setpost-rakuten-genreid button').on('click', function (e) {\n                    if ($(e.target)[0].className === 'clear') {\n                        $('#n2-setpost-rakuten-genreid .select-wrapper>*').remove();\n                        $('#n2-setpost-rakuten-genreid .result span').text('指定なし');\n                        setRakutenId();\n                    }\n                    if ($(e.target)[0].className === 'done' && confirm('選択中のIDをセットしますか？')) {\n                        $(\"#\".concat(prefix, \"-genre\")).text(genreText);\n                        $(\"#\".concat(prefix, \"-genreid\")).text($('#n2-setpost-rakuten-genreid .result span').text());\n                        $('#全商品ディレクトリID-text').val(genreText);\n                        $('#全商品ディレクトリID').val(Number($('#n2-setpost-rakuten-genreid .result span').text()));\n                        $('#n2-setpost-rakuten-genreid-wrapper').remove();\n                    }\n                    if ($(e.target)[0].className === 'close' && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {\n                        $('#n2-setpost-rakuten-genreid-wrapper').remove();\n                    }\n                });\n            });\n        });\n        // タグID検索スタート\n        $(\"#\".concat(prefix, \"-tagid-btn\")).on('click', function (e) {\n            if ($('#全商品ディレクトリID').val() === '') {\n                alert('ディレクトリIDを選択してから再度お試しください。');\n                return;\n            }\n            $('#ss_setting').append($('<div id=\"n2-setpost-rakuten-tagid-wrapper\"></div>'));\n            // テンプレートディレクトリからHTMLをロード\n            $('#n2-setpost-rakuten-tagid-wrapper').load(neoNengPath(window) + '/template/rakuten-tagid.html #n2-setpost-rakuten-tagid', function () {\n                // 保持テキストをリセットしてからsetRakutenId回す\n                tagText = '';\n                setRakutenTagId(Number($('#全商品ディレクトリID').val()));\n                // モーダル内の各ボタンの処理制御\n                $('#n2-setpost-rakuten-tagid button').on('click', function (e) {\n                    if ($(e.target)[0].className === 'clear') {\n                        $('#n2-setpost-rakuten-tagid .tags>*').remove();\n                        $('#n2-setpost-rakuten-tagid .result span').text('指定なし');\n                        // setRakutenId();\n                    }\n                    if ($(e.target)[0].className === 'done' && confirm('選択中のIDをセットしますか？')) {\n                        $(\"#\".concat(prefix, \"-tag\")).text(genreText);\n                        $(\"#\".concat(prefix, \"-tagid\")).text($('#n2-setpost-rakuten-tagid .result span').text());\n                        $('#楽天タグID-text').val(genreText);\n                        $('#楽天タグID').val(Number($('#n2-setpost-rakuten-tagid .result span').text()));\n                        $('#n2-setpost-rakuten-tagid-wrapper').remove();\n                    }\n                    if ($(e.target)[0].className === 'close' && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {\n                        $('#n2-setpost-rakuten-tagid-wrapper').remove();\n                    }\n                });\n            });\n        });\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/n2-setpost.ts?");

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
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
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
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = __webpack_require__("./src/ts/index.ts");
/******/ 	
/******/ })()
;