/**
 * 投稿の履歴
 * 
 * @param $ jQuery
 * @param string target 履歴を見るボタンを追加する要素のセレクタ名
 */
export default ($: any, target: string) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		if ( ! window['n2'].current_user.roles.includes('ss-crew') ) return
		// 履歴ボタン配置
		$(target).prepend(`<a class="btn btn-sm btn-outline-secondary d-flex align-items-center" title="変更履歴を見る（仮）" href="${window['n2'].ajaxurl}?action=n2_post_history_api&post_id=${window['wp'].data.select('core/editor').getCurrentPostId()}&type=table&order=desc" target="_blank">履歴を見る</a>`);
		
	})
}