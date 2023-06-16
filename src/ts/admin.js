import './admin-qaform';
import './admin-no-n2-caution';

jQuery(function ($) {
	$("#wp-admin-bar-my-sites").off("mouseenter mouseleave");
});

refererjump();

function refererjump(){

	// 自治体跨ぎで飛んできたら現自治体の同じページに飛ばす
	let ref_url = document.referrer;
	let ref_url_list = ref_url.split('/');
	let jump_url = '';
	const now_url_href = location.href;
	const now_url_href_list = now_url_href.split('/');
	if(ref_url_list[3] ===  now_url_href_list[3]){
		return; // 同サイト同士なら終了
	}
	const now_url_pathname = location.pathname;
	let now_url_pathname_list = now_url_pathname.split('/');
	let now_jichitai_name = ''
	if( 'wp-admin' === now_url_pathname_list[2] ){
		now_jichitai_name = now_url_pathname_list[1];
	}
	const n2_url = ref_url_list[0] + '//' + ref_url_list[2]; // 0:https, 1:空白, 2:ドメインurl
	let ref_jichitai_name = '';

	ref_url_list.forEach(function(v,i){
		if( 0 === i ){
			jump_url += v;
		} else {
			if( 'wp-admin' !== v && 3 === i ){ // 3がwp-adminはサイト管理なので除外
				ref_jichitai_name = ref_url_list[3];
				jump_url += '/' + now_jichitai_name;
			}else{
				jump_url += '/' + v; 
			}
		}

	});
	location.href = jump_url; // ページジャンプ
}
