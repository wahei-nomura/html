/**
 * ローディング削除
 * 
 * @param target ローディングを待ちたい要素のセレクタ名
 * @param duration 表示速度
 */
const show = (target, duration = 1000, $ = jQuery) => {
	$("#n2-loading").remove();
	$(target).animate({opacity: 1}, duration, 'swing');
};
/**
 * ローディング追加
 * 
 * - bodyの最後にappend
 * - #n2-loading
 *
 * @param target ローディングを待ちたい要素のセレクタ名
 * @param auto_show 自動で表示
 */
const add = (target: string, auto_show:any = false, $ = jQuery ) => {
	// ローディング追加
	$(target).css({opacity: 0});
	$('body').append('<div id="n2-loading" class="d-flex justify-content-center align-items-center vh-100 bg-white position-fixed top-0 bottom-0 start-0 end-0"><div class="spinner-border text-primary"></div></div>');
	if ( auto_show ) {
		$(window).on('load', ()=>{
			setTimeout(() => {
				show(target);
			},auto_show);
		});
	}
};
export default { add, show }