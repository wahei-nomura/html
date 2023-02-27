/**
 * 投稿の保存
 * 
 * @param $ jQuery
 * @param string target 切り替えスイッチを追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		const status = window['wp'].data.select("core/editor").getEditedPostAttribute("status");
		if ( window['n2'].current_user.roles.includes('jigyousya') && ! status.match(/draft/)) return
		// 保存ボタン配置
		$(target).prepend('<div id="n2-save-post" class="btn btn-sm btn-outline-dark d-flex align-items-center" title="保存"><span></span>保存</div>');
		
		$('#n2-save-post').on('click', () => {
			if ( ! window['wp'].data.select("core/editor").getEditedPostAttribute("title") ) {
				alert('保存するには返礼品の名前を入力してください');
				return;
			}
			$('#n2-save-post span').attr('class', 'spinner-border spinner-border-sm me-2');
			window['wp'].data.dispatch('core/editor').savePost().then(()=>{
				$('#n2-save-post span').attr('class', 'dashicons dashicons-saved me-2');
			})
		});
	})
}