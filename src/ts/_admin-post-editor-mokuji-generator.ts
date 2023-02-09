/**
 * ブロックエディタへ目次を追加する
 *
 * @param $ JQuery
 */
export default $ => {
	// 目次
	const mokuji_generator = () => {
		$('.edit-post-editor__list-view-panel-content').addClass('p-0').html('<div id="目次">');
		let html = '';
		// スチームシップ用 目次
		if ( ! window['n2'].current_user.roles.includes('jigyousya') ) {
			for ( const label of  $('#スチームシップ用 .n2-fields-list').map((k,v)=>$(v).attr('id')) ) {
				html += `<a href="#${label}" class="p-1 px-2 border-bottom small d-block text-decoration-none text-dark" style="background: #efefef;">${label}</a>`;
			}
		}
		// 事業者用 目次
		for ( const label of  $('#事業者用 .n2-fields-list').map((k,v)=>$(v).attr('id')) ) {
			html += `<a href="#${label}" class="p-1 px-2 border-bottom small d-block text-decoration-none text-dark">${label}</a>`;
		}
		$('#目次').html(html);
		// 目次クリックで点滅
		$('#目次 a').on('click', function(e){
			e.preventDefault();
			let position = $(`#${$(this).text()}`).parents('.postbox').position().top;
			position += $(`#${$(this).text()}`).position().top;
			$('.interface-interface-skeleton__content').animate({scrollTop:position}, 300);
			$(`#${$(this).text()}`).delay(300).animate({opacity: 0}, 200).animate({opacity: 1}, 200);
		});
		if ( ! window['n2'].observer ) {
			// カスタムフィールドのDOMを監視して目次を再生成
			window['n2'].observer = new MutationObserver(mokuji_generator);
			window['n2'].observer.observe( $('#poststuff').get(0), { subtree: true, childList: true } );
		}
	}
	$(".edit-post-header-toolbar__list-view-toggle").ready(() => {
		$('.edit-post-header-toolbar__list-view-toggle').on('click', () => {
			$(".edit-post-editor__list-view-panel-content").ready(mokuji_generator);
			// クッキーに状態保存
			document.cookie = window['n2'].cookie['n2-mokuji'] ? 'n2-mokuji=true; max-age=0' : 'n2-mokuji=true';
		});
		if ( window['n2'].cookie['n2-mokuji'] ) {
			$('.edit-post-header-toolbar__list-view-toggle').click();
		}
	});
};