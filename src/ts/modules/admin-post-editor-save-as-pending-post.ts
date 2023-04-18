/**
 * スチームシップへ送信
 * 
 * @param $ jQuery
 * @param string target スチームシップへ送信を追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	const n2 = window['n2'];
	const wp = window['wp'];
	$(target).ready(() => {
		/**
		 * バリデーション（拒否項目を配列で返す）
		 * @returns rejection
		 */
		const rejection = () => {
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
		}
		const status = wp.data.select("core/editor").getEditedPostAttribute("status");
		if ( ! ( n2.current_user.roles.includes('jigyousya') && status.match(/draft/) )	) return

		// 保存ボタン配置
		$(target).append(`<div id="n2-save-as-pending" class="btn btn-sm btn-primary d-flex align-items-center${rejection().length > 0 ? ' opacity-50': ''}" title="スチームシップへ送信"><span></span>スチームシップへ送信</div>`);

		$('#n2-save-as-pending').on('click', () => {
			// 必須項目が入っていない場合は送信できなくする
			if ( rejection().length > 0 ) {
				alert( '以下の項目が入力されていないため送信できません\n\n・' + rejection().join('\n・') );
				return;
			}
			if ( ! confirm('スチームシップへ送信後の編集はできません。本当に送信しますか？') ) return;
			$('#n2-save-as-pending span').attr('class', 'spinner-border spinner-border-sm me-2');
			wp.data.dispatch('core/editor').editPost({ status: 'pending' });
			wp.data.dispatch('core/editor').savePost().then(()=>{
				$('#n2-save-as-pending span').attr('class', 'dashicons dashicons-saved me-2');
				location.reload();
			})
		});

		$('body').on('click keyup', e => {
			if( 'n2-save-post' === $(e.target).attr('id') ) return;
			if ( rejection().length > 0 ) {
				$('#n2-save-as-pending').addClass('opacity-50');
			} else {
				$('#n2-save-as-pending').removeClass('opacity-50');
			}
		});

	})
}