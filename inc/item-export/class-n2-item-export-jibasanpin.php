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
		'header_string' => false,
	);
	public $jibasan_count;
	/**
	 * 地場産品CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$jibasanpin_setting = $n2->settings['地場産品'];
		$params             = $this->data['params'];
		$type               = $params['type'] ?? '';
		// CSVヘッダー配列化トス
		$this->data['header'] = $jibasanpin_setting['csv_header'][ $type ];
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
		if ( '番号' === $val ) {
			$jibasan_count = 0;
			$args          = array(
				'orderby'  => 'meta_value',
				'meta_key' => '返礼品コード',
				'order'    => 'ASC',
			);
			foreach ( N2_Items_API::get_items( $args ) as $key => $v ) {
				if ( $n2values['id'] === $v['id'] ) {
					$jibasan_count = $key + 1;
				}
			}
		}
		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^番号$/', $val )  => $jibasan_count,
			preg_match( '/^品目名$/', $val ) => $n2values['タイトル'],
			preg_match( '/^必要寄附金額$/', $val )  => $n2values['寄附金額'],
			preg_match( '/^調達費用$/', $val )  => $n2values['価格'],
			preg_match( '/^返礼割合$/', $val )  => '=IFERROR(I30/H30,"")' . $index,
			preg_match( '/^地場産品基準$/', $val ) => $n2values['地場産品類型'],
			preg_match( '/^類型該当理由$/', $val ) => $n2values['総務省提出用類型該当理由'],
			default => '',
		};
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
