import '../scss/style.scss';
import n2_setpost from './n2-setpost';

// 返礼品編集画面
if(location.href.match(/(post|post-new)\.php/)) {
	n2_setpost();
}