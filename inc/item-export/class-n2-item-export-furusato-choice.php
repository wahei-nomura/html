<?php
/**
 * ふるさとチョイスの商品エクスポート専用
 * class-n2-item-export-furusato-choice.php
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
	 * ファイル名
	 *
	 * @var string
	 */
	public $name = 'n2_export_furusato_choice.tsv';

	/**
	 * デリミタ
	 *
	 * @var string
	 */
	public $delimiter = "\t";

	/**
	 * 文字コード
	 *
	 * @var string
	 */
	public $charset = 'utf-8';

	public function create() {
		$args = array();
		$args['data'] = 'unko';
		return $args;
	}

	/**
	 * ふるさとチョイスTSVヘッダーを取得
	 */
	public function get_header() {
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
		$tsv_header_array = explode( "\t", $tsv_header );
	}
}
