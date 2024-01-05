/**
 * エディタでのN1zipダウンロード
 *
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 * @param any $ jQuery
 */
export default (target: string, $: any = jQuery) => {
	const n2 = window['n2'];
	if ( n2.current_user.roles.includes('jigyousya') ) return;
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		$(target).append(`<div id="n2-download-zip" class="components-button has-icon" title="ZIPダウンロード"><span class="dashicons dashicons-media-archive"></span></div>`);
		$('#n2-download-zip').on('click',e => window.open( n2.tmp.saved.N1zip, '_blank' ));
		if ( ! n2.custom_field.事業者用.N1zip.value ) {
			$('#n2-download-zip').hide();
		}
	});
}