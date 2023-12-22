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

/***/ "./src/ts/admin-user-profile.ts":
/*!**************************************!*\
  !*** ./src/ts/admin-user-profile.ts ***!
  \**************************************/
/***/ (() => {

eval("jQuery(function ($) {\n    $('.user-user-login-wrap .description').html('<p>ユーザー名を変更したい思いが強ければきっと変更できるでしょう。</p>');\n    var count = 0;\n    $('#user_login').css('pointer-events', 'none');\n    $('.user-user-login-wrap').on('click', function (e) {\n        count++;\n        if (count == 10) {\n            $('#user_login').removeAttr('disabled').css('pointer-events', 'auto');\n            ;\n        }\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-user-profile.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-user-profile.ts"]();
/******/ 	
/******/ })()
;