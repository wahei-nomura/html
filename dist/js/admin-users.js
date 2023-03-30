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

/***/ "./src/ts/admin-users.ts":
/*!*******************************!*\
  !*** ./src/ts/admin-users.ts ***!
  \*******************************/
/***/ (() => {

eval("jQuery(function ($) {\n    // 管理者以外は管理者を削除できない\n    if (!window['n2'].current_user.roles.includes('administrator')) {\n        // 一括編集が削除しか項目が無いのでセレクトボックスごと削除\n        $(\"div[class='alignleft actions bulkactions'\").remove();\n        // 一括権限変更から管理者と権限剥奪を除去\n        $(\"option[value='administrator']\").remove();\n        $(\"option[value='none']\").remove();\n        // 一括操作対策としてチェックボックスの除去と削除ボタンの除去\n        $('td[data-colname=\"権限グループ\"]').each(function () {\n            if ($(this).text() === '管理者') {\n                $(this).closest('tr').find('input[type=\"checkbox\"]').remove();\n                $(this).closest('tr').find('span[class=\"remove\"]').remove();\n            }\n        });\n    }\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-users.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-users.ts"]();
/******/ 	
/******/ })()
;