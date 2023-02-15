/**
 * ローディング追加
 * 
 * - bodyの最後にappend
 * - #n2-lording
 *
 * @param $ jQuery
 * @param target ローディングを待ちたい要素のセレクタ名
 */
export default ($:any, target: string) => {
	// ローディング追加
	$(target).css({opacity: 0});
	$('body').append('<div id="n2-loading" class="d-flex justify-content-center align-items-center vh-100 bg-white position-fixed top-0 bottom-0 start-0 end-0"><div class="spinner-border text-primary"></div></div>');
	// ターゲットが生成されてから
	$(target).ready(() => {
		$(target).animate({opacity: 1}, 1000, 'swing');
		// ローディング削除
		$("#n2-loading").animate({opacity: 0}, 1000, 'linear', ()=>{
			$("#n2-loading").remove();
		});
	})
};