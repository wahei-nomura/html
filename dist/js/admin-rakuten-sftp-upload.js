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

/***/ "./src/ts/admin-rakuten-sftp-upload.ts":
/*!*********************************************!*\
  !*** ./src/ts/admin-rakuten-sftp-upload.ts ***!
  \*********************************************/
/***/ (() => {

eval("jQuery(function ($) {\n    document.getElementById('ss-sftp').addEventListener('change', function (event) {\n        var fileInput = document.getElementById('ss-sftp');\n        if (!fileInput.files.length)\n            return;\n        console.log(fileInput.files);\n        var hasDeleteCSV = Array.from(fileInput.files).filter(function (file) { return file.name.indexOf('item-delete') !== -1; }).length;\n        if (hasDeleteCSV && !confirm('item-delete.csv が選択されています。続けますか？'))\n            fileInput.value = null;\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/admin-rakuten-sftp-upload.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/ts/admin-rakuten-sftp-upload.ts"]();
/******/ 	
/******/ })()
;