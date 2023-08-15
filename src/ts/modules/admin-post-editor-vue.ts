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
	// current_user追加
	data['current_user'] = n2.current_user.roles[0];
	data['楽天SPA'] = n2.portal_setting.楽天.spa || '';
	data['寄附金額チェッカー'] = '';
	data['寄附金額自動計算値'] = '';
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
		
		this.寄附金額 = await this.calc_donation(this.価格,this.送料,this.定期便);
		this.show_submit();
		// 発送サイズ・発送方法をダブル監視
		this.$watch(
			() => {
				return {
					全商品ディレクトリID: this.$data.全商品ディレクトリID ? this.$data.全商品ディレクトリID.text : false,
					価格: this.$data.価格,
					発送方法: this.$data.発送方法,
					発送サイズ: this.$data.発送サイズ,
					送料: this.$data.送料,
					その他送料: this.$data.その他送料,
					定期便: this.$data.定期便,
				}
			},
			async function(newVal, oldVal) {
				// タグIDのリセット
				if ( newVal.全商品ディレクトリID != oldVal.全商品ディレクトリID && this.タグID.text.length ) {
					if ( confirm('全商品ディレクトリIDが変更されます。\nそれに伴い入力済みのタグIDをリセットしなければ楽天で地味にエラーがでます。\n\nタグIDをリセットしてよろしいでしょうか？') ) {
						this.タグID.list = [];
						this.タグID.text = '';
					}
				}
				// 寄附金額の算出
				const size = [
					newVal.発送サイズ,
					newVal.発送方法 != '常温' ? 'cool' : ''
				].filter(v=>v);
				this.送料 = 'その他' == this.発送サイズ
					? newVal.その他送料
					: n2.delivery_fee[size.join('_')] || '';
				this.寄附金額 = await this.calc_donation(newVal.価格,this.送料,newVal.定期便);
				// 保存ボタン
				this.show_submit();
			},
		);
		// テキストエリア調整
		$('textarea[rows="auto"]').each((k,v)=>{
			this.auto_fit_tetxarea(v)
		});
		// 投稿のメタ情報を全保存
		n2.saved_post = JSON.stringify($('form').serializeArray());
	};
	const methods = {
		// 説明文・テキストカウンター
		set_info(target) {
			$(target).parents('.n2-fields-value').find('.d-none').removeClass('d-none');
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
			$(target).parents('.n2-fields-value').find('.n2-field-addition.d-none').removeClass('d-none');
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
				const add =  n2.vue.商品画像.filter( v => v.nonces );
				images.state().get('selection').add( add.map( v => wp.media.attachment(v.id) ) );
			});
			images.on( 'select close', () => {
				images.state().get('selection').forEach( img => {
					if ( ! n2.vue.商品画像.find( v => v.id == img.attributes.id ) ) {
						n2.vue.商品画像.push( img.attributes );
					}
				})
			});
			images.open();
		},
		// 楽天の全商品ディレクトリID取得（タグIDでも利用）
		async get_genreid(){
			const settings = {
				url: '//app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222',
				data: {
					applicationId: '1002772968546257164',
					genreId: this.全商品ディレクトリID.text || '0',
				},
			};
			this.全商品ディレクトリID.list = await $.ajax(settings);
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
			this.check_donation();
			const opt = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_donation_amount_api',
					price,
					delivery_fee,
					subscription,
				}
			}
			this.寄附金額自動計算値 = await $.ajax(opt);
			if ( this.寄附金額固定.filter(v=>v).length ) {
				this.check_donation();
				return this.寄附金額;
			}
			this.寄附金額チェッカー = '';
			return this.寄附金額自動計算値;
		},
		// 寄附金額の更新
		async update_donation(){
			alert(`価格：${Number(this.価格).toLocaleString()}\n送料：${Number(this.送料).toLocaleString()}\n定期便回数：${this.定期便}\nを元に再計算します。`);
			this.寄附金額 = await this.calc_donation(this.価格, this.送料, this.定期便);
			console.log(this.寄附金額)
		},
		check_donation() {
			const check = ['text-danger', '', 'text-success'];
			if ( this.寄附金額自動計算値 ) {
				this.寄附金額チェッカー = check[ Math.sign( this.寄附金額 - this.寄附金額自動計算値 ) + 1 ];
			}
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
		},
		// 説明文の例文を挿入
		insert_example_description( target ) {
			const textarea = $(target).parents('.n2-fields-value').find('textarea');
			let text = textarea.val();
			// 後にここで事業者用のテンプレートがあるか確認してゴニョゴニョする
			text += n2.custom_field.事業者用.説明文.placeholder
			textarea.val(text);
		},
		// 楽天SPA
		async get_spa_category() {
			const folderCode = '1p7DlbhcIEVIaH7Rw2mTmqJJKVDZCumYK';
			const settings = {
				url: '//www.googleapis.com/drive/v3/files/',
				data: {
					key: 'AIzaSyDQ1Mu41-8S5kBpZED421bCP8NPE7pneNU',
					q: `'${folderCode}' in parents and name = '${n2.town}' and mimeType contains 'spreadsheet'`,
				}
			};
			const d = await $.ajax(settings).catch(() => false);
			if ( !d || ! d.files.length ) {
				console.log('自治体スプレットシートが存在しません', d);
				return;
			}
			settings.url = `//sheets.googleapis.com/v4/spreadsheets/${d.files[0].id}/values/カテゴリー`;
			delete settings.data.q;
			const cat = await $.ajax(settings).catch(() => false);
			if ( ! cat ) {
				console.log('カテゴリー情報の取得失敗');
				return;
			}
			delete cat.values[0];
			n2.vue.楽天SPAカテゴリー.list = cat.values.map( (v,k) => {
				if ( ! v.length ) return
				v.forEach((e,i) => {
					v[i] = e || cat.values[k-1][i];
					v[i] = v[i].replace('.','');
				});
				return `#/${v.join('/')}/`;
			}).filter(v=>v);
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