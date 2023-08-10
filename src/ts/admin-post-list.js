import save_post_ids from "./modules/admin-post-list-save-post-ids";
import post_list_tool from "./modules/admin-post-list-tool";

const n2 = window['n2'];
jQuery( $ => {
	save_post_ids($);
	post_list_tool($);
	if ( ! n2.settings['寄附金額・送料']['除数'] || ! n2.settings['寄附金額・送料']['送料']['0101'] ) {
		alert('N2の送料設定が正しく完了していません。設定画面へリダイレクトします。');
		location.href = './admin.php?page=n2_settings_formula-delivery'
	}
});