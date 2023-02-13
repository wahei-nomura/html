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
	$(target).hide();
	$('body').append('<div id="n2-loading" class="d-flex justify-content-center align-items-center vh-100 bg-white"><div class="spinner-border text-primary"></div></div>');
	// ターゲットが生成されてから
	$(target).ready(() => {
		setTimeout(()=>{
			$(target).show(1000);
			// ローディング削除
			$("#n2-loading").remove();
		}, 500);
	})
};