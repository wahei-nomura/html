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

/***/ "./src/scss/n2-postlist.scss":
/*!***********************************!*\
  !*** ./src/scss/n2-postlist.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://neo-neng/./src/scss/n2-postlist.scss?");

/***/ }),

/***/ "./src/scss/n2-setpost.scss":
/*!**********************************!*\
  !*** ./src/scss/n2-setpost.scss ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://neo-neng/./src/scss/n2-setpost.scss?");

/***/ }),

/***/ "./src/ts/index.ts":
/*!*************************!*\
  !*** ./src/ts/index.ts ***!
  \*************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

eval("\nvar __importDefault = (this && this.__importDefault) || function (mod) {\n    return (mod && mod.__esModule) ? mod : { \"default\": mod };\n};\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\n__webpack_require__(/*! ../scss/n2-postlist.scss */ \"./src/scss/n2-postlist.scss\");\n__webpack_require__(/*! ../scss/n2-setpost.scss */ \"./src/scss/n2-setpost.scss\");\nvar n2_setpost_1 = __importDefault(__webpack_require__(/*! ./n2-setpost */ \"./src/ts/n2-setpost.ts\"));\n// 返礼品編集画面\nif (location.href.match(/(post|post-new)\\.php/)) {\n    (0, n2_setpost_1.default)();\n}\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/index.ts?");

/***/ }),

/***/ "./src/ts/n2-setpost.ts":
/*!******************************!*\
  !*** ./src/ts/n2-setpost.ts ***!
  \******************************/
/***/ ((__unused_webpack_module, exports) => {

eval("\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\nexports[\"default\"] = (function () {\n    jQuery(function ($) {\n        // クラスにテーマ名をprefixつける\n        var prefix = 'neo-neng';\n        var neoNengPath = function (window) {\n            return window.tmp_path.tmp_url;\n        };\n        // ブロックエディターレンダリング後にDOM操作して不要なメニュー削除\n        $('#editor').ready(function () {\n            $('[role=\"toolbar\"]').remove(); //ツールバー\n            $('button[aria-label=\"設定\"]').remove(); //設定ボタン\n            $('button[aria-label=\"オプション\"]').remove(); //３点リーダー\n            $('button.block-editor-post-preview__button-toggle').remove(); //プレビュー表示リンク\n            $('.is-root-container.block-editor-block-list__layout').remove(); //タイトル下のブロック\n            $('.wp-block-post-title').css('max-width', '95%');\n            // プログレストラッカーの表示調整\n            $('.interface-interface-skeleton__content').prepend($(\"#\".concat(prefix, \"-progress-tracker\")));\n        });\n        // 返礼品編集画面\n        $('#publish').on('click', function (e) {\n            // ここからバリデーション ===========================================================================================================================\n            var vError = []; // エラーを溜める\n            if ($('input#title').val() === '') {\n                $('input#title').before($(\"<p class=\\\"\".concat(prefix, \"-hissu-alert\\\" style=\\\"color:red;\\\">\\u203B\\u5FC5\\u9808\\u9805\\u76EE\\u3067\\u3059</p>\")));\n                $('input#title').css('background-color', 'pink');\n                vError.push($('input#title'));\n            }\n            // 必須\n            $(\".\".concat(prefix, \"-hissu\")).each(function (i, v) {\n                if ($(v).val() === '') {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-hissu-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-hissu-alert\\\" style=\\\"color:red;\\\">\\u203B\\u5FC5\\u9808\\u9805\\u76EE\\u3067\\u3059</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            // 0はダメ\n            $(\".\".concat(prefix, \"-notzero\")).each(function (i, v) {\n                if (Number($(v).val()) === 0) {\n                    if (!$(v).parent().find(\".\".concat(prefix, \"-notzero-alert\")).length) {\n                        $(v).before($(\"<p class=\\\"\".concat(prefix, \"-notzero-alert\\\" style=\\\"color:red;\\\">\\u203B0\\u4EE5\\u5916\\u306E\\u5024\\u3092\\u5165\\u529B\\u3057\\u3066\\u304F\\u3060\\u3055\\u3044\\u3002</p>\")));\n                    }\n                    $(v).css('background-color', 'pink');\n                    vError.push(v);\n                }\n            });\n            if (vError.length) {\n                alert('入力必須項目が未入力です。入力内容をご確認ください。');\n                e.preventDefault();\n                return;\n            }\n            // ここまでバリデーション==========================================================================================================================\n            if (!$('#n2-setpost-check-modal').length && $(e.target).val() === 'スチームシップ確認待ちとして送信') {\n                $('body').css('overflow-y', 'hidden');\n                e.preventDefault();\n                // ここから確認用モーダル==========================================================================================================================\n                $('#default_setting').append($('<div id=\"n2-setpost-check-modal-wrapper\"></div>'));\n                $('#n2-setpost-check-modal-wrapper').load(neoNengPath(window) + '/template/check-modal.html #n2-setpost-check-modal', function () {\n                    $('#n2-setpost-check-modal .result table').append(\"<tr><td>\\u8FD4\\u793C\\u54C1\\u540D</td><td>\".concat($('input#title').val(), \"</td></tr>\"));\n                    var inputs = $('#default_setting .n2-input');\n                    var checkbox = {};\n                    $.each(inputs, function (i, v) {\n                        var inputName = $(v).attr('name');\n                        var tag = v.tagName;\n                        if ((tag === 'INPUT' && $(v).attr('type') === 'text') || tag === 'TEXTAREA') {\n                            var value = $(v).val() !== '' ? String($(v).val()).replace('\\n', '<br>') : '<span class=\"noset\">入力なし</span>';\n                            $('#n2-setpost-check-modal .result table').append(\"<tr><td>\".concat(inputName, \"</td><td>\").concat(value, \"</td></tr>\"));\n                        }\n                        if (tag === 'SELECT') {\n                            var selected_1 = '未選択';\n                            $.each($(v).find('option'), function (i2, v2) {\n                                selected_1 = $(v2).attr('selected') === 'selected' && $(v2).text() !== '未選択' ? $(v2).text() : selected_1;\n                            });\n                            selected_1 = selected_1 === '未選択' ? \"<span class=\\\"noset\\\">\".concat(selected_1, \"</span>\") : selected_1;\n                            $('#n2-setpost-check-modal .result table').append(\"<tr><td>\".concat(inputName, \"</td><td>\").concat(selected_1, \"</td></tr>\"));\n                        }\n                        if (tag === 'INPUT' && $(v).attr('type') === 'checkbox') {\n                            var checkedName = $(v).parent().text();\n                            var key_1 = inputName.replace('[]', '');\n                            if ($(v).prop('checked')) {\n                                checkbox[key_1] = checkbox[key_1] === undefined ? '' + checkedName : checkbox[key_1] = checkbox[key_1] === undefined ? '' + checkedName : checkbox[key_1] + ',' + checkedName;\n                            }\n                            else {\n                                checkbox[key_1] = checkbox[key_1] === undefined || checkbox[key_1] === 'なし' ? 'なし' : checkbox[key_1].replace('なし,', '');\n                            }\n                        }\n                        if (tag === 'INPUT' && $(v).attr('type') === 'hidden') {\n                            var value = $(v).val() !== '' ? $(v).val() : false;\n                            value = value && inputName.match(/画像/) ? \"<img src=\\\"\".concat(value, \"\\\" width=\\\"100%\\\">\") : '<span class=\"noset\">なし</span>';\n                            $('#n2-setpost-check-modal .result table').append(\"<tr><td>\".concat(inputName, \"</td><td>\").concat(value, \"</td></tr>\"));\n                        }\n                    });\n                    $.each(checkbox, function (k, v) {\n                        if (v === 'なし') {\n                            $('#n2-setpost-check-modal .result table').append(\"<tr><td>\".concat(k, \"</td><td><span class=\\\"noset\\\">\").concat(v, \"</span></td></tr>\"));\n                        }\n                        else {\n                            $('#n2-setpost-check-modal .result table').append(\"<tr><td>\".concat(k, \"</td><td>\").concat(v, \"</td></tr>\"));\n                        }\n                    });\n                    $('#n2-setpost-check-modal button.cancel').on('click', function (e) {\n                        $('#n2-setpost-check-modal-wrapper').remove();\n                        $('body').css('overflow-y', 'auto');\n                    });\n                    $('#n2-setpost-check-modal button.done').on('click', function (e) {\n                        $(e.target).prop('disabled', true);\n                        $('#publish').trigger('click');\n                    });\n                });\n            } // end if(!$('#n2-setpost-check-modal').length)\n            // ここまで確認用モーダル==========================================================================================================================\n        });\n        // inputにmaxlengthが設定されているもののみ入力中の文字数表示\n        $('#ss_setting input,#ss_setting textarea,#default_setting input,#default_setting textarea').each(function (i, v) {\n            if ($(v).attr('maxlength')) {\n                $(v).parent().append($(\"<p>\".concat(String($(v).val()).length, \"\\u6587\\u5B57</p>\")));\n                $(v).on('keyup', function () {\n                    $(v).parent().find('p').text(String($(v).val()).length + '文字');\n                });\n            }\n        });\n        /**\n         *  wordpressのメディアアップロード呼び出し\n         */\n        var wpMedia = function (title, btnText, type, window) {\n            var wp = window.wp;\n            return wp.media({\n                title: title,\n                button: {\n                    text: btnText\n                },\n                library: {\n                    type: type\n                },\n                multiple: false\n            });\n        };\n        //imageアップローダーボタン \n        $(\".\".concat(prefix, \"-media-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('画像を選択', '画像を設定', 'image', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var images = customUploader.state().get(\"selection\");\n                images.each(function (image) {\n                    parent.find(\".\".concat(prefix, \"-image-url\")).attr('src', image.attributes.url);\n                    parent.find(\".\".concat(prefix, \"-image-input\")).val(image.attributes.url);\n                });\n            });\n        });\n        //zipアップローダーボタン \n        $(\".\".concat(prefix, \"-zip-toggle\")).on('click', function (e) {\n            e.preventDefault();\n            var parent = $(e.target).parent();\n            var customUploader = wpMedia('zipファイルを選択', 'zipファイルを設定', 'application/zip', window);\n            customUploader.open();\n            customUploader.on(\"select\", function () {\n                var zips = customUploader.state().get(\"selection\");\n                console.log(zips);\n                zips.each(function (zip) {\n                    console.log(zip);\n                    parent.find(\".\".concat(prefix, \"-zip-url\")).text(\"\".concat(zip.attributes.filename, \"\\u3092\\u9078\\u629E\\u4E2D\"));\n                    parent.find(\".\".concat(prefix, \"-zip-input\")).val(zip.attributes.url);\n                });\n            });\n        });\n        /** ===============================================================\n         *\n         * 楽天タグID用\n         *\n        ================================================================== */\n        // JS読み込んだ時点で、表示用のタグを生成する ============================================================================\n        // ディレクトリID用\n        $('#全商品ディレクトリID').before($(\"<p>\\u30C7\\u30A3\\u30EC\\u30AF\\u30C8\\u30EA\\u968E\\u5C64\\uFF1A<span id=\\\"\".concat(prefix, \"-genre\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-genre\")).text(String($('#全商品ディレクトリID-text').val()));\n        $('#全商品ディレクトリID').after($(\"<p>\\u30C7\\u30A3\\u30EC\\u30AF\\u30C8\\u30EAID\\uFF1A<span id=\\\"\".concat(prefix, \"-genreid\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-genreid\")).text(String($('#全商品ディレクトリID').val()));\n        // タグID用\n        $('#楽天タグID').before($(\"<p>\\u9078\\u629E\\u4E2D\\u306E\\u30BF\\u30B0\\uFF1A<span id=\\\"\".concat(prefix, \"-tag\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-tag\")).text(String($('#楽天タグID-text').val()));\n        $('#楽天タグID').after($(\"<p>\\u30BF\\u30B0ID\\uFF1A<span id=\\\"\".concat(prefix, \"-tagid\\\"></span><p>\")));\n        $(\"#\".concat(prefix, \"-tagid\")).text(String($('#楽天タグID').val()));\n        // ================================================================================================================\n        // タグ取得のAPI\n        var rakutenApiUrl = 'https://app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222?applicationId=1002772968546257164&genreId=';\n        // ジャンル>ジャンル>ジャンルの形式のテキストを保持\n        var genreText = '';\n        // 1234567/1234567/1234567みたいにする\n        var tagChain = '';\n        // タグネーム/タグネーム/タグネーム\n        var tagText = '';\n        // 最大タグID数に達していないかをカウントして表示\n        var tagCount = 32;\n        var showTagCount = function (tagCount) {\n            $('.tags-count  span').text(tagCount);\n        };\n        // ジャンルIDをパラメータで渡すことでJSONを返す\n        var getRakutenId = function (genreId) {\n            return $.ajax({\n                url: rakutenApiUrl + genreId,\n                dataType: 'JSON',\n            });\n        };\n        // 再帰的にジャンルIDをセットし続けることで下の階層のIDをとっていく\n        var setRakutenId = function (genreId, genreLevel) {\n            if (genreId === void 0) { genreId = 0; }\n            if (genreLevel === void 0) { genreLevel = 1; }\n            getRakutenId(genreId).done(function (res) {\n                // 子のジャンルがなければ終わり\n                if (!res.children.length) {\n                    return;\n                }\n                // select数字クラスを自動生成\n                if (!$(\"#n2-setpost-rakuten-genreid .select\".concat(genreLevel)).length) {\n                    $('#n2-setpost-rakuten-genreid .select-wrapper').append($(\"<select class=\\\"select\".concat(genreLevel, \"\\\"><option value=\\\"\\\" selected>\\u672A\\u9078\\u629E</option></select>\")));\n                    $.each(res.children, function (index, val) {\n                        $(\"#n2-setpost-rakuten-genreid select.select\".concat(genreLevel)).append($(\"<option value=\\\"\".concat(val.child.genreId, \"\\\">\").concat(val.child.genreName, \"</option>\")));\n                    });\n                }\n                // セレクトを変更するとジャンルIDと階層テキストを保持してまたsetRakutenIdをまわす\n                $(\"#n2-setpost-rakuten-genreid select.select\".concat(genreLevel)).on('change', function (e) {\n                    $('#n2-setpost-rakuten-genreid .result span').text(String($(e.target).val()));\n                    $(e.target).nextAll().remove();\n                    genreText += ' > ' + $(e.target).find($('option:selected')).text();\n                    genreId = Number($(e.target).val());\n                    genreLevel++;\n                    setRakutenId(genreId, genreLevel);\n                });\n            });\n        };\n        // genreIdをセットし、tagGroupからtagIdまでとっていく\n        var setRakutenTagId = function (genreId, tagLevel) {\n            if (genreId === void 0) { genreId = 0; }\n            if (tagLevel === void 0) { tagLevel = 1; }\n            getRakutenId(genreId).done(function (res) {\n                showTagCount(tagCount);\n                $.each(res.tagGroups, function (index, val) {\n                    // 含まれる全グループのブロックを生成\n                    $(\"#n2-setpost-rakuten-tagid .groups\").append($(\"<div><input type=\\\"radio\\\" name=\\\"tag-group\\\" id=\\\"gid\".concat(val.tagGroup.tagGroupId, \"\\\" value=\\\"\").concat(val.tagGroup.tagGroupName, \"\\\"><label for=\\\"gid\").concat(val.tagGroup.tagGroupId, \"\\\">\").concat(val.tagGroup.tagGroupName, \"</label></div>\")));\n                    $(\"#n2-setpost-rakuten-tagid .tags\").append($(\"<div class=\\\"gid\".concat(val.tagGroup.tagGroupId, \"\\\"></div>\")));\n                    // グループごとのブロック内にタグを配置\n                    $.each(val.tagGroup.tags, function (index, v) {\n                        $(\"#n2-setpost-rakuten-tagid .tags .gid\".concat(val.tagGroup.tagGroupId)).append($(\"<div><input type=\\\"checkbox\\\" name=\\\"tags\\\" id=\\\"tid\".concat(v.tag.tagId, \"\\\" value=\\\"\").concat(v.tag.tagName, \"\\\"><label for=\\\"tid\").concat(v.tag.tagId, \"\\\">\").concat(v.tag.tagName, \"</label></div>\")));\n                    });\n                    // 全ブロック非表示\n                    $(\"#n2-setpost-rakuten-tagid .tags>*\").css('display', 'none');\n                });\n                // グループを選択\n                $('#n2-setpost-rakuten-tagid .groups input[type=\"radio\"]').on('click', function (e) {\n                    var gid = Number($(e.target).attr('id').replace('gid', ''));\n                    // 表示中のグループブロックを非表示\n                    $(\"#n2-setpost-rakuten-tagid .tags>*\").css('display', 'none');\n                    // 選択したグループブロックを表示\n                    $(\"#n2-setpost-rakuten-tagid .tags .gid\".concat(gid)).css('display', 'block');\n                });\n                // tagを選択\n                $(\"#n2-setpost-rakuten-tagid .tags input[name=\\\"tags\\\"]\").on('change', function (e) {\n                    var tagId = Number($(e.target).attr('id').replace('tid', ''));\n                    var tagName = $(e.target).val();\n                    // チェック未→済\n                    if ($(e.target).prop('checked')) {\n                        if (tagCount !== 0) {\n                            $('#n2-setpost-rakuten-tagid .result .checked-tags').append($(\"<div data-tid=\\\"\".concat(tagId, \"\\\">\").concat(tagId, \":\").concat(tagName, \"<span></span></div>\")));\n                            tagCount--;\n                            showTagCount(tagCount);\n                        }\n                        else {\n                            $(e.target).prop('checked', false);\n                            alert('32件選択中です。');\n                        }\n                        // チェック済→未\n                    }\n                    else {\n                        $(\"#n2-setpost-rakuten-tagid .result .checked-tags div[data-tid=\\\"\".concat(tagId, \"\\\"]\")).remove();\n                        tagCount++;\n                        showTagCount(tagCount);\n                    }\n                });\n                // バツボタンで選択中のタグを削除するとcheckboxも未選択に戻る\n                $(document).on('click', \"#n2-setpost-rakuten-tagid .result .checked-tags div span\", function (e) {\n                    $(\"#tid\".concat($(e.target).parent().data('tid'))).prop('checked', false);\n                    $(e.target).parent().remove();\n                    tagCount++;\n                    showTagCount(tagCount);\n                });\n            });\n        };\n        // ディレクトリID検索スタート\n        $(\"#\".concat(prefix, \"-genreid-btn\")).on('click', function (e) {\n            if ($('#楽天タグID').val() !== '') {\n                if (!confirm('ディレクトリIDを変更すると、下の楽天タグIDがリセットされますのでご注意ください。')) {\n                    return;\n                }\n            }\n            $('#ss_setting').append($('<div id=\"n2-setpost-rakuten-genreid-wrapper\"></div>'));\n            // テンプレートディレクトリからHTMLをロード\n            $('#n2-setpost-rakuten-genreid-wrapper').load(neoNengPath(window) + '/template/rakuten-genreid.html #n2-setpost-rakuten-genreid', function () {\n                $('body').css('overflow-y', 'hidden');\n                // 保持テキストをリセットしてからsetRakutenId回す\n                genreText = '';\n                setRakutenId();\n                // モーダル内の各ボタンの処理制御\n                $('#n2-setpost-rakuten-genreid button').on('click', function (e) {\n                    if ($(e.target).hasClass('clear')) {\n                        $('#n2-setpost-rakuten-genreid .select-wrapper>*').remove();\n                        $('#n2-setpost-rakuten-genreid .result span').text('指定なし');\n                        setRakutenId();\n                    }\n                    if ($(e.target).hasClass('done') && confirm('選択中のIDをセットしますか？(楽天タグIDがリセットされます)')) {\n                        $(\"#\".concat(prefix, \"-genre\")).text(genreText);\n                        $(\"#\".concat(prefix, \"-genreid\")).text($('#n2-setpost-rakuten-genreid .result span').text());\n                        $('#全商品ディレクトリID-text').val(genreText);\n                        $('#全商品ディレクトリID').val(Number($('#n2-setpost-rakuten-genreid .result span').text()));\n                        $('#n2-setpost-rakuten-genreid-wrapper').remove();\n                        $('body').css('overflow-y', 'auto');\n                        $(\"#\".concat(prefix, \"-tag\")).text('');\n                        $(\"#\".concat(prefix, \"-tagid\")).text('');\n                        $('#楽天タグID-text').val('');\n                        $('#楽天タグID').val('');\n                    }\n                    if ($(e.target).hasClass('close') && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {\n                        $('#n2-setpost-rakuten-genreid-wrapper').remove();\n                        $('body').css('overflow-y', 'auto');\n                    }\n                });\n            });\n        });\n        // タグID検索スタート\n        $(\"#\".concat(prefix, \"-tagid-btn\")).on('click', function (e) {\n            if ($('#全商品ディレクトリID').val() === '') {\n                alert('ディレクトリIDを選択してから再度お試しください。');\n                return;\n            }\n            $('#ss_setting').append($('<div id=\"n2-setpost-rakuten-tagid-wrapper\"></div>'));\n            // テンプレートディレクトリからHTMLをロード\n            $('#n2-setpost-rakuten-tagid-wrapper').load(neoNengPath(window) + '/template/rakuten-tagid.html #n2-setpost-rakuten-tagid', function () {\n                $('body').css('overflow-y', 'hidden');\n                tagCount = 32;\n                showTagCount(tagCount);\n                // 保持テキストをリセットしてからsetRakutenId回す\n                tagChain = '';\n                tagText = '';\n                setRakutenTagId(Number($('#全商品ディレクトリID').val()));\n                // モーダル内の各ボタンの処理制御\n                $('#n2-setpost-rakuten-tagid button').on('click', function (e) {\n                    if ($(e.target).hasClass('clear')) {\n                        $('#n2-setpost-rakuten-tagid .tags>*').remove();\n                        $('#n2-setpost-rakuten-tagid .result .checked-tags>*').remove();\n                        tagCount = 32;\n                        showTagCount(tagCount);\n                        setRakutenTagId(Number($('#全商品ディレクトリID').val()));\n                    }\n                    if ($(e.target).hasClass('done') && confirm('選択中のIDをセットしますか？')) {\n                        var chekedTags = $('#n2-setpost-rakuten-tagid .tags input[name=\"tags\"]').filter(':checked');\n                        $.each(chekedTags, function (i, v) {\n                            if (i === 0) {\n                                tagText += $(v).val();\n                                tagChain += v.id.replace('tid', '');\n                            }\n                            else {\n                                tagText += '/' + $(v).val();\n                                tagChain += '/' + v.id.replace('tid', '');\n                            }\n                        });\n                        $(\"#\".concat(prefix, \"-tag\")).text(tagText);\n                        $(\"#\".concat(prefix, \"-tagid\")).text(tagChain);\n                        $('#楽天タグID-text').val(tagText);\n                        $('#楽天タグID').val(tagChain);\n                        $('#n2-setpost-rakuten-tagid-wrapper').remove();\n                        $('body').css('overflow-y', 'auto');\n                    }\n                    if ($(e.target).hasClass('close') && confirm('選択中のIDはリセットされますがそれでも閉じますか？')) {\n                        $('#n2-setpost-rakuten-tagid-wrapper').remove();\n                        $('body').css('overflow-y', 'auto');\n                    }\n                });\n            });\n        });\n        /** ===============================================================\n         *\n         * 楽天カテゴリー用\n         *\n        ================================================================== */\n        $(\"#\".concat(prefix, \"-rakutencategory\")).append('<option value=\"\">カテゴリーを選択してください</option>');\n        var folderCode = '1p7DlbhcIEVIaH7Rw2mTmqJJKVDZCumYK';\n        var api = 'https://www.googleapis.com/drive/v3/files/'; //API Request\n        var key = 'AIzaSyDQ1Mu41-8S5kBpZED421bCP8NPE7pneNU';\n        var data = {\n            key: key,\n            q: \"'\".concat(folderCode, \"' in parents\") //フォルダの中を検索するクエリ\n        };\n        var town = $('#wp-admin-bar-site-name > a').text(); // 自治体名\n        $.ajax(api, { data: data }).done(function (d) {\n            // .RakutenDataドライブのフォルダの中から該当する自治体のシートのIDを取得（セッションに保存したい）\n            var sheetID = d.files.filter(function (v) { return v.name.match(town) && v.mimeType.split('.').slice(-1)[0] == 'spreadsheet'; });\n            if (!sheetID.length)\n                return false;\n            $.ajax(\"https://sheets.googleapis.com/v4/spreadsheets/\".concat(sheetID[0].id, \"/values/\\u30AB\\u30C6\\u30B4\\u30EA\\u30FC?key=\").concat(key)).done(function (data) {\n                data = data['values'];\n                var cats, lCat, mCat;\n                $.each(data, function (k, v) {\n                    //大カテの有無による大カテ・中カテの処理\n                    if (v[0]) {\n                        lCat = v[0].replace('.', ''); //大カテあればそれ\n                        mCat = v[1] ? v[1].replace('.', '') : ''; //いったん中カテリセット\n                    }\n                    else {\n                        lCat = lCat; //大カテなければ前のを継承\n                        mCat = v[1] ? v[1].replace('.', '') : mCat; //中カテあればそれ・なければ継承\n                    }\n                    cats = '#/' + lCat + '/' + (mCat ? mCat + '/' : '') + (v[2] ? v[2].replace('.', '') + '/' : '');\n                    $(\"#\".concat(prefix, \"-rakutencategory\")).append('<option value=\"' + cats + '\" class=\"rakuten-category-item\">' + cats + '</option>');\n                });\n            });\n            //選択された項目をtextareaに値として追記していく\n            $(\"#\".concat(prefix, \"-rakutencategory\")).on('change', function () {\n                var textarea = $('textarea#楽天カテゴリー');\n                var selected = String($('.rakuten-category-item:selected').val());\n                selected = (String(textarea.val()).search(selected) == -1) ? selected : ''; //textareaにすでにあったら入れない\n                var cat = textarea.val() ? textarea.val() + (selected ? '\\n' + selected : '') : selected;\n                textarea.val(cat);\n            });\n        });\n        // ここまで楽天カテゴリー ==============================================================================================================================\n    });\n});\n\n\n//# sourceURL=webpack://neo-neng/./src/ts/n2-setpost.ts?");

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
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
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
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = __webpack_require__("./src/ts/index.ts");
/******/ 	
/******/ })()
;