import '../scss/n2-postlist.scss';
import '../scss/n2-setpost.scss';
import n2_setpost from './n2-setpost';
import n2_postlist_ajax from './n2-postlist-ajax';

// 返礼品編集画面
if(location.href.match(/(post|post-new)\.php/)) {
	n2_setpost();
}
if(location.href.match(/edit\.php/)) {
	n2_postlist_ajax();
}