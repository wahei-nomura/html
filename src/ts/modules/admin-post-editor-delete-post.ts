/**
 * 投稿の削除
 * 
 * @param string target 投稿の削除を追加する要素のセレクタ名
 * @param any $ jQuery
 */
export default (target: string, $:any = jQuery) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		const status = window['wp'].data.select("core/editor").getEditedPostAttribute("status");
		if ( 
			( window['n2'].current_user.roles.includes('jigyousya') && ! status.match(/draft/) )
			|| window['n2'].current_user.roles.includes('municipal-office')
		) return
		// 削除ボタン配置
		$(target).prepend('<div id="n2-delete-post" class="btn btn-sm btn-outline-danger d-flex align-items-center" title="削除"><span></span>削除</div>');
		$('#n2-delete-post').on('click', () => {
			if ( window['wp'].data.select("core/editor").getEditedPostAttribute("status") === 'auto-draft' ) {
				alert('削除するものがありません');
				return;
			}
			if ( ! confirm('本当に削除していいですか？') ) return;
			window['wp'].data.dispatch('core/editor').trashPost();
		});
	})
}