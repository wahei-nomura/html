import '../scss/n2-postlist.scss';
import '../scss/n2-setpost.scss';
import n2_setpost from './n2-setpost/index';
import n2_setpost_price from './n2-setpost/price';
import n2_sissubmit from './n2-sissubmit';
import n2_postlist_ajax from './n2-postlist-ajax';

// 返礼品編集画面
if(location.href.match(/(post|post-new)\.php/)) {
	n2_setpost();
	n2_setpost_price();
}
// 各種セットアップ画面
if(location.href.match(/admin\.php/)) {
	n2_sissubmit();
}
if(location.href.match(/edit\.php/)) {
	n2_postlist_ajax();
}