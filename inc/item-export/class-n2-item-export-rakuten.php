<?php
/**
 * 楽天の商品エクスポート専用
 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
 * class-n2-item-export-rakuten-item.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Rakuten' ) ) {
	new N2_Item_Export_Rakuten();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Rakuten extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'item.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '', // 基本は自動設定、falseでヘッダー文字列無し
	);

	/**
	 * RMS
	 *
	 * @var array
	 */
	private $rms = array(
		'header'       => null,
		'cabinet'      => array(),
		'use_api'      => null,
		'ignore_error' => false,
		'image_error'  => false,
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
		add_filter( mb_strtolower( get_class( $this ) ) . '_download_add_btn', array( $this, 'add_download_btn' ) );
		add_filter( mb_strtolower( get_class( $this ) ) . '_download_str', array( $this, 'change_download_str' ), 10, 2 );
	}

	/**
	 * 楽天CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// CSVヘッダー
		$this->data['header'] = $n2->settings['楽天']['csv_header']['item'];
		/**
		 * [hook] n2_item_export_rakuten_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * 楽天用の内容を配列で作成
	 */
	protected function set_data() {
		global $n2;
		$data = array();

		$item_code_list = array_map(
			function( $item ) {
				return mb_strtolower( $item['返礼品コード'] );
			},
			$this->data['n2data'],
		);
		// 事業者コード一覧
		$item_code_list = array_unique( $item_code_list );
		// 事前に取得

		$this->set_cabinet_files( $item_code_list );

		// $this->check_fatal_error( $this->data['header'], 'ヘッダーが正しくセットされていません' );
		foreach ( $this->data['n2data'] as $key => $values ) {
			$id = $values['id'];
			// 画像を取得
			$values['商品画像URL'] = $this->get_img_urls( $values );
			// アレルゲン
			$values['アレルゲン'] = preg_replace( '/（.*?）/', '', $values['アレルゲン'] );// 不純物（カッコの部分）を削除

			// 自治体ごとの共通タグIDと返礼品ごとのタグIDを結合、その際に空文字と重複を削除
			$values['タグID'] = implode( '/', array_unique( array_filter( explode( '/', "{$n2->settings['楽天']['共通タグID']}/{$values['タグID']}" ) ) ) );

			// ヘッダーをセット
			$data[ $id ] = $this->data['header'];
			array_walk( $data[ $id ], array( $this, 'walk_values' ), $values );
			$data[ $id ] = array_combine( $this->data['header'], $data[ $id ] );
		}
		/**
		 * [hook] n2_item_export_base_set_data
		 */
		$data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data', $data );
		// エラーは排除
		$data = array_diff_key( $data, $this->data['error'] );
		$data = array_values( $data );
		// dataをセット
		$this->data['data'] = $data;
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^コントロールカラム$/', $val )  => 'n',
			preg_match( '/^商品管理番号（商品URL）$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^商品番号$/', $val )  => $n2values['返礼品コード'],
			preg_match( '/^全商品ディレクトリID$/', $val ) => $n2values['全商品ディレクトリID'],
			preg_match( '/^タグID$/', $val ) => $n2values['タグID'],
			preg_match( '/^PC用キャッチコピー$/', $val )  => $n2values['キャッチコピー'],
			preg_match( '/^モバイル用キャッチコピー$/', $val )  => $n2values['キャッチコピー'],
			preg_match( '/商品名$/', $val )  => '【ふるさと納税】' . $n2values['タイトル'] . '[' . $n2values['返礼品コード'] . ']',
			preg_match( '/^販売価格$/', $val )  => $n2values['寄附金額'],
			preg_match( '/^送料$/', $val )  => 1,
			preg_match( '/^のし対応$/', $val )  =>  ( '有り' === $n2values['のし対応'] ) ? 1 : '',
			preg_match( '/^PC用商品説明文$/', $val )  => $this->pc_item_description( $n2values ),
			preg_match( '/^スマートフォン用商品説明文$/', $val )  => $this->sp_item_description( $n2values ),
			preg_match( '/^PC用販売説明文$/', $val )  => $this->pc_sales_description( $n2values ),
			preg_match( '/^商品画像URL$/', $val )  => $this->get_img_urls( $n2values ),
			preg_match( '/^代引料$/', $val )  => 1,
			preg_match( '/^在庫タイプ$/', $val )  => 1,
			preg_match( '/^在庫数$/', $val )  => 0,
			// preg_match( '/^カタログID$/', $val )  => "", // 初期値空白なので記入必要なし
			preg_match( '/^カタログIDなしの理由$/', $val )  => 1,
			default => '',
		};
		/**
		 * [hook] n2_item_export_rakuten_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
	}

	/**
	 * エラーチェック
	 *
	 * @param string $value 項目値
	 * @param string $name 項目名
	 * @param array  $n2values n2dataのループ中の値
	 *
	 * @return $value
	 */
	public function check_error( $value, $name, $n2values ) {
		/**
		 * 寄附金額エラー
		 */
		if ( '販売価格' === $name && 0 === $value ) {
			$this->add_error( $n2values['id'], "「{$name}」が0です。" );
		}

		/**
		 * 文字数制限エラー
		 */
		$len = mb_strlen( $value );// $valueの文字数
		$max = match ( $name ) {
			'キャッチコピー' => 40,
			'サイト表示事業者名' => 64,
			'地場産品類型番号' => 100,
			'（必須）お礼の品名' => 200,
			'説明','容量','申込期日','発送期日','アレルギー特記事項','消費期限' => 1000,
			default => 10000,// 仮で設定
		};
		$over = $len - $max;
		if ( $over > 0 ) {
			$this->add_error( $n2values['id'], "<div title='{$value}'>「{$name}」の文字数が{$over}文字多いです。</div>" );
		}
		// 存在する画像ファイルだけの配列を生成する
		$exist_images = function () use ( $n2values, $name ) {
			return array_filter(
				$this->make_img_urls( $n2values ),
				fn( $image ) => in_array( $image, explode( ' ', $n2values[ $name ] ), true ),
			);
		};
		/**
		 * 画像エラー
		 */
		$images = match ( $name ) {
			'商品画像URL' => $exist_images(),
			default => false,
		};

		if ( false !== $images ) {
			$max_index = end( array_keys( $images ) );
			for ( $index = 0; $index <= $max_index; $index++ ) {
				$gift_code = mb_strtolower( $n2values['返礼品コード'] );
				$image     = $gift_code . ( 0 !== $index ? '-' . $index : '' ) . '.jpg';
				if ( ! isset( $images[ $index ] ) && 'ignore_img_error' !== filter_input( INPUT_POST, 'option' ) ) {
					$this->rms['image_error'] = true;
					$this->add_error( $n2values['id'], "商品画像を先にアップロードしてください！ {$image}" );
				}
			}
		}
		return $value;
	}

	/**
	 * add download btn
	 *
	 * @param array $add_btn 追加ボタン一覧
	 * @return array
	 */
	public function add_download_btn( $add_btn ) {
		if ( $this->rms['image_error'] ) {
			$add_btn[] = array(
				'id'    => 'ignore_img_error',
				'class' => 'btn-warning',
				'text'  => '画像エラーを無視してダウンロードする',
			);
		}
		return $add_btn;
	}

	/**
	 * change download $str
	 *
	 * @param string $str str
	 * @param string $option option
	 * @return string
	 */
	public function change_download_str( $str, $option ) {
		return match ( $option ) {
			'ignore_img_error' => '',
			default => $str,
		};
	}

	/**
	 * 文字列の置換
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		global $n2;
		$str = str_replace( array_keys( $n2->special_str_convert ), array_values( $n2->special_str_convert ), $str );
		/**
		 * [hook] n2_item_export_rakuten_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}

	/**
	 * RMS APIが使えるか判定
	 */
	protected function can_use_api() {
		if ( null === $this->rms['use_api'] ) {
			$this->rms['use_api'] = N2_RMS_Cabinet_API::ajax(
				array(
					'request' => 'connect',
					'mode'    => 'func',
				),
			);
		}
		return $this->rms['use_api'];
	}

	/**
	 * キャビネットの画像ファイルを設定
	 *
	 * @param array $keywords 検索ワード
	 */
	protected function set_cabinet_files( $keywords ) {
		if ( ! $this->can_use_api() ) {
			return;
		}
		// 検索ワードでハッシュ化
		$cabinet              = N2_RMS_Cabinet_API::ajax(
			array(
				'keywords' => $keywords,
				'request'  => 'files_search',
				'mode'     => 'func',
			),
		);
		$this->rms['cabinet'] = array(
			...$this->rms['cabinet'],
			...$cabinet,
		);
	}


	/**
	 * URLを単純に生成する機能
	 *
	 * @param array $n2values n2values
	 * @return array
	 */
	protected function make_img_urls( $n2values ) {
		global $n2;
		$img_dir       = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' );
		$gift_code     = mb_strtolower( $n2values['返礼品コード'] );
		preg_match('/[0-9]{0,2}[A-Z]{2,4}/', $n2values['返礼品コード'], $m); # 事業者コード
		$business_code = mb_strtolower( $m[0] ); 
		// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
		if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
			$img_dir .= "/{$business_code}";// キャビネットの場合事業者コード追加
		}
		$result = array();
		for ( $i = 0; $i < 20; ++$i ) {
			$img_url = "{$img_dir}/{$gift_code}";
			if ( 0 === $i ) {
				$img_url .= '.jpg';
			} else {
				$img_url .= "-{$i}.jpg";
			}
			$result[ $i ] = $img_url;
		}
		return $result;
	}
	/**
	 * 楽天の存在する画像URLを取得
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param string $return_type 戻り値判定用(string|html|array)
	 * @return string|array 楽天の画像URLを(文字列|配列)で取得する
	 */
	public function get_img_urls( $n2values, $return_type = 'string' ) {
		set_time_limit( 60 );
		global $n2;
		$result = match ( isset( $n2values['商品画像URL'] ) ) {
			true => explode( ' ', $n2values['商品画像URL'] ),
			false => array(),
		};

		$code     = mb_strtolower( $n2values['返礼品コード'] );
		$requests = $this->make_img_urls( $n2values );

		// RMSを利用する
		if ( ! $result && $this->can_use_api() ) {
			if ( ! isset( $this->rms['cabinet'][ $code ] ) ) {
				$this->set_cabinet_files( array( $code ) );
			}
			$files  = array_map(
				function( $req ) {
					return $req['FileUrl'];
				},
				$this->rms['cabinet'][ $code ] ?? array(),
			);
			$result = array_map(
				function( $req ) use ( $files ) {
					return in_array( $req, $files, true ) ? $req : '';
				},
				$requests,
			);
			$result = array_filter( $result, fn( $r ) => $r );
		}

		if ( ! $result ) { // 直接存在チェック
			$result   = array();
			$response = N2_Multi_URL_Request_API::ajax(
				array(
					'urls'    => $requests,
					'mode'    => 'func',
					'request' => 'verify_images',
				)
			);
			$result   = array_map(
				function( $req ) use ( $response ) {
					return $response[ $req ] ? $req : '';
				},
				$requests,
			);
			$result   = array_filter( $result, fn( $r ) => $r );
		}

		// ========戻り値判定========
		switch ( $return_type ) {
			// 文字列を返却
			case 'string':
				return implode( ' ', $result );
			case 'html':
				$html = function() use ( $result ) {
					?>
					<?php foreach ( $result  as $index => $img_url ) : ?>
						<?php if ( array_key_last( $result ) === $index ) : ?>
							<img src="<?php echo $img_url; ?>" width="100%"><br><br>
						<?php else : ?>
							<img src="<?php echo $img_url; ?>" width="100%">
						<?php endif; ?>
					<?php endforeach; ?>
					<?php
				};
				$html();
				break;
			// 配列出力
			default:
				return $result;
		}
	}
	/**
	 * 楽天のPC用販売説明文
	 *
	 * @param array $n2values n2dataのループ中の値
	 * @param bool  $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_sales_description( $n2values, $return_string = true ) {
		// ========[html]PC用販売説明文========
		$html = function() use ( $n2values ) {
			global $n2;
			?>
			<?php $this->get_img_urls( $n2values, 'html' ); ?>
			<?php echo nl2br( $n2values['説明文'] ); ?><br><br>
			<?php $this->make_itemtable( $n2values, false ); ?><br><br>
			<?php
				echo $n2->settings['N2']['ポータル共通説明文']
					. apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $n2values['id'], 'PC用販売説明文' )
					. str_replace( '\"', '""', $n2->settings['楽天']['説明文追加html'] ?? '' );
				?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return $this->html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param array $n2values n2dataのループ中の値
	 * @param bool  $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_item_description( $n2values, $return_string = true ) {
		global $n2;
		// ========[html]PC用商品説明文========
		$html = function() use ( $n2values ) {
			?>
			<?php echo nl2br( $n2values['説明文'] ); ?><br><br>
			<?php echo nl2br( $n2values['内容量・規格等'] ); ?><br>
			<?php if ( $n2values['賞味期限'] ) : ?>
				<br>【賞味期限】<br><?php echo nl2br( $n2values['賞味期限'] ); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['消費期限'] ) : ?>
				<br>【消費期限】<br><?php echo nl2br( $n2values['消費期限'] ); ?><br>
			<?php endif; ?>
			<?php echo apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $n2values['id'], '対応機器' ); ?>
			<?php if ( $n2values['原料原産地'] ) : ?>
				<br><br>【原料原産地】<br>
				<?php echo nl2br( $n2values['原料原産地'] ); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['加工地'] ) : ?>
				<br><br>【加工地】<br>
				<?php echo nl2br( $n2values['加工地'] ); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['電子レンジ対応'] || $n2values['オーブン対応'] || $n2values['食洗機対応'] ) : ?>
				<br><br>【対応機器】<br>
				<?php echo '電子レンジ' . $n2values['電子レンジ対応'] . ' / オーブン' . $n2values['オーブン対応'] . ' / 食器洗浄機' . $n2values['食洗機対応']; ?><br>
			<?php endif; ?>
			<?php if ( $n2values['対応機器備考'] ) : ?>
				<br><br>【対応機器備考】<br>
				※<?php echo nl2br( $n2values['対応機器備考'] ); ?><br>
			<?php endif; ?>
			<?php if ( isset( $n2->settings['注意書き']['やきもの'] ) ) : ?>
				<?php echo nl2br( $n2->settings['注意書き']['やきもの'] ); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['検索キーワード'] ) : ?>
				<br><br><?php echo nl2br( $n2values['検索キーワード'] ); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo nl2br( $n2values['楽天SPAカテゴリー'] ); ?><br>
			<?php endif; ?>
			<?php
		};

		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return $this->html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のSP用商品説明文
	 *
	 * @param array $n2values n2dataのループ中の値
	 * @param bool  $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のSP用商品説明文を(文字列|HTML出力)する
	 */
	public function sp_item_description( $n2values, $return_string = true ) {
		// ========[html]SP用商品説明文========
		$html = function() use ( $n2values ) {
			global $n2;
			?>
			<?php $this->get_img_urls( $n2values, 'html' ); ?>
			<?php echo nl2br( $n2values['説明文'] ); ?><br><br>
			<?php $this->make_itemtable( $n2values, false ); ?>
			<?php if ( $n2values['検索キーワード'] ) : ?>
				<br><br><?php echo nl2br( $n2values['検索キーワード'] ); ?>
			<?php endif; ?>
			<?php if ( $n2values['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo nl2br( $n2values['楽天SPAカテゴリー'] ); ?>
			<?php endif ?>
			<?php
				echo $n2->settings['N2']['ポータル共通説明文']
					. str_replace( '\"', '""', $n2->settings['楽天']['説明文追加html'] ?? '' );
			?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return $this->html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * アレルギー表示
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param string $type type
	 *
	 * @return string
	 */
	public static function allergy_display( $n2values, $type = '' ) {
		$allergy_annotation = $n2values['アレルゲン注釈'];
		$allergens          = match ( is_array( $n2values['アレルゲン'] ) ) {
			true => implode( '・', $n2values['アレルゲン'] ),
			false => $n2values['アレルゲン'],
		};
		$not_food = match ( is_array( $n2values['商品タイプ'] ) ) {
			true => ! in_array( '食品', $n2values['商品タイプ'], true ),
			false => '食品' === $n2values['商品タイプ'],
		};
		$not_allergy        = empty( $n2values['アレルゲン'] ?: array() );
		$allergy_annotation = $allergy_annotation ? '<br>※' . $allergy_annotation : '';
		$result             = '';
		switch ( true ) {
			case ! $not_food && 'print' === $type:
				$result = 'アレルギー表示しない';
				break;
			case $not_food:
				break;
			case $not_allergy:
				$result = 'アレルギーなし食品';
				break;
			case '' !== $allergens || '' !== $allergy_annotation:
				$allergens = $allergens ?: 'なし';
				$result    = "含んでいる品目：{$allergens}{$allergy_annotation}";
				break;
		}
		return $result;
	}

	/**
	 * やきもの表示
	 *
	 * @param array $n2values n2dataのループ中の値
	 *
	 * @return string
	 */
	public static function pottery_display( $n2values ) {
		global $n2;
		$result = '';
		if ( in_array( 'やきもの', $n2values['商品タイプ'], true ) ) {
			if ( isset( $n2values['電子レンジ対応'] ) || isset( $n2values['オーブン対応'] ) || isset( $n2values['食洗機対応'] ) ) {
				$result .= '電子レンジ' . $n2values['電子レンジ対応'] . ' / オーブン' . $n2values['オーブン対応'] . ' / 食器洗浄機' . $n2values['食洗機対応'] . '<br>';
			}
			if ( isset( $n2values['対応機器備考'] ) ) {
				$result .= '※' . $n2values['対応機器備考'] . '<br>';
			}
			if ( isset( $n2->settings['注意書き']['やきもの'] ) ) {
				$result .= $n2->settings['注意書き']['やきもの'] . '<br>';
			}
		}
		return $result;
	}
	 /**
	  * 商品説明テーブル
	  *
	  * @param array $n2values n2dataのループ中の値
	  * @param bool  $return_string 戻り値を文字列で返す
	  *
	  * @return string 商品説明テーブル
	  */
	public function make_itemtable( $n2values, $return_string = true ) {
		// アレルギー表示
		$allergy_display_str = $this->allergy_display( $n2values );
		$pottery_display_str = $this->pottery_display( $n2values );
		$trs                 = array(
			'名称'      => array(
				'td' => $n2values['LH表示名'] ?: $n2values['タイトル'],
			),
			'内容量'     => array(
				'td' => nl2br( $n2values['内容量・規格等'] ),
			),
			'原料原産地'   => array(
				'td'        => nl2br( $n2values['原料原産地'] ),
				'condition' => $n2values['原料原産地'],
			),
			'加工地'     => array(
				'td'        => nl2br( $n2values['加工地'] ),
				'condition' => $n2values['加工地'],
			),
			'賞味期限'    => array(
				'td'        => nl2br( $n2values['賞味期限'] ),
				'condition' => $n2values['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => nl2br( $n2values['消費期限'] ),
				'condition' => $n2values['消費期限'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => nl2br( $n2values['発送方法'] ),
			),
			'配送期日'    => array(
				'td' => nl2br( $n2values['配送期間'] ),
			),
			'提供事業者'   => array(
				'td' => $this->get_author_name( $n2values ),
			),
			'対応機器'    => array(
				'td'        => $pottery_display_str,
				'condition' => $pottery_display_str,
			),
		);

		// 内容を追加、または上書きするためのフック
		$trs = apply_filters( 'n2_item_export_make_itemtable', $trs, $n2values['id'] );

		// ========[html]商品説明テーブル========
		$itemtable_html = function() use ( $trs ) {
			?>
			<!-- 商品説明テーブル -->
			<p><b><font size="5">商品説明</font></b></p><hr noshade color="black"><br>
			<table border="1" width="100%" cellspacing="0" cellpadding="10" bordercolor="black">
			<?php foreach ( $trs as $th => $td_params ) : ?>
				<?php if ( ! isset( $td_params['condition'] ) || $td_params['condition'] ) : ?>
				<tr><th><?php echo $th; ?></th><td><?php echo $td_params['td']; ?></td></tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</table>
			<!-- /商品説明テーブル -->
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
	if ( $return_string ) {
			return $this->html2str( $itemtable_html );
		}
		// htmlで出力
		$itemtable_html();
	}
	/**
	 * html文を文字列出力する
	 *
	 * @param function $html_function 関数名を文字列として渡す
	 * @return null|string html_tags
	 */
	public function html2str( $html_function ) {
		// 関数でなければ終了
		if ( ! is_callable( $html_function ) ) {
			return null;
		}
		ob_start();
		?>
		<?php $html_function(); ?>
		<?php
		return rtrim( str_replace( "\t", '', ob_get_clean() ), PHP_EOL );
	}
}
