// 専用CSS生成
import "../scss/admin-post-editor";

// 必要なモジュールの読み込み
import loading_view from "./_loading-view";
import alert_and_return_page from "./_alert-and-return-page";
import i18n from "./_admin-post-editor-i18n";
import title_counter from "./_admin-post-editor-title-counter";
import darkmode_toggler from "./_darkmode-toggler";
import mokuji_generator from "./_admin-post-editor-mokuji-generator";
import editor_vue from "./_admin-post-editor-vue";
import status_control from "./_admin-post-editor-status-control";

jQuery( $ => {
	loading_view($, '#wpwrap');// ローディング
	alert_and_return_page(!n2.formula_type, '寄附金額の自動計算に必須の設定値がありません。先程のページへ戻ります。');// 自動計算不可のためページに入れない
	i18n();// 翻訳
	title_counter($);// タイトルカウンター
	darkmode_toggler($, ".edit-post-header-toolbar__left");// ダークモード
	mokuji_generator($);// 目次生成
	editor_vue($);// カスタムフィールドをVueで制御
	status_control($);// ステータスコントロール
})
