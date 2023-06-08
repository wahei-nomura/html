/**
 * チェックされた投稿idのセッション保存
 * 投稿idの破棄（全部・１つ１つ）
 * 投稿idを元に商品APIを使ってリスト表示
 * 
 * @param $ jQuery
 */
import Vue from 'vue/dist/vue.min'
export default ($: any) => {
	const n2 = window['n2'];
	new Vue({
		el: '#n2-checked-posts',
		data: {
			active: false,
			ids: [],
			thead: ['', '返礼品コード', 'タイトル', '価格', '寄附金額', '事業者名'],
			items: [],
		},
		async created(){
			this.ids = (sessionStorage.getItem('n2_checked_post_ids') || '').split(',').filter(v=>v);
			if ( this.ids ) {
				this.set_items();
				this.update_checked();
				$('#n2-checked-posts').css('display', 'block');
				$('#posts-filter .check-column input').change(() => {
					// 表示中のid一覧
					let displaying_ids:any = $('#posts-filter input[name="post[]"]');
					displaying_ids = Array.from( displaying_ids );
					displaying_ids = displaying_ids.map( v=>v.value );
					// 表示中のidを一旦全削除
					this.ids = this.ids.filter( v => ! displaying_ids.includes(v) && v );
					// チェックされたidを追加
					const checked_ids = $('#posts-filter input[name="post[]"]').serializeArray().map(v=>v.value);
					this.ids = [...this.ids, ...checked_ids ];
					this.ids = new Set(this.ids);
					this.ids = Array.from(this.ids);
					sessionStorage.setItem('n2_checked_post_ids', this.ids.join(','));
					this.set_items();
				});
			}
		},
		methods: {
			async set_items() {
				// idsがない場合は初期化
				if ( ! this.ids.length ) {
					this.items = [];
					return;
				}
				this.items = await $.ajax({
					url: n2.ajaxurl,
					data: {
						action: 'n2_items_api',
						post__in: this.ids,
						orderby: 'post__in',
					},
				});
				this.items = this.items.items;
				console.log(this.items)
			},
			clear_ids( id ) {
				// id指定の場合はidのみ削除
				if ( id ) {
					this.ids = this.ids.filter(v=>v!=id);
					this.items = this.items.filter(v=>v.id!=id);
					sessionStorage.setItem('n2_checked_post_ids', this.ids.join(','));
					this.active = this.ids.length ? this.active : false;
				} else{
					if( confirm('全解除してよろしいですか') ) {
						sessionStorage.clear();
						this.items = this.ids = [];
						this.active = false;
					}
				}
				this.update_checked();
			},
			update_checked() {
				$('#posts-filter .check-column input').each((k,v) => {
					this.ids.includes($(v).attr('value')) ? $(v).prop('checked', true) : $(v).prop('checked', false);
				});
			}
		}
	});
}