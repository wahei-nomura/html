/**
 * ダークモード切り替え
 * 
 * - cookie使用
 * - cookieにn2-darkmodeがあったらbodyにクラス付与
 *
 * @param $ jQuery
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		// ダークモードスイッチ配置
		$(target).append('<div id="n2-darkmode-toggler" class="btn btn-dark ms-2">darkmode</div>');
		$("#n2-darkmode-toggler").on('click',()=>{
			$('body').toggleClass('n2-darkmode');
			document.cookie = window['n2'].cookie['n2-darkmode'] ? 'n2-darkmode=true; max-age=0' : 'n2-darkmode=true';
		});
	})
}