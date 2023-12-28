import save_post_ids from "./modules/admin-post-list-save-post-ids";
import post_list_tool from "./modules/admin-post-list-tool";

const n2 = window['n2'];
jQuery( $ => {
	//デフォルトの一括操作ツールを非表示
	$('.bulkactions').hide();
	// 検索フィールドにプレースホルダー
	$('#post-search-input').attr('placeholder','キーワード検索');
	//キーワードを変更したときにページ1にする
	$('#search-submit').on('submit', () => {
		if ( n2.query.query.paged === $('#current-page-selector').val() ) {
			$('#current-page-selector').val(1);
		}
	});
	//商品が一覧になかったらページ1に戻る
	if ($('.no-items').length) {
		location.href = location.href.replace(/paged=(\d+)/, 'paged=1');
	}
	save_post_ids($);
	post_list_tool($);
	if ( ! n2.settings['寄附金額・送料']['除数'] || ! n2.settings['寄附金額・送料']['送料']['0101'] ) {
		alert('N2の送料設定が正しく完了していません。設定画面へリダイレクトします。');
		location.href = './admin.php?page=n2_settings_formula-delivery'
	}
});