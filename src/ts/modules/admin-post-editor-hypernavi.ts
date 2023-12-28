import _ from 'lodash';
import {set_default_meta} from "./admin-post-editor-get-meta";
import {save_button_toggler} from "./admin-post-editor-save-post"
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
		$('.interface-complementary-area.edit-post-sidebar .components-panel').prepend('<iframe id="n2-hypernavi" src="edit.php?post_type=post">');
		$('#n2-hypernavi').on('load', e => {
			$(e.target).contents().find('[href$="post-new.php"]').attr('target', '_parent');
			$(e.target).contents().find('.row-title').on('click', async e => {
				e.preventDefault();
				if ( n2.tmp.diff && ! confirm( '保存せずに移動すると編集したデータは失われます。本当に移動しますか？' ) ) {
					return;
				}
				const id = $(e.target).parents('tr').attr('id').replace(/[^0-9]/g, '');
				$('#n2-view-history-id').val(id);// 履歴変更
				// オートセーブの制御（疑似移動した際にオートセーブ発火するのを防ぐ）
				const autosave = id != wp.data.select('core/editor').getCurrentPostId()
					? 'lockPostAutosaving' // lock
					: 'unlockPostAutosaving';
				wp.data.dispatch( 'core/editor' )[autosave]( 'n2-hypernavi-lock' );
				// データ取得
				let data = await $.ajax({
					url: n2.ajaxurl,
					data: {
						action: 'n2_items_api',
						p: id,
					}
				});
				// dataの浄化
				data = set_default_meta($, data.items[0]);
				console.log(id,data)
				const post = {
					id,
					title: data.タイトル,
					status: data.ステータス,
				}
				wp.data.dispatch('core/editor').editPost(post);
				$('title').text(post.title);// タイトル変更
				for ( const k in n2.tmp.vue.$data ) {
					n2.tmp.vue.$data[k] = data[k] ?? n2.tmp.vue.$data[k];
				}
				n2.tmp.vue.$data.tmp.post_title = post.title;
				n2.tmp.vue.$data.tmp.post_status = post.status;
				n2.tmp.saved = _.cloneDeep(n2.tmp.vue.$data);
				// url変更
				const url = new URL( location.href );
				url.searchParams.set('post', id);
				history.pushState(null, null, url);
				save_button_toggler();
				// ↓　N2オートセーブ（タイトルのみでほぼ無意味なので、contentの中のmetaで復旧するようにしたら使える）
				// window.sessionStorage.setItem(`wp-autosave-block-editor-post-${id}`, JSON.stringify({
				// 	post_title: wp.data.select( 'core/editor' ).getEditedPostAttribute('title'),
				// 	content: wp.data.select( 'core/editor' ).getEditedPostContent(),
				// }));

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