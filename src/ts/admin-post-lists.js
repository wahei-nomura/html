import save_post_ids from "./modules/admin-post-lists-save-post-ids";
import "./modules/ajax";
import "./modules/search";
import "./modules/tools";
import "../../node_modules/bootstrap/js/dist/dropdown"

const n2 = window['n2'];
jQuery( $ => {
	save_post_ids($);
	if ( n2.current_user.roles[0] === 'local-government' ) {
		// 新規追加ボタンを削除
		$('.page-title-action').remove();
	}
	if ( ! n2.settings['寄附金額・送料']['除数'] || ! n2.settings['寄附金額・送料']['送料']['0101'] ) {
		alert('N2の送料設定が正しく完了していません。設定画面へリダイレクトします。');
		location.href = './admin.php?page=n2_settings_formula-delivery'
	}
});