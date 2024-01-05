<?php
/**
 * ANAの商品情報取得API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API_ANA' ) ) {
	new N2_Items_API_ANA();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Items_API_ANA extends N2_Portal_Item_Data {

	/**
	 * 保存時のタイトル
	 *
	 * @var array
	 */
	public $post_title = 'ANA';

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		global $n2;
		// 自治体コードを自動取得
		$url    = 'https://furusato.ana.co.jp/products/list.php?';
		$params = array(
			'q'     => $n2->town,
			'limit' => 90,
			'o'     => 0,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ) );
		if ( is_wp_error( $data ) || 200 !== (int) $data['response']['code'] ) {
			$this->exit( 'ANAのふるさと納税の返礼品データ取得失敗' );
		}
		$data = $data['body'];
		// DOMDocument
		$dom_document = new \DOMDocument();
		@$dom_document->loadHTML( mb_convert_encoding( $data, 'HTML-ENTITIES', 'UTF-8' ) );
		$xml_object = simplexml_import_dom( $dom_document );
		// ページ数算出
		$num_result = $xml_object->xpath( '//span[@class="total_quantity"]' )[0]->__toString();
		$num_result = trim( str_replace( ',', '', $num_result ) );

		// 配列に突っ込む
		$data = $this->array_format( $xml_object );

		// マルチリクエスト
		$requests    = array();
		$params['o'] = $params['o'] + 90;
		while ( $num_result >= $params['o'] ) {
			$requests[] = array(
				'url' => $url . http_build_query( $params ),
			);
			$params['o'] = $params['o'] + 90;
		}
		$response = N2_Multi_URL_Request_API::request_multiple( $requests );
		$response = array_map( fn( $v ) => $v->body, $response );
		foreach ( $response as $res ) {
			if ( empty( $res ) ) {
				continue;
			}
			@$dom_document->loadHTML( mb_convert_encoding( $res, 'HTML-ENTITIES', 'UTF-8' ) );
			$xml_object = simplexml_import_dom( $dom_document );
			$data       = array( ...$data, ...$this->array_format( $xml_object ) );
		}
		$this->data = array_unique( $data, SORT_REGULAR );
		header( 'Content-Type: application/json; charset=utf-8' );
		// print_r($this->data);
		$this->insert_portal_data();
		exit;
	}

	/**
	 * データ配列の型の変換
	 *
	 * @param array $xml_object xmlオブジェクト
	 */
	private function array_format( $xml_object ) {
		global $n2;
		$arr = array();
		// 返礼品情報を抜く
		foreach ( $xml_object->xpath( '//div[@class="as-product_detail_link"]' ) as $k => $v ) {
			if ( ! preg_match( '{' . $n2->town . '}', $v['data-product-pref'] ) || ! empty( $v->section->div->div ) ) {
				continue;
			}
			$arr[ $k ] = array(
				'goods_g_num' => preg_replace( '/.*?\?product_code\=/', '', $v['data-product-url'] ),
				'goods_name'  => $v['data-product-name']->__toString(),
				'goods_price' => $v['data-product-price']->__toString(),
				'image'       => "https://furusato.ana.co.jp{$v->section->div->img['src']}",
				'url'         => "https://furusato.ana.co.jp{$v['data-product-url']}",
			);
		}
		return $arr;
	}

}
