import { prefix, neoNengPath, ajaxUrl } from '../functions/index'

export default () => {
	/** ===============================================================
	 * 
	 * 寄附金額計算
	 * 
	================================================================== */
	jQuery(function($) {
		
		// 計算パターンを受け取ってから処理
		$.ajax({
			url: ajaxUrl(window),
			data: {
				action: 'N2_Setpost',
			},
		}).done(res => {
			const data=JSON.parse(res)
			console.log(data)
			
			/** ===============================================================
			 * 
			 * 送料制御
			 * 
			================================================================== */
			class ControlSouryou {
				public webSyukka: boolean; // web出荷かどうかtrue or false
				public deliveryCool: boolean; // クール便かどうか true or false
				private price: number|string;
				public deliveryPattern: {[s: string]: {[s: string]: number|string}};

				constructor(webSyukka: boolean, deliveryCool: boolean, deliveryPattern: {[s: string]: {[s: string]: number|string}}) {
					this.webSyukka=webSyukka;
					this.deliveryCool=deliveryCool;
					this.deliveryPattern=deliveryPattern;
				}

				// 金額更新
				set setPrice(price: number|string) {
					this.price=price;
					this.webSyukka=price===0? false:true;
				}

				// 金額取得
				get getPrice() {
					return this.price;
				}

				// クール判定
				set setDeliveryCool(cool: boolean) {
					this.deliveryCool=cool;
				}

				// 料金表オブジェクトをもとにサイズを料金へ変換
				convertPrice(size: string) {
					return this.deliveryPattern[this.deliveryCool? 'cool':'normal'][size];
				}
			}
			
			// インスタンス生成
			const souryouState=new ControlSouryou($('#発送サイズ').text()!=='その他', $('#発送方法').val()!=='常温', data['delivery_pattern']);
			// 金額セット
			souryouState.setPrice=souryouState.convertPrice($('#発送サイズ>option:selected').text())
			
			// 発送方法の変更に合わせて発想サイズの選択肢変更
			const changeSizeSelect=(souryouState) => {
				// 発送サイズをcool表示に
				$.each($('#発送サイズ>option'), (index, option) => {
					if(!Object.keys(souryouState.deliveryPattern['cool']).includes($(option).text())) {
						$(option).css('display', `${souryouState.deliveryCool? 'none':'block'}`)
					}
				})
			}
			// 送料決定プロセス
			const souryouDecision=(souryouState) => {
				souryouState.setPrice=souryouState.convertPrice($('#発送サイズ>option:selected').text())
				$('label[for="送料"] + p').text(`${souryouState.webSyukka? souryouState.getPrice.toLocaleString():''}`)
				$('#送料').val(souryouState.getPrice)
				$('#送料').attr('type', `${souryouState.webSyukka? 'hidden':'text'}`)
			}

			// JS起動時処理
			$('label[for="送料"]').after($(`<p></p>`))
			changeSizeSelect(souryouState)
			souryouDecision(souryouState)

			// イベント監視---------------------------------------------------------------------------------------------------
			$('#発送サイズ').on('change', e => {
				souryouDecision(souryouState)
				// 寄附金額再計算
				priceState.setsouryou=Number($('#送料').val())
				showPrice(priceState)
			})

			$('#発送方法').on('change', e => {
				// クール便判定再セット
				souryouState.setDeliveryCool=$('#発送方法').val()!=='常温';
				// あとからクールになった時に発想サイズリセット
				if($('#発送サイズ>option:selected').text()!=='未選択'&&souryouState.deliveryCool) {
					alert('発送方法が変更になったため発送サイズをリセットしました')
					$('#発送サイズ>option[value=""]').prop('selected', true)
				}
				
				changeSizeSelect(souryouState)
				souryouDecision(souryouState)
				priceState.setsouryou=Number($('#送料').val())
				showPrice(priceState)
			})

			// ここまでイベント監視

			/** ===============================================================
			 * 
			 * 寄附金額制御
			 * 
			================================================================== */

			// 価格と寄附金額の状態を監視するクラス
			class AutoCalc {
				private kakaku: number;
				private kifukingaku: any;
				private pattern: string;
				private souryou: number;
				private teiki: number;

				constructor(kakaku: number, kifukingaku: any, souryou: number, teiki: number) {
					this.kakaku=kakaku;
					this.kifukingaku=kifukingaku;
					this.souryou=souryou;
					this.teiki=teiki===0? 1:teiki;
				}

				// 価格更新
				set setkakaku(price: number) {
					this.kakaku=price;
				}
				// 価格取得
				get getkakaku() {
					return this.kakaku;
				}

				// 送料更新
				set setsouryou(price: number) {
					this.souryou=price;
				}
				// 送料取得
				get getsouryou() {
					return this.souryou;
				}

				// 定期回数更新
				set setteiki(count: number) {
					this.teiki=count;
				}
				// 定期回数取得
				get getteiki() {
					return this.teiki;
				}

				// 寄附金額更新
				set setkifu(price: number) {
					this.kifukingaku=price;
				}

				// 計算パターン変更
				set setpattern(pattern: string) {
					this.pattern=pattern;
				}

				// 寄附金額に入力があるかチェック
				checkPrice() {
					return Number(this.kifukingaku)!==0&&this.kifukingaku!=='';
				}

				// 最低ラインの寄附金額計算
				errorPrice() {
					return Math.ceil(this.kakaku/400)*1000*this.teiki;
				}

				// 自動計算
				calcPrice() {
					const kakaku=this.kakaku;
					const kifukingaku=this.kifukingaku;
					const souryou=this.souryou;
					// PHPから計算パターンをJSの式（文字列）として受け取りevalでプログラムとして実行
					return eval(this.pattern)*this.teiki;
				}

				// 差額計算
				diffPrice() {
					return Number(this.kifukingaku)-this.calcPrice();
				}
			}

			// インスタンス生成
			const priceState=new AutoCalc(Number($('#価格').val()), $('#寄附金額').val(), Number($('#送料').val()), Number($('#定期便').val()));

			// インスタンスにパターンセット
			priceState.setpattern=data.kifu_auto_pattern
			
			// もろろのDOM操作をまとめて関数化
			const showPrice=(priceState): void => {
				if(Number(priceState.errorPrice())>=Number($('#寄附金額').val())) {
					if(!$('#寄附金額').parent().find(`.${prefix}-alert`).length) {
						$('#寄附金額').before($(`<p class="${prefix}-alert" style="color:red;">※寄附金額が低すぎます。</p>`))
					}
				} else {
					$('#寄附金額').parent().find(`.${prefix}-alert`).remove()
				}
				$('#寄附金額 + p').html(`
				自動計算の値：${priceState.calcPrice().toLocaleString()}（<span style="color:${priceState.diffPrice()>=0? 'turquoise':'red'}">差額：${priceState.diffPrice().toLocaleString()}</span>）<br>
				価格：${(priceState.getkakaku*priceState.getteiki).toLocaleString()}（${priceState.getkakaku.toLocaleString()} × ${priceState.getteiki}回）<br>
				送料：${(priceState.getsouryou*priceState.getteiki).toLocaleString()}（${priceState.getsouryou.toLocaleString()} × ${priceState.getteiki}回）
				`)
			}
			
			// 寄附金額み入力時のみ自動計算を入力値に反映
			if(!priceState.checkPrice()) {
				$('#寄附金額').val(priceState.calcPrice())
				priceState.setkifu=priceState.calcPrice();
			}
			
			// 自動計算値と差額表示用DOMセット
			$('#寄附金額').after($('<p></p>'))
			
			// デフォルトで計算値、差額表示
			showPrice(priceState)
			
			// イベント監視 ------------------------------------------------------------------------------------------
			$('#価格').on('keyup', e => {
				priceState.setkakaku=Number($(e.target).val())
				showPrice(priceState)
			})

			$('#送料').on('keyup mouseup', e => {
				priceState.setsouryou=Number($(e.target).val())
				showPrice(priceState)
			})

			$('#定期便').on('change', e => {
				priceState.setteiki=Number($(e.target).val())
				showPrice(priceState)
			})
			
			$('#寄附金額').on('keyup mouseup', e => {
				priceState.setkifu=Number($(e.target).val())
				showPrice(priceState)
			})
			// ここまでイベント ---------------------------------------------------------------------------------------
		})


		// ここまで寄附金額計算 ==============================================================================================================================
	})
}
