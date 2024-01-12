/**
 * 投稿ステータスのプログレスバーの制御
 *
 * @param any $ JQuery
 */
export default ($:any = jQuery) => {
	const n2 = window['n2'];
	const wp = window['wp'];
	const statuses = [
		{
			status: 'auto-draft',
			label: '入力開始',
			class: 'progress-bar bg-secondary col-1',
		},
		{
			status: 'draft',
			label: '入力中',
			class: 'progress-bar bg-secondary col-3',
		},
		{
			status: 'pending',
			label: 'スチームシップ 確認中',
			class: 'progress-bar bg-danger col-6',
		},
		{
			status: 'publish',
			label: 'ポータル登録準備中',
			class: 'progress-bar bg-primary col-9',
		},
		{
			status: 'registered',
			label: 'ポータル登録済',
			class: 'progress-bar bg-success col-12',
		},
		{
			status: 'private',
			label: '非公開',
			class: 'progress-bar bg-dark col-12',
		},
		{
			status: 'trash',
			label: 'ゴミ箱',
			class: 'progress-bar bg-white text-dark col-12',
		},
	];
	$(".edit-post-layout__metaboxes").ready(() => {
		// プログレスバー
		$('.edit-post-header').before('<div id="n2-progress" class="progress rounded-0" style="height: 1.5em;width: 100%;"><div></div></div>');
		// ステータスの更新
		wp.data.subscribe(()=>{
			const status = statuses.find( v => v.status === wp.data.select('core/editor').getEditedPostAttribute('status') );
			$('#n2-progress > *').text(status.label).attr( 'class', status.class );
		});
		// 事業者はこれ以上何もしない
		if ( n2.current_user.roles.includes('jigyousya') ) return;
		$('#n2-progress')
			.css({cursor: 'pointer',height: '2.5em'})
			.on('mousemove', e => {
				const level = Math.ceil( e.clientX*4 /$('#n2-progress').width() );
				$('#n2-progress').attr('title', `「${statuses[level].label}」に変更`)
			})
			.on('click', e => {
				const level = Math.ceil( e.offsetX*4 /$('#n2-progress').width() );
				wp.data.dispatch('core/editor').editPost({ status: statuses[level].status });
			});

	});
};