// 必要なモジュールの読み込み
import loading_view from "./modules/loading-view";
import i18n from "./modules/admin-post-editor-i18n";
import title_counter from "./modules/admin-post-editor-title-counter";
import delete_post_button from "./modules/admin-post-editor-delete-post";
import view_history_button from "./modules/admin-post-editor-view-history";
import save_post_button from "./modules/admin-post-editor-save-post";
import darkmode_toggler from "./modules/darkmode-toggler";
import zenmode_toggler from "./modules/zenmode-toggler";
import download_images from "./modules/admin-post-editor-download-images";
import download_zip from "./modules/admin-post-editor-download-zip";
import mokuji_generator from "./modules/admin-post-editor-mokuji-generator";
import hypernavi from "./modules/admin-post-editor-hypernavi";
import editor_vue from "./modules/admin-post-editor-vue";
import status_control from "./modules/admin-post-editor-status-control";
jQuery( $ => {
	loading_view.add('#wpwrap');// ローディング
	const n2 = window['n2'];
	i18n();// 翻訳
	setTimeout(()=>{
		title_counter();// タイトルカウンター
		view_history_button(".edit-post-header__settings");// 履歴ボタン
		delete_post_button(".edit-post-header__settings");// 削除ボタン
		save_post_button(".edit-post-header__settings");// 保存ボタン
		darkmode_toggler(".edit-post-header__settings");// ダークモード
		zenmode_toggler(".edit-post-header__settings");// ZENモード
		download_images(".edit-post-header-toolbar__left");// 画像一括ダウンロード
		download_zip(".edit-post-header-toolbar__left");// N1zipダウンロード
		mokuji_generator();// 目次生成
		hypernavi();// ナビ生成
		editor_vue();// カスタムフィールドをVueで制御
		status_control();// ステータスコントロール
	}, 100);
});
