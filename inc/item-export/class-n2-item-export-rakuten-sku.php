<?php
/**
 * 楽天SKU対応　商品エクスポート専用
 * class-n2-item-export-rakuten-sku.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten_sku&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Rakuten_SKU' ) ) {
	new N2_Item_Export_Rakuten_SKU();
	return;
}

/**
 * N2_Item_Export_Rakuten_SKU
 */
class N2_Item_Export_Rakuten_SKU extends N2_Item_Export_Rakuten {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'normal-item.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '', // 基本は自動設定、falseでヘッダー文字列無し
	);
	/**
	 * RMS
	 *
	 * @var array
	 */
	protected $rms = array(
		'header'       => null,
		'cabinet'      => array(),
		'use_api'      => null,
		'ignore_error' => false,
		'image_error'  => false,
	);

	/**
	 * 楽天CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// 初期化
		$this->data['sku'] = array(
			'max' => array(),
		);

		// 旧select.csv部分
		$selects = $n2->portal_setting['楽天']['select'];
		$selects = str_replace( array( "\r\n", "\r" ), "\n", $selects );// 改行コード統一
		$selects = preg_split( '/\n{2,}/', $selects );// 連続改行で分ける

		foreach ( $selects as $index => $select ) {
			$select                             = array_filter( explode( "\n", $select ) );
			$name                               = array_shift( $select );
			$this->data['sku']['max']['select'] = max( $this->data['sku']['max']['select'] ?? 0, count( $select ) );
			$selects[ $index ]                  = array( $name => $select );
		}

		// 商品画像をあらかじめ取得
		$sku_list = array_map(
			function( $item ) {
				return mb_strtolower( $item['返礼品コード'] );
			},
			$this->data['n2data'],
		);
		// 返礼品コード一覧
		$sku_list = array_unique( $sku_list );
		$this->set_cabinet_files( $sku_list );

		foreach ( $this->data['n2data'] as $key => $values ) {
			$this->data['n2data'][ $key ]['商品画像URL'] = $this->get_img_urls( $values );
			$images                                  = array_filter( explode( ' ', $this->data['n2data'][ $key ]['商品画像URL'] ) );
			$this->data['sku']['max']['cabinet']     = max( $this->data['sku']['max']['cabinet'] ?? 0, count( $images ) );
			$variation                               = $values['バリエーション項目名定義'] ?? ''; // keyを仮に設定
			$variation                               = array_filter( explode( '|', $variation ) );
			$this->data['sku']['max']['variation']   = max( $this->data['sku']['max']['variation'] ?? 0, count( $variation ) );
			$attribute                               = $values['商品属性'] ?? ''; // keyを仮に設定
			$attribute                               = array_filter( explode( '|', $attribute ) );
			$this->data['sku']['max']['attribute']   = max( $this->data['sku']['max']['attribute'] ?? 0, count( $attribute ) );
		}

		// CSVヘッダー
		$sku_header = $n2->portal_setting['楽天']['csv_header']['sku'];

		foreach ( $sku_header as $type => $headers ) {
			// レベル毎
			foreach ( $headers as $index => $header ) {
				if ( ! is_array( $header ) ) {
					$this->data['sku']['header'][ $type ][] = $header;
					continue;
				}

				$loop_count = match ( true ) {
					(bool) preg_grep( '/商品画像/', $header )=> $this->data['sku']['max']['cabinet'],
					(bool) preg_grep( '/バリエーション\%d選択肢定義/', $header ) => $this->data['sku']['max']['variation'],
					(bool) preg_grep( '/商品オプション選択肢/', $header ) => $this->data['sku']['max']['select'],
					(bool) preg_grep( '/バリエーション項目キー/', $header ) => $this->data['sku']['max']['variation'],
					(bool) preg_grep( '/商品属性/', $header ) => $this->data['sku']['max']['attribute'],
				};

				for ( $i = 1; $i <= $loop_count; $i++ ) {
					foreach ( $header as $head ) {
						$this->data['sku']['header'][ $type ][] = sprintf( $head, $i );
					}
				}
			}
		}
		$this->data['header'] = array_reduce( array_values( $this->data['sku']['header'] ), 'array_merge', array() );
		/**
		 * [hook] n2_item_export_rakuten_sku_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * 楽天用の内容を配列で作成
	 */
	protected function set_data() {
		global $n2;
		$data = array();

		foreach ( $this->data['n2data'] as $key => $values ) {
			$id = $values['id'];
			// アレルゲン
			$values['アレルゲン'] = preg_replace( '/（.*?）/', '', $values['アレルゲン'] );// 不純物（カッコの部分）を削除

			// レベル毎
			$data[ $id ] = array_keys( $this->data['sku']['header'] );
			array_walk( $data[ $id ], array( $this, 'walk_values' ), $values );

			
			
			$data[ $id ] = array_map( null, ...$data[ $id ] );
			$data[ $id ] = array_map(
				function( $row ) {
					return array( $row[0], ...$row[1], $row[2] );
				},
				$data[ $id ],
			);

			$data[ $id ] = array_combine( $this->data['header'], $data[ $id ] );
		}
		/**
		 * [hook] n2_item_export_rakuten_sku_set_data
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
	 * 楽天CSVの仕様：https://docs.google.com/spreadsheets/d/1OSvaCDPOG9L3YUGlq7NH3tliL7VScWOC9v8Whw3qw9U/edit#gid=2033843158
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {

		$header = $this->data['header'];
		array_walk( $header, array( $this, "walk_${val}_values" ), $n2values );
		$val = $header;
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://docs.google.com/spreadsheets/d/1OSvaCDPOG9L3YUGlq7NH3tliL7VScWOC9v8Whw3qw9U/edit#gid=2033843158
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_item_values( &$val, $index, $n2values ) {
		global $n2;
		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^商品管理番号（商品URL）$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^商品番号$/', $val )  => $n2values['返礼品コード'],
			preg_match( '/商品名$/', $val )  => '【ふるさと納税】' . $n2values['タイトル'] . '[' . $n2values['返礼品コード'] . ']',
			preg_match( '/^倉庫指定$/', $val ) => 0,
			preg_match( '/^ジャンルID$/', $val ) => preg_replace( '/\//', '|', $n2values['全商品ディレクトリID'] ),
			preg_match( '/^非製品属性タグID$/', $val ) => preg_replace( '/\//', '|', $n2->portal_setting['楽天']['tag_id'] ),
			preg_match( '/^キャッチコピー$/', $val )  => $n2values['キャッチコピー'],
			preg_match( '/^PC用商品説明文$/', $val )  => $this->pc_item_description( $n2values ),
			preg_match( '/^スマートフォン用商品説明文$/', $val )  => $this->sp_item_description( $n2values ),
			preg_match( '/^PC用販売説明文$/', $val )  => $this->pc_sales_description( $n2values ),
			preg_match( '/^商品画像タイプ/', $val )  => 'CABINET',
			preg_match( '/(?<=商品画像パス)[0-9]{1,}/', $val, $match )  => explode( ' ', $this->get_img_urls( $n2values ) )[ $match[0] ] ?? '',
			default => '',
		};
		/**
		 * [hook] n2_item_export_rakuten_sku_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . __FUNCTION__, $data, $val, $n2values );
	}
	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://docs.google.com/spreadsheets/d/1OSvaCDPOG9L3YUGlq7NH3tliL7VScWOC9v8Whw3qw9U/edit#gid=2033843158
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_option_values( &$val, $index, $n2values ) {
		global $n2;
		$selects = $n2->portal_setting['楽天']['select'];
		$selects = str_replace( array( "\r\n", "\r" ), "\n", $selects );// 改行コード統一
		$selects = preg_split( '/\n{2,}/', $selects );// 連続改行で分ける
		foreach ( $selects as $select ) {
			$select = explode( "\n", $select );
			$name   = array_shift( $select );
			$data[] = match ( 1 ) {
				preg_match( '/^選択肢タイプ$/', $val ) => 's',// s：セレクトボックス　c：チェックボックス　f：フリーテキスト　i：項目選択肢別在庫　全角・大文字を半角に自動的に変換。
				preg_match( '/^商品オプション項目名$/', $val ) => $name,// 255byteまで。
				preg_match( '/(?<=商品オプション選択肢)[0-9]{1,}/', $val, $match ) => $select[ $match[0] -1 ] ?? '',// 32byteまで。
				preg_match( '/^商品オプション選択必須$/', $val ) => 1,// 空欄可。0：選択必須としない 1：選択必須とする
				default => '',
			};
		}
		/**
		 * [hook] n2_item_export_rakuten_sku_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . __FUNCTION__, $data, $val, $n2values );
	}
	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://docs.google.com/spreadsheets/d/1OSvaCDPOG9L3YUGlq7NH3tliL7VScWOC9v8Whw3qw9U/edit#gid=2033843158
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_sku_values( &$val, $index, $n2values ) {

		global $n2;
		// preg_matchで判定
		$data = match ( 1 ) {
			
			preg_match( '/^SKU管理番号$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^システム連携用SKU番号$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^販売価格$/', $val )  => $n2values['寄附金額'],
			preg_match( '/^のし対応$/', $val )  =>  ( '有り' === $n2values['のし対応'] ) ? 1 : '',
			preg_match( '/^在庫数$/', $val )  => 0,
			preg_match( '/^送料$/', $val )  => 1,
			preg_match( '/^カタログIDなしの理由$/', $val )  => 1,
			preg_match( '/^代引料$/', $val )  => 1,
			default => '',
		};
		/**
		 * [hook] n2_item_export_rakuten_sku_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . __FUNCTION__, $data, $val, $n2values );
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
		return $value;
	}
}
