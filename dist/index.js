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

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nexports[\"default\"] = (function () {\n    jQuery(function ($) {\n        // クラスにテーマ名をprefixつける\n        var prefix = 'neo-neng';\n        var neoNengPath = function (window) {\n            return window.tmp_path.tmp_url;\n        };\n        // 返礼品編集画面\n        $('form').on('submit', function (e) {\n            var vError = [];\n            $(\".\".concat(prefix, \"-hissu\")).each(function (i, v) {\n                if ($(v).val() === '') {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-hissu-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-hissu-alert\\\" style=\\\"color:red;\\\">\\u203B\\u5FC5\\u9808\\u9805\\u76EE\\u3067\\u3059</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            $(\".\".concat(prefix, \"-notzero\")).each(function (i, v) {\n                if (Number($(v).val()) === 0) {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-notzero-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-notzero-alert\\\" style=\\\"color:red;\\\">\\u203B0\\u4EE5\\u5916\\u306E\\u5024\\u3092\\u5165\\u529B\\u3057\\u3066\\u304F\\u3060\\u3055\\u3044\\u3002</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            if (vError.length) {\n                alert('入力内容をご確認ください。');\n                e.preventDefault();\n                return false;\n            }\n        });\n        // inputにmaxlengthが設定されているもののみ入力中の文字数表示\n        $('#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea').each(function (i, v) {\n            if ($(v).attr('maxlength')) {\n                $(v).parent().append($(\"<p>\".concat(String($(v).val()).length, \"\\u6587\\u5B57</p>\")));\n                $(v).on('keyup', function () {\n                    $(v).parent().find('p').text(String($(v).val()).length + '文字');\n                });\n            }\n        });\n        /**\n         *  wordpressのメディアアップロード呼び出し\n         */\n        var wpMedia = function (title, btnText, type, window) {\n            var wp = window.wp;\n            return wp.media({\n                title: title,\n                button: {\n                    text: btnText\n                },\n                library: {\n                    type: type\n                },\n                multiple: false\n            });\n        };\n        //imageアップローダーボタン \n        $(\".\".concat(prefix, \"-media-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('画像を選択', '画像を設定', 'image', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var images = customUploader.state().get(\"selection\");\n                images.each(function (image) {\n                    parent.find(\".\".concat(prefix, \"-image-url\")).attr('src', image.attributes.url);\n                    parent.find(\".\".concat(prefix, \"-image-input\")).val(image.attributes.url);\n                });\n            });\n        });\n        //zipアップローダーボタン \n        $(\".\".concat(prefix, \"-zip-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('zipファイルを選択', 'zipファイルを設定', 'application/zip', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var zips = customUploader.state().get(\"selection\");\n                console.log(zips);\n                zips.each(function (zip) {\n                    console.log(zip);\n                    parent.find(\".\".concat(prefix, \"-zip-url\")).text(\"\".concat(zip.attributes.filename, \"\\u3092\\u9078\\u629E\\u4E2D\"));\n                    parent.find(\".\".concat(prefix, \"-zip-input\")).val(zip.attributes.url);\n                });\n            });\n        });\n        /** ===============================================================\n         *\n         * 楽天ID\n         *\n        ================================================================== */\n        // タグ取得のAPI\n        var rakutenApiUrl = 'https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=';\n        // ジャンルIDをパラメータで渡すことでJSONを返す\n        var getRakutenId = function (genreId) {\n            var url = \"https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=\".concat(genreId);\n            return $.ajax({\n                url: url,\n                dataType: 'JSON',\n            });\n        };\n        // とりあえず画面に突っ込んでるだけ。あとでボタンと連動させる\n        $('#ss_setting').append($('<div id=\"n2-setpost-rakuten-genleid-wrapper\"></div>'));\n        $('#n2-setpost-rakuten-genleid-wrapper').load(neoNengPath(window) + '/template/rakuten-genleid.html');\n        // 初期値\n        var genleId = 0;\n        var genleLevel = 1;\n        var setRakutenId = function (genleId, genleLevel) {\n            getRakutenId(genleId).done(function (res) {\n                if (!res.children.length) {\n                    return;\n                }\n                if (!$(\"#n2-setpost-rakuten-genleid .select\".concat(genleLevel)).length) {\n                    $('#n2-setpost-rakuten-genleid .select-wrapper').append($(\"<select class=\\\"select\".concat(genleLevel, \"\\\"><option value=\\\"\\\" selected>\\u672A\\u9078\\u629E</option></select>\")));\n                    $.each(res.children, function (index, val) {\n                        $(\"#n2-setpost-rakuten-genleid select.select\".concat(genleLevel)).append($(\"<option value=\\\"\".concat(val.child.genreId, \"\\\">\").concat(val.child.genreName, \"</option>\")));\n                    });\n                }\n                $(\"#n2-setpost-rakuten-genleid select.select\".concat(genleLevel)).on('change', function (e) {\n                    $('#n2-setpost-rakuten-genleid .result span').text(String($(e.target).val()));\n                    $(e.target).nextAll().remove();\n                    genleId = $(e.target).val();\n                    genleLevel++;\n                    setRakutenId(genleId, genleLevel);\n                });\n                $('#n2-setpost-rakuten-genleid button').on('click', function (e) {\n                    if ($(e.target)[0].className === 'clear') {\n                        $('#n2-setpost-rakuten-genleid .select-wrapper>*').remove();\n                        $('#n2-setpost-rakuten-genleid .result span').text('指定なし');\n                        genleId = 0;\n                        genleLevel = 1;\n                        setRakutenId(genleId, genleLevel);\n                    }\n                });\n            });\n        };\n        setRakutenId(genleId, genleLevel);\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/n2-setpost.ts?");

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