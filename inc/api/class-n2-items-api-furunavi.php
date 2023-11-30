<?php
/**
 * ふるなびの商品情報取得API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API_Furunavi' ) ) {
	new N2_Items_API_Furunavi();
	return;
}

/**
 * ふるなびの商品情報取得API
 */
class N2_Items_API_Furunavi extends N2_Portal_Item_Data {

	/**
	 * 保存時のタイトル
	 *
	 * @var array
	 */
	public $post_title = 'ふるなび';

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		global $n2;
		$url    = 'https://furunavi.jp/get_product_list.ashx?';
		$params = array(
			'keyword'  => $n2->town,
			'pagesize' => 100,
			'pageno'   => 1,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ) );
		if ( is_wp_error( $data ) || 200 !== (int) $data['response']['code'] ) {
			$this->exit( 'ふるなびの返礼品データ取得失敗' );
		}
		$data = $data['body'];
		$data = json_decode( $data, true );
		// ページ数算出
		$num_result    = $data['ProductCount'];
		$max_num_pages = ceil( $num_result / $params['pagesize'] );
		// 配列に突っ込む
		$data = $this->array_format( $data['ProductData'] );

		// マルチリクエスト
		$requests = array();
		$params['pageno']++;
		while ( $max_num_pages >= $params['pageno'] ) {
			$requests[] = array(
				'url' => $url . http_build_query( $params ),
			);
			$params['pageno']++;
		}
		$response = N2_Multi_URL_Request_API::request_multiple( $requests );
		$response = array_map( fn( $v ) => json_decode( $v->body, true ), $response );
		foreach ( $response as $res ) {
			if ( empty( $res ) ) {
				continue;
			}
			$data = array( ...$data, ...$this->array_format( $res['ProductData'] ) );
		}
		$this->data = array_unique( $data, SORT_REGULAR );
		$this->insert_portal_data();
		exit;
	}

	/**
	 * データの浄化＆配列の型の変換
	 *
	 * @param array $v 配列
	 */
	public function array_format( $v ) {
		global $n2;
		// 自治体のものだけにデータの浄化
		$v = array_values( array_filter( $v, fn( $d ) => $d['MunicipalName'] === $n2->town && 0 !== $d['Stock'] ) );
		$v = array_map(
			function( $d ) use ( $n2 ) {
				preg_match( "/\[{$n2->regex['item_code']['strict']}\]/", $d['ProductName'], $m );
				return array(
					'goods_g_num' => $m[1] ?? '',
					'goods_name'  => $d['ProductName'],
					'goods_price' => preg_replace( '/[^0-9]/', '', $d['LowerAmount'] ),
					'image'       => "https://cf.furunavi.jp/product_images/800/{$d['ProductID']}/1.jpg",
					'url'         => "https://furunavi.jp/product_detail.aspx?pid={$d['ProductID']}",
				);
			},
			$v
		);
		return $v;
	}
}
