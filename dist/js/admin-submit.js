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

/***/ "./src/ts/admin-submit.ts":
/*!********************************!*\
  !*** ./src/ts/admin-submit.ts ***!
  \********************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nvar functions_1 = __webpack_require__(/*! ./modules/functions */ \"./src/ts/modules/functions.ts\");\njQuery(function ($) {\n    console.log('submit.ts読み込み中');\n    // 各種セットアップの更新\n    $('.sissubmit').on('click', function (e) {\n        e.preventDefault();\n        var $this = $(this), data = $this.parents('form').serialize();\n        if (!$this.parents('form')[0].reportValidity()) {\n            alert(\"入力されていない項目があります\");\n            return false;\n        }\n        if ((0, functions_1.ajaxUrl)(window)) {\n            $this.val(\"　更新中...　\");\n            $.ajax({\n                type: \"POST\",\n                url: (0, functions_1.ajaxUrl)(window),\n                data: data,\n            })\n                .done(function (data) {\n                console.log(data);\n                alert(\"更新完了！\");\n                $this.val(\"　更新する　\");\n            });\n        }\n        else {\n            alert('更新に失敗しました');\n        }\n        return false;\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-submit.ts?");

/***/ }),

/***/ "./src/ts/modules/functions.ts":
/*!*************************************!*\
  !*** ./src/ts/modules/functions.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, exports) => {

eval("\n/**\n * 複数ファイルで使いまわしたい変数や関数があればここに\n *\n * 読み込むファイルではimport { prefix, neoNengPath, ajaxUrl } from '../n2-functions/index'を記載\n */\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nexports.homeUrl = exports.ajaxUrl = exports.neoNengPath = exports.prefix = void 0;\n// クラス名にプレフィックスを付けてるところがある\nexports.prefix = \"neo-neng\";\n// PHPからこのテーマのディレクトリパスを受けとっている\nvar neoNengPath = function (window) {\n    return window.tmp_path.tmp_url;\n};\nexports.neoNengPath = neoNengPath;\n// wp_ajax用のパスを受け取っている\nvar ajaxUrl = function (window) {\n    return window.tmp_path.ajax_url;\n};\nexports.ajaxUrl = ajaxUrl;\n// PHPからWordpressのトップパスを受け取っている\nvar homeUrl = function (window) {\n    return window.tmp_path.home_url;\n};\nexports.homeUrl = homeUrl;\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/modules/functions.ts?");

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
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./src/ts/admin-submit.ts");
/******/ 	
/******/ })()
;