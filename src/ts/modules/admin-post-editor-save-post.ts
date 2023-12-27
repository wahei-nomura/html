import get_meta from "./admin-post-editor-get-meta";
import {copy} from "./functions";

/**
 * 返礼品の保存
 * 
 * @param $ jQuery
 * @param string target 返礼品の保存を追加する要素のセレクタ名
 */
export default (target: string, $: any = jQuery) => {
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
			console.log(typeof n2.vue.$data.寄附金額)

			const fd = $('form').serializeArray();
			const data = copy( n2.vue.$data, true );
			data.tmp = copy( n2.saved_post.tmp ?? {}, true );
			data.tmp.post_title = wp.data.select('core/editor').getEditedPostAttribute('title');
			data.tmp.post_status = wp.data.select('core/editor').getEditedPostAttribute('status');
			let diff = JSON.stringify(n2.saved_post) != JSON.stringify(data);
			console.log('saved',n2.saved_post)
			console.log('data',data)
			diff = wp.data.select('core/editor').getEditedPostAttribute('title') ? diff : true;
			// 総務省申請理由がない場合は保存させない
			if ( fd.filter( v=>v.name.match(/総務省申請不要理由/) && !v.value ).length ) {
				diff = false;
			}
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
		if ( n2.current_user.roles.includes('jigyousya') && ! status.match(/draft/) ) return
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
			// フォーカス外して保存した場合にVueの$watchが発火しないので強制$watch
			n2.vue.$data._force_watch++;
			// フォーカス外さずそのまま保存した場合にVueの$watchの発火が間に合わないのでresolveを待つ
			new Promise( resolve => {
				n2.save_post_promise_resolve = resolve;
			}).then(()=>{
				// カスタムフィールドの保存
				const meta = get_meta();
				wp.data.dispatch( 'core/editor' ).editPost({ meta });
	
				// 保存時の挙動
				wp.data.dispatch('core/editor').savePost().then(
					() => {
						$(window).off('beforeunload');
						$('#n2-save-post').attr('class', btn_class.saved).find('span').attr('class', 'dashicons dashicons-saved me-2');
						// 現状のカスタム投稿データを保持
						n2.saved_post = copy(n2.vue.$data, true);
					},
					reason => {
						console.log( '保存失敗', reason );
						/**
						 * ローカルストレージにエラーログを１件だけ保存
						 * 見方：ブラウザのコンソールにJSON.parse(localStorage.n2log)
						 */
						const n2log = JSON.parse( localStorage.n2log || '{}' );
						n2log.admin_post_editor_save_post_error = {
							date: new Date().toLocaleString( 'ja-JP', { timeZone: 'Asia/Tokyo' }),
							log: reason
						};
						localStorage.n2log = JSON.stringify( n2log );
						if ( confirm( '何らかの理由で保存に失敗しました。\nもう一度保存を試みますか？' ) ) {
							$('#n2-save-post').click();
						}
					}
				);
			});
		});
	})
}