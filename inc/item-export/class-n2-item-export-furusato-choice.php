<?php
/**
 * ふるさとチョイスの商品エクスポート専用
 * class-n2-item-export-furusato-choice.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_furusato_choice&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Furusato_Choice' ) ) {
	new N2_Item_Export_Furusato_Choice();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Furusato_Choice extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_furusato_choice.tsv',
		'delimiter'     => "\t",
		'charset'       => 'utf-8',
		'header_string' => false,
	);



	/**
	 * ふるさとチョイスTSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$auth = $n2->choice['auth'];
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( "{$auth['user']}:{$auth['pass']}" ),
			),
		);
		// 取得
		$res = wp_remote_get( $auth['url'], $args );
		// TSVヘッダー本体
		$tsv_header = trim( $res['body'] );
		// TSVヘッダー配列化
		$this->data['header'] = explode( "\t", $tsv_header );
		/**
		 * [hook] n2_item_export_furusato_choice_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（基本的に拡張で上書きする）
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param string $values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $values ) {
		$data = '';
		switch ( $val ) {
			case '（必須）お礼の品名':
				$data = $values['タイトル'];
				break;
		}
		/**
		 * [hook] n2_item_export_base_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $index );
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
		$str = preg_replace( '/\"{3,}/', '""', $str );
		/**
		 * [hook] n2_item_export_furusato_choice_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
