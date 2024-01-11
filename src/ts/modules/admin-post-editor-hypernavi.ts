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
		$('.interface-complementary-area.edit-post-sidebar .components-panel')
			// iframe埋め込み
			.prepend('<iframe id="n2-hypernavi" src="edit.php?post_type=post">')
			// リサイズ制御
			.on('mousedown', () => {
				if(n2.tmp._isResizing) return;
				n2.tmp._isResizing = true;
				$('#n2-hypernavi').parent().addClass('is-resizing');
			});
		// リサイズ制御
		$('body')
			.on('mousemove', e => {
				if(!n2.tmp._isResizing) return;
				const w = document.body.clientWidth - e.clientX;
				$('#n2-hypernavi').width(w);
				document.cookie = `n2-hypernavi-width=${w}`;
				n2.cookie['n2-hypernavi-width'] = w;
			})
			.on('mouseup', () => {
				if(!n2.tmp._isResizing) return;
				n2.tmp._isResizing = false
				$('#n2-hypernavi').parent().removeClass('is-resizing');
			});
		
		$('#n2-hypernavi')
			.width(n2.cookie['n2-hypernavi-width']||803)
			.on('load', e => {
				// 親フレームで開きたい
				$(e.target).contents().find('#wp-admin-bar-root-default a,#menu-dashboard a[href="my-sites.php"],[href$="post-new.php"],#wp-admin-bar-logout a').attr('target', '_parent');
				$(e.target).contents().find(`#post-${wp.data.select('core/editor').getCurrentPostId()}`).addClass('is-active')
				$(e.target).contents().find('.row-title').on('click', async e => {
					e.preventDefault();
					const id = $(e.target).parents('tr').attr('id').replace(/[^0-9]/g, '');
					change_data(id);
					// url変更
					const url = new URL( location.href );
					url.searchParams.set('post', id);
					if ( url.href == location.href ) {
						return;
					}
					history.pushState({id}, null, url);
				});
			});
	};
	const change_data = async (id) => {
		if ( n2.tmp.diff && ! confirm( '保存せずに移動すると編集したデータは失われます。本当に移動しますか？' ) ) {
			return;
		}
		// 履歴変更
		$('#n2-post-id').val(id);
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
		console.log(id,data);
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
		// 迷子防止
		$('#n2-hypernavi').contents().find('#the-list > tr').removeClass('is-active');
		$('#n2-hypernavi').contents().find(`#post-${id}`).addClass('is-active');
		// 画像DL＆N1ZIPDLの制御
		$('#n2-download-images,#n2-download-zip').hide();
		if ( data.商品画像.length ) $('#n2-download-images').show();
		if ( data.N1zip ) $('#n2-download-zip').show();
		save_button_toggler();
		// ↓　N2オートセーブ（タイトルのみでほぼ無意味なので、contentの中のmetaで復旧するようにしたら使える）
		// window.sessionStorage.setItem(`wp-autosave-block-editor-post-${id}`, JSON.stringify({
		// 	post_title: wp.data.select( 'core/editor' ).getEditedPostAttribute('title'),
		// 	content: wp.data.select( 'core/editor' ).getEditedPostContent(),
		// }));
	};
	window.addEventListener("popstate", e => change_data(e.state.id) );
	$('.interface-complementary-area.edit-post-sidebar .components-panel').ready(() => {
		$(".interface-complementary-area.edit-post-sidebar .components-panel").ready(hypernavi_generator);
		$('.interface-pinned-items button').on('click', () => {
			$(".interface-complementary-area.edit-post-sidebar .components-panel").ready(hypernavi_generator);
		});
	});
};