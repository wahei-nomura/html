// 必要なモジュールの読み込み
import loading_view from "./modules/loading-view";
import alert_and_return_page from "./modules/alert-and-return-page";
import i18n from "./modules/admin-post-editor-i18n";
import title_counter from "./modules/admin-post-editor-title-counter";
import darkmode_toggler from "./modules/darkmode-toggler";
import mokuji_generator from "./modules/admin-post-editor-mokuji-generator";
import editor_vue from "./modules/admin-post-editor-vue";
import status_control from "./modules/admin-post-editor-status-control";

jQuery( $ => {
	const n2 = window['n2'];
	loading_view($, '#wpwrap');// ローディング
	alert_and_return_page(!n2.formula_type, '寄附金額の自動計算に必須の設定値がありません。先程のページへ戻ります。');// 自動計算不可のためページに入れない
	i18n();// 翻訳
	title_counter($);// タイトルカウンター
	darkmode_toggler($, ".edit-post-header__settings");// ダークモード
	mokuji_generator($);// 目次生成
	editor_vue($);// カスタムフィールドをVueで制御
	status_control($);// ステータスコントロール
})
