/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/ts/admin-no-n2-caution.ts":
/*!***************************************!*\
  !*** ./src/ts/admin-no-n2-caution.ts ***!
  \***************************************/
/***/ (() => {

eval("jQuery(function ($) {\n    /**\n     * n2_active_flagがfalseの時に注意文を出す\n     */\n    var n2 = window['n2'];\n    var cautionBox = $('<a class=\"no_active_caution\" onclick=\"this.remove()\">N2未稼働 更新作業はN1で行って下さい</a>');\n    if (!n2.n2_active_flag) {\n        $('#wpwrap').append(cautionBox);\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-no-n2-caution.ts?");

/***/ }),

/***/ "./src/ts/admin-qaform.ts":
/*!********************************!*\
  !*** ./src/ts/admin-qaform.ts ***!
  \********************************/
/***/ (() => {

eval("jQuery(function ($) {\n    /**\n     * 事業者アカウントログイン時に右下にGoogleフォームのリンクを常時表示\n     */\n    var n2 = window['n2'];\n    var formLink = $('<a href=\"https://docs.google.com/forms/d/e/1FAIpQLScbze4H3puDboZ0zEZ_vfx7EzpiV0KJFeKFjFnGjymxqekw5Q/viewform\" target=\"_blank\"><span class=\"dashicons dashicons-warning\"></span>システムの不具合はこちら</a>');\n    formLink.css({\n        'position': 'fixed ',\n        'bottom': '10px ',\n        'right': '10px ',\n        'z-index': '99999 ',\n        'display': 'flex ',\n        'justify-content': 'center ',\n        'align-items': 'center ',\n        'text-align': 'center ',\n        'color': '#fff ',\n        'font-size': '13px',\n        'background-color': '#b2292c ',\n        'border-radius': '4px',\n        'box-shadow': '0 3px 5px rgba(0, 0, 0, 0.3)',\n        'padding': '4px ',\n        'text-decoration': 'none',\n    });\n    if (n2.current_user.roles[0] !== 'administrator') {\n        $('#wpwrap').append(formLink);\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-qaform.ts?");

/***/ }),

/***/ "./src/ts/admin.js":
/*!*************************!*\
  !*** ./src/ts/admin.js ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _admin_qaform__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./admin-qaform */ \"./src/ts/admin-qaform.ts\");\n/* harmony import */ var _admin_qaform__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_admin_qaform__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _admin_no_n2_caution__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./admin-no-n2-caution */ \"./src/ts/admin-no-n2-caution.ts\");\n/* harmony import */ var _admin_no_n2_caution__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_admin_no_n2_caution__WEBPACK_IMPORTED_MODULE_1__);\n\n\n\njQuery(function ($) {\n\t$(\"#wp-admin-bar-my-sites\").off(\"mouseenter mouseleave\");\n});\n\nrefererjump();\n\nfunction refererjump(){\n\n\t// 自治体跨ぎで飛んできたら現自治体の同じページに飛ばす\n\tlet ref_url = document.referrer;\n\tlet ref_url_list = ref_url.split('/');\n\tlet jump_url = '';\n\tconst now_url_href = location.href;\n\tconst now_url_href_list = now_url_href.split('/');\n\tif(ref_url_list[3] ===  now_url_href_list[3]){\n\t\treturn; // 同サイト同士なら終了\n\t}\n\tconst now_url_pathname = location.pathname;\n\tlet now_url_pathname_list = now_url_pathname.split('/');\n\tlet now_jichitai_name = ''\n\tif( 'wp-admin' === now_url_pathname_list[2] ){\n\t\tnow_jichitai_name = now_url_pathname_list[1];\n\t}\n\tconst n2_url = ref_url_list[0] + '//' + ref_url_list[2]; // 0:https, 1:空白, 2:ドメインurl\n\tlet ref_jichitai_name = '';\n\n\tref_url_list.forEach(function(v,i){\n\t\tif( 0 === i ){\n\t\t\tjump_url += v;\n\t\t} else {\n\t\t\tif( 'wp-admin' !== v && 3 === i ){ // 3がwp-adminはサイト管理なので除外\n\t\t\t\tref_jichitai_name = ref_url_list[3];\n\t\t\t\tjump_url += '/' + now_jichitai_name;\n\t\t\t}else{\n\t\t\t\tjump_url += '/' + v; \n\t\t\t}\n\t\t}\n\n\t});\n\tlocation.href = jump_url; // ページジャンプ\n}\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin.js?");

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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
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
/******/ 	var __webpack_exports__ = __webpack_require__("./src/ts/admin.js");
/******/ 	
/******/ })()
;