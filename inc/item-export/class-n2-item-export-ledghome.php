<?php
/**
 * 通常版LedgHOMEの商品エクスポート専用
 * class-n2-item-export-ledghome.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Ledghome' ) ) {
	new N2_Item_Export_Ledghome();
	return;
}

/**
 * N2_Item_Export_Ledghome
 */
class N2_Item_Export_Ledghome extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_ledghome.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '',
	);

	/**
	 * LedgHOMECSVヘッダーを取得
	 */
	protected function set_header() {
		/**
		 * [hook] n2_item_export_lhcloud_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', array('unko') );
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
		/**
		 * [hook] n2_item_export_lhcloud_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $val, $index, $n2values );
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
		 * [hook] n2_item_export_lhcloud_check_error
		 */
		$value = apply_filters( mb_strtolower( get_class( $this ) ) . '_check_error', $value, $name, $n2values );
		return $value;
	}
	/**
	 * 文字列の置換
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		/**
		 * [hook] n2_item_export_lhcloud_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
