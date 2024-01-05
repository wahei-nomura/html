/**
 * エディタでの画像一括ダウンロード
 *
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 * @param any $ jQuery
 */
export default (target: string, $: any = jQuery ) => {
	const n2 = window['n2'];
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		$(target).append('<div id="n2-download-images" class="components-button has-icon" title="画像一括ダウンロード"><span class="dashicons dashicons-images-alt2"></span></div>');
		$("#n2-download-images").on('click',()=>{
			const params = new URLSearchParams({
				action: 'n2_download_images_by_id',
				ids: $('#n2-post-id').val(),
			}).toString();
			window.open( `${n2.ajaxurl}?${params}`, '_blank' );
		});
		if ( ! n2.custom_field.事業者用.商品画像.value.length ) {
			$('#n2-download-images').hide();
		}
	});
}