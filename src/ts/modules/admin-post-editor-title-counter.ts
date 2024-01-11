/**
 * ブロックエディタへ目次を追加する
 *
 * @param any $ JQuery
 */
export default ($:any = jQuery) => {
	const n2 = window['n2'];
	const wp = window['wp'];
	// タイトル文字数カウンター
	$('.editor-post-title__input').ready(function () {
		$('.editor-post-title__input').before('<div id="n2-title-counter" class="badge bg-dark position-absolute top-100 end-0 rounded-0 shadow-sm">');
		wp.data.subscribe(()=>{
			$('#n2-title-counter').html(`${wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' ).length}文字`);
		});
	});
};