import "../scss/admin.scss";
import '../scss/n2-postlist.scss';
import '../scss/n2-setpost.scss';
import n2_admin from './n2-admin';
import n2_setpost from './n2-setpost';
import n2_sissubmit from './n2-sissubmit';
import n2_postlist from './n2-postlist';

// n2_admin();

// 返礼品編集画面
if(location.href.match(/(post|post-new)\.php/)) {
	// n2_setpost();
}
// 各種セットアップ画面
/**
 * wp_ajax用のファイル読み込み、ページ制限外しておく
 * 2022/07/14@taiki
 */
// if(location.href.match(/admin\.php/)) {
	n2_sissubmit();
// }
if(location.href.match(/edit\.php/)) {
	n2_postlist();
}
