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

/***/ "./src/ts/modules/admin-bar-menu-self-destruct.ts":
/*!********************************************************!*\
  !*** ./src/ts/modules/admin-bar-menu-self-destruct.ts ***!
  \********************************************************/
/***/ (() => {

eval("/**\n * 自爆ボタン\n */\njQuery(function ($) {\n    var destruct_self_account = function () {\n        if (!confirm('アカウントを削除します。続けますか？')) {\n            return;\n        }\n        if (!confirm('本当に辞めるんですか？もう一度考えてください！')) {\n            return;\n        }\n        if (!confirm('おつかれさまでした。ところで、本当に削除してもいいんですよね？')) {\n            return;\n        }\n        if (!confirm('後悔はありませんか？まだ間に合いますよ！')) {\n            return;\n        }\n        if (!confirm('これで最後です。本当にアカウントを削除しますか？\\nよーく考えてからボタンをクリックしてくださいね！')) {\n            return;\n        }\n        window.addEventListener('hashchange', function () {\n            var params = {\n                action: 'n2_user_destruct_self_account',\n            };\n            var urlSearchParam = new URLSearchParams(params).toString();\n            var data = {\n                id: window['n2'].current_user.ID,\n                n2nonce: location.hash.replace('#', ''),\n            };\n            $.ajax({\n                url: window['n2'].ajaxurl + '?' + urlSearchParam,\n                type: 'POST',\n                data: data,\n            }).then(function (res) {\n                alert(res);\n                location.reload();\n            });\n        });\n    };\n    $('#wp-admin-bar-destruct-self').on('click', destruct_self_account);\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/modules/admin-bar-menu-self-destruct.ts?");

/***/ }),

/***/ "./src/ts/modules/admin-qaform.ts":
/*!****************************************!*\
  !*** ./src/ts/modules/admin-qaform.ts ***!
  \****************************************/
/***/ (() => {

eval("jQuery(function ($) {\n    /**\n     * 事業者アカウントログイン時に右下にGoogleフォームのリンクを常時表示\n     */\n    var n2 = window['n2'];\n    var formLink = $('<a href=\"https://docs.google.com/forms/d/e/1FAIpQLScbze4H3puDboZ0zEZ_vfx7EzpiV0KJFeKFjFnGjymxqekw5Q/viewform\" target=\"_blank\"><span class=\"dashicons dashicons-warning\"></span>システムの不具合はこちら</a>');\n    formLink.css({\n        'position': 'fixed ',\n        'bottom': '10px ',\n        'right': '10px ',\n        'z-index': '99999 ',\n        'display': 'flex ',\n        'justify-content': 'center ',\n        'align-items': 'center ',\n        'text-align': 'center ',\n        'color': '#fff ',\n        'font-size': '13px',\n        'background-color': '#b2292c ',\n        'border-radius': '4px',\n        'box-shadow': '0 3px 5px rgba(0, 0, 0, 0.3)',\n        'padding': '4px ',\n        'text-decoration': 'none',\n    });\n    if (n2.current_user.roles[0] !== 'administrator' && window == window.parent) {\n        $('#wpwrap').append(formLink);\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/modules/admin-qaform.ts?");

/***/ }),

/***/ "./src/ts/admin.js":
/*!*************************!*\
  !*** ./src/ts/admin.js ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _modules_admin_qaform__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./modules/admin-qaform */ \"./src/ts/modules/admin-qaform.ts\");\n/* harmony import */ var _modules_admin_qaform__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_modules_admin_qaform__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _modules_admin_bar_menu_self_destruct__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modules/admin-bar-menu-self-destruct */ \"./src/ts/modules/admin-bar-menu-self-destruct.ts\");\n/* harmony import */ var _modules_admin_bar_menu_self_destruct__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_modules_admin_bar_menu_self_destruct__WEBPACK_IMPORTED_MODULE_1__);\n\n\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin.js?");

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