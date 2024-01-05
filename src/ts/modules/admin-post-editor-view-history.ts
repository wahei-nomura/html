/**
 * 投稿の履歴
 * 
 * @param string target 履歴を見るボタンを追加する要素のセレクタ名
 * @param any $ jQuery
 */
export default (target: string, $:any = jQuery) => {
	// ターゲットDOMが生成されてから
	$(target).ready(() => {
		if ( window['n2'].current_user.roles.includes('jigyousya') || window['n2'].current_user.roles.includes('local-government') ) return
		// 履歴ボタン配置
		$(target).prepend(`
		<form method="get" action="${window['n2'].ajaxurl}" target="_blank">
			<input type="hidden" name="action" value="n2_post_history_api">
			<input type="hidden" name="type" value="table">
			<input type="hidden" name="order" value="desc">
			<input id="n2-post-id" type="hidden" name="post_id" value="${window['wp'].data.select('core/editor').getCurrentPostId()}">
			<button class="btn btn-sm btn-outline-secondary d-flex align-items-center" title="履歴を見る">履歴を見る</button>
		</form>
		`);
		
	})
}