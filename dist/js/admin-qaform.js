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

/***/ "./src/ts/admin-qaform.ts":
/*!********************************!*\
  !*** ./src/ts/admin-qaform.ts ***!
  \********************************/
/***/ (() => {

eval("jQuery(function ($) {\n    /**\n     * 事業者アカウントログイン時に右下にGoogleフォームのリンクを常時表示\n     */\n    var n2 = window['n2'];\n    var formLink = $('<a href=\"https://docs.google.com/forms/d/e/1FAIpQLScbze4H3puDboZ0zEZ_vfx7EzpiV0KJFeKFjFnGjymxqekw5Q/viewform\" target=\"_blank\"><span class=\"dashicons dashicons-warning\"></span>システムの不具合は<br>こちら</a>');\n    formLink.css({\n        'position': 'fixed ',\n        'bottom': '10px ',\n        'right': '10px ',\n        'z-index': '99999 ',\n        'display': 'flex ',\n        'justify-content': 'center ',\n        'align-items': 'center ',\n        'flex-direction': 'column',\n        'text-align': 'center ',\n        'color': '#fff ',\n        'font-size': '13px',\n        'background-color': '#b2292c ',\n        'border-radius': '50% ',\n        'box-shadow': '0 3px 5px rgba(0, 0, 0, 0.3)',\n        'width': '130px ',\n        'height': '90px ',\n        'padding': '2px ',\n        'text-decoration': 'none',\n    });\n    if (n2.current_user.roles[0] !== 'administrator') {\n        $('#wpwrap').append(formLink);\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-qaform.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-qaform.ts"]();
/******/ 	
/******/ })()
;