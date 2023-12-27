import Vue from 'vue/dist/vue.min'
import draggable from 'vuedraggable'
import loading_view from "./loading-view";
import save_as_pending from "./admin-post-editor-save-as-pending-post"
import {copy} from "./functions";

/**
 * カスタムフィールドをVueで制御
*
* @param $ jQuery
*/
export default ($: any = jQuery) => {
	const n2 = window['n2'];
	const wp = window['wp'];
	const data = {};
	for( const id in n2.custom_field ){
		for( const name in n2.custom_field[id] ){
			data[name] = n2.custom_field[id][name].value;
		}
	}
	data['_force_watch'] = 1;// 外部から変更して強制でwatchをFire
	// ※保存に必要ないデータは全部tmpへ（APIデータ・フラグなど）
	data['tmp'] = {
		post_title: '',
		post_status: '',
		current_user: n2.current_user.roles[0],
		number_format: true,// ３桁区切りカンマ用
		寄附金額自動計算値: '',
		寄附金額チェッカー: '',
		商品属性アニメーション: false,
		楽天SPA対応: n2.settings.楽天.楽天SPA || '',
		楽天SPAカテゴリー: [],
		楽天カテゴリー: [],
		楽天納期情報: {},
		楽天ジャンルID: [],
	};
	const created = async function() {
		this.tmp.post_title = wp.data.select('core/editor').getCurrentPostAttribute('title');
		this.tmp.post_status = wp.data.select('core/editor').getCurrentPostAttribute('status');

		// ローディング削除
		loading_view.show('#wpwrap', 500);

		save_as_pending.append_button("#n2-save-post");// スチームシップへ送信
		this.寄附金額 = await this.calc_donation(this.価格,this.送料,this.定期便);
		this.get_rakuten_category();
		await this.get_rakuten_delvdate();
		this.control_submit();
		// 発送サイズ・発送方法をダブル監視
		this.$watch(
			() => {
				// dataを全部監視する準備
				const data = {};
				for ( const name in this.$data ) {
					data[name] = this.$data[name];
				}
				console.log('watching')
				return data;
			},
			async function(newVal, oldVal) {
				// 保存ボタン
				this.control_submit();
				// 寄附金額の算出
				const size = [
					newVal.発送サイズ,
					newVal.発送方法 != '常温' ? 'cool' : ''
				].filter(v=>v);
				this.送料 = newVal.発送サイズ != oldVal.発送サイズ ? '' : this.送料 ;// 事業者に隠すため送料の初期化
				this.送料 = n2.settings['寄附金額・送料']['送料'][size.join('_')] || this.送料;// 送料設定されていない場合は送料をそのまま
				this.寄附金額 = await this.calc_donation(newVal.価格,this.送料,newVal.定期便);
				if ( n2.save_post_promise_resolve ) {
					n2.save_post_promise_resolve('resolve');
				}
			},
		);
		// テキストエリア調整
		$('textarea[rows="auto"]').each((k,v)=>{
			this.auto_fit_tetxarea(v)
		});
		// 保存の判定に使う
		n2.saved_post = copy(this.$data, true);
		// 「進む」「戻る」の制御をデフォルトに戻す
		wp.data.dispatch( 'core/keyboard-shortcuts' ).unregisterShortcut('core/editor/undo');
		wp.data.dispatch( 'core/keyboard-shortcuts' ).unregisterShortcut('core/editor/redo');
		this.check_tax(); 
	};
	const methods = {
		// 地場産品類型に応じて類型該当理由の注意書きを修正
		update_applicable_reason() {
			let info = n2.custom_field['スチームシップ用']['類型該当理由']['description'] + '<br>※記入例：' + n2.settings['N2']['類型該当理由注意書き'][this.地場産品類型];
			if(undefined !== n2.settings['N2']['類型該当理由注意書き'][this.地場産品類型]){
				$('#類型該当理由').find('.n2-fields-value').data('description',info);
				$('#類型該当理由').find('.n2-field-description').find('.alert-primary').html(info);
			}
		},
		// 説明文・テキストカウンター
		set_info(target) {
			let description_text = $(target).parents('.n2-fields-value').data('description');
			$(target).parents('.n2-fields-value').find('.d-none').removeClass('d-none');
			const info = [
				$(target).parents('.n2-fields-value').data('description') && ! document.cookie.match(/n2-zenmode/) 
					? `<div class="alert alert-primary mb-2">${description_text}</div>`
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
			text = text.replace(/[^A-Za-z0-9_-]/g, '');
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
			$event.target.value = this[target] = text;
		},
		// 価格の端数の自動調整
		async auto_adjust_price() {
			if ( n2.settings['寄附金額・送料']['自動価格調整'] == '調整しない' ) {
				this.価格 = Math.ceil(this.価格);
				return;
			}
			const opt = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_adjust_price_api',
					price: this.価格,
					subscription: this.定期便,
				}
			}
			this.価格 = await $.ajax(opt);
		},
		// メディアアップローダー関連
		add_media(){
			if ( n2.media ) {
				n2.media.open();
				return;
			}
			// N1の画像データにはnoncesが無い
			n2.media = wp.media({
				title: "商品画像", 
				multiple: "add",
				library: {type: "image"},
			});
			n2.media.on( 'open', () => {
				// N2のものだけに
				console.log(n2.media)
				const add =  n2.vue.商品画像.filter( v => v.nonces );
				n2.media.state().get('selection').add( add.map( v => wp.media.attachment(v.id) ) );
			});
			n2.media.on( 'select close', () => {
				n2.media.state().get('selection').forEach( img => {
					if ( ! n2.vue.商品画像.find( v => v.id == img.attributes.id ) ) {
						n2.vue.商品画像.push( img.attributes );
					}
				})
			});
			n2.media.open();
		},
		// 楽天の全商品ディレクトリID取得
		async get_genreid(){
			const settings = {
				url: '//app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222',
				data: {
					applicationId: '1002772968546257164',
					genreId: this.全商品ディレクトリID || '0',
				},
			};
			this.tmp.楽天ジャンルID = await $.ajax(settings);
		},
		// 楽天の商品属性を取得
		async insert_rms_attributes( mandatoryFlg = false ) {
			this.tmp.商品属性アニメーション = true;
			const opt = {
				url: n2.ajaxurl,
				data: {
					action: 'n2_rms_navigation_api_ajax',
					mode: 'json',
					call: 'genres_attributes_dictionary_values_get',
					genreId: this.全商品ディレクトリID || '0',
				},
			};
			let res = await $.ajax(opt);
			res = JSON.parse(res);
			let attr = res.genre.attributes;
			attr = mandatoryFlg ? attr.filter( v => v.properties.rmsMandatoryFlg ) : attr;
			if ( this.商品属性.length ) {
				const values = {}
				for( const v of this.商品属性 ) {
					values[v.nameJa] = {
						value: v.value ?? '',
						unitValue: v.unitValue ?? null,
					};
				}
				attr = attr.map(v=>{
					if ( values[v.nameJa] ) {
						v.value = values[v.nameJa].value;
						v.unitValue = values[v.nameJa].unitValue;
					}
					return v;
				});
			}
			this.商品属性 = attr;
			this.tmp.商品属性アニメーション = false;
		},
		set_rms_attributes_value(index, value) {
			console.log('set_rms_attributes_value!!!')
			const attributes = this.商品属性;
			attributes[index].value = value;
			this.商品属性 = attributes;
		},
		set_rms_attributes_unit(index, unitValue) {
			const attributes = this.商品属性;
			attributes[index].unitValue = unitValue;
			this.商品属性 = attributes;
		},
		get_units(v) {
			return v.unit ? [v.unit, ...v.subUnits] : [];
		},
		// 楽天SPAカテゴリーで利用
		update_textarea(id, target = '楽天SPAカテゴリー', delimiter = '\n', maxrow = null){
			// 重複削除
			const arr = this[target] ? Array.from( new Set( this[target].split( delimiter ) ) ): [];
			// 削除
			if ( arr.includes( id.toString() ) ) {
				this[target] = arr.filter( v => v != id ).join( delimiter )
			}
			// 追加
			else if ( ! maxrow || arr.length < maxrow ) {
				// 楽天のタグIDの上限
				if ( target == 'タグID' && arr.length >= ( $('[type="rakuten-tagid"]').attr('maxlength') as any)/8 ) return;
				this[target] = [...arr, id].filter( v => v ).join( delimiter );
			}
			// 自動可変高　一瞬ずらさんとまだレンダリングされてない
			setTimeout( ()=>{
				$(`[name="n2field[${target}]"]`).get(0).dispatchEvent( new Event('focus') );
			}, 10 )
		},
		// 楽天カテゴリで利用
		update_textarea_by_selected_option( event, index, target = '楽天カテゴリー', delimiter = '\n' ) {
			this.clearRakutenCategory(event,index);
			this.update_textarea( event.target.value, target, delimiter, 5);
			event.target.value = '';
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
			this.tmp.寄附金額自動計算値 = await $.ajax(opt);
			if ( this.寄附金額固定.filter(v=>v).length ) {
				this.check_donation();
				return this.寄附金額;
			}
			this.tmp.寄附金額チェッカー = '';
			return this.tmp.寄附金額自動計算値;
		},
		// 寄附金額の更新
		async update_donation(){
			alert(`価格：${Number(this.価格).toLocaleString()}\n送料：${Number(this.送料).toLocaleString()}\n定期便回数：${this.定期便}\nを元に再計算します。`);
			this.寄附金額 = await this.calc_donation(this.価格, this.送料, this.定期便);
			console.log(this.寄附金額)
		},
		check_donation() {
			const check = ['text-danger', '', 'text-success'];
			if ( this.tmp.寄附金額自動計算値 ) {
				this.tmp.寄附金額チェッカー = check[ Math.sign( this.寄附金額 - this.tmp.寄附金額自動計算値 ) + 1 ];
			}
		},
		// 取り扱い方法自動設定
		check_handling_method() {
			// 既に登録されている or 発送サイズがヤマト以外は何もしない
			if ( this.取り扱い方法.length || ! this.発送サイズ.match(/010[1-8]+/) ) return;
			// やきものにチェックしたら自動で「ビン・ワレモノ」「下積み禁止」
			if ( this.商品タイプ.includes('やきもの') ) {
				if ( confirm( '取り扱い方法を「ビン・ワレモノ」「下積み禁止」に設定しますか？') ) {
					this.取り扱い方法 =  ['ビン・ワレモノ', '下積み禁止'];
				}
			}
		},
		check_tax(){
			if ( "" !== n2.custom_field['事業者用']['税率'].value ) return; // 既に設定済なら動かさない
			if ( this.商品タイプ.includes('食品') ) {
				this.税率 = '8';
			}else{
				this.税率 = '10';
			}
		},
		// スチームシップへ送信ボタンの制御
		control_submit() {
			// 必須漏れがあれば、「スチームシップへ送信」できなくする
			if ( save_as_pending.rejection().length > 0 ) {
				$('#n2-save-as-pending').addClass('opacity-50');
			} else {
				$('#n2-save-as-pending').removeClass('opacity-50');
			}
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
		// 楽天SPAカテゴリー取得
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
			n2.vue.tmp.楽天SPAカテゴリー = cat.values.map( (v,k) => {
				if ( ! v.length ) return
				v.forEach((e,i) => {
					v[i] = e || cat.values[k-1][i];
					v[i] = v[i].replace('.','');
				});
				return `#/${v.join('/')}/`;
			}).filter(v=>v);
		},
		// 楽天カテゴリー
		async get_rakuten_category(){
			this.tmp.楽天カテゴリー = await $.ajax({
				url: n2.ajaxurl,
				data:{
					action:'n2_rms_category_api_ajax',
					call:'categories_get',
					mode:'json',
				}
			});
		},
		clearRakutenCategory(e,index){
			e.preventDefault();
			n2.vue.楽天カテゴリー = this.楽天カテゴリーselected.filter((_,i)=>i!==index).join('\n')
		},
		// 楽天納期
		async get_rakuten_delvdate(){
			this.$set(this.tmp.楽天納期情報, '', '選択して下さい' );
			let res = await $.ajax({
				url: n2.ajaxurl,
				data:{
					action:'n2_rms_shop_api_ajax',
					call:'delvdate_master_get',
					mode:'json',
				}
			});
			for ( const v of res ) {
				this.$set(this.tmp.楽天納期情報, v.delvdateNumber, `[${v.delvdateNumber}] ${v.delvdateCaption}` );
			}
		},
	};
	const components = {
		draggable,
	};
	const computed = {
		楽天カテゴリーselected(){
			return this.楽天カテゴリー.split('\n').filter(x=>x);
		}
	};

	// メタボックスが生成されてから
	$('.edit-post-layout__metaboxes').ready(()=>{
		n2.vue = new Vue({
			el: '.edit-post-layout__metaboxes',
			data,
			created,
			methods,
			components,
			computed,
		});
	});
};