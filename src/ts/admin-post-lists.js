import "./modules/ajax";
import "./modules/search";
import "./modules/tools";
import "./modules/ajax-dl";
import "./modules/ajax-rakuten-transfer";
import "./modules/bulk-update-status";
import "../../node_modules/bootstrap/js/dist/dropdown"

const n2 = window['n2'];
jQuery(function($) {
	if ( n2.current_user.roles[0] === 'municipal-office' ) {
		// 新規追加ボタンを削除
		$('.page-title-action').remove();
	}
})
