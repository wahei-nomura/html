<?php
/**
 * 総務省提出用の地場産品エクスポート専用
 * class-n2-item-export-jibasanpin.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Jibasanpin' ) ) {
	new N2_Item_Export_Jibasanpin();
	return;
}

/**
 * N2_Item_Export_Jibasanpin
 */
class N2_Item_Export_Jibasanpin extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'jibasanpin.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '"総務省提出用地場産品リスト"' . PHP_EOL,
	);

	/**
	 * 地場産品CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$lh_setting = $n2->settings['地場産品'];
		$params     = $this->data['params'];
		$type       = $params['type'] ?? '謝礼品リスト';
		// CSVヘッダー配列化
		$this->data['header'] = $lh_setting['csv_header'];
		/**
		 * [hook] n2_item_export_lhcloud_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * LedgHOMECSVの仕様：https://steamship.docbase.io/posts/2917248
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		$params = $this->data['params'];
		$data   = array();
		// LH設定
		$lh_setting = $n2->settings['LedgHOME'];
		// 定期便の初期化
		$n2values['定期便'] = $n2values['定期便'] ?: 1;
		// eチケット判定
		$is_e_ticket = in_array( 'eチケット', (array) $n2values['商品タイプ'], true );
		// 発送サイズがヤマトか判定
		$is_yamato = is_numeric( $n2values['発送サイズ'] );
		// ループ回数
		$loop = match ( $params['type'] ) {
			'謝礼品リスト' => $n2values['定期便'],
			'税率リスト' => 1,
			default => $n2values['定期便'] > 1 ? 1 : 0,
		};
		for ( $i = 1; $i <= $loop; $i++ ) {
			// 返礼品コード
			$item_code = $n2values['返礼品コード'] . ( $loop > 1 ? "_{$i}/{$n2values['定期便']}" : '' );
			// データ配列
			$data[ $i ] = match ( $val ) {
				'団体コード' => $index,
				'都道府県' => $index,
				'市区町村' => $index,
				'番号' => $index + 1,
				'品目名' => $n2values['タイトル'],
				'必要寄附金額' => $n2values['寄附金額'],
				'調達費用' => $lh_setting['価格'],
				'返礼割合' => $index,
				'地場産品基準' => $n2values['地場産品類型'],
				'類型該当理由' => $n2values['類型該当理由'],
				default => '',
			};
		}
		/**
		 * [hook] n2_item_export_lhcloud_walk_values
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
		global $n2;
		foreach ( (array) $value as $num => $val ) {
			// SS的必須漏れエラー
			if ( preg_match( '/謝礼品番号|事業者|価格（税込み）|寄附設定金額/', $name ) && '' === trim( $val ) ) {
				$this->add_error( $n2values['id'], "LH項目：「{$name}」が設定できません。" );
			}
		}
		return $value;
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
		 * [hook] n2_item_export_lhcloud_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
