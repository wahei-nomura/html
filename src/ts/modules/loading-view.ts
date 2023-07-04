/**
 * ローディング追加
 * 
 * - bodyの最後にappend
 * - #n2-lording
 *
 * @param $ jQuery
 * @param target ローディングを待ちたい要素のセレクタ名
 */
export default ($:any, target: string, auto_remove: boolean = false ) => {
	// ローディング追加
	$(target).css({opacity: 0});
	$('body').append('<div id="n2-loading" class="d-flex justify-content-center align-items-center vh-100 bg-white position-fixed top-0 bottom-0 start-0 end-0"><div class="spinner-border text-primary"></div></div>');
	// グローバル変数n2にローディング削除メソッド追加
	window['n2'].remove_loading = (target, duration = 1000) => {
		$("#n2-loading").remove();
		$(target).animate({opacity: 1}, duration, 'swing');
	};
	if ( auto_remove ) {
		window['n2'].remove_loading(target);
	}
};