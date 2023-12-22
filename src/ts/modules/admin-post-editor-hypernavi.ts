import {set_default_meta} from "./admin-post-editor-get-meta";
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
			$(e.target).contents().find('[href$="post-new.php"]').attr('target', '_parent');
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
				// dataの浄化
				data = set_default_meta($, data.items[0]);
				console.log(data)
				const p = {
					id,
					title: data.タイトル,
					status: data.ステータス,
				}
				wp.data.dispatch('core/editor').editPost(p);
				for ( const k in n2.vue.$data ) {
					n2.vue.$data[k] = data[k] ?? n2.vue.$data[k];
				}
				$('title').text(data.タイトル);
				$('#n2-view-history-id').val(id);
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