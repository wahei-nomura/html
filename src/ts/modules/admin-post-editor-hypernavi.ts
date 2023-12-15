/**
 * 爆速ナビを追加
 *
 * @param any $ JQuery
 */
export default ($:any = jQuery) => {
	const n2 = window['n2'];
	const wp = window['wp'];
	// 一覧ページを突っ込む
	const hypernavi_generator = () => {
		$('.interface-complementary-area.edit-post-sidebar .components-panel').html('<iframe id="n2-hypernavi" src="edit.php?post_type=post">');
		$('#n2-hypernavi').on('load', e => {
			$(e.target).contents().find('.row-title').on('click', async e => {
				e.preventDefault();
				const id = $(e.target).parents('tr').attr('id').replace(/[^0-9]/g, '');
				console.log(id);
				let data = await $.ajax({
					url: n2.ajaxurl,
					data: {
						action: 'n2_items_api',
						p: id,
					}
				});
				data = data.items[0];
				const p = {
					id,
					title: data.タイトル,
					status: data.ステータス,
				}
				wp.data.dispatch('core/editor').editPost(p);
				for ( const k in n2.vue.$data ) {
					if ( undefined == data[k] ) {
						// tmpと_force_watch以外初期化
						if ( ! k.match(/^(tmp|_force_watch)$/) ) {
							switch ( typeof n2.vue.$data[k] ) {
								case 'object': n2.vue.$data[k] = []; break;
								default: n2.vue.$data[k] = '';
							}
						}
					} else {
						n2.vue.$data[k] = data[k];
					}
				}
			});
		});
	}
	$('.interface-complementary-area.edit-post-sidebar .components-panel').ready(() => {
		$(".interface-complementary-area.edit-post-sidebar .components-panel").ready(hypernavi_generator);
		$('.interface-pinned-items button').on('click', () => {
			$(".interface-complementary-area.edit-post-sidebar .components-panel").ready(hypernavi_generator);
		});
	});
};