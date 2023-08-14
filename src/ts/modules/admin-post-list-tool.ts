/**
 * チェックされた投稿idのセッション保存
 * 投稿idの破棄（全部・１つ１つ）
 * 投稿idを元に商品APIを使ってリスト表示
 * 
 * @param any $ jQuery
 */
import Vue from 'vue/dist/vue.min'
export default ($: any) => {
	const n2 = window['n2'];
	new Vue({
		el: '#n2-admin-post-list-tool',
		data: {
			id: false,
			item: false,
			custom_field: []
		},
		created() {
			const role = n2.current_user.roles[0];
			// カスタムフィールドを整頓
			this.custom_field = 'jigyousya' !== role
				? [
					...Object.keys(n2.custom_field['自治体用']),
					...Object.keys(n2.custom_field['スチームシップ用']),
				]
				: ['返礼品コード'];

			this.custom_field = [
				...this.custom_field,
				...Object.keys(n2.custom_field['事業者用']),
			];
			let hides = ['N1zip','商品画像'];
			if ( 'jigyousya' == role ) hides.push('送料');
			this.custom_field = this.custom_field.filter(v => ! hides.includes(v));
			// ツールボックスを挿入
			$('#the-list .hentry').each(function(){
				const id = $(this).attr('id').split('-')[1];
				$(this).find('td.title').prepend(`<div class="n2-admin-post-list-tool-open" data-id="${id}">`);
			});
			// ポップオーバー
			$('.n2-admin-post-list-tool-open').on('click', async e => {
				this.id = $(e.target).data('id');
				await this.set_item_data();
				$('#n2-admin-post-list-tool').get(0).showPopover();
			});
		},
		methods: {
			async set_item_data() {
				const item = await $.ajax({
					url: n2.ajaxurl,
					data: {
						action: 'n2_items_api',
						post__in: [this.id],
						post_status: ['any', 'trash'],
					},
				});
				this.item = item.items[0] || false;
				console.log(this.item)
			}
		}
	})
}