/**
 * チェックされた投稿idのセッション保存
 * 投稿idの破棄（全部・１つ１つ）
 * 投稿idを元に商品APIを使ってリスト表示
 * 
 * @param any $ jQuery
 */
export default ($: any) => {
	$('#posts-filter .n2-view-post').click(async function(){
		const n2 = window['n2'];
		const id = $(this).data('id');
		const item = await $.ajax({
			url: n2.ajaxurl,
			data: {
				action: 'n2_items_api',
				post__in: [id],
				orderby: 'post__in',
			},
		});
		$(this).append('item')
		console.log(item);
	});
}