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

/***/ "./src/ts/admin-index.ts":
/*!*******************************!*\
  !*** ./src/ts/admin-index.ts ***!
  \*******************************/
/***/ (() => {

eval("jQuery(function ($) {\n    $.ajax({\n        url: \"\".concat(window['n2'].ajaxurl, \"?action=n2_dashboard_custom_help_widget_text\")\n    }).then(function (res) {\n        $('#custom_help_widget .inside').html(res);\n    });\n    $.ajax({\n        url: \"\".concat(window['n2'].ajaxurl, \"?action=n2_dashboard_jichitai_widget_text\")\n    }).then(function (res) {\n        $('#jichitai_widget .inside').html(res);\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-index.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-index.ts"]();
/******/ 	
/******/ })()
;