import get_meta from "./admin-post-editor-get-meta";
/**
* バリデーション（拒否項目を配列で返す）
* @returns rejection
*/
const rejection = ($: any = jQuery) => {
	let rejection = [];
	// 必須項目対象
	let required = $('.edit-post-layout__metaboxes [required]').serializeArray();
	rejection = required.filter( v => !v.value );
	// アレルギー品目ありの場合はアレルゲンを必須にする
	if ( 1 == required.filter( v => ( v.value.match(/アレルギー品目あり/) || v.name.match(/アレルゲン/) ) ).length ) {
		rejection.push({ name: 'n2field[アレルゲン]' });
	}
	rejection = rejection.map( v => v.name.match(/n2field\[(.*?)\]/)[1] );
	return rejection;
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
	$(target).ready(() => {
		const status = wp.data.select("core/editor").getEditedPostAttribute("status");
		// 事業者の下書き状態以上では何もしない
		if ( ! ( n2.current_user.roles.includes('jigyousya') && status.match(/draft/) )	) return;
		// 「スチームシップへ送信」ボタン配置
		$(target).before(`<div id="n2-save-as-pending" class="btn btn-sm btn-primary d-flex align-items-center" title="スチームシップへ送信"><span></span>スチームシップへ送信</div>`);

		$('#n2-save-as-pending').on('click', () => {
			// 必須項目が入っていない場合は送信できなくする
			if ( rejection().length > 0 ) {
				alert( '以下の項目が入力されていないため送信できません\n\n・' + rejection().join('\n・') );
				return;
			}
			if ( ! confirm('スチームシップへ送信後の編集はできません。本当に送信しますか？') ) return;
			$('#n2-save-as-pending span').attr('class', 'spinner-border spinner-border-sm me-2');
			// カスタムフィールドの保存
			const meta = get_meta();
			wp.data.dispatch( 'core/editor' ).editPost({ meta, status: 'pending' });
			wp.data.dispatch('core/editor').savePost().then(()=>{
				$('#n2-save-as-pending span').attr('class', 'dashicons dashicons-saved me-2');
				$(window).off('beforeunload');
				location.reload();
			});
		});
	});
};

export default { append_button, rejection };