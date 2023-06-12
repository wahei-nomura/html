import save_post_ids from "./modules/admin-post-lists-save-post-ids";
import "./modules/ajax";
import "./modules/search";
import "./modules/tools";
import "../../node_modules/bootstrap/js/dist/dropdown"

const n2 = window['n2'];
jQuery( $ => {
	save_post_ids($);
	if ( n2.current_user.roles[0] === 'municipal-office' ) {
		// 新規追加ボタンを削除
		$('.page-title-action').remove();
	}
});