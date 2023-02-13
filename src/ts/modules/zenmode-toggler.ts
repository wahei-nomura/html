/**
 * 禅モード切り替え（説明不要モード）
 * 
 * - cookie使用
 * - cookieにn2-zenmodeがあったらbodyにクラス付与
 *
 * @param $ jQuery
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		// ダークモードスイッチ配置
		$(target).append('<div id="n2-zenmode-toggler" class="components-button has-icon" title="説明不要モード"><span class="dashicons dashicons-welcome-comments"></span></div>');
		if ( window['n2'].cookie['n2-zenmode'] ) {
			$('#n2-zenmode-toggler').addClass('is-pressed');
		}
		$("#n2-zenmode-toggler").on('click',()=>{
			$('#n2-zenmode-toggler').toggleClass('is-pressed');
			document.cookie = document.cookie.match(/n2-zenmode/) ? 'n2-zenmode=true; max-age=0' : 'n2-zenmode=true';
		});
	})
}