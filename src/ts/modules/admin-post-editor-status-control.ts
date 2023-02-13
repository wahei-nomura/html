/**
 * ブロックエディタへ目次を追加する
 *
 * @param $ JQuery
 */
export default $ => {
	const n2 = window['n2'];
	const wp = window['wp'];
	const status = {
		'auto-draft': {
			label: '入力開始',
			class: 'progress-bar bg-secondary col-1',
		},
		'draft': {
			label: '入力中',
			class: 'progress-bar bg-secondary col-5',
		},
		'pending': {
			label: 'スチームシップ 確認中',
			class: 'progress-bar bg-danger col-7',
		},
		'publish': {
			label: 'ポータル登録準備中',
			class: 'progress-bar bg-primary col-10',
		},
		'private': {
			label: '非公開',
			class: 'progress-bar bg-dark col-12',
		},
		'unko': {
			label: 'ポータル登録済',
			class: 'progress-bar bg-success col-12',
		},
	};
	$(".edit-post-layout__metaboxes").ready(() => {

		// プログレスバー
		$('.edit-post-header').before('<div class="progress rounded-0" style="height: 1.5em;width: 100%;"><div id="n2-progress"></div></div>');
		n2.post_status = wp.data.select("core/editor").getEditedPostAttribute("status");

		// レビュー待ち　かつ　事業者ログイン
		if ( n2.post_status == 'pending' && n2.current_user.roles.includes('jigyousya') ) {
			$('#normal-sortables, .editor-post-title').addClass('pe-none')
				.find('input,textarea,select').addClass('border-0');
			$('.interface-interface-skeleton__content').on('click', ()=>{
				alert('スチームシップに送信後の編集はできません。');
			})
			wp.data.dispatch( 'core/editor' ).lockPostSaving( 'n2-pending' );
		}
		wp.data.subscribe(()=>{
			
			$('#n2-progress').text(status[n2.post_status].label).attr( 'class', status[n2.post_status].class );
			// レビュー待ち　かつ　事業者ログイン
			if ( n2.post_status == 'pending' && n2.current_user.roles.includes('jigyousya') ) {
				$('#normal-sortables, .editor-post-title').addClass('pe-none')
					.find('input,textarea,select').addClass('border-0');
			}
			n2.post_status = wp.data.select("core/editor").getEditedPostAttribute("status");
		});
	});
};