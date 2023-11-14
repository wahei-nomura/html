<?php
/**
 * ふるさとチョイス商品API
 * https://www.furusato-tax.jp/ajax/city/product/42201?page=3
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API_Furusato_Choice' ) ) {
	new N2_Items_API_Furusato_Choice();
	return;
}

/**
 * ふるさとチョイス商品API
 */
class N2_Items_API_Furusato_Choice extends N2_Portal_Item_Data {

	/**
	 * 保存時のタイトル
	 *
	 * @var array
	 */
	public $post_title = 'ふるさとチョイス';

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		// 自治体コード取得
		if ( ! preg_match( '/\/f([0-9]{5})/', get_bloginfo( 'url' ), $m ) ) {
			$this->exit( '自治体コードが取得できません' );
		}
		$town_code = $m[1];
		$url       = "https://www.furusato-tax.jp/ajax/city/product/{$town_code}?";
		$params    = array(
			'page' => 1,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ) );
		if ( is_wp_error( $data ) || 200 !== (int) $data['response']['code'] ) {
			$this->exit( 'ふるさとチョイスの返礼品データ取得失敗' );
		}
		$data = $data['body'];

		// DOMDocument
		$dom_document = new DOMDocument();
		@$dom_document->loadHTML( mb_convert_encoding( $data, 'HTML-ENTITIES', 'UTF-8' ) );
		$xml_object = simplexml_import_dom( $dom_document );
		// ページ数算出
		$num_result    = $xml_object->xpath( '//span[@class="num-result"]' )[0]->__toString();
		$num_result    = trim( str_replace( ',', '', $num_result ) );
		$max_num_pages = ceil( $num_result / 30 );
		// 配列に突っ込む
		$data = $this->array_format( $xml_object );

		// マルチリクエスト
		$requests = array();
		$params['page']++;
		while ( $max_num_pages >= $params['page'] ) {
			$requests[] = array(
				'url' => $url . http_build_query( $params ),
			);
			$params['page']++;
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
		$this->insert_portal_data();
		exit;
	}

	/**
	 * データ配列の型の変換
	 *
	 * @param array $xml_object xmlオブジェクト
	 */
	public function array_format( $xml_object ) {
		$arr = $xml_object->xpath( '//div[@class="card-product"]/button | //p[@class="card-product__code"] | //p[@class="card-product__price"] | //a[@class="card-product__link"] | //img[@class="card-product__img"]' );
		$arr = array_chunk( $arr, 5 );
		// 返礼品情報を抜く
		foreach ( $arr as $k => $v ) {
			$arr[ $k ] = array();
			foreach ( $v as $key => $value ) {
				switch ( $value['class']->__toString() ) {
					case 'card-product__heart addfavorite':
						$arr[ $k ]['goods_name'] = $value['data-text']->__toString();
						break;
					case 'card-product__code':
						$arr[ $k ]['goods_g_num'] = preg_replace( '/[^0-9A-Z]/', '', $value->__toString() );
						break;
					case 'card-product__price':
						$arr[ $k ]['goods_price'] = preg_replace( '/[^0-9]/', '', $value->__toString() );
						break;
					case 'card-product__link':
						$arr[ $k ]['url'] = 'https://www.furusato-tax.jp' . $value['href']->__toString();
						break;
					case 'card-product__img':
						$arr[ $k ]['image'] = $value['src']->__toString();
						break;
				}
			}
			ksort( $arr[ $k ] );
		}
		return $arr;
	}
}
