/**
 * 返礼品の保存
 * 
 * @param $ jQuery
 * @param string target 返礼品の保存を追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	const n2 = window['n2'];
	const wp = window['wp'];
	const btn_class = {
		save: 'btn btn-sm btn-dark d-flex align-items-center px-4',
		saved: 'btn btn-sm btn-outeline-dark d-flex align-items-center disabled'
	}
	$('body').on('click keyup', e => {
		if( 'n2-save-post' === $(e.target).attr('id') ) return;
		setTimeout(()=>{
			// 差分チェック
			const editor = wp.data.select('core/editor');
			let diff = n2.saved_post != JSON.stringify($('form').serializeArray());
			diff = diff || editor.getEditedPostAttribute('status') != editor.getCurrentPostAttribute('status');
			diff = diff || editor.getEditedPostAttribute('title') != editor.getCurrentPostAttribute('title');
			diff = editor.getEditedPostAttribute('title') ? diff : true;
			if ( diff ){
				$('#n2-save-post').attr('class', btn_class.save).find('span').attr('class', '');
				$(window).on('beforeunload', () => '' );
			} else {
				$('#n2-save-post').attr('class', btn_class.saved).find('span').attr('class', 'dashicons dashicons-saved me-2');
				$(window).off('beforeunload');
			}
		},100)
	});
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		const editor = wp.data.select('core/editor');
		const status = editor.getEditedPostAttribute("status");
		if ( ( n2.current_user.roles.includes('jigyousya') && ! status.match(/draft/) )
		|| n2.current_user.roles.includes('municipal-office')
		) return
		// 保存ボタン配置
		const button = 'auto-draft' == status
			? `<div id="n2-save-post" class="${btn_class.save}" title="保存"><span></span>保存</div>`
			: `<div id="n2-save-post" class="${btn_class.saved}" title="保存"><span class="dashicons dashicons-saved me-2"></span>保存</div>`;
		$(target).prepend(button);
		
		$('#n2-save-post').on('click', () => {
			if ( ! editor.getEditedPostAttribute("title") ) {
				alert('保存するには返礼品の名前を入力してください');
				return;
			}
			$('#n2-save-post span').attr('class', 'spinner-border spinner-border-sm me-2');
			wp.data.dispatch('core/editor').savePost().then(()=>{
				$('#n2-save-post').attr('class', btn_class.saved).find('span').attr('class', 'dashicons dashicons-saved me-2');
				// 現状のカスタム投稿データを保持
				n2.saved_post = JSON.stringify($('form').serializeArray());
			})
		});
	})
}