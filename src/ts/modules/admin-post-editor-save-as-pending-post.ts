import get_meta from "./admin-post-editor-get-meta";
/**
* バリデーション（拒否項目を配列で返す）
* @returns rejection
*/
const rejection = ($: any = jQuery) => {
	let required = [];
	let ok = [];
	$('.edit-post-layout__metaboxes [required]').each((k,v) => {
		required.push(v.name.match(/n2field\[(.*?)\]/)[1]);
		if ( v.value && ( 'checkbox' !== v.type || v.checked ) ) ok.push(v.name.match(/n2field\[(.*?)\]/)[1]);
	});
	// それぞれの重複削除
	required = Array.from(new Set(required));
	ok = Array.from(new Set(ok));
	// 拒否項目
	return required.filter( v => !ok.includes(v) );
};

/**
 * スチームシップへ送信
 * 
 * @param $ jQuery
 * @param string target スチームシップへ送信を追加する要素のセレクタ名
 */
const append_button = (target: string, $: any = jQuery) => {
	// ターゲットDOMが生成されてから
	const n2 = window['n2'];
	const wp = window['wp'];
	// 事業者以外は無関係
	if ( ! n2.current_user.roles.includes('jigyousya') ) return;
	$(target).ready(() => {
		const status = wp.data.select('core/editor').getEditedPostAttribute("status");
		// 事業者の下書き状態以上では何もしない
		// 「スチームシップへ送信」ボタン配置
		$(target).before(`<div id="n2-save-as-pending" class="btn btn-sm btn-primary d-flex align-items-center" title="スチームシップへ送信"><span></span>スチームシップへ送信</div>`);
		// 事業者の保存系の制御
		wp.data.subscribe(()=>{
			if ( wp.data.select('core/editor').getEditedPostAttribute('status').match(/draft/) ){
				$('#n2-save-post,#n2-save-as-pending').removeClass('d-none');
				$('#normal-sortables, .editor-post-title').removeClass('pe-none')
					.find('input,textarea,select').removeClass('border-0');
				if ( wp.data.select('core/editor').isPostSavingLocked() ){
					wp.data.dispatch( 'core/editor' ).unlockPostSaving('n2-lock');
					$('.interface-interface-skeleton__content').off('click');
				}
			}
			else {
				$('#n2-save-post,#n2-save-as-pending').addClass('d-none');
				$('#normal-sortables, .editor-post-title').addClass('pe-none')
					.find('input,textarea,select').addClass('border-0');
				if ( ! wp.data.select('core/editor').isPostSavingLocked() ){
					wp.data.dispatch( 'core/editor' ).lockPostSaving('n2-lock');
					$('.interface-interface-skeleton__content').off('click').on('click', () => alert('スチームシップに送信後の編集はできません。'));
				}
			}
		});
		$('#n2-save-as-pending').on('click', () => {
			// 必須項目が入っていない場合は送信できなくする
			if ( rejection().length > 0 ) {
				alert( '以下の項目が入力されていないため送信できません\n\n・' + rejection().join('\n・') );
				return;
			}
			if ( ! confirm('スチームシップへ送信後の編集はできません。本当に送信しますか？') ) return;
			$('#n2-save-as-pending span').attr('class', 'spinner-border spinner-border-sm me-2');
			// フォーカス外して保存した場合にVueの$watchが発火しないので強制$watch
			n2.tmp.vue.$data._force_watch++;
			// フォーカス外さずそのまま保存した場合にVueのwatchが発火しないのでresolveを待つ
			new Promise( resolve => {
				n2.tmp.save_post_promise_resolve = resolve;
			}).then(()=>{
				// カスタムフィールドの保存
				const meta = get_meta();
				wp.data.dispatch('core/editor').editPost({ meta, status: 'pending' });
				wp.data.dispatch('core/editor').savePost().then(()=>{
					$('#n2-save-as-pending span').attr('class', 'dashicons dashicons-saved me-2');
					$(window).off('beforeunload');
					n2.tmp.diff = false;
					$('#n2-hypernavi').attr('src',$('#n2-hypernavi').attr('src'));
				});
			});
		});
	});
};

export default { append_button, rejection };