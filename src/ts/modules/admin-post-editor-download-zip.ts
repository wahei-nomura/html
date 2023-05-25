/**
 * エディタでのN1zipダウンロード
 *
 * @param $ jQuery
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		if ( ! window['n2'].custom_field.事業者用.N1zip.value ) return;
		$(target).append(`<div onclick="window.open( n2.custom_field.事業者用.N1zip.value, '_blank' )" id="n2-download-zip" class="components-button has-icon" title="ZIPダウンロード"><span class="dashicons dashicons-media-archive"></span></div>`);
	});
}