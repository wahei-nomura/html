import Vue from 'vue/dist/vue.min'
import draggable from 'vuedraggable'
/**
 * カスタムフィールドをVueで制御
 *
 * @param $ jQuery
 */
export default $ => {
	const n2 = window['n2'];
	const wp = window['wp'];
	const data = {};
	for( const id in n2.custom_field ){
		for( const name in n2.custom_field[id] ){
			data[name] = n2.custom_field[id][name].value;
		}
	}
	data['delivery_fee'] = n2.delivery_fee;
	const created = async function() {
		this.全商品ディレクトリID = {
			text: this.全商品ディレクトリID,
			list: [],
		};
		this.タグID = {
			text: this.タグID,
			group: '',
			list: [],
		};

		this.寄附金額 = this.寄附金額 || await this.calc_donation(this.価格,this.送料,this.定期便);
		this.show_submit();
		// 発送サイズ・発送方法をダブル監視
		this.$watch(
			() => {
				return {
					価格: this.$data.価格,
					発送方法: this.$data.発送方法,
					発送サイズ: this.$data.発送サイズ,
					送料: this.$data.送料,
					定期便: this.$data.定期便,
				}
			},
			async function(newVal, oldVal) {
				const size = [
					newVal.発送サイズ,
					newVal.発送方法 != '常温' ? 'cool' : ''
				].filter(v=>v);
				this.送料 = n2.delivery_fee[size.join('_')] || newVal.送料;
				// 発送サイズ未選択で送料リセット
				if ( ! newVal.発送サイズ ) {
					this.送料 = '';
				}
				this.寄附金額 = await this.calc_donation(newVal.価格,this.送料,newVal.定期便);
				this.show_submit();
			},
		);
		// テキストエリア調整
		$('textarea[rows="auto"]').each((k,v)=>{
			this.auto_fit_tetxarea(v)
		});
	};
	const methods = {
		// 説明文・テキストカウンター
		set_info(target) {
			const info = [
				$(target).parents('.n2-fields-value').data('description') && ! document.cookie.match(/n2-zenmode/) 
					? `<div class="alert alert-primary mb-2">${$(target).parents('.n2-fields-value').data('description')}</div>`
					: '',
				$(target).attr('maxlength')
					? `文字数：${($(target).val() as any).length} / ${$(target).attr('maxlength')}`
					: '',
			].filter( v => v );
			if ( ! info.length ) return
			if ( ! $(target).parents('.n2-fields-value').find('.n2-field-description').length ) {
				$(target).parents('.n2-fields-value').prepend(`<div class="n2-field-description small lh-base col-12">${info.join('')}</div>`);
			}
			
			if ( $(target).attr('maxlength') ) {
				$(target).parents('.n2-fields-value').find('.n2-field-description').html(info.join(''));
			}
		},
		// 強制半角数字入力
		force_half_size_text($event, type, target) {
			// 全角英数を半角英数に変換
			let text = $event.target.value.replace(/[Ａ-Ｚａ-ｚ０-９]/g, s => String.fromCharCode(s.charCodeAt(0) - 65248) );
			// 半角英数以外削除
			text = text.replace(/[^A-Za-z0-9]/g, '');
			switch (type) {
				case 'number':
					// 半角数字以外削除
					text = text.replace(/[^0-9]/g, '');
					break;
				case 'uppercase':
					text = text.toUpperCase();
					break;
				case 'lowercase':
					text = text.toLowerCase();
					break;
			}
			if ( ! target ) {
				return text;
			}
			$event.target.value = text;
			this[target] = text;
		},
		// メディアアップローダー関連
		add_media(){
			// N1の画像データにはnoncesが無い
			const images = wp.media({
				title: "商品画像", 
				multiple: "add",
				library: {type: "image"}
			});
			images.on( 'open', () => {
				// N2のものだけに
				const add =  this.商品画像.filter( v => v.nonces );
				images.state().get('selection').add( add.map( v => wp.media.attachment(v.id) ) );
			});
			images.on( 'select', () => {
				this.商品画像 =  [
						...this.商品画像.filter( v => !v.nonces ),// N1のみ展開
						...images.state().get('selection').map( v => v.attributes )
					];
			});
			images.open();
		},
		// 楽天の全商品ディレクトリID取得（タグIDでも利用）
		async get_genreid( tagid_reset = false ){
			const settings = {
				url: '//app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222',
				data: {
					applicationId: '1002772968546257164',
					genreId: this.全商品ディレクトリID.text || '0',
				},
			};
			this.全商品ディレクトリID.list = await $.ajax(settings);
			if ( tagid_reset && this.タグID.text ) {
				if ( confirm('全商品ディレクトリIDが変更されます。\nそれに伴い入力済みのタグIDをリセットしなければ楽天で地味にエラーがでます。\n\nタグIDをリセットしてよろしいでしょうか？') ) {
					this.タグID.list = [];
					this.タグID.text = '';
				} else {
					this.タグID.text = this.タグID.text;
				}
			}
		},
		// タグIDと楽天SPAカテゴリーで利用
		update_textarea(id, target = 'タグID', delimiter = '/'){
			// 重複削除
			const arr = this[target].text ? Array.from( new Set( this[target].text.split( delimiter ) ) ): [];
			// 削除
			if ( arr.includes( id.toString() ) ) {
				this[target].text = arr.filter( v => v != id ).join( delimiter )
			}
			// 追加
			else {
				// 楽天のタグIDの上限
				if ( target == 'タグID' && arr.length >= ( $('[type="rakuten-tagid"]').attr('maxlength') as any)/8 ) return;
				this[target].text = [...arr, id].filter( v => v ).join( delimiter );
			}
			// 自動可変高　一瞬ずらさんとまだレンダリングされてない
			setTimeout( ()=>{
				$(`[name="n2field[${target}]"]`).get(0).dispatchEvent( new Event('focus') );
			}, 10 )
		},
		// 寄附金額計算
		async calc_donation(price, delivery_fee, subscription) {

			// 寄附金額固定の場合は計算しない
			if ( this.寄附金額固定.filter(v=>v).length ) return this.寄附金額;
			const opt = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_donation_amount_api',
					price,
					delivery_fee,
					subscription,
				}
			}
			return await $.ajax(opt);
		},
		// 寄附金額の更新
		async update_donation(){
			alert(`価格：${this.価格}\n送料：${this.送料}\n定期便回数：${this.定期便}\nを元に再計算します。`);
			this.寄附金額 = await this.calc_donation(this.価格, this.送料, this.定期便);
			console.log(this.寄附金額)
		},
		// スチームシップへ送信ボタンの制御
		show_submit() {
			if ( this.価格 > 0 && this.送料 > 0  ) {
				wp.data.dispatch( 'core/editor' ).unlockPostSaving( 'n2-lock' );
			} else {
				wp.data.dispatch( 'core/editor' ).lockPostSaving( 'n2-lock' );
			}
		},
		// テキストエリアの高さを自動可変式に
		auto_fit_tetxarea(textarea){
			$(textarea).height('auto').height($(textarea).get(0).scrollHeight);
		}
	};
	const components = {
		draggable,
	};
	// メタボックスが生成されてから
	$('.edit-post-layout__metaboxes').ready(()=>{
		n2.vue = new Vue({
			el: '.edit-post-layout__metaboxes',
			data,
			created,
			methods,
			components,
		});
	});
};