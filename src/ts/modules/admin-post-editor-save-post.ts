import get_meta from "./admin-post-editor-get-meta";
import _ from 'lodash';
const $ = jQuery;
const n2 = window['n2'];
const btn_class = {
	save: 'btn btn-sm btn-dark d-flex align-items-center px-4',
	saved: 'btn btn-sm btn-outeline-dark d-flex align-items-center disabled'
}
/**
 * 返礼品の保存
 * 
 * @param $ jQuery
 * @param string target 返礼品の保存を追加する要素のセレクタ名
 */
export default (target: string) => {
	const wp = window['wp'];
	$('body').on('click keyup', e => {
		if( 'n2-save-post' === $(e.target).attr('id') ) return;
		setTimeout(save_button_toggler,1);
	});
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		// 保存ボタン配置
		const button = 'auto-draft' == wp.data.select('core/editor').getEditedPostAttribute('status')
			? `<div id="n2-save-post" class="${btn_class.save}" title="保存"><span></span>保存</div>`
			: `<div id="n2-save-post" class="${btn_class.saved}" title="保存"><span class="dashicons dashicons-saved me-2"></span>保存</div>`;
		$(target).prepend(button);
		$('#n2-save-post').on('click', () => {
			if ( ! wp.data.select('core/editor').getEditedPostAttribute('title') ) {
				alert('保存するには返礼品の名前を入力してください');
				return;
			}
			$('#n2-save-post span').attr('class', 'spinner-border spinner-border-sm me-2');
			// フォーカス外して保存した場合にVueの$watchが発火しないので強制$watch
			n2.tmp.vue.$data._force_watch++;
			// フォーカス外さずそのまま保存した場合にVueの$watchの発火が間に合わないのでresolveを待つ
			new Promise( resolve => {
				n2.tmp.save_post_promise_resolve = resolve;
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
						n2.tmp.saved = _.cloneDeep(n2.tmp.vue.$data);
						n2.tmp.saved.tmp.post_title = wp.data.select('core/editor').getEditedPostAttribute('title');
						n2.tmp.saved.tmp.post_status = wp.data.select('core/editor').getEditedPostAttribute('status');
						n2.tmp.diff = false;
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
	});
};

export const save_button_toggler = () => {
	// 保存ボタン無しの場合は何もしない
	if( $('#n2-save-post').hasClass('d-none') ) return;
	const wp = window['wp'];
	// 差分チェック
	const data = _.cloneDeep( n2.tmp.vue.$data );
	data.tmp = _.cloneDeep( n2.tmp.saved.tmp ?? {} );
	data.tmp.post_title = wp.data.select('core/editor').getEditedPostAttribute('title');
	data.tmp.post_status = wp.data.select('core/editor').getEditedPostAttribute('status');
	n2.tmp.diff = ! _.isEqualWith(n2.tmp.saved, data, (a,b)=>{ if(a==b) return true } );// 型無視して比較
	// 総務省申請理由がない場合は保存させない
	n2.tmp.diff = '不要' == data.総務省申請 && ! data.総務省申請不要理由 ? false : n2.tmp.diff;
	if ( n2.tmp.diff ){
		$('#n2-save-post').attr('class', btn_class.save).find('span').attr('class', '');
		$(window).on('beforeunload', () => '' );
	} else {
		$('#n2-save-post').attr('class', btn_class.saved).find('span').attr('class', 'dashicons dashicons-saved me-2');
		$(window).off('beforeunload');
	}
};