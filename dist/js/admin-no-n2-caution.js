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

eval("jQuery(function ($) {\n    /**\n     * n2_active_flagがfalseの時に注意文を出す\n     */\n    var n2 = window['n2'];\n    var active_flag = n2.n2_active_flag;\n    var cautionBox = $('<a class=\"no_active_caution\" onclick=\"this.remove()\">N2未稼働 更新作業はN1で行って下さい</a>');\n    console.log(n2.n2_active_flag);\n    if ('false' === active_flag) {\n        $('#wpwrap').append(cautionBox);\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-no-n2-caution.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-no-n2-caution.ts"]();
/******/ 	
/******/ })()
;