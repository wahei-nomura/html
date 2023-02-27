/**
 * スチームシップへ送信
 * 
 * @param $ jQuery
 * @param string target スチームシップへ送信を追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		const status = window['wp'].data.select("core/editor").getEditedPostAttribute("status");
		if ( ! window['n2'].current_user.roles.includes('jigyousya') || ! status.match(/draft/)) return

		// 保存ボタン配置
		$(target).prepend('<div id="n2-save-as-pending" class="btn btn-sm btn-primary d-flex align-items-center" title="スチームシップへ送信"><span></span>スチームシップへ送信</div>');
		$('#n2-save-as-pending').on('click', () => {
			// 必須項目が入っていない場合は送信できなくする
			if ( $('.edit-post-layout__metaboxes [required]').serializeArray().find(v=>!v.value) ) {
				alert('全項目を埋めてから送信してください');
				return;
			}
			if ( ! confirm('スチームシップへ送信後の編集はできません。本当に送信しますか？') ) return;
			$('#n2-save-as-pending span').attr('class', 'spinner-border spinner-border-sm me-2');
			window['wp'].data.dispatch('core/editor').editPost({ status: 'pending' });
			window['wp'].data.dispatch('core/editor').savePost().then(()=>{
				$('#n2-save-as-pending span').attr('class', 'dashicons dashicons-saved me-2');
				location.reload();
			})
		});
	})
}