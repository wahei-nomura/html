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

/***/ "./src/ts/admin-user-new.ts":
/*!**********************************!*\
  !*** ./src/ts/admin-user-new.ts ***!
  \**********************************/
/***/ (() => {

eval("jQuery(function ($) {\n    // 管理者以外のユーザーは管理者を追加できない\n    if (!window['n2'].current_user.roles.includes('administrator')) {\n        $(\"option[value='administrator'\").remove(); // 管理者\n    }\n    $('#noconfirmation,#adduser-noconfirmation').prop('checked', true);\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-user-new.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-user-new.ts"]();
/******/ 	
/******/ })()
;