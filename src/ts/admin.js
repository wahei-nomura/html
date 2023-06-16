import './admin-qaform';
import './admin-no-n2-caution';

jQuery(function ($) {
	$("#wp-admin-bar-my-sites").off("mouseenter mouseleave");
});

refererjump();

function refererjump(){ // 自治体跨ぎで飛んできたら現自治体の同じページに飛ばす
	let jump_url = '';
	const ref_url_list = document.referrer.split('/');
	const now_url_pathname_list = location.pathname.split('/');
	if(ref_url_list[3] ===  now_url_pathname_list[1] || 'wp-admin'  ===  now_url_pathname_list[1] ||  'wp-admin'  ===  ref_url_list[3] ){
		return; // 同サイト同士、または現サイトor元サイトがサイトネットワークなら終了
	}

	ref_url_list.forEach(function(v,i){
		if( 0 === i ){
			jump_url += v;
		} else {
			if( 'wp-admin' !== v && 3 === i ){ // ドメイン直後がwp-adminはサイト管理なので除外
				jump_url += '/' + now_url_pathname_list[1];
			}else{
				jump_url += '/' + v; 
			}
		}
	});
	jQuery(function ($) { // 飛び先が存在するか判定
		$.ajax({
		url: jump_url,
		type: 'GET'
		}).always(function (jqXHR) {
			if('404' === jqXHR.status){ // urlが存在しない場合は飛ばない
				return;
			}
		});
	});
	// location.href = jump_url; // refererを元にしたページにジャンプ
}
